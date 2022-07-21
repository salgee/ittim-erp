<?php
/*
 * @Date: 2021-04-05 20:53:34
 * @LastEditTime: 2021-11-17 17:14:25
 */

namespace catchAdmin\order\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\order\model\OrdersTemp as ordersTempModel;
use catchAdmin\basics\model\Shop;
use catchAdmin\order\model\OrderRecords;
use catcher\platform\AmazonSpService;
use catcher\platform\EbayService;
use catcher\platform\OpenCartService;
use catcher\platform\OverstockService;
use catcher\platform\ShopifyService;
use catcher\platform\WalmartService;
use catcher\platform\WayfairService;
use catcher\platform\HouzzService;
use catcher\Code;

class OrdersTemp extends CatchController
{
    protected $ordersTempModel;

    public function __construct(OrdersTempModel $ordersTempModel)
    {
        $this->ordersTempModel = $ordersTempModel;
    }

    /**
     * 列表
     * @time 2021年02月23日 14:44
     * @param Request $request 
     */
    public function index(Request $request): \think\Response
    {
        $query = $this->ordersTempModel::withoutField('order_info');
        $params = $request->param();
        // 平台名称
        if (isset($params['platform_name']) && $params['platform_name']) {
            $query->whereLike('platform_name', $params['platform_name']);
        }
        // 店铺  
        if (isset($params['shop_id']) && $params['shop_id']) {
            $query->where('shop_id', $params['shop_id']);
        }
        // 是否同步  is_sync_order
        if (isset($params['is_sync_order']) && $params['is_sync_order'] !== '') {
            $query->where('is_sync_order', $params['is_sync_order']);
        }
        // 订单编号 
        if (isset($params['order_no']) && $params['order_no']) {
            $query->where('order_no', $params['order_no']);
        }
        // 订单编号2
        if (isset($params['order_no2']) && $params['order_no2']) {
            $query->where('order_no2', $params['order_no2']);
        }

        return CatchResponse::paginate($query->order('updated_at', 'desc')->paginate()->each(function (&$item) {
            $item['shop_name'] = Shop::where('id', $item['shop_id'])->value('shop_name');
        }));
    }

    /**
     * 保存信息
     * @time 2021年02月23日 14:44
     * @param Request $request 
     */
    public function save(Request $request): \think\Response
    {
        return CatchResponse::success($this->ordersTempModel->storeBy($request->post()));
    }

    /**
     * 读取
     * @time 2021年02月23日 14:44
     * @param $id 
     */
    public function read($id): \think\Response
    {
        return CatchResponse::success($this->ordersTempModel->findBy($id));
    }

    /**
     * 更新
     * @time 2021年02月23日 14:44
     * @param Request $request 
     * @param $id
     */
    // public function update(Request $request, $id): \think\Response
    // {
    //     return CatchResponse::success($this->ordersTempModel->updateBy($id, $request->post()));
    // }

    /**
     * 删除
     * @time 2021年02月23日 14:44
     * @param $id
     */
    // public function delete($id): \think\Response
    // {
    //     return CatchResponse::success($this->ordersTempModel->deleteBy($id));
    // }
    // abstract public function syncOrder(OrdersTempModel $ordersTempModel);
    /**
     * 订单手工拆单
     */
    public function manualSplitOrder(Request $request, $id)
    {
        try {
            $orderData = $this->ordersTempModel->where('id', $id)->find();
            if (!$orderData) {
                return CatchResponse::fail('订单不存在');
            }
            if ((int)$orderData['is_sync_order'] == 0) {
                return CatchResponse::fail('订单为同步，不可进行手工拆单');
            }
            // Wayfair 不支持手工查单
            if ((int)$orderData['platform_id'] == 3) {
                return CatchResponse::fail('Wayfair平台 不支持手工查单');
            }
            $orderModel = new OrderRecords;
            if ($orderModel->where([
                'platform_no' => $orderData['order_no'], 'order_source' => 0, 'order_type' => 5,
                'is_delivery' => 1
            ])->column('id')) {
                return CatchResponse::fail('Fba订单已出库，不可重新拆单');
            }
            $this->ordersTempModel->startTrans();
            // 查找已拆成功订单
            $order = $orderModel->where(['platform_no' => $orderData['order_no'], 'order_source' => 0])->column('id');
            if (count($order) > 0) {
                if ($count = $orderModel->where(['platform_no' => $orderData['order_no']])
                    ->where(['logistics_status' => 0, 'order_source' => 0])->count()
                ) {
                    if (count($order) !== $count) {
                        $this->ordersTempModel->rollback();
                        return CatchResponse::fail('订单已有发货单，不可重新拆单');
                    }
                    $ids = implode(',', $order);
                    // 删除相关订单
                    $orderModel->deleteBy($ids);
                }
            }

            // 修改手工拆单状态
            if (!$this->ordersTempModel->updateBy($id, ['is_sync_order' => 0])) {
                $this->ordersTempModel->rollback();
                return CatchResponse::fail('订单拆单状态修改失败');
            }
            // 拆单
            $platforms = [
                Code::AMAZON, Code::EBAY, Code::OPENCART, Code::OVERSTOCK, Code::SHOPIFY, Code::WALMART, Code::WAYFAIR, Code::HOUZZ
            ];
            // 同步orderTemp的订单到ERP系统的order
            foreach ($platforms as $val) {
                switch ($val) {
                    case Code::AMAZON:
                        App(AmazonSpService::class)->syncOrderByAccount([$val], null, false, $id);
                        break;
                    case Code::EBAY:
                        App(EbayService::class)->syncOrderByAccount([$val], null, false, $id);
                        break;
                    case Code::OPENCART:
                        App(OpenCartService::class)->syncOrderByAccount([$val], null, false, $id);
                        break;
                    case Code::OVERSTOCK:
                        App(OverstockService::class)->syncOrderByAccount([$val], null, false, $id);
                        break;
                    case Code::SHOPIFY:
                        App(ShopifyService::class)->syncOrderByAccount([$val], null, false, $id);
                        break;
                    case Code::WALMART:
                        App(WalmartService::class)->syncOrderByAccount([$val], null, false, $id);
                        break;
                    case Code::WAYFAIR:
                        App(WayfairService::class)->syncOrderByAccount([$val], null, false, $id);
                        break;
                    case Code::HOUZZ:
                        App(HouzzService::class)->syncOrderByAccount([$val], null, false, $id);
                        break;
                }
            }
            $this->ordersTempModel->commit();
            return CatchResponse::success('手工拆单成功');
            // } else {
            //     $this->ordersTempModel->commit();
            //     return CatchResponse::success('订单已有发货单，不可重新拆单');
            // }
        } catch (\Exception $exception) {

            $this->ordersTempModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }
}
