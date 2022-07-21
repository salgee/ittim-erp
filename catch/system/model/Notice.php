<?php

namespace catchAdmin\system\model;

use catchAdmin\system\model\search\NoticeSearch;
use catchAdmin\permissions\model\Users as usersModel;
use catcher\base\CatchModel as Model;

class Notice extends Model
{
    use NoticeSearch;
    // 表名
    public $name = 'notice';
    // 数据库字段映射
    public $field = array(
        'id',
        // 公告标题
        'title',
        // 公告内容
        'content',
        // 状态：1-已发布，2-未发布
        'status',
        // 排序
        'sort',
        // 创建人ID
        'creator_id',
        // 创建时间
        'created_at',
        // 更新人ID
        'updater_id',
        // 更新时间
        'updated_at',
        // 软删除
        'deleted_at',
    );

    public const PUBLISH = 1; // 已发布
    public const UN_PUBLISH = 2; // 未发布


    public function getList()
    {
        return $this->field([
                $this->aliasField('content'),
                $this->aliasField('created_at'),
                $this->aliasField('id'),
                $this->aliasField('status'),
                $this->aliasField('title'),
                $this->aliasField('updated_at'),
            ])
            ->catchSearch()
            ->catchLeftJoin(usersModel::class, 'id', 'updater_id', ['username as updater'])
            ->catchOrder()
            ->creator()
            ->paginate();
    }

    /**
     * 管理公告发布与否
     * @param $id
     * @return Notice
     */
    public function publish($id)
    {
        $notice = $this->findBy($id);

        $status = $notice['status'] == Notice::PUBLISH ? Notice::UN_PUBLISH : Notice::PUBLISH;

        return $this->where('id', $id)->update([
            'status' => $status,
            'updated_at' => time(),
            'updater_id' => request()->user()['id']
        ]);
    }
}
