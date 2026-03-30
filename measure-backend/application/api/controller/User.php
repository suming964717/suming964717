<?php
namespace app\api\controller;

use think\Db;

class User extends Base
{
    /**
     * 获取当前用户信息
     * GET /api/user/info
     */
    public function info()
    {
        $this->checkAuth();
        $u = $this->user;
        $isMember = $this->isMember();
        $freeLimit = (int)(Db::name('configs')->where('key', 'free_daily_limit')->value('value') ?? 10);

        $todayUsage = $this->getTodayUsage();

        return $this->success([
            'id'            => $u['id'],
            'nickname'      => $u['nickname'],
            'avatar'        => $u['avatar'],
            'mobile'        => $u['mobile'] ? substr_replace($u['mobile'], '****', 3, 4) : '',
            'is_member'     => $isMember,
            'member_type'   => (int)$u['member_type'],
            'member_expire' => $u['member_expire'],
            'total_usage'   => (int)$u['total_usage'],
            'today_usage'   => $todayUsage,
            'free_limit'    => $freeLimit,
            'remaining'     => $isMember ? 99999 : max(0, $freeLimit - $todayUsage),
        ]);
    }

    /**
     * 更新用户信息
     * POST /api/user/update
     * Body: { nickname?, avatar? }
     */
    public function update()
    {
        $this->checkAuth();

        $data = [];
        if ($nickname = input('post.nickname', '')) {
            $data['nickname'] = mb_substr($nickname, 0, 20);
        }
        if ($avatar = input('post.avatar', '')) {
            $data['avatar'] = $avatar;
        }

        if (empty($data)) {
            return $this->fail('没有需要更新的内容');
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        Db::name('users')->where('id', $this->userId)->update($data);

        return $this->success([], '更新成功');
    }

    /**
     * 记录一次工具使用（检查限制）
     * POST /api/user/use
     * Body: { type: "ruler/level/decibel" }
     */
    public function use()
    {
        $this->checkAuth();

        if (!$this->isMember()) {
            $freeLimit  = (int)(Db::name('configs')->where('key', 'free_daily_limit')->value('value') ?? 10);
            $todayUsage = $this->getTodayUsage();
            if ($todayUsage >= $freeLimit) {
                return $this->fail('今日免费次数已用完，请开通会员', 403, [
                    'limit' => $freeLimit,
                    'used'  => $todayUsage,
                ]);
            }
        }

        $this->incrementUsage();
        $todayUsage = $this->getTodayUsage();
        $freeLimit  = (int)(Db::name('configs')->where('key', 'free_daily_limit')->value('value') ?? 10);

        return $this->success([
            'today_usage' => $todayUsage,
            'remaining'   => $this->isMember() ? 99999 : max(0, $freeLimit - $todayUsage),
        ]);
    }
}
