<?php

namespace catchAdmin\report\model;

use catcher\base\CatchModel as Model;

class ReportOrderAfterSale extends Model
{
    // 表名
    public $name = 'report_order_after_sale';
    // 数据库字段映射
    public $field = array(
        'id',
        // 订单报表ID
        'report_order_id',
        // 售后订单id
        'after_order_id',
        // 售后类型
        'type',
        // 售后产生费用
        'amount',
        // 创建人ID
        'creator_id',
        // 创建时间
        'created_at',
        // 更新时间
        'updated_at',
        // 软删除
        'deleted_at',
    );

    /**
     * 写入报表订单售后费用信息
     * @param $orderNo
     * @param $type
     * @param $amount
     * @param $afterId
     * @return bool
     */
    public function saveAfterSale($orderNo, $type, $amount, $afterId){
        // 根据订单号查询id
        if (!$id = ReportOrder::where('order_no', $orderNo)->value('id')){
            return false;
        };
        if (!$idOrder = $this->where(['after_order_id'=> $afterId, 'type' => $type])->value('id')) {
            return $this->storeBy([
                'report_order_id' => $id,
                'after_order_id' => $afterId,
                'type' => $type,
                'amount' => $amount
            ]);
        }else{
            return $this->updateBy($idOrder, ['amount' => $amount]);
        }
    }
}
