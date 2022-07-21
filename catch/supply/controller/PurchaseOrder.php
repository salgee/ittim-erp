<?php


namespace catchAdmin\supply\controller;


// use catchAdmin\permissions\model\Users;
// use catchAdmin\supply\excel\CommonExport;
use catchAdmin\supply\excel\PurchaseOrderExport;
use catchAdmin\supply\model\PurchaseOrderProducts;
use catchAdmin\supply\model\PurchaseOrders;
use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catcher\Code;

use think\facade\Db;

class PurchaseOrder extends CatchController
{
    protected $purchaseOrdersModel;
    protected $purchaseOrderProductsModel;

    public function __construct(
        PurchaseOrders $purchaseOrders,
        PurchaseOrderProducts $purchaseOrderProducts
    ) {
        $this->purchaseOrdersModel        = $purchaseOrders;
        $this->purchaseOrderProductsModel = $purchaseOrderProducts;
    }

    /**
     * 列表
     * @return \think\response\Json
     */
    public function index(Request $request)
    {
        return CatchResponse::paginate($this->purchaseOrdersModel->getList('list'));
    }

    /**
     * 导出
     *
     * @param Request $request
     * @param CatchAuth $auth
     * @return void
     */
    public function export(Request $request)
    {

        $data = $request->param();

        ini_set('memory_limit', '1024M');

        $res = $this->purchaseOrdersModel->getList('export');

        $data = $request->post();
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->purchaseOrdersModel->exportField();
        }

