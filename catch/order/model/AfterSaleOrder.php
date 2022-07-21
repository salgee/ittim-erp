<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-03-25 15:33:20
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-11-26 14:58:18
 * @Description: 
 */

namespace catchAdmin\order\model;

use catcher\base\CatchModel as Model;
use catcher\Code;
use catchAdmin\order\model\search\AfterSaleOrderSearch;
use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\warehouse\model\Warehouses;
use catchAdmin\basics\model\ShopWarehouse;
use catchAdmin\permissions\model\Users;

class AfterSaleOrder extends Model
{
    use DataRangScopeTrait;
    use AfterSaleOrderSearch;

    // 表名
    public $name = 'after_sale_order';
    // 数据库字段映射
    public $field = array(
        'id',
        // 是否生成入库（出库）单子 0-否 1-是
        'is_warehousing',
        // 商品入库类型 1-良品 2-残品
        'goods_warehous_type',
        // 入库单id
        'warehous_order_id',
        // 订单编号（系统自动生成SH+年月日+4位流水）
        'sale_order_no',
        // 0-待审核 1-审核通过 2-审核驳回
        'status',
        // 审核拒绝原因
        'examine_reason',
        // 售后原因 1-客户无理由退款退货 2-描述不符 3-包装问题 4-质量问题 5-物流问题 6-其他
        'sale_reason',
        // 备注
        'remarks',
        // 平台订单编号
        'platform_order_no',
        // 原平台订单编号
        'platform_order_no2',
        // 原平台订单编号2
        'platform_no_ext',
        // 平台订单id
        'order_id',
        // 店铺id
        'shop_id',
        // 客户id
        'company_id',
        // 原始订单类型  // 订单类型;0-销售订单;1-异常订单;2-借卖订单;3-客户订单;4-预售订单;5-亚马逊平台发货(FBA)
        'order_type',
        // 申请售后时输入金额
        'fill_amount',
        // 退款金额（售后产生费用）
        'refund_amount',
        // 售后类型 1-仅退款 2-退货退款 3-补货 4-召回 5-修改地址
        'type',
        // 退款类型 1-部分退款 2-全部退款
        'refund_type',
        // 补货类型 1-整件补货 2-配件补货
        'replenish_type',
        // 修改金额 type=5,4 召回操作费  召回操作费
        'modify_amount',
        // 物流单号 type=2,3,4
        'logistics_no',
        //物流名称
        'shipping_name',
        // 退款、补发物流
        'refund_logistics',
        // type=4召回数量 type=2退货数量
        'recall_num',
        // 快递费
        'logistics_fee',
        // 实体仓库ID
        'storage_id',
        // 虚拟仓库
        'vi_id',
        // 物流单号时间
        'tracking_date',
        // 修改人
        'update_by',
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
     * 状态查看
     */
    public static $orderType = array(
        Code::ORDER_SALES_REFUND => '订单退款',
        Code::ORDER_SALES_REFUNDALL => '退货退款',
        Code::ORDER_SALES_CPFR => '补货',
        Code::ORDER_SALES_RECALL => '召回',
        Code::ORDER_SALES_MODIFY_ADDRESS => '修改地址'
    );

    /**
     * 售后原因
     */
    public static $orderSaleReason = array(
        1 => '客户无理由退款退货',
        2 => '描述不符',
        3 => '包装问题',
        4 => '质量问题',
        5 => '物流问题',
        6 => '其他'
    );

    /**
     * 审核状态
     */
    public static $orderStatus = array(
        Code::AFTER_STATUS_WAIT => '待审核',
        Code::AFTER_STATUS_PASS => '审核通过',
        Code::AFTER_STATUS_REFUSE => '审核驳回'
    );

    /**
     * 重写订单列表数据
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList()
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        $whereOr = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['is_company']) {
                if ($prowerData['company_id']) {
                    $where = [
                        'o.company_id' => $prowerData['company_id']
                    ];
                }
            }
            if ($prowerData['shop_ids'] && !$prowerData['is_company']) {
                $whereOr = [
                    ['o.shop_id', 'in',  $prowerData['shop_ids']]
                ];
            }
        }
        return $this->dataRange()
            ->catchSearch()
            ->whereOr(function ($query) use ($whereOr, $where) {
                if (count($whereOr) > 0 || count($where) > 0) {
                    $query->where($whereOr)
                        ->where($where)
                        ->catchSearch();
                }
            })
            // ->whereOr($where)
            // ->whereOr($whereOr)
            ->field('o.*, s.shop_name, u.username as creator_name, IFNULL(us.username, "-") as update_name,
            og.goods_code, og.name as goods_name, og.goods_id, cg.parent_name, cg.name as category_name,
            p.name_ch, p.category_id, p.code as goods_codes, s.platform_id, pf.name as platform_name')
            ->alias('o')
            ->leftJoin('order_item_records og', 'og.order_record_id = o.order_id and og.type=0')
            ->leftJoin('product p', 'p.id = og.goods_id')
            ->leftJoin('category cg', 'cg.id = p.category_id')
            ->leftJoin('shop_basics s', 's.id = o.shop_id')
            ->leftJoin('platform pf', 'pf.id = s.platform_id')
            ->leftJoin('users u', 'u.id = o.creator_id')
            ->leftJoin('users us', 'us.id = o.update_by')
            ->order('id', 'desc')
            ->paginate();
    }

    /**
     * * 导出数据
     * @param $fileData  导出字段集合
     */
    public function getExportList()
    {
        $fileList = ['o.*', 'o.id as order_ids', 'sb.shop_name', 'lc.name as logistics_name'];
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        $whereOr = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['is_company']) {
                if ($prowerData['company_id']) {
                    $where = [
                        'o.company_id' => $prowerData['company_id']
                    ];
                }
            }
            if ($prowerData['shop_ids'] && !$prowerData['is_company']) {
                $whereOr = [
                    ['o.shop_id', 'in',  $prowerData['shop_ids']]
                ];
            }
        }
        return $this->dataRange()
            // ->whereOr($where)
            // ->whereOr($whereOr)
            ->whereOr(function ($query) use ($whereOr, $where) {
                if (count($whereOr) > 0 || count($where) > 0) {
                    $query->where($whereOr)
                        ->where($where)
                        ->catchSearch();
                }
            })
            ->field($fileList)
            ->alias('o')
            ->catchSearch()
            ->leftJoin('shop_basics sb', 'sb.id= o.shop_id')
            ->leftJoin('lforwarder_company lc', 'lc.id = o.refund_logistics')
            ->select()->each(function (&$item) {
                $item['type_text'] = $this::$orderType[$item['type']];
                $item['status_text'] = $this::$orderStatus[$item->status];
            });
    }
    /**
     * 售后导出字段  exportField
     */
    public function exportField()
    {
        return [
            [
                'title' => '订单ID',
                'filed' => 'order_ids',
            ],
            [
                'title' => '售后类型',
                'filed' => 'type_text',
            ],
            [
                'title' => '订单编号',
                'filed' => 'sale_order_no',
            ],
            [
                'title' => '平台订单编号',
                'filed' => 'platform_order_no',
            ],
            [
                'title' => '原平台订单编号',
                'filed' => 'platform_order_no2',
            ],
            [
                'title' => '店铺名称',
                'filed' => 'shop_name',
            ],
            [
                'title' => '订单状态',
                'filed' => 'status_text',
            ],
            [
                'title' => '售后原因',
                'filed' => 'sale_reason',
            ],
            [
                'title' => '申请售后时输入金额',
                'filed' => 'fill_amount',
            ],
            [
                'title' => '修改金额',
                'filed' => 'modify_amount',
            ],
            [
                'title' => '物流单号',
                'filed' => 'logistics_no',
            ],
            [
                'title' => '退款物流',
                'filed' => 'logistics_name',
            ],
            [
                'title' => '创建时间',
                'filed' => 'created_at',
            ],
        ];
    }

    /**
     * 详情
     * @param $id 订单id
     */
    public function findByInfo($id)
    {
        $data = $this->field('o.*')
            ->alias('o')
            ->where('id', '=', $id)
            ->find();
        $warehouses = [];
        if (!empty($data['storage_id'])) {
            $warehouses = Warehouses::field('w.name as warehouses_name, w.state, w.city, w.street')
                ->alias('w')
                ->where('id', '=', $data['storage_id'])
                ->find();
            $data['warehouses_name'] = $warehouses['warehouses_name'];
            $data['state'] = $warehouses['state'];
            $data['city'] = $warehouses['city'];
            $data['street'] = $warehouses['street'];
        }
        return $data;
    }

    /**
     * 获取当前实体仓下的虚拟仓库仓库
     * @param $id 仓库id (实体)
     * @param $shop_id 店铺id
     */
    public function warehousesSubclass($id, $shop_id)
    {
        $list = [];
        $list['remnant'] = Warehouses::where(['type' => 3, 'parent_id' => $id, 'is_active' => 1])->select();
        $list['virtual'] = ShopWarehouse::where(['shop_id' => $shop_id, 'warehouse_id' => $id])->select()->each(function ($item) {
            $item['name'] = Warehouses::where('id', $item['warehouse_fictitious_id'])->value('name');
        });
        return $list;
    }

    /**
     * 获取店铺下的实体仓库
     * @param $shop_id 店铺id
     */
    public function warehouseShop($shop_id)
    {
        return ShopWarehouse::where(['shop_id' => $shop_id])->select()->each(function ($item) {
            $data = Warehouses::where('id', $item['warehouse_id'])->find();
            $item['entity_name'] = $data['name'];
            $item['state'] = $data['state'];
            $item['city'] = $data['city'];
            $item['street'] = $data['street'];
        });
    }

    /**
     * 售后产生费用
     * @param $id 售后id
     * @param $warehouseFee 退货物流单号对应的物流费用
     */
    public function warehouseFee($id, $warehouseFee)
    {
        try {
            if (!$id) $id = 0;
            if (!$warehouseFee) $warehouseFee = 0;

            //查询数据
            $afterOrderData = $this->alias('aso')
                ->field('aso.*,odp.transaction_price_value,odp.tax_amount_value,odp.return_num,odp.after_amount,odp.number,odp.freight_fee')
                ->leftJoin('order_deliver_products odp', 'aso.id= odp.after_order_id')
                ->where(['aso.id' => $id])
                ->find();
            if (!$afterOrderData) {
                return false;
            }

            $afterOrderData = $afterOrderData->toArray();
            $fee = 0;

            //退货退款产生的售后费用
            if ($afterOrderData['type'] == 2) {

                //良品,退款金额+退货物流单号对应的物流费用（取自财务管理>物流应付账款查询中导入的物流单号对应的费用）-（采购基准价*数量+海运费*数量+关税*数量)
                if ($afterOrderData['goods_warehous_type'] == 1) {
                    $feeo =  bcadd($afterOrderData['after_amount'], $warehouseFee, 2);

                    $feet = bcmul(
                        $afterOrderData['return_num'],
                        bcadd(
                            bcadd($afterOrderData['transaction_price_value'], $afterOrderData['tax_amount_value'], 2),
                            $afterOrderData['freight_fee'],
                            2
                        ),
                        2
                    );

                    $fee = bcsub($feeo, $feet, 2);
                } else if ($afterOrderData['goods_warehous_type'] == 2) {

                    //残品,退款金额+退货物流单号对应的物流费用（取自财务管理>物流应付账款查询中导入的物流单号对应的费用）
                    $fee = bcadd($afterOrderData['after_amount'], $warehouseFee, 2);
                }
            }

            //补货产生的售后费用
            if ($afterOrderData['type'] == 3) {

                //整件补货,补货物流对应的物流费（取自财务管理>物流应付账款查询中导入的数据）
                if ($afterOrderData['replenish_type'] == 1) {
                    $fee = $warehouseFee;
                } else if ($afterOrderData['replenish_type'] == 2) {

                    //配件补货,补发商品的采购基准价*数量+补发商品的海运费*数量+补发商品的关税*数量+补发商品的订单操作费*数量+快递费（取自财务管理>物流应付账款查询中导入的数据）
                    /*差订单操作费没有加进去*/
                    $fee =  bcmul(
                        $afterOrderData['number'],
                        bcadd(
                            bcadd(
                                bcadd($afterOrderData['transaction_price_value'], $afterOrderData['tax_amount_value'], 2),
                                $afterOrderData['freight_fee'],
                                2
                            ),
                            $warehouseFee,
                            2
                        ),
                        2
                    );
                }
            }

            // 更新售后订单售后费用
            $this->updateBy($id, ['refund_amount' => $fee]);

            return true;
        } catch (\Exception $e) {
            echo $e->getMessage();
            die;
            return false;
        }
    }
}
