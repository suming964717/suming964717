<?php
namespace app\admin\controller;

use think\Db;

class User extends Base
{
    // 用户列表
    public function index()
    {
        $keyword = input('get.keyword', '');
        $status  = input('get.status', '');
        $member  = input('get.member', '');
        $page    = max(1, (int)input('get.page', 1));
        $size    = 20;

        $query = Db::name('users');
        if ($keyword) $query->where('mobile|nickname', 'like', "%$keyword%");
        if ($status !== '') $query->where('status', $status);
        if ($member === '1') $query->where('member_type', '>', 0);
        if ($member === '0') $query->where('member_type', 0);

        $total = $query->count();
        $list  = $query->order('id', 'desc')->page($page, $size)->select();

        foreach ($list as &$u) {
            $u['is_member'] = ($u['member_type'] == 4) ||
                ($u['member_type'] > 0 && $u['member_expire'] && strtotime($u['member_expire']) > time());
        }

        $this->assign(compact('list', 'total', 'page', 'size', 'keyword', 'status', 'member'));
        return $this->fetch();
    }

    // 用户详情
    public function detail()
    {
        $id   = (int)input('get.id', 0);
        $user = Db::name('users')->where('id', $id)->find();
        if (!$user) return $this->error('用户不存在');

        $orders  = Db::name('orders')->where('user_id', $id)->order('id', 'desc')->limit(10)->select();
        $history = Db::name('measure_history')->where('user_id', $id)->order('id', 'desc')->limit(20)->select();

        $this->assign(compact('user', 'orders', 'history'));
        return $this->fetch();
    }

    // 禁用/启用用户
    public function toggleStatus()
    {
        $id     = (int)input('post.id', 0);
        $status = (int)input('post.status', 1);
        Db::name('users')->where('id', $id)->update(['status' => $status]);
        $this->log('用户状态变更', "用户ID:{$id} -> status:{$status}");
        return json(['code' => 1, 'msg' => '操作成功']);
    }

    // 手动赠送/修改会员
    public function grantMember()
    {
        $id   = (int)input('post.id', 0);
        $type = (int)input('post.type', 0);
        $days = (int)input('post.days', 0);

        if ($type == 4) {
            $expire = null;
        } else {
            $expire = date('Y-m-d H:i:s', time() + $days * 86400);
        }

        Db::name('users')->where('id', $id)->update([
            'member_type'   => $type,
            'member_expire' => $expire,
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);

        $this->log('赠送会员', "用户ID:{$id} type:{$type} days:{$days}");
        return json(['code' => 1, 'msg' => '操作成功']);
    }
}
