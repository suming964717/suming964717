<?php
namespace app\api\controller;

use think\Controller;
use think\Request;
use app\common\library\Jwt;
use think\Db;

class Base extends Controller
{
    protected $userId = null;
    protected $user   = null;

    /**
     * 返回JSON成功
     */
    protected function success($data = [], string $msg = 'ok', int $code = 200)
    {
        return json(['code' => $code, 'msg' => $msg, 'data' => $data]);
    }

    /**
     * 返回JSON失败
     */
    protected function fail(string $msg = '请求失败', int $code = 400, $data = null)
    {
        return json(['code' => $code, 'msg' => $msg, 'data' => $data]);
    }

    /**
     * 从请求头获取并验证 JWT Token（必须登录）
     */
    protected function checkAuth(): bool
    {
        $token = $this->request->header('Authorization', '');
        $token = str_replace('Bearer ', '', $token);

        if (!$token) {
            $this->fail('未登录，请先登录', 401)->send();
            exit;
        }

        try {
            $payload = Jwt::decode($token);
            $this->userId = $payload['user_id'] ?? null;

            if (!$this->userId) throw new \Exception('Token无效');

            $user = Db::name('users')->where('id', $this->userId)->find();
            if (!$user || $user['status'] != 1) {
                $this->fail('账号不存在或已被禁用', 401)->send();
                exit;
            }

            $this->user = $user;
            return true;
        } catch (\Exception $e) {
            $this->fail($e->getMessage(), 401)->send();
            exit;
        }
    }

    /**
     * 检查会员权限
     */
    protected function checkMember(): bool
    {
        $this->checkAuth();

        $u = $this->user;
        $isMember = ($u['member_type'] == 4) ||
            ($u['member_type'] > 0 && $u['member_expire'] && strtotime($u['member_expire']) > time());

        if (!$isMember) {
            $this->fail('该功能需要开通会员', 403)->send();
            exit;
        }

        return true;
    }

    /**
     * 判断当前用户是否是会员
     */
    protected function isMember(): bool
    {
        if (!$this->user) return false;
        $u = $this->user;
        return ($u['member_type'] == 4) ||
            ($u['member_type'] > 0 && $u['member_expire'] && strtotime($u['member_expire']) > time());
    }

    /**
     * 用户今日使用次数
     */
    protected function getTodayUsage(): int
    {
        if (!$this->user) return 0;
        $u = $this->user;
        if ($u['usage_date'] !== date('Y-m-d')) return 0;
        return (int)$u['today_usage'];
    }

    /**
     * 增加今日使用次数
     */
    protected function incrementUsage(): void
    {
        if (!$this->userId) return;
        $today = date('Y-m-d');
        if ($this->user['usage_date'] === $today) {
            Db::name('users')->where('id', $this->userId)->setInc('today_usage');
        } else {
            Db::name('users')->where('id', $this->userId)->update([
                'today_usage' => 1,
                'usage_date'  => $today,
            ]);
        }
        Db::name('users')->where('id', $this->userId)->setInc('total_usage');
    }
}
