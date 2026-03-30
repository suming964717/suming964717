<?php
namespace app\admin\controller;

use think\Controller;
use think\Db;
use think\Session;

class Base extends Controller
{
    protected function initialize()
    {
        // 检查登录状态（排除登录页）
        $action = $this->request->action();
        $controller = strtolower($this->request->controller());

        if ($controller === 'index' && $action === 'login') return;
        if ($controller === 'index' && $action === 'doLogin') return;

        if (!Session::get('admin_logged')) {
            $this->redirect('/admin/index/login')->send();
            exit;
        }
    }

    protected function success($msg = '操作成功', $url = '', $data = [])
    {
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => $msg, 'data' => $data]);
        }
        $this->assign(['alert_type' => 'success', 'alert_msg' => $msg]);
        if ($url) return $this->redirect($url);
        return $this->error($msg);
    }

    protected function error($msg = '操作失败', $url = '')
    {
        if ($this->request->isAjax()) {
            return json(['code' => 0, 'msg' => $msg]);
        }
        return $this->fetch('common/error', ['msg' => $msg, 'url' => $url]);
    }

    protected function log(string $action, string $content = '')
    {
        Db::name('admin_logs')->insert([
            'admin'      => Session::get('admin_user'),
            'action'     => $action,
            'content'    => $content,
            'ip'         => $this->request->ip(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
