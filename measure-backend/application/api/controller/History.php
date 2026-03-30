<?php
namespace app\api\controller;

use think\Db;

class History extends Base
{
    /**
     * 获取测量历史
     * GET /api/history/list?type=ruler&page=1&size=20
     */
    public function list()
    {
        $this->checkMember(); // 会员专属

        $type = input('get.type', '');
        $page = max(1, (int)input('get.page', 1));
        $size = min(50, max(1, (int)input('get.size', 20)));

        $query = Db::name('measure_history')->where('user_id', $this->userId);
        if ($type) $query->where('type', $type);

        $total = $query->count();
        $list  = $query->order('id', 'desc')
            ->limit(($page - 1) * $size, $size)
            ->select();

        foreach ($list as &$item) {
            $item['extra'] = $item['extra'] ? json_decode($item['extra'], true) : null;
        }

        return $this->success([
            'total' => $total,
            'page'  => $page,
            'size'  => $size,
            'list'  => $list,
        ]);
    }

    /**
     * 保存测量记录
     * POST /api/history/save
     * Body: { type, value, unit, extra?, note? }
     */
    public function save()
    {
        $this->checkMember();

        $type  = input('post.type', '');
        $value = input('post.value', '');
        $unit  = input('post.unit', '');
        $extra = input('post.extra', null);
        $note  = input('post.note', '');

        if (!in_array($type, ['ruler', 'level', 'decibel'])) {
            return $this->fail('无效的类型');
        }
        if ($value === '') return $this->fail('测量值不能为空');

        $id = Db::name('measure_history')->insertGetId([
            'user_id'    => $this->userId,
            'type'       => $type,
            'value'      => $value,
            'unit'       => $unit,
            'extra'      => $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null,
            'note'       => mb_substr($note, 0, 200),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success(['id' => $id], '保存成功');
    }

    /**
     * 删除单条记录
     * DELETE /api/history/delete?id=xxx
     */
    public function delete()
    {
        $this->checkMember();
        $id = (int)input('get.id', 0);

        $row = Db::name('measure_history')
            ->where('id', $id)
            ->where('user_id', $this->userId)
            ->find();

        if (!$row) return $this->fail('记录不存在');

        Db::name('measure_history')->where('id', $id)->delete();
        return $this->success([], '删除成功');
    }

    /**
     * 清空所有记录
     * DELETE /api/history/clear
     */
    public function clear()
    {
        $this->checkMember();
        Db::name('measure_history')->where('user_id', $this->userId)->delete();
        return $this->success([], '已清空历史记录');
    }
}
