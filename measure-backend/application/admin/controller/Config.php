<?php
namespace app\admin\controller;

use think\Db;

class Config extends Base
{
    // 配置列表页
    public function index()
    {
        $group  = input('get.group', 'basic');
        $groups = ['basic' => '基础配置', 'sms' => '短信配置', 'payment' => '支付配置'];

        $configs = Db::name('configs')->where('group', $group)->select();
        $map = [];
        foreach ($configs as $c) {
            $map[$c['key']] = $c['value'];
        }

        $this->assign(compact('group', 'groups', 'map'));
        return $this->fetch();
    }

    // 保存配置
    public function save()
    {
        $group = input('post.group', 'basic');
        $data  = input('post.configs', []);

        foreach ($data as $key => $value) {
            // 密码特殊处理：如果是修改密码，先MD5
            if ($key === 'admin_password' && $value !== '') {
                $value = md5($value);
            }
            Db::name('configs')->where('key', $key)->update([
                'value'      => $value,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        $this->log('保存配置', "group:{$group}");
        return $this->redirect('/admin/config/index?group=' . $group);
    }
}