        $excel = new PurchaseOrderExport();
        $url = $excel->export($res, $exportField, '采购申请单');
        return  CatchResponse::success($url);
    }

    /**
     * 保存信息
     * @time 2021年01月23日 14:55
     *
     * @param Request $request
     */
    public function save(Request $request): \think\Response
    {
        try {

            $this->purchaseOrdersModel->startTrans();

            $data               = $request->post();
            $data['code']       = $this->purchaseOrdersModel->createOrderNo();
            $data['created_by'] = $data['creator_id'];
            $res                = $this->purchaseOrdersModel->storeBy($data);

            if (isset($data['products'])) {
                //组装商品信息
                $products = [];
                foreach ($data['products'] as $val) {
                    $row        = [
                        'supply_id' => $val['supply_id'],
                        'purchase_order_id' => $res,
                        'goods_id' => $val['goods_id'],
                        'goods_code' => $val['goods_code'] ?? '',
                        'category_name' => $val['category_name'],
                        'goods_name' => $val['goods_name'],
                        'upc' => $val['upc'] ?? '',
                        'goods_name_en' => $val['goods_name_en'],
                        'container_rate' => $val['container_rate'],
                        'goods_pic' => $val['goods_pic'],
                        'buyer' => $val['buyer'],
                        'number' => $val['number'],
                        'price' => $val['price'],
                        'amount' => $val['price'] * $val['number'],
                        'delivery_date' => $val['delivery_date'],
                        'notes' => $val['notes'],
                        'type' => $val['type'],
                    ];
                    $products[] = $row;
                }
                $this->purchaseOrderProductsModel->saveAll($products);
                $this->purchaseOrdersModel->fixProduct($res);
            }

            //添加配件
            if (isset($data['parts'])) {
                $products = [];
                foreach ($data['parts'] as $val) {
                    $row        = [
                        'supply_id' => $val['supply_id'],
                        'purchase_order_id' => $res,
                        'goods_id' => $val['goods_id'],
                        'goods_code' => $val['goods_code'] ?? '',
                        'category_name' => $val['category_name'],
                        'goods_name' => $val['goods_name'],
                        'goods_name_en' => $val['goods_name_en'] ?? '',
                        'container_rate' => $val['container_rate'],
                        'goods_pic' => $val['goods_pic'],
                        'buyer' => $val['buyer'],
                        'number' => $val['number'],
                        'price' => $val['price'],
                        'amount' => $val['price'] * $val['number'],
                        'delivery_date' => $val['delivery_date'],
                        'notes' => $val['notes'],
                        'type' => $val['type'],
                    ];
                    $products[] = $row;
                }
                $this->purchaseOrderProductsModel->saveAll($products);
                $this->purchaseOrdersModel->fixProduct($res);
            }


            $this->purchaseOrdersModel->commit();
            return CatchResponse::success(['id' => $res]);
        } catch (\Exception $exception) {
            $this->purchaseOrdersModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }


    /**
     * 更新
     * @time 2021年01月23日 14:55
     *
     * @param Request $request
     * @param         $id
     */
    public function update(Request $request, $id): \think\Response
    {
        try {
            $this->purchaseOrdersModel->startTrans();

            $data = $request->post();

            $data['updated_by'] = $data['creator_id'];

            $res = $this->purchaseOrdersModel->updateBy($id, $data);

            Db::table('purchase_order_products')->where('purchase_order_id', $id)->delete();


            if (isset($data['products'])) {
                //组装商品信息
                $products = [];
                foreach ($data['products'] as $val) {
                    $row        = [
                        'supply_id' => $val['supply_id'],
                        'purchase_order_id' => $id,
                        'goods_id' => $val['goods_id'],
                        'goods_code' => $val['goods_code'],
                        'category_name' => $val['category_name'],
                        'goods_name' => $val['goods_name'],
                        'goods_name_en' => $val['goods_name_en'],
                        'container_rate' => $val['container_rate'],
                        'goods_pic' => $val['goods_pic'],
                        'buyer' => $val['buyer'],
                        'number' => $val['number'],
                        'price' => $val['price'],
                        'upc' => $val['upc'] ?? '',
                        'amount' => $val['price'] * $val['number'],
                        'delivery_date' => $val['delivery_date'],
                        'notes' => $val['notes'],
                        'type' => $val['type'],

                    ];
                    $products[] = $row;
                }
                $this->purchaseOrderProductsModel->saveAll($products);
                $this->purchaseOrdersModel->fixProduct($id);
            }

            //添加配件
            if (isset($data['parts'])) {
                //组装商品信息
                $products = [];
                foreach ($data['parts'] as $val) {
                    $row        = [
                        'supply_id' => $val['supply_id'],
                        'purchase_order_id' => $id,
                        'goods_id' => $val['goods_id'],
                        'goods_code' => $val['goods_code'] ?? '',
                        'category_name' => $val['category_name'],
                        'goods_name' => $val['goods_name'],
                        'goods_name_en' => $val['goods_name_en'] ?? '',
                        'container_rate' => $val['container_rate'],
                        'goods_pic' => $val['goods_pic'],
                        'buyer' => $val['buyer'],
                        'number' => $val['number'],
                        'price' => $val['price'],
                        'amount' => $val['price'] * $val['number'],
                        'delivery_date' => $val['delivery_date'],
                        'notes' => $val['notes'],
                        'type' => $val['type'],
                    ];
                    $products[] = $row;
                }
                $this->purchaseOrderProductsModel->saveAll($products);
                $this->purchaseOrdersModel->fixProduct($id);
            }


            $this->purchaseOrdersModel->commit();
            return CatchResponse::success($res);
        } catch (\Exception $exception) {
            $this->purchaseOrdersModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }


    /**
     * 详情
     *
     * @param $id
     *
     * @return \think\response\Json
     */
    public function read($id)
    {
        $order           = $this->purchaseOrdersModel->find($id);
        $order['products'] = $this->purchaseOrdersModel->products($id);
        $order['parts']    = $this->purchaseOrdersModel->parts($id);
        return CatchResponse::success($order);
    }

    /**
     * 删除
     * @time 2021年01月23日 14:55
     *
     * @param $id
     */
    public function delete($id): \think\Response
    {
        //检测是否可以删除，已审核的采购单不可以审核
        $order = $this->purchaseOrdersModel->findBy($id);
        if (!$order) {
            return CatchResponse::fail('订单不存在', Code::FAILED);
        }

        if ($order->audit_status > 0) {
            return CatchResponse::fail('订单已审核，不能删除', Code::FAILED);
        }

        return CatchResponse::success($this->purchaseOrdersModel->deleteBy($id));
    }


    /**
     * 批量删除
     *
     * @param Request $request
     * @return void
     */
    public function batchDelete(Request $request)
    {
        $data = $request->param();
        $ids  = $data['ids'];

        foreach ($ids as $id) {
            $order = $this->purchaseOrdersModel->findBy($id);
            if (!$order) {
                return CatchResponse::fail('订单不存在', Code::FAILED);
            }

            if ($order->audit_status > 0) {
                return CatchResponse::fail('订单' . $order->code . '已审核，不能删除', Code::FAILED);
            }
        }
        return CatchResponse::success($this->purchaseOrdersModel->deleteBy($ids));
    }

    /**
     * 修改审核状态
     *
     * @param Request $request
     * @param CatchAtuh $auth
     * @return \think\response\Json
     */
    public function changeAuditStatus(Request $request)
    {
        try {

            $data = $request->post();
            $this->purchaseOrdersModel->startTrans();

            $order = $this->purchaseOrdersModel->findBy($data['id']);
            if (!$order) {
                return CatchResponse::fail('采购单不存在', Code::FAILED);
            }

            if ($order->audit_status == 0) {
                return CatchResponse::fail('采购单未提交审核，请先提交审核', Code::FAILED);
            }

            $this->purchaseOrdersModel->updateBy($data['id'], $data);

            if (isset($data['products'])) {
                //修改采购单商品信息
                foreach ($data['products'] as $val) {
                    $product = $this->purchaseOrderProductsModel->findBy($val['id']);
                    $row     = [
                        'price' => $val['price'],
                        'number' => $val['number'],
                        // 'amount' => $product->getAttr('price') * $val['number'],
                        'amount' => $val['price'] * $val['number'],
                        'arrive_date' => $val['arrive_date'],
                    ];
                    $this->purchaseOrderProductsModel->updateBy($val['id'], $row);
                }
            }
            $this->purchaseOrdersModel->fixProduct($data['id']);
            $this->purchaseOrdersModel->commit();
        } catch (\Exception $exception) {
            $this->purchaseOrdersModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }

    /**
     * 运营修改审核状态
     *
     * @param Request $request
     * @param CatchAtuh $auth
     * @return \think\response\Json
     */
    public function operateChangeAuditStatus(Request $request)
    {
        try {

            $data = $request->post();
            $this->purchaseOrdersModel->startTrans();

            $order = $this->purchaseOrdersModel->findBy($data['id']);
            if (!$order) {
                return CatchResponse::fail('采购单不存在', Code::FAILED);
            }

            if ($order->audit_status == 0) {
                return CatchResponse::fail('采购单未提交审核，请先提交审核', Code::FAILED);
            }


            if ($order->audit_status < 2) {
                return CatchResponse::fail('采购单采购员未审核，请先采购员审核', Code::FAILED);
            }

            $this->purchaseOrdersModel->updateBy($data['id'], $data);

            if (isset($data['products'])) {
                //修改采购单商品信息
                foreach ($data['products'] as $val) {
                    $product = $this->purchaseOrderProductsModel->findBy($val['id']);
                    $row     = [
                        'number' => $val['number'],
                        'amount' => $product->getAttr('price') * $val['number'],
                        'arrive_date' => $val['arrive_date'],
                    ];
                    $this->purchaseOrderProductsModel->updateBy($val['id'], $row);
                }
            }
            $this->purchaseOrdersModel->fixProduct($data['id']);
            $this->purchaseOrdersModel->commit();
        } catch (\Exception $exception) {
            $this->purchaseOrdersModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }

    /**
     * 提交审核
     *
     * @param Request $request
     *
     * @return \think\response\Json
     */
    public function SubmitAudit(Request $request)
    {
        $data = $request->param();
        $ids  = $data['ids'];

        foreach ($ids as $id) {
            $this->purchaseOrdersModel->updateBy($id, ['audit_status' => 1]);
        }
        return CatchResponse::success(true);
    }

    /**
     * 修正入库单生成 json 数据
     */
    public function purchaseJsonFix(Request $request)
    {
        $id = $request->param('id') ?? '';
        $this->purchaseOrdersModel->fixProduct($id);
        return CatchResponse::success(true);
    }
}
