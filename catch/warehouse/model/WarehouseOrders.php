<?php

namespace catchAdmin\warehouse\model;

use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\permissions\model\Users;
use catchAdmin\supply\model\SubOrders;
use catchAdmin\warehouse\model\search\WarehouseOrderSearch;
use catcher\base\CatchModel as Model;
use catcher\Code;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;
use catchAdmin\basics\model\ShopWarehouse;

class WarehouseOrders extends Model
{
    use BaseOptionsTrait, ScopeTrait, WarehouseOrderSearch, DataRangScopeTrait;

    // 表名
    public $name = 'warehouse_orders';
    // 数据库字段映射
    public $field
    = array(
        'id',
        // 入库单号
        'code',
        // 实体仓id
        'entity_warehouse_id',
        // 虚拟仓id
        'virtual_warehouse_id',
        //入库单来源
        'source',  //purchase 采购单， manual 手工 returned 退货 allot 调拨  check 盘点 void 作废
        // 入库原因
        'notes',
        // 状态
        'audit_status', //0 待提交 1待审核 2 审核通过 -1 审核驳回
        //审核意见
        'audit_notes',
        //审核人
        'audit_by',
        //审核时间
        'audit_time',
        //入库状态
        'warehousing_status', // 0 待入库 1已入库
        //入库时间
        'warehousing_time',
        // products json
        'products',
        // parts
        'parts',
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

    protected $append
    = [
        'audit_status_text', 'warehousing_status_text',  'cabinet_no'
        // 'created_by_name', 'updated_by_name', 'audit_status_text', 'warehousing_status_text',
        // 'entity_warehouse', 'virtual_warehouse', 'cabinet_no', 'audit_by_name'
        // , 'product_info'
        // , 'products', 'parts'
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
            ->field('w.id, w.code,w.virtual_warehouse_id, w.products, w.parts, w.notes, w.source, w.audit_time, w.warehousing_status,
                w.warehousing_time, w.audit_notes, w.audit_status, w.created_at, w.updated_at, w.created_by, w.updated_by,
                wh.name as virtual_warehouse, whe.name as entity_warehouse,
                IFNULL(usa.username, "-") as audit_by_name,
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
            ->leftJoin('users usa', 'usa.id = w.audit_by')
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
                'title' => '入库单号',
                'filed' => 'code',
            ],
            [
                'title' => '入库实体仓',
                'filed' => 'entity_warehouse',
            ],
            [
                'title' => '入库虚拟仓',
                'filed' => 'virtual_warehouse',
            ],
            [
                'title' => '入库状态',
                'filed' => 'warehousing_status_text',
            ],
            [
                'title' => '入库时间',
                'filed' => 'warehousing_time',
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

    public function products($id = '')
    {
        if (empty($id)) {
            $id = $this->getAttr('id');
        }
        return WarehouseOrderProducts::where([
            'warehouse_order_id' => $id,
            'type' => 1
        ])
            ->field('id, goods_name, warehouse_order_id, number, goods_code, goods_name_en, goods_pic, category_name, 
            type, goods_id')
            ->select();
    }

    public function parts($id = '')
    {
        if (empty($id)) {
            $id = $this->getAttr('id');
        }
        return WarehouseOrderProducts::where([
            'warehouse_order_id' => $id,
            'type' => 2
        ])
            ->field('id, goods_name, warehouse_order_id, number, goods_code, goods_name_en, goods_pic, category_name, type, goods_id')
            ->select();
    }

    // public function getProductsAttr()
    // {
    //     return $this->products();
    // }

    // public function getPartsAttr()
    // {
    //     return $this->parts();
    // }

    // public function getProductInfoAttr()
    // {
    //     $data = [];
    //     $products =  WarehouseOrderProducts::where([
    //         'warehouse_order_id' => $this->getAttr('id'),
    //     ])->select();

    //     foreach ($products as $p) {
    //         $row['goods_name'] = $p->goods_name;
    //         $row['goods_code'] = $p->goods_code;
    //         $row['number'] = $p->number;
    //         $data[] = $row;
    //     }

    //     return $data;
    // }

    public function getCreatedByName()
    {
        return Users::where('id', $this->getAttr('created_by'))->value('username') ?? '';
    }

    public function getUpdatedByName()
    {
        return Users::where('id', $this->getAttr('updated_by'))->value('username') ?? '';
    }

    public function getAuditByName()
    {
        return Users::where('id', $this->getAttr('audit_by'))->value('username') ?? '';
    }

    public function getEntityWarehouse($id)
    {
        return Warehouses::where('id', $id)->value('name') ?? '';
    }

    public function getVirtualWarehouse($id)
    {
        return Warehouses::where('id', $id)->value('name') ?? '';
    }
    public function getCabinetNoAttr()
    {
        $order = SubOrders::where('warehouse_order_id', $this->id)->find();
        if (!$order) {
            return '';
        }

        return $order->transOrder->cabinet_no ?? '';
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


    public function getWarehousingStatusTextAttr()
    {
        switch ($this->getAttr('warehousing_status')) {
            case 0:
                return '待入库';
                break;
            case 1:
                return '已入库';
                break;
            default:
                return '';
                break;
        }
    }

    /**
     * 生成订单号
     * @return string
     */
    public function createOrderNo()
    {
        $date  = date('Ymd');
        $time  = strtotime($date);
        $count = $this->where('created_at', '>', $time)->count();
        $str   = sprintf("%04d", $count + 1);
        return "SH" . $date . $str;
    }

    /**
     * 生成批次号
     * @return int|mixed
     */
    public function createBatchNo()
    {
        $date  = date('Ym');
        $time = strtotime(date('Y-m-01', strtotime(date("Y-m-d"))));
        $count = $this->where('created_at', '>', $time)->count();
        $str   = sprintf("%06d", $count + 1);
        return $date . $str;
    }

    /**
     * 创建入库单
     *
     * @param $data
     * @param bool $trans
     *
     * @return mixed
     * @throws \Exception
     */
    public function createWarehouseOrder($data, $trans = true)
    {
        try {
            if ($trans) {
                $this->startTrans();
            }
            $rowProductempty  = [];
            $dataObj = $data;
            $dataObj['code'] = $this->createOrderNo();
            $dataObj['parts'] = json_encode($rowProductempty);
            $dataObj['products'] = json_encode($rowProductempty);
            $res      = $this->createBy($dataObj);
            $products = [];
            foreach ($data['products'] as $val) {
                $row        = [
                    'warehouse_order_id' => $res,
                    'goods_id' => $val['goods_id'],
                    'goods_code' => $val['goods_code'],
                    'category_name' => $val['category_name'],
                    'goods_name' => $val['goods_name'],
                    'goods_name_en' => $val['goods_name_en'],
                    'goods_pic' => $val['goods_pic'],
                    'number' => $val['number'],
                    'type' => $val['type'],
                    'batch_no' => $val['batch_no'] ?? $this->createBatchNo()
                ];
                $products[] = $row;
            }
            $model = new WarehouseOrderProducts();
            $model->saveAll($products);
            $this->fixProduct($res);
            if ($trans) {
                $this->commit();
            }
            return $res;
        } catch (\Exception $exception) {
            if ($trans) {
                $this->rollback();
            }
            throw  new \Exception($exception->getMessage(), Code::FAILED);
        }
    }

    /**
     * 生成入库单json数据
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
        $model = new WarehouseOrderProducts();
        $data = [];
        foreach ($dataAll as $key => $value) {
            $product = $model->field(['id', 'goods_name', 'goods_code', 'number'])->where(['type' => 1, 'warehouse_order_id' => $value['id']])->select();

            $parts = $model->field(['id', 'goods_name', 'goods_code', 'number'])->where(['type' => 2, 'warehouse_order_id' => $value['id']])->select();
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
