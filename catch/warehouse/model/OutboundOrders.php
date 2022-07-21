<?php

namespace catchAdmin\warehouse\model;

use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\permissions\model\Users;
use catchAdmin\warehouse\model\search\OutboundOrderSearch;
use catcher\base\CatchModel as Model;
use catcher\Code;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;
use catchAdmin\warehouse\model\OutboundOrderProducts;
use catchAdmin\basics\model\ShopWarehouse;

class OutboundOrders extends Model
{
    use BaseOptionsTrait, ScopeTrait, OutboundOrderSearch, DataRangScopeTrait;

    // 表名
    public $name = 'outbound_orders';
    // 数据库字段映射
    public $field = array(
        'id',
        // 出库单号
        'code',
        // 出库实体仓id
        'entity_warehouse_id',
        // 出库虚拟仓id
        'virtual_warehouse_id',
        // 出库单来源 sales 销售， manual 手工，  allot 调拨，  check 盘点
        'source',
        // 审核状态 0 待提交 1待审核 2 审核通过 -1 审核驳回
        'audit_status',
        // 审核原因
        'audit_notes',
        // 审核状态 0 待出库  1已出库
        'outbound_status',
        // 出库时间
        'outbound_time',
        // products json
        'products',
        // parts
        'parts',
        // 出库原因
        'notes',
        // 创建人
        'created_by',
        // 修改人
        'updated_by',
        // 创建时间
        'created_at',
        // 修改时间
        'updated_at',
        // 删除时间
        'deleted_at',
    );

    protected $json = ['products', 'parts'];
    protected $jsonAssoc = true;

    protected $append = [
        'audit_status_text', 'outbound_status_text',
        // 'created_by_name', 'updated_by_name',  'entity_warehouse', 'virtual_warehouse', 'products', 'parts'
    ];

