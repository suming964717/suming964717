<?php
namespace app\api\controller;

use think\Db;
use app\common\library\Jwt;
use app\common\library\Sms;

class Auth extends Base
{
    /**
     * 发送短信验证码
     * POST /api/auth/sendSms
     * Body: { mobile: "13800138000" }
     */
    public function sendSms()
    {
        $mobile = input('post.mobile', '');

        if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            return $this->fail('请输入正确的手机号');
        }

        $result = Sms::send($mobile, 'login');

        if ($result['code'] != 1) {
            return $this->fail($result['msg']);
        }

        $data = ['msg' => $result['msg']];
        // 调试模式返回验证码，生产环境去掉
        if (isset($result['debug_code'])) {
            $data['debug_code'] = $result['debug_code'];
        }

        return $this->success($data, $result['msg']);
    }

    /**
     * 手机号验证码登录（无则自动注册）
     * POST /api/auth/login
     * Body: { mobile, code }
     */
    public function login()
    {
        $mobile = input('post.mobile', '');
        $code   = input('post.code', '');

        if (!preg_match('/^1[3-9]\d{9}$/', $mobile)) {
            return $this->fail('请输入正确的手机号');
        }

        if (!$code) {
            return $this->fail('请输入验证码');
        }

        // 开发调试模式允许 000000
        $debugPass = config('app_debug') && $code === '000000';

        if (!$debugPass && !Sms::verify($mobile, $code, 'login')) {
            return $this->fail('验证码错误或已过期');
        }

        // 查找或创建用户
        $user = Db::name('users')->where('mobile', $mobile)->find();

        if (!$user) {
            $uid = Db::name('users')->insertGetId([
                'mobile'   => $mobile,
                'nickname' => '用户' . substr($mobile, -4),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $user = Db::name('users')->where('id', $uid)->find();
        }

        if ($user['status'] != 1) {
            return $this->fail('账号已被禁用，请联系客服');
        }

        // 生成 JWT
        $token = Jwt::encode(['user_id' => $user['id']]);

        return $this->success([
            'token' => $token,
            'user'  => $this->formatUser($user),
        ], '登录成功');
    }

    /**
     * 微信授权登录（H5场景，需要微信公众号配置）
     * POST /api/auth/wechatLogin
     * Body: { code } (微信网页授权code)
     */
    public function wechatLogin()
    {
        $code = input('post.code', '');
        if (!$code) return $this->fail('缺少微信code');

        $appid  = Db::name('configs')->where('key', 'wechat_appid')->value('value');
        $secret = Db::name('configs')->where('key', 'wechat_api_key')->value('value');

        if (!$appid || !$secret) {
            return $this->fail('微信登录未配置，请联系管理员');
        }

        // 用code换取access_token + openid
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$secret}&code={$code}&grant_type=authorization_code";
        $res = json_decode(file_get_contents($url), true);

        if (isset($res['errcode'])) {
            return $this->fail('微信授权失败: ' . ($res['errmsg'] ?? ''));
        }

        $openid = $res['openid'];
        // 获取用户信息
        $infoUrl = "https://api.weixin.qq.com/sns/userinfo?access_token={$res['access_token']}&openid={$openid}&lang=zh_CN";
        $info    = json_decode(file_get_contents($infoUrl), true);

        // 查找或创建用户（微信用openid作为标识，手机号为空）
        $user = Db::name('users')->where('openid', $openid)->find();
        if (!$user) {
            $uid = Db::name('users')->insertGetId([
                'openid'     => $openid,
                'nickname'   => $info['nickname'] ?? '微信用户',
                'avatar'     => $info['headimgurl'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
            $user = Db::name('users')->where('id', $uid)->find();
        }

        $token = Jwt::encode(['user_id' => $user['id']]);
        return $this->success(['token' => $token, 'user' => $this->formatUser($user)], '登录成功');
    }

    /**
     * 格式化用户数据（对外输出）
     */
    private function formatUser(array $user): array
    {
        $isMember = ($user['member_type'] == 4) ||
            ($user['member_type'] > 0 && $user['member_expire'] && strtotime($user['member_expire']) > time());

        return [
            'id'            => $user['id'],
            'nickname'      => $user['nickname'],
            'avatar'        => $user['avatar'],
            'mobile'        => $user['mobile'] ? substr_replace($user['mobile'], '****', 3, 4) : '',
            'is_member'     => $isMember,
            'member_type'   => (int)$user['member_type'],
            'member_expire' => $user['member_expire'],
            'total_usage'   => (int)$user['total_usage'],
        ];
    }
}
