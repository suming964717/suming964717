<?php
namespace app\admin\controller;

use think\Db;

class Order extends Base
{
    public function index()
    {
        $keyword   = input('get.keyword', '');
        $payStatus = input('get.pay_status', '');
        $payType   = input('get.pay_type', '');
        $startDate = input('get.start_date', '');
        $endDate   = input('get.end_date', '');
        $page      = max(1, (int)input('get.page', 1));
        $size      = 20;

        $query = Db::name('orders')->alias('o')
            ->join('users u', 'u.id = o.user_id')
            ->field('o.*, u.mobile, u.nickname');

        if ($keyword) $query->where('o.order_no|u.mobile', 'like', "%$keyword%");
        if ($payStatus !== '') $query->where('o.pay_status', $payStatus);
        if ($payType !== '') $query->where('o.pay_type', $payType);
        if ($startDate) $query->where('o.created_at', '>=', $startDate . ' 00:00:00');
        if ($endDate)   $query->where('o.created_at', '<=', $endDate . ' 23:59:59');

        $total = $query->count();
        $list  = $query->order('o.id', 'desc')->page($page, $size)->select();
        $totalAmount = $query->where('o.pay_status', 1)->sum('o.amount');

        $this->assign(compact('list', 'total', 'page', 'size', 'keyword', 'payStatus', 'payType', 'startDate', 'endDate', 'totalAmount'));
        return $this->fetch();
    }
}
