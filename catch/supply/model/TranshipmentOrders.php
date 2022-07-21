<?php

namespace catchAdmin\supply\model;

use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\permissions\model\Users;
use catchAdmin\supply\model\search\TranshipmentOrderSearch;
use catchAdmin\warehouse\model\Warehouses;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;
use think\facade\Db;
use catchAdmin\basics\model\Lforwarder;
use catchAdmin\system\model\DictionaryData;

class TranshipmentOrders extends Model
{
    use BaseOptionsTrait, ScopeTrait, TranshipmentOrderSearch, DataRangScopeTrait;
    // 表名
    public $name = 'transhipment_orders';
    // 数据库字段映射
    public $field = array(
        'id',
        //供应商id
        'supply_id',
        //合同id
        'purchase_contract_id',
        // 出运单号
        'code',
        // 运输方式
        'shipment',
        // 起运港
        'shipment_port',
        // 目的港
        'destination_port',
        // 拼柜类型
        'lcl_type',
        // 柜型
        'cabinet_type',
        // 柜号
        'cabinet_no',
        // 封箱号
        'seal_no',
        // 船名航次
        'ships_name',
        // 货代公司
        'lforwarder_company',
        // 装箱日期
        'loading_date',
        // 起运日期
        'shipment_date',
        // 预计到期日期
        'arrive_date',
        // 提单号
        'bl_no',
        //费用单id
        'bill_id',
        // 备注
        'notes',
        // 审核状态，0-待审核 1-已审核 -1 审核拒绝
        'audit_status',
        //审核意见
        'audit_notes',
        //是否分仓 1-是 0-否
        'is_sub',
        //是否分仓确认 1-是 0-否
        'sub_confirm',
        //到仓状态 0  未确认 1 已确认
        'arrive_status',
        //批次号
        'batch_no',
        //建议实体仓id
        'warehouse_id',
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

    protected $append = [
        'created_by_name', 'updated_by_name', 'invoice_no', 'arrive_number', 'audit_status_text',
        'contract_code', 'entity_warehouse', 'product_info', 'lforwarder_company_name', 'destination_port_text',
        'shipment_port_text', 'lcl_type_text'
    ];

    public static function exportField()
    {
        return  [
            [
                'title' => '装柜日期',
                'filed' => 'loading_date',
            ],
            [
                'title' => '商品编码',
                'filed' => 'code',
            ],
            [
                'title' => '中文名称',
                'filed' => 'name_ch',
            ],
            [
                'title' => '箱率',
                'filed' => 'container_rate',
            ],
            [
                'title' => '转运数量',
                'filed' => 'trans_number',
            ],
            [
                'title' => '起运港',
                'filed' => 'shipment_port_text',
            ],
            [
                'title' => '目的港',
                'filed' => 'destination_port_text',
            ],
            [
                'title' => '拼柜类型',
                'filed' => 'lcl_type_text',
            ],
            [
                'title' => '柜号',
                'filed' => 'cabinet_no',
            ],
            [
                'title' => '封箱号',
                'filed' => 'seal_no',
            ],
            [
                'title' => '船名航次',
                'filed' => 'ships_name',
            ],
            [
                'title' => '货代',
                'filed' => 'lforwarder_company_name',
            ],
            [
                'title' => '起运日期',
                'filed' => 'shipment_date',
            ],
            [
                'title' => '预计到仓日期',
                'filed' => 'arrive_date',
            ],
            [
                'title' => '提单号',
                'filed' => 'bl_no',
            ],
            [
                'title' => '备注',
                'filed' => 'notes',
            ]
        ];
    }
    // 目的港 DestinationPortText
    public function getDestinationPortTextAttr()
    {
        return DictionaryData::where('id', $this->getAttr('destination_port'))->value('dict_data_name') ?? '';
    }
    // 起运港
    public function getShipmentPortTextAttr()
    {
        return DictionaryData::where('id', $this->getAttr('shipment_port'))->value('dict_data_name') ?? '';
    }
    // 柜型 LclType
    public function getLclTypeTextAttr()
    {
        return DictionaryData::where('id', $this->getAttr('lcl_type'))->value('dict_data_name') ?? '';
    }

    public function  getLforwarderCompanyNameAttr()
    {
        return Lforwarder::where('id', $this->getAttr('lforwarder_company'))->value('name') ?? '';
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class, 'supply_id');
    }


    public function contract()
    {
        return $this->belongsTo(PurchaseContracts::class, 'purchase_contract_id');
    }