    /**
     * 列表
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList($action = 'list')
    {
        $users = new Users;
        $whereOr = [];
        $prowerData = $users->getRolesList();

        // 绑定店铺
        if ($prowerData['shop_ids']) {
            // 获取店铺下虚拟仓库 id
            $ids = [];
            // 获取绑定店铺下仓库
            $ids = ShopWarehouse::whereIn('shop_id', $prowerData['shop_ids'])->column('warehouse_fictitious_id');
            if (count($ids) > 0) {
                $idsVi = implode(',', $ids);
                $whereOr = [
                    ['w.virtual_warehouse_id', 'in', $idsVi]
                ];
            }
        }
        $order =  $this
            ->dataRange([], 'created_by')
            ->field('w.id, w.code,w.virtual_warehouse_id,w.entity_warehouse_id, 
                w.source,w.audit_status, w.audit_notes, w.outbound_status,
                w.outbound_time,w.products, w.parts, w.notes, 
                w.created_at, w.updated_at, w.created_by, w.updated_by,
                wh.name as virtual_warehouse, whe.name as entity_warehouse,
                u.username as created_by_name, IFNULL(us.username, "-") as updated_by_name')
            ->alias('w')
            ->catchSearch()
            ->whereOr(function ($query) use ($whereOr) {
                if (count($whereOr) > 0) {
                    $query->where($whereOr)
                        ->catchSearch();
                }
            })
            ->leftJoin('users u', 'u.id = w.created_by')
            ->leftJoin('users us', 'us.id = w.updated_by')
            ->leftJoin('warehouses wh', 'wh.id = w.virtual_warehouse_id')
            ->leftJoin('warehouses whe', 'whe.id = w.entity_warehouse_id');

        if ($action == 'list') { //列表
            $order = $order->order('w.id', 'desc')->paginate();
        } elseif ($action == 'export') { //导出
            $order = $order->order('w.id', 'desc')->select();
        }
        return $order;
    }

    public static function  exportField()
    {
        return [
            [
                'title' => '出库单号',
                'filed' => 'code',
            ],
            [
                'title' => '出库实体仓',
                'filed' => 'entity_warehouse',
            ],
            [
                'title' => '出库虚拟仓',
                'filed' => 'virtual_warehouse',
            ],
            [
                'title' => '审核状态',
                'filed' => 'audit_status_text',
            ],
            [
                'title' => '商品名称',
                'filed' => 'goods_name',
            ],
            [
                'title' => '商品sku',
                'filed' => 'goods_code',
            ],
            [
                'title' => '商品数量',
                'filed' => 'number',
            ],
            [
                'title' => '创建人',
                'filed' => 'created_by_name',
            ],
            [
                'title' => '创建时间',
                'filed' => 'created_at',
            ]
        ];
    }

    // public function getProductsAttr()
    // {
    //     return $this->products();
    // }

    // public function getPartsAttr()
    // {
    //     return $this->parts();
    // }

    public function products($id = '')
    {
        if (empty($id)) {
            $id = $this->getAttr('id');
        }
        return OutboundOrderProducts::where(['outbound_order_id' => $id, 'type' => 1])
            ->select();
    }

    public function parts($id = '')
    {
        if (empty($id)) {
            $id = $this->getAttr('id');
        }
        return OutboundOrderProducts::where(['outbound_order_id' => $id, 'type' => 2])->select();
    }

    public function getCreatedByName()
    {
        return Users::where('id', $this->getAttr('created_by'))->value('username') ?? '';
    }

    public function getUpdatedByName()
    {
        return Users::where('id', $this->getAttr('updated_by'))->value('username') ?? '';
    }

    public function getEntityWarehouse($id)
    {
        return Warehouses::where('id', $id)->value('name') ?? '';
    }

    public function getVirtualWarehouse($id)
    {
        return Warehouses::where('id', $id)->value('name') ?? '';
    }


    public function getAuditStatusTextAttr()
    {

        switch ($this->getAttr('audit_status')) {
            case '-1':
                return '审核驳回';
                break;
            case 0:
                return '待提交';
                break;
            case 1:
                return '待审核';
                break;
            case 2:
                return '审核通过';
                break;
            default:
                return '';
                break;
        }
    }


    public function getOutboundStatusTextAttr()
    {
        switch ($this->getAttr('outbound_status')) {
            case 0:
                return '待出库';
                break;
            case 1:
                return '已出库';
                break;
            default:
                return '';
                break;
        }
    }


    public function createOutOrder($data)
    {
        try {

            $this->startTrans();
            $rowProductempty  = [];
            $dataObj = $data;
            $dataObj['parts'] = json_encode($rowProductempty);
            $dataObj['products'] = json_encode($rowProductempty);
            $res = $this->createBy($dataObj);
            $products = [];
            foreach ($data['products'] as $val) {
                $row = [
                    'outbound_order_id' => $res,
                    'goods_id' => $val['goods_id'],
                    'goods_code' => $val['goods_code'],
                    'category_name' => $val['category_name'],
                    'goods_name' => $val['goods_name'],
                    'goods_name_en' => $val['goods_name_en'],
                    'goods_pic' => $val['goods_pic'],
                    'number' => $val['number'],
                    'type' => $val['type'],
                    'batch_no' => $val['batch_no']
                ];
                $products[] = $row;
            }

            $model = new OutboundOrderProducts();
            $model->saveAll($products);
            $this->fixProduct($res);
            $this->commit();
            return  $res;
        } catch (\Exception $exception) {
            $this->rollback();
            throw  new \Exception($exception->getMessage(), Code::FAILED);
        }
    }
    /**
     * 获取出库订单
     * @param $v仓库id $v1 虚拟仓库id $v2 商品id
     */
    public function getWarehosebob($v, $v1, $v2)
    {
        return $this->field('a.id')
            ->alias('a')
            ->where('a.entity_warehouse_id', '=', $v)
            ->where('a.virtual_warehouse_id', '=', $v1)
            ->where('a.audit_status', '=', 1)
            ->leftJoin('outbound_order_products s', 'a.id = s.outbound_order_id ')
            ->where('s.goods_id ', '=', $v2)
            ->select();
    }

    /**
     * fixProduct
     */
    public function fixProduct($id = null)
    {
        $where = [];
        if (!empty($id)) {
            $where = [
                'id' => $id
            ];
        }
        $dataAll = $this->where($where)->order('products','asc')->select();
        $model = new OutboundOrderProducts();
        $data = [];
        foreach ($dataAll as $key => $value) {
            $product = $model->field(['id', 'goods_name', 'goods_code', 'number'])->where(['type' => 1, 'outbound_order_id' => $value['id']])->select();

            $parts = $model->field(['id', 'goods_name', 'goods_code', 'number'])->where(['type' => 2, 'outbound_order_id' => $value['id']])->select();
            if (!empty($product)) {
                $data['products'] =  json_encode($product);
            }
            if (!empty($parts)) {
                $data['parts'] =  json_encode($parts);
            }
            $this->where(['id' => $value['id']])->update($data);
        }
        return true;
    }
}
