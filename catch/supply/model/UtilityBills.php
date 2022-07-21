<?php

namespace catchAdmin\supply\model;

use catchAdmin\permissions\model\Users;
use catchAdmin\supply\model\search\UtilitybillSearch;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;
use catchAdmin\system\model\DictionaryData;
use catchAdmin\basics\model\Lforwarder;

class UtilityBills extends Model
{
    use BaseOptionsTrait, ScopeTrait, UtilitybillSearch;
    // 表名
    public $name = 'utility_bills';
    // 数据库字段映射
    public $field = array(
        'id',
        // 提单号
        'bl_no',
        // 付款抬头
        'pay_title',
        // 柜号
        'cabinet_no',
        // 起运日期
        'shipment_date',
        // 国内陆运费用
        'domestic_trans',
        // 海运费
        'ocean_shipping',
        // 国外路运费用
        'overseas_trans',
        // 目的港
        'destination_port',
        // 装柜日期
        'loading_date',
        // 海运货代
        'ocean_lforwarder_id',
        // 国内段（货代)
        'domestic_lforwarder_id',
        // 国外段（货代)
        'overseas_lforwarder_id',
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

    protected $append
        = [
            'created_by_name', 'updated_by_name', 'destination_port_text', 'domestic_lforwarder', 'ocean_lforwarder', 'overseas_lforwarder',
            'domestic_trans_amount', 'ocean_shipping_amount', 'overseas_trans_amount', 'tax_fee', 'amount_usd', 'amount_rmb',
            'fee_ocean'
        ];

    // 国外段（货代）  OverseasLforwarder
    public function getOverseasLforwarderAttr()
    {
        return Lforwarder::where('id', $this->getAttr('overseas_lforwarder_id'))->value('name') ?? '';
    }
    // 国内段（货代） 
    public function getDomesticLforwarderAttr()
    {
        return Lforwarder::where('id', $this->getAttr('domestic_lforwarder_id'))->value('name') ?? '';
    }
    // 海运段（货代 Ocean_lforwarder_id
    public function getOceanLforwarderAttr()
    {
        return Lforwarder::where('id', $this->getAttr('ocean_lforwarder_id'))->value('name') ?? '';
    }
    // 目的港 DestinationPortText
    public function getDestinationPortTextAttr()
    {
        return DictionaryData::where('id', $this->getAttr('destination_port'))->value('dict_data_name') ?? '';
    }

    public function getCreatedByNameAttr () {
        return Users::where('id', $this->getAttr('created_by'))->value('username') ?? '';
    }

    public function getUpdatedByNameAttr () {
        return Users::where('id', $this->getAttr('updated_by'))->value('username') ?? '';
    }
    // 国内陆运费总和
    public function getDomesticTransAmountAttr(){
        $res = json_decode($this->getAttr('domestic_trans'), true);
        return $res['traile_fee'] + $res['detour_fee'] + $res['advance_fee'] + $res[
            'declare_fee'];
    }
    //  国外陆运费
    public function getOverseasTransAmountAttr(){
        $res = json_decode($this->getAttr('overseas_trans'), true);
        return $res['other_fee'] ;
    }

    public function getOceanShippingAmountAttr(){
        $res = json_decode($this->getAttr('ocean_shipping'), true);
        return $res['amount'] ?? 0;
    }
    // 海运费 fee
    public function getFeeOceanAttr() {
        $res = json_decode($this->getAttr('ocean_shipping'), true);
        return $res['fee'] ?? 0;
    }

    // 税金 tax_fee  
    public function getTaxFeeAttr() {
        $res = json_decode($this->getAttr('ocean_shipping'), true);
        return $res['tax_fee'] ?? 0;
    }
    // 总美元费用
    public function getAmountUsdAttr() {
        $res = json_decode($this->getAttr('ocean_shipping'), true);
        return $res['amount_usd'] ?? 0;
    }
    // 人民币费用
    public function getAmountRmbAttr() {
        $res = json_decode($this->getAttr('ocean_shipping'), true);
        return $res['amount_rmb'] ?? 0;
    }

    /**
     * 海运单
     * @param $id
     *
     * @return array
     */
    public function oceanShippingBill($id) {
        $res = $this->findBy($id);
        return $this->getBill($res, 'ocean_shipping');
    }

    /**
     * 国内陆运单
     * @param $id
     *
     * @return array
     */
    public function domesticTransBill($id) {
        $res = $this->findBy($id);

        return $this->getBill($res, 'domestic_trans');
    }

    public function totalBill($id) {
        $res = $this->findBy($id);
        $list= [];

        $domesticTransBill = json_decode($res->domestic_trans, true);
        $oceanShippingBill = json_decode($res->ocean_shipping, true);
        $overSeaShippingBill = json_decode($res->overseas_trans, true);

        //根据提单号获取商品
        $transOrders = TranshipmentOrders::where('bl_no', $res->bl_no)->select();
        foreach ($transOrders AS $order) {
            //获取商品二级分类
            $cates = PurchaseOrderProducts::alias('pop')->leftJoin('transhipment_order_products top', 'top.purchase_product_id = pop.id')
            ->where('top.trans_order_id', $order->id)
            ->column('pop.category_name');
            $row = [
                'bl_no' => $res->bl_no,
                'cabinet_no' => $order->cabinet_no,
                'shipment_date' => $res->shipment_date,
                'loading_date' => $res->loading_date,
                'domestic_trans' => $domesticTransBill,
                'ocean_shipping' => $oceanShippingBill,
                'overseas_trans' => $overSeaShippingBill,
                'cates' =>  $cates
            ];
            $list[] = $row;
        }
        return $list;
    }

    /**
     * 获取指定费用详情
     * @param $bill
     * @param $type
     *
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    function getBill($bill, $type) {
        if (!$bill) {
            return [];
        }

        $data = [
            'bl_no' => $bill->bl_no,
            'shipment_date' => $bill->shipment_date,

        ];
        $data = array_merge($data, json_decode($bill->$type, true));
        //根据提单号获取商品
        $transOrders = TranshipmentOrders::where('bl_no', $bill->bl_no)->select();
        $list = [];
        foreach ($transOrders AS $order) {
            $data['loading_date'] =  $order->loading_date;
            $products = $order->products($order->id);
            foreach ($products AS $product) {
                //获取cate
                $row['category_name'] = $product['category_name'];
                $category = $product->product->category ?? '';
                if ($category) {
                    $row['category_name'] = $category->parent_name . "-" . $category->getAttr('name');
                }
                $row['name'] = $product['goods_name'];
                $row['cabinet_no'] =  $order->cabinet_no;
                $row['warehouse'] = $this->getWarehouse($order->id, $product['id']);
                $row =array_merge($row, $data);
                $list[] = $row;
            }
        }

        return $list;
    }

    public function getWarehouse($orderId, $goodsId) {
        $subOrder = SubOrders::where(['trans_order_id'=> $orderId, 'trans_goods_id' => $goodsId])->find();
        if (!$subOrder) {
            return '';
        }
        return $subOrder->entity_warehouse;
    }
}