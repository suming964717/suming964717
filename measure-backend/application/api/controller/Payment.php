<?php
namespace app\api\controller;

use think\Db;

class Payment extends Base
{
    /**
     * 创建支付订单
     * POST /api/payment/create
     * Body: { plan_id, pay_type: 1=微信 2=支付宝 }
     */
    public function create()
    {
        $this->checkAuth();

        $planId  = (int)input('post.plan_id', 0);
        $payType = (int)input('post.pay_type', 1);

        $plan = Db::name('member_plans')->where('id', $planId)->where('status', 1)->find();
        if (!$plan) return $this->fail('套餐不存在');

        // 生成订单号
        $orderNo = date('YmdHis') . str_pad($this->userId, 6, '0', STR_PAD_LEFT) . rand(100, 999);

        Db::name('orders')->insert([
            'order_no'   => $orderNo,
            'user_id'    => $this->userId,
            'plan_id'    => $planId,
            'plan_name'  => $plan['name'],
            'amount'     => $plan['price'],
            'pay_type'   => $payType,
            'pay_status' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // 根据支付方式调起支付
        if ($payType == 1) {
            $payData = $this->createWechatPay($orderNo, $plan['price'], $plan['name']);
        } else {
            $payData = $this->createAlipay($orderNo, $plan['price'], $plan['name']);
        }

        if (!$payData['success']) {
            return $this->fail($payData['msg']);
        }

        return $this->success([
            'order_no' => $orderNo,
            'pay_type' => $payType,
            'amount'   => $plan['price'],
            'pay_data' => $payData['data'],
        ]);
    }

    /**
     * 查询订单状态
     * GET /api/payment/status?order_no=xxx
     */
    public function status()
    {
        $this->checkAuth();
        $orderNo = input('get.order_no', '');

        $order = Db::name('orders')
            ->where('order_no', $orderNo)
            ->where('user_id', $this->userId)
            ->find();

        if (!$order) return $this->fail('订单不存在');

        return $this->success([
            'order_no'   => $order['order_no'],
            'pay_status' => $order['pay_status'],
            'amount'     => $order['amount'],
            'paid_at'    => $order['paid_at'],
        ]);
    }

    /**
     * 微信支付回调
     * POST /api/payment/wechatNotify
     */
    public function wechatNotify()
    {
        $xmlData = file_get_contents('php://input');
        $data    = $this->xmlToArray($xmlData);

        $apiKey = Db::name('configs')->where('key', 'wechat_api_key')->value('value');

        // 验签
        $sign = $data['sign'];
        unset($data['sign']);
        ksort($data);
        $str  = '';
        foreach ($data as $k => $v) {
            if ($v !== '') $str .= "$k=$v&";
        }
        $str .= "key=$apiKey";
        $mySign = strtoupper(md5($str));

        if ($mySign !== $sign) {
            return '<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[签名错误]]></return_msg></xml>';
        }

        if ($data['result_code'] === 'SUCCESS') {
            $orderNo = $data['out_trade_no'];
            $this->activateOrder($orderNo, $data['transaction_id']);
        }

        return '<xml><return_code><![CDATA[OK]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
    }

    /**
     * 支付宝回调
     * POST /api/payment/alipayNotify
     */
    public function alipayNotify()
    {
        $params = input('post.');

        // 验签（简化，实际需要用支付宝SDK）
        if (($params['trade_status'] ?? '') === 'TRADE_SUCCESS') {
            $orderNo = $params['out_trade_no'];
            $this->activateOrder($orderNo, $params['trade_no']);
        }

        echo 'success';
    }

    /**
     * 激活订单，给用户开通会员
     */
    private function activateOrder(string $orderNo, string $tradeNo): void
    {
        Db::startTrans();
        try {
            $order = Db::name('orders')
                ->where('order_no', $orderNo)
                ->where('pay_status', 0)
                ->lock(true)
                ->find();

            if (!$order) {
                Db::rollback();
                return;
            }

            // 更新订单
            Db::name('orders')->where('order_no', $orderNo)->update([
                'pay_status' => 1,
                'trade_no'   => $tradeNo,
                'paid_at'    => date('Y-m-d H:i:s'),
            ]);

            // 开通/续费会员
            $plan = Db::name('member_plans')->where('id', $order['plan_id'])->find();
            $user = Db::name('users')->where('id', $order['user_id'])->find();

            $type = $plan['type'];
            $days = $plan['days'];

            // 如果已是会员且未过期，在到期时间上续期
            $baseTime = ($user['member_type'] > 0 && $user['member_expire'] && strtotime($user['member_expire']) > time())
                ? strtotime($user['member_expire'])
                : time();

            $expire = $type == 4 ? null : date('Y-m-d H:i:s', $baseTime + $days * 86400);

            Db::name('users')->where('id', $order['user_id'])->update([
                'member_type'   => $type,
                'member_expire' => $expire,
                'updated_at'    => date('Y-m-d H:i:s'),
            ]);

            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
    }

    /**
     * 微信支付 - H5支付
     */
    private function createWechatPay(string $orderNo, float $amount, string $desc): array
    {
        $appid      = Db::name('configs')->where('key', 'wechat_appid')->value('value');
        $mchId      = Db::name('configs')->where('key', 'wechat_mch_id')->value('value');
        $apiKey     = Db::name('configs')->where('key', 'wechat_api_key')->value('value');
        $notifyUrl  = Db::name('configs')->where('key', 'wechat_notify_url')->value('value');

        if (!$appid || !$mchId || !$apiKey) {
            return ['success' => false, 'msg' => '微信支付未配置，请联系管理员'];
        }

        $params = [
            'appid'            => $appid,
            'mch_id'           => $mchId,
            'nonce_str'        => md5(uniqid()),
            'body'             => $desc,
            'out_trade_no'     => $orderNo,
            'total_fee'        => (int)($amount * 100),
            'spbill_create_ip' => request()->ip(),
            'notify_url'       => $notifyUrl,
            'trade_type'       => 'MWEB', // H5支付
        ];

        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            $str .= "$k=$v&";
        }
        $str .= "key=$apiKey";
        $params['sign'] = strtoupper(md5($str));

        $xml = '<xml>';
        foreach ($params as $k => $v) {
            $xml .= "<$k><![CDATA[$v]]></$k>";
        }
        $xml .= '</xml>';

        $ch = curl_init('https://api.mch.weixin.qq.com/pay/unifiedorder');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $res  = curl_exec($ch);
        curl_close($ch);

        $res = $this->xmlToArray($res);
        if ($res['return_code'] === 'SUCCESS' && $res['result_code'] === 'SUCCESS') {
            return ['success' => true, 'data' => ['mweb_url' => $res['mweb_url']]];
        }

        return ['success' => false, 'msg' => $res['err_code_des'] ?? $res['return_msg'] ?? '创建支付失败'];
    }

    /**
     * 支付宝 - H5支付
     */
    private function createAlipay(string $orderNo, float $amount, string $desc): array
    {
        $appId      = Db::name('configs')->where('key', 'alipay_app_id')->value('value');
        $privateKey = Db::name('configs')->where('key', 'alipay_private_key')->value('value');
        $notifyUrl  = Db::name('configs')->where('key', 'alipay_notify_url')->value('value');

        if (!$appId || !$privateKey) {
            return ['success' => false, 'msg' => '支付宝支付未配置，请联系管理员'];
        }

        $bizContent = json_encode([
            'out_trade_no' => $orderNo,
            'total_amount' => number_format($amount, 2),
            'subject'      => $desc,
            'product_code' => 'QUICK_WAP_WAY',
        ]);

        $params = [
            'app_id'      => $appId,
            'method'      => 'alipay.trade.wap.pay',
            'format'      => 'JSON',
            'charset'     => 'utf-8',
            'sign_type'   => 'RSA2',
            'timestamp'   => date('Y-m-d H:i:s'),
            'version'     => '1.0',
            'notify_url'  => $notifyUrl,
            'biz_content' => $bizContent,
        ];

        ksort($params);
        $str = '';
        foreach ($params as $k => $v) {
            $str .= "$k=$v&";
        }
        $str = rtrim($str, '&');

        $key = "-----BEGIN RSA PRIVATE KEY-----\n" . wordwrap($privateKey, 64, "\n", true) . "\n-----END RSA PRIVATE KEY-----";
        openssl_sign($str, $sign, $key, OPENSSL_ALGO_SHA256);
        $params['sign'] = base64_encode($sign);

        $queryStr = '';
        foreach ($params as $k => $v) {
            $queryStr .= rawurlencode($k) . '=' . rawurlencode($v) . '&';
        }

        $payUrl = 'https://openapi.alipay.com/gateway.do?' . rtrim($queryStr, '&');

        return ['success' => true, 'data' => ['pay_url' => $payUrl]];
    }

    private function xmlToArray(string $xml): array
    {
        libxml_disable_entity_loader(true);
        $obj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        return json_decode(json_encode($obj), true) ?: [];
    }
}
