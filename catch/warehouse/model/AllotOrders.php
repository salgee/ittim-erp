<?php

namespace catchAdmin\warehouse\model;

use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\permissions\model\Users;
use catchAdmin\warehouse\model\search\AllotOrderSearch;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;
use catchAdmin\basics\model\ShopWarehouse;
use catchAdmin\warehouse\model\AllotOrderProducts;

class AllotOrders extends Model
{
    use BaseOptionsTrait, ScopeTrait, AllotOrderSearch, DataRangScopeTrait;

    // 表名
    public $name = 'allot_orders';
    // 数据库字段映射
    public $field
    = array(
        'id',
        // 实体仓id
        'entity_warehouse_id',
        // 调入仓库
        'transfer_in_warehouse_id',
        // 调出仓库
        'transfer_out_warehouse_id',
        // 审核状态 0 待提交 1 待审核 2调出审核通过 3 调入审核通过 -1 调出审核驳回 -2 调入审核驳回
        'audit_status',
        // 审核意见
        'audit_notes',
        // 调拨原因
        'notes',
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
        'audit_status_text',
        // 'created_by_name', 'updated_by_name', 'entity_warehouse',
        // 'transfer_in_warehouse', 'transfer_out_warehouse', 'products', 'parts'
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
        $whereOr1 = [];
        // 运营岗位
        if ($prowerData['is_operation']) {
            // 绑定店铺
            if ($prowerData['shop_ids']) {
                // 获取店铺下虚拟仓库 id
                $ids = [];
                // 获取绑定店铺下仓库
                $ids = ShopWarehouse::whereIn('shop_id', $prowerData['shop_ids'])->column('warehouse_fictitious_id');
                if (count($ids) > 0) {
                    $idsVi = implode(',', $ids);
                    $whereOr = [
                        ['w.transfer_in_warehouse_id', 'in', $idsVi],
                    ];
                    $whereOr1 = [
                        ['w.transfer_out_warehouse_id', 'in', $idsVi]
                    ];
                }
            }
        }
        $order =  $this
            ->dataRange([], 'created_by')
            ->field('w.id, w.entity_warehouse_id,w.transfer_in_warehouse_id, w.products, w.parts, w.notes, w.transfer_out_warehouse_id,
                w.audit_notes, w.audit_status, w.created_at, w.updated_at, w.created_by, w.updated_by,
                wh.name as transfer_in_warehouse, whe.name as entity_warehouse,whout.name as transfer_out_warehouse,
                u.username as created_by_name, IFNULL(us.username, "-") as updated_by_name')
            ->alias('w')
            ->catchSearch()
            ->whereOr(function ($query) use ($whereOr, $whereOr1) {
                if ($whereOr) {
                    $query->whereOr($whereOr);
                }
                if ($whereOr1) {
                    $query->whereOr($whereOr1);
                }
            })
            ->leftJoin('users u', 'u.id = w.created_by')
            ->leftJoin('users us', 'us.id = w.updated_by')
            ->leftJoin('warehouses whout', 'whout.id = w.transfer_out_warehouse_id')
            ->leftJoin('warehouses wh', 'wh.id = w.transfer_in_warehouse_id')
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
                'title' => '实体仓',
                'filed' => 'entity_warehouse',
            ],
            [
                'title' => '调入虚拟仓',
                'filed' => 'transfer_in_warehouse',
            ],
            [
                'title' => '调出虚拟仓',
                'filed' => 'transfer_out_warehouse',
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

    public function products($id)
    {
        return AllotOrderProducts::where(['allot_order_id' => $id, 'type' => 1])
            ->select();
    }

    public function parts($id)
    {
        return AllotOrderProducts::where(['allot_order_id' => $id, 'type' => 2])
            ->select();
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

    public function getTransferInWarehouse($id)
    {
        return Warehouses::where('id', $id)->value('name')
            ??
            '';
    }

    public function getTransferOutWarehouse($id)
    {
        return Warehouses::where('id', $id)->value('name') ??
            '';
    }


    public function getAuditStatusTextAttr()
    {

        switch ($this->getAttr('audit_status')) {
            case '-2':
                return '调入审核驳回';
                break;
            case '-1':
                return '调出审核驳回';
                break;
            case 0:
                return '待提交';
                break;
            case 1:
                return '待审核';
                break;
            case 2:
                return '调出审核通过';
                break;
            case 3:
                return '调入审核通过';
                break;
            default:
                return '';
                break;
        }
    }
    public function getOutboundOrderProductsOut($entityWarehouseId, $virtualWarehouseId, $products, $number = 0)
    {
        $data = [];
        foreach ($products as &$product) {
            //根据调拨商品查找可出库批次 重新组装出库单商品数据
            $row   = $product;

            $stock = WarehouseStock::where([
                'entity_warehouse_id' => $entityWarehouseId,
                'virtual_warehouse_id' => $virtualWarehouseId,
                'goods_code' => $product['goods_code'],
                'goods_type' => $product['type']
            ]);
            if ($product['number'] > 0) {
                if ($number == 0) {
                    $stock = $stock->where('number', '>', 0);
                } else {
                    $stock = $stock->where('number', '>=', $number);
                }
            }
            $stock = $stock->order('batch_no', 'asc')
                ->select()
                ->toArray();

            foreach ($stock as $s) {
                //商品数量为0退出循环
                if ($product['number'] == 0) {
                    break;
                }

                //当前批次库存数少于商品调拨出库数 则当前批次数量全部出库
                if ($s['number'] < $product['number']) {
                    $row['number']     = $s['number'];
                    $row['batch_no']   = $s['batch_no'];
                    $product['number'] = $product['number'] - $s['number'];
                } else {
                    //当前批次库存数大于等于商品出库数
                    $row['number']     = $product['number'];
                    $row['batch_no']   = $s['batch_no'];
                    $product['number'] = 0;
                }

                $data[] = $row;
            }
        }

        return $data;
    }


    public function getOutboundOrderProducts($entityWarehouseId, $virtualWarehouseId, $products, $number = 0)
    {
        $data = [];
        foreach ($products as &$product) {
            //根据调拨商品查找可出库批次 重新组装出库单商品数据
            $row   = $product;

            $stock = WarehouseStock::where([
                'entity_warehouse_id' => $entityWarehouseId,
                'virtual_warehouse_id' => $virtualWarehouseId,
                'goods_code' => $product['goods_code'],
                'goods_type' => $product['type']
            ]);
            if ($product['number'] > 0) {
                if ($number == 0) {
                    $stock = $stock->where('number', '>=', $row['number']);
                } else {
                    $stock = $stock->where('number', '>=', $number);
                }
            }
            $stock = $stock->order('batch_no', 'asc')
                ->select()
                ->toArray();

            foreach ($stock as $s) {
                //商品数量为0退出循环
                if ($product['number'] == 0) {
                    break;
                }

                //当前批次库存数少于商品调拨出库数 则当前批次数量全部出库
                if ($s['number'] < $product['number']) {
                    $row['number']     = $s['number'];
                    $row['batch_no']   = $s['batch_no'];
                    $product['number'] = $product['number'] - $s['number'];
                } else {
                    //当前批次库存数大于等于商品出库数
                    $row['number']     = $product['number'];
                    $row['batch_no']   = $s['batch_no'];
                    $product['number'] = 0;
                }

                $data[] = $row;
            }
        }

        return $data;
    }


    /**
     * 新增调拨单
     *
     * @param  $data
     * @return void
     */
    public function add($data)
    {
        $allotOrderProductsModel = new AllotOrderProducts();
        $dataObj = $data;
        $rowProductempty = [];
        $dataObj['parts'] = json_encode($rowProductempty);
        $dataObj['products'] = json_encode($rowProductempty);
        $res = $this->createBy($dataObj);

        if (isset($data['products'])) {
            $products = [];
            foreach ($data['products'] as $val) {
                $row        = [
                    'allot_order_id' => $res,
                    'goods_id' => $val['goods_id'],
                    'goods_code' => $val['goods_code'],
                    'category_name' => $val['category_name'],
                    'goods_name' => $val['goods_name'],
                    'goods_name_en' => $val['goods_name_en'],
                    'goods_pic' => $val['goods_pic'],
                    'packing_method' => $val['packing_method'],
                    'number' => $val['number'],
                    'type' => $val['type'],
                ];
                $products[] = $row;
            }

            $allotOrderProductsModel->saveAll($products);
            $this->fixProduct($res);
        }

        if (isset($data['parts'])) {
            $products = [];
            foreach ($data['parts'] as $val) {
                $row        = [
                    'allot_order_id' => $res,
                    'goods_id' => $val['goods_id'],
                    'goods_code' => $val['goods_code'],
                    'category_name' => $val['category_name'],
                    'goods_name' => $val['goods_name'],
                    'goods_name_en' => $val['goods_name_en'],
                    'goods_pic' => $val['goods_pic'],
                    'packing_method' => $val['packing_method'],
                    'number' => $val['number'],
                    'type' => $val['type'],
                ];
                $products[] = $row;
            }
            $allotOrderProductsModel->saveAll($products);
            $this->fixProduct($res);
        }
        return $res;
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
        $model = new AllotOrderProducts();
        $data = [];
        foreach ($dataAll as $key => $value) {
            $product = $model->field(['id', 'goods_name', 'goods_code', 'number'])->where(['type' => 1, 'allot_order_id' => $value['id']])->select();

            $parts = $model->field(['id', 'goods_name', 'goods_code', 'number'])->where(['type' => 2, 'allot_order_id' => $value['id']])->select();
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