    public function getCreatedByNameAttr()
    {
        return Users::where('id', $this->getAttr('created_by'))->value('username') ?? '';
    }

    public function getUpdatedByNameAttr()
    {
        return Users::where('id', $this->getAttr('updated_by'))->value('username') ?? '';
    }

    public function getInvoiceNoAttr()
    {
        return '';
    }


    public function getArriveNumberAttr()
    {
        //实际到仓数量
        if ($this->arrive_status == 1) {
            return SubOrders::where('trans_order_id', $this->getAttr('id'))->sum('number');
        }

        return 0;
    }

    public function getAuditStatusTextAttr()
    {

        switch ($this->getAttr('audit_status')) {
            case '-1':
                return '审核驳回';
                break;
            case 0:
                return '待审核';
                break;
            case 1:
                return '审核通过';
                break;
            default:
                return '待审核';
                break;
        }
    }

    public function createTransShipmentNo()
    {
        $date = date('Ymd');
        $time = strtotime($date);
        $count = TranshipmentOrders::where('created_at', '>', $time)->count();
        $str = sprintf("%04d", $count + 1);
        return "SHIPMENT" . $date . $str;
    }

    public function getProductInfoAttr()
    {

        $data = [];
        $products =  PurchaseOrderProducts::join(
            'transhipment_order_products',
            'purchase_order_products.id=transhipment_order_products.purchase_product_id'
        )
            ->field('purchase_order_products.*,transhipment_order_products.id as id, transhipment_order_products.trans_number, transhipment_order_products.purchase_contract_id')
            ->where('trans_order_id', $this->id)
            ->select();
        foreach ($products as $val) {
            $row['goods_name'] = $val->goods_name;
            $row['goods_code'] = $val->goods_code;
            $data[] = $row;
        }

        return $data;
    }

    public function products($id, $type = 1)
    {

        $products =  PurchaseOrderProducts::join(
            'transhipment_order_products',
            'purchase_order_products.id=transhipment_order_products.purchase_product_id'
        )
            ->field('purchase_order_products.*,transhipment_order_products.id as id, transhipment_order_products.trans_number, transhipment_order_products.purchase_contract_id')
            ->where('trans_order_id', $id)
            ->where('purchase_order_products.type', $type)
            ->select();

        //获取商品的分仓信息
        foreach ($products as $product) {
            $product->sub_order = SubOrders::where(['trans_order_id' => $id, 'trans_goods_id' => $product->id])->select();
            $product->contract_code = PurchaseContracts::where('id', $product->purchase_contract_id)->value('code') ??
                '';
        }

        return $products;
    }

    public function subProducts($id, $type = 1)
    {

        $products = SubOrders::alias('so')
            ->leftJoin('transhipment_order_products top', 'top.id=so.trans_goods_id')
            ->leftJoin('purchase_order_products pop', 'pop.id=top.purchase_product_id')
            ->field('so.id as sub_order_id, so.entity_warehouse_id, so.virtual_warehouse_id, so.number ,
                                        pop.id as pid,pop.goods_code, pop.category_name, pop.goods_name,
                                        pop.goods_name_en, pop.container_rate, pop.goods_pic, pop.number as purchase_number,pop.delivery_date,
                                        pop.notes,top.purchase_contract_id, pop.purchase_order_id')
            ->where('so.trans_order_id', $id)
            ->where('pop.type', $type)
            ->select();

        foreach ($products as &$product) {
            $product->purchase_order_code =  PurchaseOrders::where(
                'id',
                $product->purchase_order_id
            )->value('code')
                ?? '';
            $product->contract_code =  PurchaseContracts::where('id', $product->purchase_contract_id)
                ->value('code')
                ?? '';
        }
        return $products;
    }

    public function  amount()
    {
        $amount = 0;
        $transProducts = TranshipmentOrderProducts::where('trans_order_id', $this->id)->select();
        foreach ($transProducts as $product) {
            $pp = PurchaseOrderProducts::find($product->purchase_product_id);
            $amount +=  $pp->price * $product->trans_number;
        }

        //减去客户预付款
        return $amount *  (1 - ($this->supply->pay_ratio) / 100);
    }

    public function getEntityWarehouseAttr()
    {
        $warehouse =  Warehouses::where('id', $this->warehouse_id)->find();
        if (!$warehouse) {
            return '';
        }

        return $warehouse->getAttr('name') ?? '';
    }
}
