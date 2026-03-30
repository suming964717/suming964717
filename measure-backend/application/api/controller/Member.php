<?php
namespace app\api\controller;

use think\Db;

class Member extends Base
{
    /**
     * 获取所有会员套餐
     * GET /api/member/plans
     */
    public function plans()
    {
        $plans = Db::name('member_plans')
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select();

        foreach ($plans as &$p) {
            $p['price']          = (float)$p['price'];
            $p['original_price'] = (float)$p['original_price'];
            $p['features']       = json_decode($p['features'] ?? '[]', true);
            $p['is_recommend']   = (bool)$p['is_recommend'];
            if ($p['original_price'] > 0) {
                $p['save_amount'] = round($p['original_price'] - $p['price'], 2);
            }
        }

        return $this->success($plans);
    }

    /**
     * 获取当前用户会员状态
     * GET /api/member/status
     */
    public function status()
    {
        $this->checkAuth();
        $u = $this->user;

        $isMember = $this->isMember();
        $typeMap   = [0 => '免费用户', 1 => '月度会员', 2 => '季度会员', 3 => '年度会员', 4 => '永久会员'];

        return $this->success([
            'is_member'       => $isMember,
            'member_type'     => (int)$u['member_type'],
            'member_type_name'=> $typeMap[$u['member_type']] ?? '未知',
            'member_expire'   => $u['member_expire'],
            'days_remaining'  => $this->daysRemaining($u),
        ]);
    }

    private function daysRemaining(array $u): int
    {
        if ($u['member_type'] == 4) return 36500; // 永久
        if (!$u['member_expire']) return 0;
        $diff = strtotime($u['member_expire']) - time();
        return max(0, (int)ceil($diff / 86400));
    }
}
