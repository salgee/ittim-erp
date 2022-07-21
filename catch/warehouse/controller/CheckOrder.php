<?php


namespace catchAdmin\warehouse\controller;


use catchAdmin\product\model\Product;
use catchAdmin\supply\excel\CommonExport;
use catchAdmin\warehouse\model\AllotOrders;
use catchAdmin\warehouse\model\CheckOrderProducts;
use catchAdmin\warehouse\model\CheckOrders;
use catchAdmin\warehouse\model\CheckOrderWarehouseProducts;
use catchAdmin\warehouse\model\OutboundOrderProducts;
use catchAdmin\warehouse\model\OutboundOrders;
use catchAdmin\warehouse\model\WarehouseOrders;
use catchAdmin\warehouse\model\Warehouses;
use catchAdmin\warehouse\model\WarehouseStock;
use catcher\base\CatchController;
use catcher\base\CatchRequest;
use catcher\CatchResponse;
use catcher\Code;
use think\facade\Db;

class CheckOrder extends CatchController {
    protected $checkOrdersModel;
    protected $checkOrderProductsModel;
    protected $checkOrderWarehouseProductsModel;
    protected $outboundOrdersModel;
    protected $allotOrdersModel;
    protected $warehouseOrdersModel;
    protected $warehouseStockModel;

    public function __construct (CheckOrders $checkOrders, CheckOrderProducts $checkOrderProducts,
        CheckOrderWarehouseProducts $checkOrderWarehouseProducts,
        OutboundOrders $outboundOrders, AllotOrders $allotOrders,
        WarehouseOrders $warehouseOrders, WarehouseStock $warehouseStock) {
        $this->checkOrdersModel                 = $checkOrders;
        $this->checkOrderProductsModel          = $checkOrderProducts;
        $this->checkOrderWarehouseProductsModel = $checkOrderWarehouseProducts;
        $this->outboundOrdersModel              = $outboundOrders;
        $this->allotOrdersModel                 = $allotOrders;
        $this->warehouseOrdersModel             = $warehouseOrders;
        $this->warehouseStockModel              = $warehouseStock;
    }

    /**
     * 列表
     * @return \think\response\Json
     */
    public function index () {
        $res = $this->checkOrdersModel->getList();
        return CatchResponse::paginate($res);
    }

    /**
     * 导出
     * @return \think\response\Json
     */
    public function export(CatchRequest $request) {

        $data = $request->post();

        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->checkOrdersModel->exportField();
        }

