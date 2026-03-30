<?php
namespace app\admin\controller;

use think\Db;

class Plan extends Base
{
    // 套餐列表
    public function index()
    {
        $list = Db::name('member_plans')->order('sort', 'asc')->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    // 编辑套餐（GET: 显示表单, POST: 保存）
    public function edit()
    {
        $id = (int)input('get.id', 0);

        if ($this->request->isPost()) {
            $data = [
                'name'           => input('post.name', ''),
                'price'          => (float)input('post.price', 0),
                'original_price' => (float)input('post.original_price', 0),
                'days'           => (int)input('post.days', 30),
                'description'    => input('post.description', ''),
                'features'       => input('post.features', '[]'),
                'is_recommend'   => (int)input('post.is_recommend', 0),
                'sort'           => (int)input('post.sort', 0),
                'status'         => (int)input('post.status', 1),
            ];

            if ($id) {
                Db::name('member_plans')->where('id', $id)->update($data);
                $this->log('编辑套餐', "ID:{$id} name:{$data['name']}");
            } else {
                $data['type'] = (int)input('post.type', 1);
                Db::name('member_plans')->insert($data);
                $this->log('新增套餐', "name:{$data['name']}");
            }

            return $this->redirect('/admin/plan/index');
        }

        $plan = $id ? Db::name('member_plans')->where('id', $id)->find() : null;
        $this->assign('plan', $plan);
        return $this->fetch();
    }

    // 删除套餐
    public function delete()
    {
        $id = (int)input('post.id', 0);
        Db::name('member_plans')->where('id', $id)->delete();
        $this->log('删除套餐', "ID:{$id}");
        return json(['code' => 1, 'msg' => '已删除']);
    }

    // 上架/下架
    public function toggleStatus()
    {
        $id     = (int)input('post.id', 0);
        $status = (int)input('post.status', 1);
        Db::name('member_plans')->where('id', $id)->update(['status' => $status]);
        return json(['code' => 1, 'msg' => '操作成功']);
    }
}
