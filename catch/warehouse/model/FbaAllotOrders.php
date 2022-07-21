<?php

namespace catchAdmin\warehouse\model;

use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\permissions\model\Users;
use catchAdmin\warehouse\model\search\FbaAllotOrderSearch;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;
use catchAdmin\system\model\DictionaryData;
use catchAdmin\warehouse\model\FbaAllotOrderProducts;
use catchAdmin\basics\model\ShopWarehouse;

class FbaAllotOrders extends Model
{
    use BaseOptionsTrait, ScopeTrait, FbaAllotOrderSearch, DataRangScopeTrait;
    // 表名
    public $name = 'fba_allot_orders';
    // 数据库字段映射
    public $field = array(
        'id',
        // 调出仓库实体仓id
        'entity_warehouse_id',
        // 调出虚拟仓id
        'virtual_warehouse_id',
        // fba仓库id
        'fba_warehouse_id',
        // 发货类型
        'delivery_type',
        // 配送方式
        'shipping_type',
        // 是否由客户安排发货
        'customer_type',
        // 装箱服务
        'packing_service',
        // 增值服务
        'value_added_services',
        // 产品换标服务
        'label_change_service',
        // 外箱贴标服务
        'containers_label_service',
        // 托盘贴标服务
        'pallet_label_service',
        // 物流费
        'logistics_fee',
        // 特殊说明文件
        'attachment',
        //0 待提交 1待审核 2 审核通过 -1 审核驳回
        'audit_status',
        // 审核意见
        'audit_notes',
        'bill_of_lading_number',
        'amazon_po_id',
        //备注
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

    protected $append = [
        'audit_status_text',
        // 'created_by_name', 'updated_by_name', 
        //  'entity_warehouse', 
        // 'virtual_warehouse', 'fba_warehouse',
        // 'label_price', 'pallet_price', 'outbound_price',
        // 'parts', 'products', 
        'delivery_type_text', 'shipping_type_text'
    ];

    /**
     * 列表
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList($action = 'list')
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $whereOr = [];
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
                        ['w.entity_warehouse_id', 'in', $idsVi],
                    ];
                    $whereOr1 = [
                        ['w.virtual_warehouse_id', 'in', $idsVi]
                    ];
                }
            }
        }
        $order =  $this
            ->dataRange([], 'created_by')
            ->field('w.id, w.entity_warehouse_id, w.virtual_warehouse_id,w.fba_warehouse_id,
                w.delivery_type, w.shipping_type, w.customer_type, w.packing_service,
                w.value_added_services, w.label_change_service, w.containers_label_service,
                w.pallet_label_service, w.logistics_fee, w.audit_status,w.audit_notes,
                w.bill_of_lading_number, w.amazon_po_id,
                w.products, w.parts, w.notes, w.created_at, w.updated_at, w.created_by, w.updated_by,
                wh.name as virtual_warehouse, wen.name as entity_warehouse, whfba.name as fba_warehouse,
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
            ->leftJoin('warehouses wh', 'wh.id = w.virtual_warehouse_id')
            ->leftJoin('warehouses wen', 'wen.id = w.entity_warehouse_id')
            ->leftJoin('warehouses whfba', 'whfba.id = w.fba_warehouse_id');

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
                'title' => '日期',
                'filed' => 'created_at',
            ],
            [
                'title' => '运营',
                'filed' => 'created_by_name',
            ],
            [
                'title' => '提货方式',
                'filed' => 'delivery_type_text',
            ],
            [
                'title' => '承运商',
                'filed' => 'shipping_type_text',
            ],
            [
                'title' => 'BOL',
                'filed' => 'bill_of_lading_number',
            ],
            [
                'title' => '调出实体仓',
                'filed' => 'entity_warehouse',
            ],
            [
                'title' => '调出虚拟仓',
                'filed' => 'virtual_warehouse',
            ],
            [
                'title' => '调入FBA仓',
                'filed' => 'fba_warehouse',
            ],
            [
                'title' => '审核状态',
                'filed' => 'audit_status_text',
            ],
            [
                'title' => '物流费',
                'filed' => 'logistics_fee',
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
                'title' => '托盘数量',
                'filed' => 'pallet_number',
            ],
            [
                'title' => '贴标费',
                'filed' => 'label_price',
            ],
            [
                'title' => '打托费',
                'filed' => 'pallet_price',
            ],
            [
                'title' => '出库费',
                'filed' => 'outbound_price',
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
        // return $this->hasMany(FbaAllotOrderProducts::class, 'fba_allot_order_id', 'id');
        return FbaAllotOrderProducts::where(['fba_allot_order_id' => $id, 'type' => 1])
            ->select();
    }

    public function parts($id)
    {
        return FbaAllotOrderProducts::where(['fba_allot_order_id' => $id, 'type' => 2])
            ->select();
    }

    public function product()
    {
        return $this->hasMany(FbaAllotOrderProducts::class, 'fba_allot_order_id', 'id');
    }

    // public function getLabelPriceAttr()
    // {
    //     return FbaAllotOrderProducts::where('fba_allot_order_id', $this->getAttr('id'))->sum('label_price');
    // }

    // public function getPalletPriceAttr()
    // {
    //     return FbaAllotOrderProducts::where('fba_allot_order_id', $this->getAttr('id'))->sum('pallet_price');
    // }

    // public function getOutboundPriceAttr()
    // {
    //     return FbaAllotOrderProducts::where('fba_allot_order_id', $this->getAttr('id'))->sum('outbound_price');
    // }

    public function getCreatedByName()
    {
        return Users::where('id', $this->getAttr('created_by'))->value('username') ?? '';
    }

    public function getUpdatedByName()
    {
        return Users::where('id', $this->getAttr('updated_by'))->value('username') ?? '';
    }

    public function getEntityWarehouse($entity_warehouse_id)
    {
        return Warehouses::where('id', $entity_warehouse_id)->value('name') ?? '';
    }

    public function getVirtualWarehouse($virtual_warehouse_id)
    {
        return Warehouses::where('id', $virtual_warehouse_id)->value('name') ?? '';
    }

    public function getFbaWarehouse($fba_warehouse_id)
    {
        return Warehouses::where('id', $fba_warehouse_id)->value('name') ??
            '';
    }

    public function getDeliveryTypeTextAttr()
    {
        return DictionaryData::where('id', $this->getAttr('delivery_type'))->value('dict_data_name');
    }
    // 承运商 ShippingTypeText
    public function getShippingTypeTextAttr()
    {
        return DictionaryData::where('id', $this->getAttr('shipping_type'))->value('dict_data_name');
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
        $model = new FbaAllotOrderProducts();
        $data = [];
        foreach ($dataAll as $key => $value) {
            $product = $model->field(['id', 'goods_name', 'goods_code', 'number', 'pallet_number', 'label_price', 'pallet_price', 'outbound_price'])
                ->where(['type' => 1, 'fba_allot_order_id' => $value['id']])->select();

            $parts = $model->field(['id', 'goods_name', 'goods_code', 'number', 'pallet_number', 'label_price', 'pallet_price', 'outbound_price'])
                ->where(['type' => 2, 'fba_allot_order_id' => $value['id']])->select();
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