        $res = $this->checkOrdersModel->catchSearch()->select()->toArray();

        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '盘点单');
        return  CatchResponse::success($url);
    }

    /**
     * 保存信息
     * @time 2021年01月23日 14:55
     *
     * @param CatchRequest $request
     */
    public function save (CatchRequest $request): \think\Response {

        try {
            $data               = $request->param();
            $data['created_by'] = $data['creator_id'];
            $data['code']       = $this->checkOrdersModel->createOrderNo();
            $this->checkOrdersModel->storeBy($data);
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 更新
     *
     * @param CatchRequest $request
     * @param [type] $id
     * @return void
     */
    public function update (CatchRequest $request, $id) {

        $data  = $request->param();
        $order = $this->checkOrdersModel->findBy($id);
        if ($order->status == 1) {
            return CatchResponse::fail('已盘点，不能修改', Code::FAILED);
        }

        $data['updated_by'] = $data['creator_id'];
        $this->checkOrdersModel->updateBy($id, $data);
        DB::table('check_order_products')->where('check_order_id', $id)->delete();
        DB::table('check_order_warehouse_products')
          ->where('check_order_id', $id)
          ->delete();
        return CatchResponse::success(true);

    }

   /**
    * 详情
    *
    * @param $id
    * @return \think\Response
    */
    public function read ($id) {
        $order = $this->checkOrdersModel->find($id);
        return CatchResponse::success($order);
    }

    /**
     * 更新盘点单盘点库存
     *
     * @param CatchRequest $request
     * @return \think\Response
     */
    public function updateOrderStock (CatchRequest $request): \think\Response {
        try {
            $data = $request->param();

            //$this->checkOrdersModel->startTrans();

            $order = $this->checkOrdersModel->findBy($data['id']);

            if ($order->status == 1) {
                return CatchResponse::fail('已盘点，不能修改', Code::FAILED);
            }

            if (!isset($data['products'])) {
                return CatchResponse::fail('请选择商品', Code::FAILED);
            }

            $data['updated_by'] = $data['creator_id'];
            $this->checkOrdersModel->updateBy($data['id'], $data);

            //组装商品数据
            $products = [];
            foreach ($data['products'] as $val) {
                $stockDifference = DB::table('check_order_warehouse_products')
                ->where('check_order_id', $data['id'])
                ->where('goods_id', $val['goods_id'])
                ->sum('stock_difference');

                if ($stockDifference != $val['stock_difference']) {
                    return CatchResponse::fail('盘点结果与盘点库存不一致，请检查盘点库存', Code::FAILED);
                }
                $row        = [
                    'check_order_id' => $data['id'],
                    'goods_id' => $val['goods_id'],
                    'check_stock' => $val['check_stock'],
                    'stock_difference' => $val['stock_difference'],
                    'stock' => $val['stock'] ?? 0
                ];
                $products[] = $row;
            }
            DB::table('check_order_products')->where('check_order_id', $data['id'])->delete();
            $this->checkOrderProductsModel->saveAll($products);


            //根据盘库结果生成出库入库单

            $res = CheckOrderWarehouseProducts::where('check_order_id', $data['id'])->select();

            if (!empty($res)) {
                foreach ($res as $val) {

                    //创建出库单
                    $product = $val->product;

                    $tempProduct = [
                        [
                            'goods_id' => $product->id,
                            'goods_name' => $product->name_ch,
                            'goods_name_en' => $product->name_en,
                            'goods_code' => $product->code,
                            'category_name' => $product->category_name,
                            'goods_pic' => $product->image_url,
                            'number' => abs($val->stock_difference),
                            'type' => 1,
                        ]
                    ];

                    if ($val->stock_difference < 0) {

                        //获取批次信息
                        $products = $this->allotOrdersModel->getOutboundOrderProducts
                        ($order->entity_warehouse_id, $val->virtual_warehouse_id, $tempProduct);

                        $orderData = [
                            'entity_warehouse_id' => $order->entity_warehouse_id,
                            'virtual_warehouse_id' => $val->virtual_warehouse_id,
                            'source' => 'check',
                            'audit_status' => 2,   //盘点出库单默认已审核
                            'outbound_status' => 1, //盘点出库单默认已出库
                            'outbound_time' => date('Y-m-d H:i:s'),
                            'created_by' => $data['creator_id'],
                            'notes' => $order->notes,
                            'products' => $products
                        ];

                        $this->outboundOrdersModel->createOutOrder($orderData);

                        //变更库存
                        foreach ($products as $product) {
                            $this->warehouseStockModel->reduceStock($order->entity_warehouse_id,
                                                                    $val->virtual_warehouse_id,
                                                                    $product['goods_code'],
                                                                    $product['batch_no'],
                                                                    $product['number'],
                                                                    $product['type'],
                                                                    'CheckOrder',
                                                                    $order->id,


                            );
                        }
                    }

                    if ($val->stock_difference > 0) {
                        //创建入库单
                        $orderData = [
                            'entity_warehouse_id' => $order->entity_warehouse_id,
                            'virtual_warehouse_id' => $val->virtual_warehouse_id,
                            'source' => 'check',
                            'audit_status' => 2,
                            'created_by' => $data['creator_id'],
                            'notes' => $order->notes,
                            'products' => $tempProduct
                        ];
                        $this->warehouseOrdersModel->createWarehouseOrder($orderData);
                    }
                }
            }
            $order->status = 1;
            $order->save();
            $this->checkOrdersModel->commit();
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            // $this->checkOrdersModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 根据盘点单获取商品信息
     *
     * @param CatchRequest $request
     * @param  $id
     *
     * @return \think\response\Json
     */
    public function warehouseGoods (CatchRequest $request, $id) {
        $checkOrder = $this->checkOrdersModel->findBy($id);
        if (!$checkOrder) {
            return CatchResponse::fail('盘点单不存在', Code::FAILED);
        }

        //获取盘点单实体仓下虚拟仓所有的商品
        $res = $this->checkOrdersModel->products($checkOrder->id, $checkOrder->entity_warehouse_id);
        return CatchResponse::success($res);
    }


    /**
     * 根据盘点单获取配件信息
     *
     * @param CatchRequest $request
     * @param              $id
     *
     * @return \think\response\Json
     */
    public function warehouseParts (CatchRequest $request, $id) {
        $checkOrder = $this->checkOrdersModel->findBy($id);
        if (!$checkOrder) {
            return CatchResponse::fail('盘点单不存在', Code::FAILED);
        }

        //获取盘点单实体仓下虚拟仓所有的商品
        $res = $this->checkOrdersModel->parts($checkOrder->id, $checkOrder->entity_warehouse_id);
        return CatchResponse::success($res);
    }

    /**
     * 获取盘点商品信息详情
     *
     * @param CatchRequest $request
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function warehouseGoodsStock (CatchRequest $request) {
        $data       = $request->param();
        $checkOrder = $this->checkOrdersModel->findBy($data['id']);
        if (!$checkOrder) {
            return CatchResponse::fail('盘点单不存在', Code::FAILED);
        }

        $product
            = Product::field('id, image_url, code, name_ch,name_en, packing_method, category_id')
                     ->find($data['goods_id'] ?? 0);

        if (!$product) {
            return CatchResponse::fail('商品不存在', Code::FAILED);
        }


        $virtual_warehouse_id = Warehouses::where('parent_id', $checkOrder->entity_warehouse_id)
                                          ->column('id');
        //查询当前商品所有虚拟仓库存
        $warehosueStock = WarehouseStock::alias('ws')
                                        ->leftJoin('warehouses w', 'w.id = ws.virtual_warehouse_id')
                                        ->where('ws.goods_code', $product->code)
                                        ->whereIn('ws.virtual_warehouse_id', $virtual_warehouse_id)
                                        ->field('w.id, w.name, sum(ws.number) as number')
                                        ->group('virtual_warehouse_id')
                                        ->select()
                                        ->toArray();
        //查询已经存入的盘点数据


        foreach ($warehosueStock as $key => &$val) {
            $cwp = $this->checkOrderWarehouseProductsModel->where('check_order_id', $data['id'])
                                                          ->where('goods_id', $data['goods_id'])
                                                          ->where('virtual_warehouse_id', $val['id'])
                                                          ->find();
            if (!$cwp) {
                $val['stock_difference'] = 0;
                $val['check_result']     = '';
                $val['notes']            = '';
                continue;
            }

            $val['stock_difference'] = $cwp->stock_difference;

            $val['check_result'] = '平';
            if ($cwp->stock_difference > 0) {
                $val['check_result'] = '盘赢';
            }

            if ($cwp->stock_difference < 0) {
                $val['check_result'] = '盘亏';
            }
            $val['notes'] = $cwp->notes ?? '';
        }
        $data = [
            'product' => $product,
            'warehouse' => $warehosueStock
        ];

        return CatchResponse::success($data);
    }

    /**
     * 录入盘点结果
     *
     * @param CatchRequest $request
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function updateWarehouseGoodsStock (CatchRequest $request) {
        $data       = $request->param();
        $checkOrder = $this->checkOrdersModel->findBy($data['id']);
        if (!$checkOrder) {
            return CatchResponse::fail('盘点单不存在', Code::FAILED);
        }

        if (!isset($data['stock'])) {
            return CatchResponse::fail('盘点单不存在', Code::FAILED);
        }

        DB::table('check_order_warehouse_products')
          ->where('check_order_id', $data['id'])
          ->where('goods_id', $data['goods_id'])
          ->delete();

        $list = [];
        foreach ($data['stock'] as $val) {
            $list[] = [
                'check_order_id' => $data['id'],
                'goods_id' => $data['goods_id'],
                'virtual_warehouse_id' => $val['virtual_warehouse_id'],
                'stock_difference' => $val['stock_difference'],
                'notes' => $val['notes'],
            ];
        }

        $this->checkOrderWarehouseProductsModel->saveAll($list);
        return CatchResponse::success(true);
    }


    /**
     * 删除
     * @time 2021年01月23日 14:55
     *
     * @param $id
     */
    public function delete (CatchRequest $request): \think\Response {
        $data = $request->param();
        $ids  = $data['ids'];

        foreach ($ids as $id) {
            $order = $this->checkOrdersModel->findBy($id);
            if ($order && $order->status == 1) {
                return CatchResponse::fail($order->getAttr('name') . ' 已盘点，不能删除', Code::FAILED);
            }
            $this->checkOrdersModel->deleteBy($id);
        }

        return CatchResponse::success(true);
    }
}