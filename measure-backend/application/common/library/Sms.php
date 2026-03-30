<?php
namespace app\common\library;

use think\Db;

class Sms
{
    /**
     * 发送短信验证码
     */
    public static function send(string $mobile, string $type = 'login'): array
    {
        // 频率限制：同号60秒内只能发1次
        $last = Db::name('sms_codes')
            ->where('mobile', $mobile)
            ->where('type', $type)
            ->where('created_at', '>=', date('Y-m-d H:i:s', time() - 60))
            ->find();
        if ($last) {
            return ['code' => 0, 'msg' => '发送太频繁，请60秒后重试'];
        }

        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiredAt = date('Y-m-d H:i:s', time() + 300); // 5分钟有效

        // 记录验证码到数据库
        Db::name('sms_codes')->insert([
            'mobile'     => $mobile,
            'code'       => $code,
            'type'       => $type,
            'expired_at' => $expiredAt,
        ]);

        // 读取配置
        $provider = Db::name('configs')->where('key', 'sms_provider')->value('value') ?? 'aliyun';

        if ($provider === 'aliyun') {
            $result = self::sendAliyun($mobile, $code);
        } else {
            $result = self::sendTencent($mobile, $code);
        }

        if (!$result['success']) {
            // 如果是开发环境，直接返回验证码
            if (config('app_debug')) {
                return ['code' => 1, 'msg' => "【调试模式】验证码: $code", 'debug_code' => $code];
            }
            return ['code' => 0, 'msg' => '短信发送失败: ' . $result['msg']];
        }

        return ['code' => 1, 'msg' => '验证码已发送，5分钟内有效'];
    }

    /**
     * 验证验证码
     */
    public static function verify(string $mobile, string $code, string $type = 'login'): bool
    {
        $record = Db::name('sms_codes')
            ->where('mobile', $mobile)
            ->where('code', $code)
            ->where('type', $type)
            ->where('used', 0)
            ->where('expired_at', '>=', date('Y-m-d H:i:s'))
            ->order('id', 'desc')
            ->find();

        if (!$record) return false;

        // 标记已使用
        Db::name('sms_codes')->where('id', $record['id'])->update(['used' => 1]);
        return true;
    }

    /**
     * 阿里云短信
     */
    private static function sendAliyun(string $mobile, string $code): array
    {
        $accessKey    = Db::name('configs')->where('key', 'aliyun_access_key')->value('value');
        $accessSecret = Db::name('configs')->where('key', 'aliyun_access_secret')->value('value');
        $signName     = Db::name('configs')->where('key', 'aliyun_sms_sign')->value('value');
        $templateCode = Db::name('configs')->where('key', 'aliyun_sms_template_login')->value('value');

        if (!$accessKey || !$accessSecret) {
            return ['success' => false, 'msg' => '短信服务未配置'];
        }

        $params = [
            'AccessKeyId'      => $accessKey,
            'Action'           => 'SendSms',
            'Format'           => 'JSON',
            'PhoneNumbers'     => $mobile,
            'RegionId'         => 'cn-hangzhou',
            'SignName'         => $signName,
            'SignatureMethod'   => 'HMAC-SHA1',
            'SignatureNonce'    => uniqid(),
            'SignatureVersion'  => '1.0',
            'TemplateCode'     => $templateCode,
            'TemplateParam'    => json_encode(['code' => $code]),
            'Timestamp'        => gmdate('Y-m-d\TH:i:s\Z'),
            'Version'          => '2017-05-25',
        ];

        ksort($params);
        $query = '';
        foreach ($params as $k => $v) {
            $query .= '&' . rawurlencode($k) . '=' . rawurlencode($v);
        }
        $query = substr($query, 1);
        $sign = base64_encode(hash_hmac('sha1', 'GET&%2F&' . rawurlencode($query), $accessSecret . '&', true));
        $url  = 'https://dysmsapi.aliyuncs.com/?' . $query . '&Signature=' . rawurlencode($sign);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $res = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($res, true);
        if (($data['Code'] ?? '') === 'OK') {
            return ['success' => true];
        }
        return ['success' => false, 'msg' => $data['Message'] ?? '未知错误'];
    }

    /**
     * 腾讯云短信
     */
    private static function sendTencent(string $mobile, string $code): array
    {
        // 腾讯云短信接入（需配置SDK，此处为示意）
        return ['success' => false, 'msg' => '腾讯云短信暂未实现，请使用阿里云'];
    }
}
