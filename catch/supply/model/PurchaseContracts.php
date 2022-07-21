<?php

namespace catchAdmin\supply\model;

use catchAdmin\basics\model\Company;
use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\permissions\model\Users;
use catchAdmin\product\model\Parts;
use catchAdmin\product\model\Product;
use catchAdmin\product\model\ProductGroup;
use catchAdmin\product\model\ProductInfo;
use catchAdmin\supply\controller\PurchaseContract;
use catchAdmin\supply\model\search\PurchaseContractSearch;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;

class PurchaseContracts extends Model
{
    use BaseOptionsTrait, ScopeTrait, PurchaseContractSearch, DataRangScopeTrait;

    // 表名
    public $name = 'purchase_contracts';
    // 数据库字段映射
    public $field
    = array(
        'id',
        //采购单id
        'purchase_order_id',
        //采购单编号
        'purchase_order_code',
        // 供应商id
        'supply_id',
        // 供应商名称
        'supply_name',
        //客户id
        'company_id',
        //需方名称
        'company_name',
        //需方联系人
        'company_contacts',
        //需方电话
        'company_mobile',
        //需方地址
        'company_address',
        // 合同编号
        'code',
        // 批次好
        'batch_no',
        // 采购员
        'buyer',
        // 采购金额
        'amount',
        // 审核状态，0-未审核 1-已审核 -1 审核驳回
        'audit_status',
        //审核意见
        'audit_notes',
        // 转出运状态 0-待出运 1-部分出运 -1 已出运
        'transshipment',
        //合同内容
        'content',
        //合同附件
        'attachment',
        // 创建人
        'created_by',
        // 创建时间
        'created_at',
        // 修改时间
        'updated_at',
        // 删除时间
        'deleted_at',
    );

    protected $json = ['attachment'];
    protected $append
    = [
        'buyer', 'created_by_name', 'updated_by_name', 'audit_status_text', 'transshipment_text'
    ];


    public function exportField()
    {
        return [
            [
                'title' => 'ID',
                'filed' => 'id',
            ],
            [
                'title' => '合同编码',
                'filed' => 'code',
            ],
            [
                'title' => '所属供应商',
                'filed' => 'supply_name',
            ],
            [
                'title' => '采购总金额',
                'filed' => 'amount',
            ],
            [
                'title' => '审核状态',
                'filed' => 'audit_status_text',
            ],
            [
                'title' => '转出运状态',
                'filed' => 'transshipment_text',
            ],
            [
                'title' => '创建人',
                'filed' => 'created_by_name',
            ],
            [
                'title' => '创建时间',
                'filed' => 'created_at',
            ],
        ];
    }

    public function products($id, $type = 1)
    {
        $productIds = PurchaseContractProducts::where('purchase_contract_id', $id)
            ->column('purchase_product_id');
        $query =  PurchaseOrderProducts::whereIn('id', $productIds);
        if ($type) {
            $query->where('type', $type);
        }

        $products = $query->select();

        foreach ($products  as $product) {

            $product->package = [];
            $product->upc_code = '';
            if ($product->getAttr('type') == 1) {
                $p = Product::find($product->goods_id);
                // $product->package = ProductGroup::where('product_id', $p->id ?? 0)->select();
                $product->package = ProductInfo::where('product_id', $p->id ?? 0)->select();
                $product->upc_code = $p->bar_code_upc ?? '';
            }

            if ($product->getAttr('type') == 2) {
                $p = Parts::find($product->goods_id);
                $product->upc_code = $p->code ?? '';
            }
            $product->goods_pic = $p->image_url;
        }

        return $products;
    }

    public function supply()
    {
        return $this->belongsTo(Supply::class, 'supply_id', 'id');
    }

    public function createContractNo($companyCode, $supplyCode)
    {
        //合同编码：客户代码(从客户管理中调转资料)+供应商代码（从供应商管理中调转资料）+年月日+流水（3位）
        $date = date('Ymd');
        $time = strtotime($date);
        $count = PurchaseContracts::where('created_at', '>', $time)->count();

        $str = sprintf("%03d", $count + 1);
        return $companyCode . $supplyCode . $date . $str;
    }


    public function getBuyerAttr()
    {
        $product
            = PurchaseContractProducts::join('purchase_order_products', 'purchase_order_products.id = purchase_contract_products.purchase_product_id')
            ->where('purchase_contract_id', $this->getAttr('id'))
            ->find();
        if ($product) {
            return $product->getAttr('buyer') ?? '';
        }
        return  '';
    }

    public function getCreatedByNameAttr()
    {
        return Users::where('id', $this->getAttr('created_by'))->value('username') ?? '';
    }

    public function getUpdatedByNameAttr()
    {
        return Users::where('id', $this->getAttr('updated_by'))->value('username') ?? '';
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

    public function getTransshipmentTextAttr()
    {
        switch ($this->getAttr('transshipment')) {
            case 0:
                return '待出运';
                break;
            case 1:
                return '部分出运';
                break;
            case 2:
                return '已出运';
                break;
            default:
                return '待出运';
                break;
        }
    }

    public function getCompanyAddressAttr($value)
    {
        if (!$value) {
            return $this->company->address ?? '';
        }

        return $value;
    }

    public function  prepayAmount()
    {
        return $this->getAttr('amount') * ($this->supply->pay_ratio / 100);
    }

    public function order()
    {
        return $this->belongsTo(PurchaseOrders::class, 'purchase_order_id', 'id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }
}
