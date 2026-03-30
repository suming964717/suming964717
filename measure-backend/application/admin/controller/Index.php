<?php
namespace app\admin\controller;

use think\Db;
use think\Session;

class Index extends Base
{
    // 后台首页/仪表盘
    public function index()
    {
        $stats = [
            'total_users'   => Db::name('users')->count(),
            'today_users'   => Db::name('users')->where('created_at', '>=', date('Y-m-d'))->count(),
            'total_members' => Db::name('users')->where('member_type', '>', 0)->count(),
            'total_orders'  => Db::name('orders')->where('pay_status', 1)->count(),
            'today_revenue' => Db::name('orders')->where('pay_status', 1)->where('paid_at', '>=', date('Y-m-d'))->sum('amount'),
            'total_revenue' => Db::name('orders')->where('pay_status', 1)->sum('amount'),
            'total_history' => Db::name('measure_history')->count(),
        ];

        // 最近7天收入趋势
        $revenue7 = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-$i days"));
            $revenue7[] = [
                'date' => $day,
                'revenue' => (float)Db::name('orders')->where('pay_status', 1)->where('paid_at', 'like', "$day%")->sum('amount'),
                'users'   => (int)Db::name('users')->where('created_at', 'like', "$day%")->count(),
            ];
        }

        // 最近订单
        $recentOrders = Db::name('orders')
            ->alias('o')
            ->join('users u', 'u.id = o.user_id')
            ->field('o.*, u.mobile, u.nickname')
            ->where('o.pay_status', 1)
            ->order('o.id', 'desc')
            ->limit(10)
            ->select();

        $this->assign('stats', $stats);
        $this->assign('revenue7', json_encode($revenue7));
        $this->assign('recentOrders', $recentOrders);
        return $this->fetch();
    }

    // 登录页
    public function login()
    {
        if (Session::get('admin_logged')) {
            return $this->redirect('/admin/index/index');
        }
        return $this->fetch();
    }

    // 处理登录
    public function doLogin()
    {
        $username = input('post.username', '');
        $password = input('post.password', '');

        $adminUser = Db::name('configs')->where('key', 'admin_username')->value('value');
        $adminPass = Db::name('configs')->where('key', 'admin_password')->value('value');

        if ($username === $adminUser && md5($password) === $adminPass) {
            Session::set('admin_logged', true);
            Session::set('admin_user', $username);
            return $this->redirect('/admin/index/index');
        }

        $this->assign('error', '账号或密码错误');
        return $this->fetch('login');
    }

    // 退出
    public function logout()
    {
        Session::clear();
        return $this->redirect('/admin/index/login');
    }
}
