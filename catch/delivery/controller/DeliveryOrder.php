<?php

namespace catchAdmin\delivery\controller;

use catchAdmin\delivery\common\DeliveryUpsCommon;
use catchAdmin\delivery\common\DeliveryUspsCommon;
use catchAdmin\delivery\Logistics\Ups;
use catchAdmin\order\model\OrderDeliver as orderModel;
use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catcher\Code;
use catcher\exceptions\FailedException;
use catchAdmin\delivery\common\DeliveryCommon as model;

// use catchAdmin\delivery\common\DeliveryUpsCommon as ups;
use catchAdmin\order\model\OrderRecords as orsModel;
use catchAdmin\warehouse\model\Warehouses as warModel;
use catchAdmin\order\model\OrderBuyerRecords;
use catchAdmin\order\model\OrderItemRecords;
use catchAdmin\product\model\ProductGroup;
use catchAdmin\basics\model\ZipCode;
// use catchAdmin\warehouse\model\Warehouses;
use catchAdmin\order\model\OrderDeliverProducts;

use catchAdmin\basics\model\LogisticsFeeConfig;
use catchAdmin\basics\model\Company;
use catchAdmin\product\model\Product;
use catchAdmin\basics\model\LogisticsFeeConfigInfo;
use catchAdmin\basics\model\OrderFeeSetting;
use catchAdmin\product\model\Parts;
use catchAdmin\warehouse\model\AllotOrders;

// use Ups\Entity\Address;
// use Ups\AddressValidation;
use catchAdmin\basics\model\Shop;
use catchAdmin\permissions\model\Users;
use think\Db;

use catchAdmin\report\model\ReportOrder;
use catchAdmin\system\model\Config;
use catchAdmin\warehouse\model\WarehouseStock;

use catchAdmin\warehouse\model\Warehouses;
use catchAdmin\basics\model\ShopWarehouse;
use catchAdmin\order\model\AfterSaleOrder;
use catchAdmin\order\model\OrderRecords;
use catchAdmin\supply\excel\CommonExport;
use catchAdmin\basics\excel\ZipCodeImport;
use catchAdmin\product\model\Category;
use Spipu\Html2Pdf\Html2Pdf;
use think\facade\Filesystem;
use setasign\Fpdi\Tcpdf\Fpdi;
use catchAdmin\basics\model\ZipCodeSpecial;
use think\facade\Cache;
use catcher\platform\AmazonService;
use catcher\platform\AmazonSpService;
use catcher\platform\OpenCartService;
use catcher\platform\OverstockService;
use catcher\platform\ShopifyService;
use catcher\platform\WayfairService;
use catcher\platform\WalmartService;
use catcher\platform\EbayService;
use catcher\platform\HouzzService;

use catchAdmin\warehouse\model\OutboundOrders;
use catchAdmin\warehouse\model\WarehouseOrders;
use think\facade\Log;
use ZipArchive;
use Carbon\Carbon;
use catchAdmin\order\model\OrdersTemp as OrdersTempModel;


class DeliveryOrder extends CatchController
{
    protected $model;
    protected $orderModel;

    public function __construct(Model $model, orderModel $orderModel)
    {
        $this->orderModel = $orderModel;
        $this->model      = $model;
    }
    /**
     * 发货列表 getList
     * @param Request $request
     */
    public function index(Request $request): \think\Response
    {
        $type = $request->param('delivery_state');
        $list = $this->orderModel->getList($type);
        return CatchResponse::paginate($list);
    }

    /**
     * 获取包裹详细信息
     * order_id 包裹表内的包裹id
     * type 查看订单详情1-只查看;2-手工发货
     * @time 2021年03月18日 19:03
     *
     * @param Request $request
     */
    public function deliveryInfo(Request $request)
    {
        try {
            $o_id = $request->param('order_id');
            if (!isset($o_id)) {
                throw new FailedException('无订单id');
            }
            $type = $request->param('type');
            if (!isset($type)) {
                throw new FailedException('无访问类型');
            }
            // 对手工发货需要的信息进行筛选返回
            $order_info = $this->model->getOrderInfo($o_id, $type, $request->user()->id);

            return CatchResponse::success($order_info);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage() . '异常', $code);
        }
    }

    /**
     * 确认发货单接口  对订单包裹信息进行审核
     * @time 2021年03月18日 19:03
     *
     * @param Request $request
     */
    public function confirmDeliver(Request $request, $id)
    {
        try {
            $orderData = $this->orderModel->findBy($id);
            if (!$orderData) {
                throw new FailedException('订单信息');
            }
            $order_info = $this->model->upDeliver($id, $request->user()->id);
            // 生成报表订单信息
            $reportOrder = new ReportOrder;
            if (!$reportOrder->where('order_no', $orderData['order_no'])->find()) {
                $reportOrder->saveOrder($orderData['order_no']);
            }
            return CatchResponse::success($order_info);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage() . '异常', $code);
        }
    }

    /**
     * 获取快递单号【向UPS/USPS发货】
     * @time 2021年11月08日
     *
     * @param Request $request
     */
    public function delivery(Request $request)
    {
        try {
            $data = $request->param();
            if (!isset($data['invoice_no'])) {
                return CatchResponse::fail('参数异常', Code::FAILED);
            }
            $now = time();
            foreach ($data['invoice_no'] as $val) {
                $order = orderModel::with(['orderBuyRecord', 'product', 'productGroup'])
                    ->where('invoice_no', $val)
                    ->find();
                $shippingCode = '';
                if ($order->logistics_type == 1 && $order->delivery_state != 6) {
                    switch (strtoupper($data['shipping_name'])) {
                        case 'UPS':
                            $ups = new DeliveryUpsCommon([]);
                            $shippingCode = $ups->shippment($order);
                            $shipping_code = $shippingCode['trackingNumber'];
                            break;
                        case 'USPS_CUBIC_PM':
                            $deliveryUspsCommon = new DeliveryUspsCommon();
                            $shippingCodeData = $deliveryUspsCommon->shippment($order, 'PM');
                            if (!$shippingCodeData['code']) {
                                return CatchResponse::fail($shippingCodeData['message']);
                            } else {
                                $shippingCode =  $shippingCodeData['message'];
                                $shipping_code = strlen($shippingCode['trackingNumber']) > 29 ? substr($shippingCode['trackingNumber'], 8) : '';
                            }
                            break;
                        case 'USPS_FCM':
                            $deliveryUspsCommon = new DeliveryUspsCommon();
                            $shippingCodeData = $deliveryUspsCommon->shippment($order, 'FCM');
                            if (!$shippingCodeData['code']) {
                                return CatchResponse::fail($shippingCodeData['message']);
                            } else {
                                $shippingCode =  $shippingCodeData['message'];
                                $shipping_code = strlen($shippingCode['trackingNumber']) > 29 ? substr($shippingCode['trackingNumber'], 8) : '';
                            }
                            break;
                    }
                    $order->shipping_id = $data['shipping_id'];
                    $order->shipping_name = $data['shipping_name'];
                    $order->shipping_code2 = $shippingCode['trackingNumber'];
                    $order->shipping_code = $shipping_code;
                    $order->tracking_date = $shippingCode['tracking_date'];
                    $order->send_at = $now;
                    $order->delivery_process_status = 1;
                    $order->delivery_state = 2;
                    $order->save();

                    //判断是否全部发货
                    $odCount = orderModel::where('order_record_id', $order->order_record_id)->whereNotIn('delivery_state', '6')->count();
                    $hasTrackingCount = orderModel::where('order_record_id', $order->order_record_id)->whereNotIn('delivery_state', '1,6')->count();
                    if (!empty($odCount)) {
                        $orderStatus = 2; //默认部分发货 如果全部发货单都已经获取ups运单号 则置为全部发货
                        $orderStatus = $odCount == $hasTrackingCount ? 3 : 2;

                        OrderRecords::where('id', $order->order_record_id)->update(['status' => $orderStatus]);
                    }
                }
            }

            return CatchResponse::success(true);
        } catch (\Exception $e) {
            return CatchResponse::fail('操作失败：' . $e->getMessage(), Code::FAILED);
        }
    }
    /**
     * 取消usps cancel
     */
    public function cancelUspsLabe(Request $request)
    {

        $data =  $request->post();
        $deliveryUspsCommon = new DeliveryUspsCommon();
        $order = orderModel::where(['shipping_code' => $data['tracks']])->find();
        $companyWarehouse  = new Warehouses;
        $usps_json = $companyWarehouse->where(['id' => $order['en_id']])->value('usps_json');
        $obj = json_decode($usps_json, true);
        $userId = $obj['WebtoolsID'];

        $shippingCodeData = $deliveryUspsCommon->cancel($data['tracks'], $userId);
        return CatchResponse::success($shippingCodeData);
    }
    /**
     * 
     */

    /**
     * 获取快递单号【向ups发货】
     * @time 2021年03月18日 19:03
     *
     * @param Request $request
     */
    public function deliveryUps(Request $request)
    {
        try {
            $data = $request->param();
            if (!isset($data['invoice_no'])) {
                return CatchResponse::fail('参数异常', Code::FAILED);
            }


            $now = time();

            foreach ($data['invoice_no'] as $val) {
                $order                          = orderModel::with(['orderBuyRecord', 'product', 'productGroup'])
                    ->where('invoice_no', $val)
                    ->find();

                if ($order->logistics_type == 1 && $order->delivery_state != 6) {
                    $ups = new DeliveryUpsCommon([]);
                    $shippingCode                   = $ups->shippment($order);
                    $order->shipping_id             = $data['shipping_id'];
                    $order->shipping_name           = $data['shipping_name'];
                    $order->shipping_code           = $shippingCode['trackingNumber'];
                    $order->tracking_date           = $shippingCode['tracking_date'];
                    $order->send_at                 = $now;
                    $order->delivery_process_status = 1;
                    $order->delivery_state = 2;
                    $order->save();

                    //判断是否全部发货
                    $odCount = orderModel::where('order_record_id', $order->order_record_id)->count();
                    $hasTrackingCount = orderModel::where('order_record_id', $order->order_record_id)->whereNotIn('delivery_state', '1')->count();
                    $orderStatus = 2; //默认部分发货 如果全部发货单都已经获取ups运单号 则置为全部发货
                    $orderStatus = $odCount == $hasTrackingCount ? 3 : 2;

                    OrderRecords::where('id', $order->order_record_id)->update(['status' => $orderStatus]);
                }
            }

            return CatchResponse::success(true);
        } catch (\Exception $e) {
            return CatchResponse::fail('操作失败：' . $e->getMessage(), Code::FAILED);
        }
    }

    /**
     * 获取usps tracking
     * @param Request $request
     * @throws \Exception
     */
    public function deliveryUspsTracking(Request $request)
    {
        try {
            $shippingCode = $request->post('shipping_code', '');
            $usps = new DeliveryUspsCommon();
            $order = orderModel::where(['shipping_code' => $shippingCode])->find();
            $companyWarehouse  = new Warehouses;
            $usps_json = $companyWarehouse->where(['id' => $order['en_id']])->value('usps_json');
            $obj = json_decode($usps_json, true);
            $userId = $obj['WebtoolsID'];

            // 测试数据
            // $res = $usps->tracking(['420223099205590218293100290917'], $userId);
            // $res = new Carbon('November 12, 2021 8:54 am');
            $res = $usps->tracking([$shippingCode], $userId);

            $res =  $usps->tracking([$shippingCode], $userId);
            if (!is_array($res)) {
                return CatchResponse::success($res);
            } else {
                if ($res && isset($res['TrackDetail'])) {
                    $oldXmlObj = $res['TrackDetail'];
                    $result = $oldXmlObj;
                    $code = $result;
                    if (is_array($code)) {
                        $code = $result[0];
                    }
                    $EventTime = !empty($code['EventTime']) > 0 ? $code['EventTime'] : '';
                } else {
                    $oldXmlObj = $res['TrackSummary'];
                    $result = $oldXmlObj;
                    $code = $result;
                    $EventTime = '';
                }

                if (strpos($code['Event'], 'Out for Delivery') !== false) {
                    $delivery_state = 5;
                } else {
                    $delivery_state = 4;
                }
                // $date_time =  new Carbon($code['EventDate'] . ' ' . $code['EventTime']);
                $dateTime = Carbon::parse($code['EventDate'] . ' ' . $EventTime)->toDateTimeString();


                $deliver_day = strtotime($dateTime);

                orderModel::where('shipping_code', $shippingCode)
                    ->update(['delivery_state' => $delivery_state, 'tracking_info' => json_encode($res), 'deliver_day' => $deliver_day]);

                return CatchResponse::success($res);
            }
        } catch (\Exception $e) {
            return CatchResponse::fail('操作失败：' . $e->getMessage(), Code::FAILEDO);
        }
    }

    /**
     * 获取物流信息
     * @param Request $request
     * @throws \Exception
     */
    public function deliveryUpsTracking(Request $request)
    {
        try {
            $shippingCode = $request->post('shipping_code', '');
            $ups          = new DeliveryUpsCommon([]);
            $res          = $ups->tracking($shippingCode);


            $result = $res;
            if (is_array($result)) {
                $result = $res[0];
            }

            $code = $result->Status->StatusType->Code;

            if ($code == 'D' || $code == 'X') {
                $delivery_state = 5;
            } else {
                $delivery_state = 4;
            }

            $deliver_day = strtotime($result->GMTDate . ' ' . $result->GMTTime);

            orderModel::where('shipping_code', $shippingCode)
                ->update(['delivery_state' => $delivery_state, 'tracking_info' => json_encode($res), 'deliver_day' => $deliver_day]);

            return CatchResponse::success($res);
        } catch (\Exception $e) {
            return CatchResponse::fail('操作失败：' . $e->getMessage(), Code::FAILEDO);
        }
    }


    /**
     * 获取面单
     * @param CatchAtuh $auth
     * @param Request $request
     * @return void
     */
    public function getLabel(Request $request)
    {
        $data = $request->param();
        if (!isset($data['deliver_id'])) {
            return CatchResponse::fail('参数异常', Code::FAILED);
        }
        // $html2pdf = new Html2Pdf('L', [380, 220]);

        $codes = orderModel::whereIn('id', $data['deliver_id'])->column('shipping_code, tracking_date');
        $res   = [];
        foreach ($codes as $codeNew) {
            $datePath = '';
            $code = $codeNew['shipping_code'];
            if (!empty($codeNew['tracking_date'])) {
                $datePath = $codeNew['tracking_date'] . '/';
            }
            $imagePath = Filesystem::disk('local')->path("upslabel/{$datePath}{$code}.png");
            $res[] = env('APP.DOMAIN') . '/images/upslabel/' . $datePath . $code . '.png';
            $img = new \Imagick($imagePath);
            $newpath = Filesystem::disk('local')->path("upslabel/{$datePath}{$code}.pdf");
            $img->setImageFormat('pdf');
            $img->writeImage($newpath);

            $pdfs[] = $newpath;
        }


        $pdf = new Fpdi();
        // 載入現在 PDF 檔案
        for ($i = 0; $i < count($pdfs); $i++) {
            $page_count = $pdf->setSourceFile($pdfs[$i]);
            for ($pageNo = 1; $pageNo <= $page_count; $pageNo++) {
                //一页一页的读取PDF，添加到新的PDF
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], $size);
                $pdf->useTemplate($templateId);
                $pdf->SetFont('Helvetica');
                $pdf->SetXY(5, 5);
            }
        }

        /**
         * 默认是I：在浏览器中打开，D：下载，F：在服务器生成pdf
         * S：只返回pdf的字符串，个人感觉无实在意义
         */
        if ($codes[0]['tracking_date']) {
            $datePath = $codes[0]['tracking_date'] . '/';
        } else {
            $datePath = '';
        }

        $mergePdf = "merge_"  . time() . ".pdf";
        $mergePdfPath = Filesystem::disk('local')->path('upslabel/' . $datePath . $mergePdf);

        $pdf->output($mergePdfPath, "F");

        $mergePdfUrl = env('APP.DOMAIN') . '/images/upslabel/' . $datePath . $mergePdf;

        // 生成 zip
        $mergeZip = "zipups_"  . time() . ".zip";
        // 压缩多个文件
        $filename = Filesystem::disk('local')->path("upslabel/{$datePath}{$mergeZip}"); // 压缩包所在的位置路径
        $zip = new ZipArchive();
        $zip->open($filename, ZipArchive::CREATE);   //打开压缩包
        foreach ($codes as $codeNew) {
            $file = $codeNew['shipping_code'];
            $datePathNew = '';
            if (!empty($codeNew['tracking_date'])) {
                $datePathNew = $codeNew['tracking_date'] . '/';
            }

            $imagePath = Filesystem::disk('local')->path("upslabel/{$datePathNew}{$file}.png");
            $zip->addFile($imagePath, basename($file . ".png"));   //向压缩包中添加文件
        }
        $zip->close();  //关闭压缩包
        $zipData = env('APP.DOMAIN') . '/images/upslabel/' . $datePath . $mergeZip;
        // 結束 FPDI 剖析器
        // $pdf->closeParsers();
        return CatchResponse::success(['images' => $res, 'pdf' => $mergePdfUrl, 'zip' => $zipData]);
    }


    /**
     * 获取面单
     * @param CatchAtuh $auth
     * @param Request $request
     * @return void
     */
    public function getLabelAll(Request $request)
    {
        $data = $request->param();
        if (!isset($data['deliver_id'])) {
            return CatchResponse::fail('参数异常', Code::FAILED);
        }
        // $html2pdf = new Html2Pdf('L', [380, 220]);

        $codes = orderModel::whereIn('id', $data['deliver_id'])->column('shipping_code, shipping_code2, shipping_name, tracking_date');
        $res   = [];
        $datePath = '';
        foreach ($codes as $codeItem) {
            $code = $codeItem['shipping_code2'];
            if (!empty($codeItem['tracking_date'])) {
                $datePath = $codeItem['tracking_date'] . '/';
            }

            if (strtoupper($codeItem['shipping_name']) == 'UPS') {
                $codeType = 'upslabel/' . $datePath;
            } else {
                $codeType = 'uspslabel/' . $datePath;
            }


            $imagePath = Filesystem::disk('local')->path("{$codeType}{$code}.png");
            $res[] = env('APP.DOMAIN') . '/images/' . $codeType . $code . '.png';
            $img = new \Imagick($imagePath);
            $newpath = Filesystem::disk('local')->path("{$codeType}{$code}.pdf");
            $img->setImageFormat('pdf');
            $img->writeImage($newpath);

            $pdfs[] = $newpath;
        }


        $pdf = new Fpdi();
        // 載入現在 PDF 檔案
        for ($i = 0; $i < count($pdfs); $i++) {
            $page_count = $pdf->setSourceFile($pdfs[$i]);
            for ($pageNo = 1; $pageNo <= $page_count; $pageNo++) {
                //一页一页的读取PDF，添加到新的PDF
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], $size);
                $pdf->useTemplate($templateId);
                $pdf->SetFont('Helvetica');
                $pdf->SetXY(5, 5);
            }
        }

        /**
         * 默认是I：在浏览器中打开，D：下载，F：在服务器生成pdf
         * S：只返回pdf的字符串，个人感觉无实在意义
         */
        $mergePdf = "merge_"  . time() . ".pdf";
        $mergePdfPath = Filesystem::disk('local')->path('uspslabel/' . $datePath . $mergePdf);

        $pdf->output($mergePdfPath, "F");

        $mergePdfUrl = env('APP.DOMAIN') . '/images/uspslabel/' . $datePath . $mergePdf;

        // 生成 zip
        $mergeZip = "zipups_"  . time() . ".zip";
        // 压缩多个文件
        $filename = Filesystem::disk('local')->path("uspslabel/{$datePath}{$mergeZip}"); // 压缩包所在的位置路径
        $zip = new ZipArchive();
        $zip->open($filename, ZipArchive::CREATE);   //打开压缩包
        $datePathNew  = '';
        foreach ($codes as $codeItem) {
            $file = $codeItem['shipping_code2'];
            if (!empty($codeItem['tracking_date'])) {
                $datePathNew = $codeItem['tracking_date'] . '/';
            }
            $imagePath = Filesystem::disk('local')->path("uspslabel/{$datePathNew}{$file}.png");
            $zip->addFile($imagePath, basename($file . ".png"));   //向压缩包中添加文件
        }
        $zip->close();  //关闭压缩包
        $zipData = env('APP.DOMAIN') . '/images/uspslabel/' . $datePathNew  . $mergeZip;
        // 結束 FPDI 剖析器
        // $pdf->closeParsers();
        return CatchResponse::success(['images' => $res, 'pdf' => $mergePdfUrl, 'zip' => $zipData]);
    }


    /**
     * 打印面单
     *
     * @param Request $request
     *
     * @return `\think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function printLabel(Request $request)
    {
        $data = $request->param();
        if (!isset($data['deliver_id'])) {
            return CatchResponse::fail('参数异常', Code::FAILED);
        }

        foreach ($data['deliver_id'] as $id) {
            $order = orderModel::find($id);
            if ($order && $order->delivery_process_status == 1) {
                $order->delivery_state          = 3;
                $order->delivery_process_status = 2;
                $order->print_time = time();
                $order->save();
            }
            // 获取已打印物流单
            $deliveryCount = $this->orderModel->where(['order_record_id' => $order['order_record_id'], 'order_type_source' => 1])
                ->where('delivery_process_status', '2')
                ->whereNotIn('delivery_state', '6')
                ->count();
            OrderRecords::where('id', $order['order_record_id'])
                ->update(['print_delivery_num' => $deliveryCount]);
        }

        return CatchResponse::success(true);
    }

    /**
     * 订单发货
     * @param $id 订单id
     */
    public function ordersDeliver(Request $request)
    {
        $data = $request->post();
        $ids = $data['ids'];
        $type = $data['type'];
        $successData = [];
        $failData = [];
        $userMoneyFail = [];
        $productFail = [];
        // writeLog('delivery_log', $ids);
        try {
            // 1-自有仓库 2-第三方仓库
            if ((int)$type == 2) {
                // 第三方仓库
                if (empty($data['warehouse_id']) || empty($data['warehouse_fictitious_id'])) {
                    return CatchResponse::fail('参数不正确', Code::FAILED);
                }
                $warehouses = new Warehouses;
                // 验证仓库
                if (!$warehouse = $warehouses->where('id', $data['warehouse_fictitious_id'])->find()) {
                    return CatchResponse::fail('请检查选择仓库是否存在', Code::FAILED);
                }
                // 发货单
                foreach ($ids as $value) {
                    // 获取订单信息
                    $orderRecords = new orsModel;
                    $orderData = $orderRecords->where(['id' => $value, 'status' => 1])->find();
                    if (!$orderData || $orderData['logistics_status'] != 0) {
                        $failData[] = $value;
                    } else {
                        // 查询数据是否存在
                        $idRedis = 'completeOrder|' . $orderData['id'];
                        $idsValue = Cache::get($idRedis);
                        if ($idsValue) {
                            $failData[] = $value;
                        } else {
                            $idsValue = Cache::set($idRedis, $orderData['id'], 2);
                            $successData[] = $value;
                            // 订单商品信息
                            $orderItemRecords = new OrderItemRecords;
                            $orderGoods = $orderItemRecords->alias('g')->field('g.*, pf.*, p.image_url, p.company_id,
                                p.packing_method, p.merge_num, p.code as goods_code1')
                                ->where('order_record_id', $value)
                                ->leftJoin('product_info pf', 'pf.product_id=g.goods_id')
                                ->leftJoin('product p', 'p.id=g.goods_id')
                                ->find();
                            // 发货订单参数拼装
                            $row = [
                                'idRedis' => $idRedis,
                                'en_id' =>  $data['warehouse_id'],
                                'vi_id' => $data['warehouse_fictitious_id'],
                                'deliver_type' => 1, // 0-自有发货 1-第三方发货
                                'order_type' => (int)$orderData['order_type'],
                                'order_record_id' => $value,
                                'goods_id' => $orderGoods['goods_id'],
                                'goods_code' => $orderGoods['goods_code1'],
                                'order_no' => $orderData['order_no'],
                                'order_delivery_type' => 1, // 发货类型 1-整单发货 2-拆分发货
                                'platform_no' => $orderData['platform_no'], // 平台订单编号1
                                'platform_no_ext' => $orderData['platform_no_ext'], // 平台订单编号1
                                // 'shipping_method' => '', // 运输方式
                                'goods_pic' => $orderGoods['image_url'], // 商品缩率图
                                'platform_id' => $orderData['platform_id'], // 平台ID
                                'company_id' => $orderGoods['company_id'], // 所属客户id
                                'shop_basics_id' => $orderData['shop_basics_id'], // 店铺ID
                                'status' => 0, // 待审核
                                'transaction_price_value' => $orderGoods['transaction_price_value'],
                                'tax_amount_value' => $orderGoods['tax_amount_value'],
                                'freight_fee' => $orderGoods['freight_fee'],
                                'creator_id' => $data['creator_id'],
                                'packing_method' => $orderGoods->packing_method
                            ];
                            // 商品id
                            $goodsId = $orderGoods->goods_id;
                            // 普通包装商品
                            $numOrder = 1;
                            if ((int)$orderGoods->packing_method == 1) {
                                // 商品合计发货数量
                                $merge_num = $orderGoods->merge_num;
                                // 商品总数量
                                $total_num = $orderGoods->quantity_purchased;
                                // 余数
                                $bcmod = bcmod($total_num, $merge_num);
                                // 拆分后订单数量
                                $numOrder = floor(bcdiv($total_num, $merge_num, 2));
                                // 当订单商品总数大于 合并发货数量 发货类型 拆分发货
                                if ((int) $total_num > (int) $merge_num) {
                                    $row['order_delivery_type'] = 2;
                                }
                                // 普通商品发货
                                $this->normalGoodsData($row, $numOrder, $bcmod, $merge_num, $orderGoods);
                            } else {
                                // 多箱包装商品
                                $productGroup = new ProductGroup;
                                $orderGoodsData = $productGroup->where('product_id', $goodsId)->select();
                                // 当订单商品总数大于 合并发货数量 发货类型 拆分发货 || 订单商品数量大于 1 或者 分组数量大于 1
                                if (count($orderGoodsData) > 1 || (int)$orderGoods['quantity_purchased'] > 1) {
                                    $row['order_delivery_type'] = 2;
                                }
                                // 发货总数量等于 订单商品数量*分组商品类型数量
                                // $row['number'] = $orderGoods->quantity_purchased;
                                // 发货单多箱拆单
                                $this->multiBoxGoodsData($row, $orderGoodsData, $orderGoods->quantity_purchased, $orderGoods);
                            }
                        }
                    }
                }
            } else {
                foreach ($ids as $value) {
                    // 获取订单信息
                    $orderRecords = new orsModel;
                    $orderData = $orderRecords->where(['id' => $value, 'status' => 1])->find();
                    if ($orderData && $orderData['logistics_status'] != 0) {
                        $failData[] = $value;
                    } else {
                        // 查询数据是否存在
                        $idRedis = 'completeOrder|' . $orderData['id'];
                        $idsValue = Cache::get($idRedis);
                        if ($idsValue) {
                            $failData[] = $value;
                        } else {
                            $idsValue = Cache::set($idRedis, $orderData['id'], 2);

                            // 订单商品信息
                            $orderItemRecords = new OrderItemRecords;
                            $orderGoods = $orderItemRecords->alias('g')->field('g.*, pf.*, p.image_url, p.company_id,
                                p.packing_method, p.merge_num, p.code as goods_code1')
                                ->where('order_record_id', $value)
                                ->leftJoin('product_info pf', 'pf.product_id=g.goods_id')
                                ->leftJoin('product p', 'p.id=g.goods_id')
                                ->find();
                            $orderBuyerRecords = new OrderBuyerRecords;
                            $orderGoods['address_postalcode'] = $orderBuyerRecords->where(['order_record_id' => $value, 'type' => 0, 'is_disable' => 1])
                                ->value('address_postalcode');
                            // 查询客户可用余额
                            $company = new Company;
                            $amount = $company->where('id', $orderGoods['company_id'])->value('overage_amount');
                            if ((float)$amount <= 0 && $orderData['order_type'] == 3) {
                                $userMoneyFail[] = $value . '订单编码：' . $orderData['order_no'];
                            } else {
                                $successData[] = $value;
                                // 发货订单参数拼装
                                $row = [
                                    'idRedis' => $idRedis,
                                    'deliver_type' => 0, // 0-自有发货 1-第三方发货
                                    'order_type' => (int)$orderData['order_type'],
                                    'logistics_type' => 1, // 0-未设置 1-自有物流 2-它有物流
                                    'order_record_id' => $value,
                                    'goods_id' => $orderGoods['goods_id'],
                                    'goods_code' => $orderGoods['goods_code1'],
                                    'order_no' => $orderData['order_no'],
                                    'order_delivery_type' => 1, // 发货类型 1-整单发货 2-拆分发货
                                    'platform_no' => $orderData['platform_no'], // 平台订单编号1
                                    'platform_no_ext' => $orderData['platform_no_ext'], // 平台订单编号1
                                    // 'shipping_method' => '', // 运输方式
                                    'goods_pic' => $orderGoods['image_url'], // 商品缩率图
                                    'platform_id' => $orderData['platform_id'], // 平台ID
                                    'company_id' => $orderGoods['company_id'], // 所属客户id
                                    'shop_basics_id' => $orderData['shop_basics_id'], // 店铺ID
                                    'status' => 0, // 待审核
                                    'transaction_price_value' => $orderGoods['transaction_price_value'],
                                    'tax_amount_value' => $orderGoods['tax_amount_value'],
                                    'freight_fee' => $orderGoods['freight_fee'],
                                    'creator_id' => $data['creator_id'],
                                    'packing_method' => $orderGoods->packing_method
                                ];
                                // 商品id
                                $goodsId = $orderGoods->goods_id;
                                // 普通包装商品
                                $numOrder = 1;
                                if ((int)$orderGoods->packing_method == 1) {
                                    // 商品合计发货数量
                                    $merge_num = $orderGoods->merge_num;
                                    // 商品总数量
                                    $total_num = $orderGoods->quantity_purchased;
                                    // 余数
                                    $bcmod = bcmod($total_num, $merge_num);
                                    // 拆分后订单数量
                                    $numOrder = floor(bcdiv($total_num, $merge_num, 2));
                                    // 当订单商品总数大于 合并发货数量 发货类型 拆分发货
                                    if ((int) $total_num > (int) $merge_num) {
                                        $row['order_delivery_type'] = 2;
                                    }
                                    $this->normalGoodsData($row, $numOrder, $bcmod, $merge_num, $orderGoods);
                                } else {
                                    // 多箱包装商品
                                    $productGroup = new ProductGroup;
                                    $orderGoodsData = $productGroup->where('product_id', $goodsId)->select();
                                    // 当订单商品总数大于 合并发货数量 发货类型 拆分发货
                                    // if (count($orderGoodsData) > 1) {
                                    //     $row['order_delivery_type'] = 2;
                                    // }
                                    // 当订单商品总数大于 合并发货数量 发货类型 拆分发货 || 订单商品数量大于 1 或者 分组数量大于 1
                                    if (count($orderGoodsData) > 1 || (int)$orderGoods['quantity_purchased'] > 1) {
                                        $row['order_delivery_type'] = 2;
                                    }
                                    // 发货总数量等于 订单商品数量*分组商品类型数量
                                    // $row['number'] = $orderGoods->quantity_purchased;
                                    // 发货单拆单
                                    $this->multiBoxGoodsData($row, $orderGoodsData, $orderGoods->quantity_purchased, $orderGoods);
                                }
                            }
                        }
                    }
                }
            }
            $dataArr = [];
            $dataArr['failData'] = $failData;
            $dataArr['successData'] = $successData;
            $dataArr['userMoneyFail'] = $userMoneyFail;
            $dataArr['productFail'] = $productFail;
            return CatchResponse::success($dataArr);
        } catch (\Exception $e) {
            $message = sprintf($e->getCode() . ':' . $e->getMessage() .
                ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            Log::info($message);
            return CatchResponse::fail('操作异常，请稍后再试');
        }
    }
    /**
     * 多箱商品发货
     * @param &$row 发货单商品
     * @param $orderGoodsData 商品分组信息
     * @param $total_num 订单商品数量
     */
    public function multiBoxGoodsData($row, $orderGoodsData, $total_num, $orderGoods)
    {
        $list = [];
        // 发货单数量
        foreach ($orderGoodsData as $value) {
            for ($i = 1; $i <= $total_num; $i++) {
                $row['length_AS_total'] = $value['length_AS'];
                $row['width_AS_total'] = $value['width_AS'];
                $row['height_AS_total'] = $value['height_AS'];
                $row['weight_AS_total'] = $value['weight_gross_AS'];
                $row['logistics_status'] = 2; // 1-成功发货订单，2-异常发货订
                $row['goods_number'] = 1;
                $row['number'] = 1;
                $row['goods_group_id'] = $value['id'];
                $row['goods_group_name'] = $value['name'];
                $row['goods'] = $value['name'];
                $list[] = $row;
            }
        }
        // 获取订单 是否自动发货 配置
        // 获取时候自动发货 1-自动发货  2-手工发货
        $config_deliver = Config::where(['key' => 'order.delivery'])->value('value');
        if (isset($config_deliver) && (int)$config_deliver == 2 && (int)$row['deliver_type'] == 0) {
            $row['logistics_status'] = 2;
            // 生成异常发货单 (当没有仓库是)
            $this->createdAbnormalOrder($list, $row);
            Cache::delete($row['idRedis']);
            return true;
        }
        // 第三方发货 仓库信息
        if ((int)$row['deliver_type'] == 1) {
            $warehouseData = [
                'warehouse_id' => $row['en_id'],
                'warehouse_fictitious_id' => $row['vi_id']
            ];
            $news_key = [1];
        } else {
            // 客户订单发货
            if ((int)$row['order_type'] == 3) {
                // 匹配仓库，客户仓库
                $warehouseData = $this->matchCompanyWarehouse($orderGoods['address_postalcode'], $row['company_id']);
            } else {
                // 匹配仓库
                $warehouseData = $this->matchShopWarehouse($orderGoods['address_postalcode'], $row['shop_basics_id']);
            }
        }

        if (!$warehouseData) {
            $row['logistics_status'] = 2; // 发货单类型（1-成功发货订单，2-异常发货订单）
            // 生成异常发货单 (当没有仓库是)
            $this->createdAbnormalOrder($list, $row);
            Cache::delete($row['idRedis']);
            return true;
        }
        if ((int)$row['deliver_type'] == 0) {
            // 获取仓库邮编
            $warehouseData = $warehouseData->toArray();
            // 过滤掉数组中没有邮编分区的
            $news_key = array_filter($warehouseData, function ($item) {
                return $item['zone'] !== 0 && $item['zone'] <= 5;
            });
        }

        // 当没有找到一个邮编分区的时候
        if (count($news_key) < 1) {
            $row['logistics_status'] = 2;
            // 生成异常发货单 （当没有邮编分区仓库时候）
            $this->createdAbnormalOrder($list, $row);
            Cache::delete($row['idRedis']);
            return true;
        }
        $orderRecords = new orsModel;
        $isDelivery = false;
        foreach ($list as $key => $value) {
            $listNew = [$value];
            if ((int)$row['deliver_type'] == 0) {
                $zons = array_column($news_key, 'zone');
                // 按照从小到大排序
                array_multisort($zons, SORT_ASC, $news_key);
                // var_dump('$news_key', $news_key); exit;
                // 循环当前匹配仓库（找出合适商品的）
                foreach ($news_key as $value) {
                    if ($isDelivery = $orderRecords->deliveryOther($row, $value, $listNew)) {
                        foreach ($listNew as &$a) {
                            $a['en_id'] = $value['warehouse_id'];
                            $a['vi_id'] = $value['warehouse_fictitious_id'];
                        }
                        break;
                    }
                }
            } else {
                if ($isDelivery = $orderRecords->deliveryOther($row, $warehouseData, $listNew)) {
                    foreach ($listNew as &$a) {
                        $a['en_id'] = $warehouseData['warehouse_id'];
                        $a['vi_id'] = $warehouseData['warehouse_fictitious_id'];
                    }
                }
            }

            if (!$isDelivery) {
                // 生成异常发货单 （当仓库商品没有匹配成功时候）
                $this->createdAbnormalOrder($listNew, $row);
                continue;
            } else {
                // var_dump('===>>>>>', $list[0]); exit;
                // 生成正常订单
                foreach ($listNew as $key => $value) {
                    $orderFee = $this->goodsFee($orderGoods['address_postalcode'], $row['company_id'], $value['goods_group_id'], $zons[0] ?? 0, 1);
                    $value['freight_weight_price'] = $orderFee['freight_weight_price'];
                    $value['freight_additional_price'] = $orderFee['freight_additional_price'];
                    $value['order_price'] = $orderFee['order_price'];
                    $value['postcode_fee'] = $orderFee['postcode_fee'];
                    $value['hedge_fee'] = $orderFee['hedge_fee'] ?? 0;
                    $value['zone'] = $zons[0] ?? 0;

                    $value['logistics_status'] = 1;
                    $id = $this->orderModel->createBy($value);
                    $orderDeliverProducts = new OrderDeliverProducts;
                    $value['product'] = $listNew[0]['product'];
                    $value['product']['order_deliver_id']  = $id;
                    $value['product']['order_id']  = $value['order_record_id'];
                    $value['product']['transaction_price_currencyid'] = 'USD';
                    $value['product']['transaction_price_value'] = $value['transaction_price_value'];
                    $value['product']['tax_amount_value']  = $value['tax_amount_value'];
                    $value['product']['freight_fee'] = $value['freight_fee'] ?? 0;
                    $value['product']['tax_amount_currencyid']  = 'USD';
                    $value['product']['type']  = 1; // 1-普通商品 2-配件
                    $value['product']['warehouses_id'] = $value['vi_id'];
                    $value['product']['goods_group_id'] = $value['goods_group_id'];
                    $value['product']['goods_group_name'] = $value['goods_group_name'];
                    // 商品关联商品
                    $orderDeliverProducts->createBy($value['product']);
                    // 扣除金额
                    if ((int)$row['order_type'] == 3) {
                        $amount = bcmul($orderFee['total'], $value['number'], 2);
                        if (!empty((float)$amount)) {
                            $company = new Company;
                            $company->amountDeduction($amount, $row['company_id']);
                        }
                    }
                }
                // 修改原始订单发货状态(修改发货状态为正常)
                $orsModel = new orsModel;
                $orsModel->where('id', $row['order_record_id'])->update(['logistics_status' => 1]);
                //            Cache::delete($row['idRedis']);
            }
            Cache::delete($row['idRedis']);
        }
        // $listNew = [$listNew];
        // 自有发货
    }
    /**
     * 对比对象数值大小
     * $list
     */
    public function  compareSize($list, $arr, $merge_num)
    {
        asort($arr);
        $min = array_shift($arr);

        if ($list['length_AS'] == $min) {
            $list['length_AS'] = bcmul($list['length_AS'], $merge_num, 6);
        }
        if ($list['width_AS'] == $min) {
            $list['width_AS'] = bcmul($list['width_AS'], $merge_num, 6);
        }
        if ($list['height_AS'] == $min) {
            $list['height_AS'] = bcmul($list['height_AS'], $merge_num, 6);
        }
        return $list;
    }
    /**
     * 普通商品发货单
     * @param $num 订单数量
     * @param $row 数据
     * @param $merge_num 合并发货数量
     * @param $bcmod 余数
     * @param $orderGoods 商品信息
     */
    public function normalGoodsData($row, $num, $bcmod, $merge_num, $orderGoods)
    {
        $arr = [$orderGoods->width_AS, $orderGoods->length_AS, $orderGoods->height_AS];
        $listObj = [
            'length_AS' => $orderGoods->length_AS,
            'width_AS' => $orderGoods->width_AS,
            'height_AS' => $orderGoods->height_AS,
        ];
        $listNew = $this->compareSize($listObj, $arr, $merge_num);
        $list = [];
        for ($i = 0; $i < $num; $i++) {
            $row['number'] = $merge_num;
            $row['length_AS_total'] = $listNew['length_AS'];
            $row['width_AS_total'] = $listNew['width_AS'];
            $row['height_AS_total'] = $listNew['height_AS'];
            $row['weight_AS_total'] = number_format(round(bcmul($orderGoods->weight_gross_AS, $merge_num, 6), 6), 6);
            $row['volume_weight_AS'] = $orderGoods->volume_weight_AS;
            $row['height_AS'] = $orderGoods->height_AS;
            $row['width_AS'] = $orderGoods->width_AS;
            $row['length_AS'] = $orderGoods->length_AS;
            $row['logistics_status'] = 2;
            $row['indexKey'] = $i . 'normal';
            $list[] = $row;
        }
        if ((int) $bcmod > 0) {
            $listNew2 = $this->compareSize($listObj, $arr, $bcmod);
            $row['number'] = $bcmod;
            $row['length_AS_total'] = $listNew2['length_AS'];
            $row['width_AS_total'] = $listNew2['width_AS'];
            $row['height_AS_total'] = $listNew2['height_AS'];
            $row['weight_AS_total'] = number_format(round(bcmul($orderGoods->weight_gross_AS, $bcmod, 6), 6), 6);
            $row['logistics_status'] = 2;
            $row['volume_weight_AS'] = $orderGoods->volume_weight_AS;
            $row['height_AS'] = $orderGoods->height_AS;
            $row['width_AS'] = $orderGoods->width_AS;
            $row['length_AS'] = $orderGoods->length_AS;
            $row['indexKey'] = 'normal-bcmod';
            $list[] = $row;
        }
        // 获取订单 是否自动发货 配置 (自由发货判断)
        // 获取时候自动发货 1-自动发货  2-手工发货
        $config_deliver = Config::where(['key' => 'order.delivery'])->value('value');
        if (isset($config_deliver) && (int)$config_deliver == 2 && (int)$row['deliver_type'] == 0) {
            $row['logistics_status'] = 2;
            $this->createdAbnormalOrder($list, $row);
            Cache::delete($row['idRedis']);
            return true;
        }
        // 第三方发货 仓库信息
        if ((int)$row['deliver_type'] == 1) {
            $warehouseData = [
                'warehouse_id' => $row['en_id'],
                'warehouse_fictitious_id' => $row['vi_id']
            ];
            $news_key = [1];
        } else {
            // 客户订单发货
            if ((int)$row['order_type'] == 3) {
                // 匹配仓库，客户仓库
                $warehouseData = $this->matchCompanyWarehouse($orderGoods['address_postalcode'], $row['company_id']);
            } else {
                // 匹配仓库
                $warehouseData = $this->matchShopWarehouse($orderGoods['address_postalcode'], $row['shop_basics_id']);
            }
        }

        if (!$warehouseData) {
            $row['logistics_status'] = 2;
            // 生成异常发货单 (当没有仓库是)
            $this->createdAbnormalOrder($list, $row);
            Cache::delete($row['idRedis']);
            return true;
        }
        // 自有发货 仓库信息
        if ((int)$row['deliver_type'] == 0) {
            // 获取仓库邮编
            $warehouseData = $warehouseData->toArray();
            // 过滤掉数组中没有邮编分区的
            $news_key = array_filter($warehouseData, function ($item) {
                return $item['zone'] !== 0 && $item['zone'] <= 5;
            });
        }

        // var_dump($news_key); exit;
        // 当没有找到一个邮编分区的时候
        if (count($news_key) < 1) {
            $row['logistics_status'] = 2;
            // 生成异常发货单 （当没有邮编分区仓库时候）
            $this->createdAbnormalOrder($list, $row);
            Cache::delete($row['idRedis']);
            return true;
        }
        $orderRecords = new orsModel;
        foreach ($list as $key => $val) {
            $listNew = [];
            $isDelivery = false;
            # code...
            $listNew = [$val];
            // 自有仓库
            if ((int)$row['deliver_type'] == 0) {
                $zons = array_column($news_key, 'zone');
                // 按照从小到大排序
                array_multisort($zons, SORT_ASC, $news_key);
                // 循环当前匹配仓库（找出合适商品的）
                foreach ($news_key as $value) {
                    if ($isDelivery = $orderRecords->deliveryOther($row, $value, $listNew)) {
                        foreach ($listNew as &$a) {
                            $a['en_id'] = $value['warehouse_id'];
                            $a['vi_id'] = $value['warehouse_fictitious_id'];
                        }
                        break;
                    }
                }
                // 获取各项订单操作费用
                // 获取第一个最近的仓库
                // $warehouseData = $news_key[0];
            } else {
                // 仓库发货
                if ($isDelivery = $orderRecords->deliveryOther($row, $warehouseData, $listNew)) {
                    foreach ($listNew as &$a) {
                        $a['en_id'] = $warehouseData['warehouse_id'];
                        $a['vi_id'] = $warehouseData['warehouse_fictitious_id'];
                    }
                }
            }

            $company = new Company();
            if (!$isDelivery) {
                // 生成异常发货单 （当仓库商品没有匹配成功时候）
                $this->createdAbnormalOrder($listNew, $row);
                continue;
            } else {
                $orderFee = $this->goodsFee($orderGoods['address_postalcode'], $row['company_id'], $row['goods_id'], $zons[0] ?? 0);
                // 生成正常订单
                foreach ($listNew as $key => $value) {
                    $value['logistics_status'] = 1;
                    $value['freight_weight_price'] = $orderFee['freight_weight_price'];
                    $value['freight_additional_price'] = $orderFee['freight_additional_price'];
                    $value['order_price'] = $orderFee['order_price'] ?? 0;
                    $value['postcode_fee'] = $orderFee['postcode_fee'];
                    $value['hedge_fee'] = $orderFee['hedge_fee'] ?? 0;
                    $value['zone'] = $zons[0] ?? 0;

                    // 生成异常发货单 (当没有仓库是)
                    $id = $this->orderModel->createBy($value);

                    $orderDeliverProducts = new OrderDeliverProducts;
                    $value['product']['order_deliver_id']  = $id;
                    $value['product']['order_id']  = $value['order_record_id'];
                    $value['product']['transaction_price_currencyid'] = 'USD';
                    $value['product']['transaction_price_value'] = $value['transaction_price_value'];
                    $value['product']['tax_amount_value']  = $value['tax_amount_value'];
                    $value['product']['freight_fee'] = $value['freight_fee'] ?? 0;
                    $value['product']['tax_amount_currencyid']  = 'USD';
                    $value['product']['type']  = 1; // 1-普通商品 2-配件
                    $value['product']['warehouses_id'] = $value['vi_id'];
                    $value['product']['goods_group_id'] = $value['goods_group_id'] ?? 0;
                    $value['product']['goods_group_name'] = $value['goods_group_name'] ?? '';
                    $orderDeliverProducts->createBy($value['product']);
                    // 扣除费用 amountDeduction
                    // 扣除客户金额 自有发货扣除
                    if ((int)$row['order_type'] == 3) {
                        $amount = bcmul($orderFee['total'], $value['number'], 2);
                        if (!empty((float)$amount)) {
                            $company->amountDeduction($amount, $row['company_id']);
                        }
                    }
                }
                // 修改原始订单发货状态(修改发货状态为异常)
                $orsModel = new orsModel;
                $orsModel->where('id', $row['order_record_id'])->update(['logistics_status' => 1]);
                //            Cache::delete($row['idRedis']);
            }
            Cache::delete($row['idRedis']);
        }
    }
    /**
     * 匹配仓库
     * @param 订单地址邮编 $address_postalcode
     * @param 店铺id shop_basics_id
     */
    public function matchShopWarehouse($address_postalcode, $shop_basics_id)
    {
        $shopWarehouse  = new ShopWarehouse;
        $warehouseData = $shopWarehouse->field('w.zipcode, sw.warehouse_id, sw.warehouse_fictitious_id')
            ->alias('sw')
            ->where(['sw.shop_id' => $shop_basics_id])
            ->where(['w.is_third_part' => 0])
            ->where(['w.is_active' => 1])
            ->leftJoin('warehouses w', 'w.id =sw.warehouse_fictitious_id')
            ->select()->each(function (&$item) use ($address_postalcode) {
                $dest = new ZipCode;
                $dest = $dest->selZipzone($address_postalcode, $item->zipcode);
                $item->zone = $dest->zone ?? 0;
                $item->zipId = $dest['id'] ?? 0;
            });
        return $warehouseData;
    }
    /**
     * 匹配客户仓库
     * @param 订单地址邮编 $address_postalcode
     * @param 客户id $company_id
     */
    public function matchCompanyWarehouse($address_postalcode, $company_id)
    {
        $companyWarehouse  = new Warehouses;
        $warehouseData = $companyWarehouse->field('w.is_active, w.company_id,w.zipcode, w.id as warehouse_fictitious_id, w.parent_id as warehouse_id')
            ->alias('w')
            ->where('w.type', 2) // 虚拟仓库
            ->where(['w.is_active' => 1])
            ->where('w.company_id', $company_id)
            ->select()->each(function (&$item) use ($address_postalcode) {
                $dest = new ZipCode;
                $dest = $dest->selZipzone($address_postalcode, $item->zipcode);
                $item->zone = $dest->zone ?? 0;
                $item->zipId = $dest['id'] ?? 0;
            });
        return $warehouseData;
    }
    /**
     * 生成异常发货单
     */
    public function createdAbnormalOrder($list, $row)
    {
        // 生成异常发货单
        $this->orderModel->insertAllBy($list);
        // 修改原始订单发货状态(修改发货状态为异常)
        $orsModel = new orsModel;
        $orsModel->where('id', $row['order_record_id'])->update(['logistics_status' => 2]);
    }

    /**
     * 手工发货单发货
     * @param $id 发货单id
     */
    public function manualDelivery(Request $request, $id)
    {
        try {
            $data = $request->post();
            $deliver_type = $data['type']; // 1- 自有仓库 2-第三方仓库
            if (empty($data['warehouse_id']) || empty($data['warehouse_fictitious_id']) || empty($data['type'])) {
                return CatchResponse::fail('参数不正确', Code::FAILED);
            }
            // 发货单详情
            $orderData =  $this->orderModel->where(['id' => $id, 'logistics_status' => 2])->find();
            if (!$orderData) {
                return CatchResponse::fail('订单不存在', Code::FAILED);
            }
            if ($orderData['status'] == 1) {
                return CatchResponse::fail('订单已手工发货，请勿重复', Code::FAILED);
            }
            $productData = Product::where('id', $orderData['goods_id'])->find();
            // 商品类型 1-普通 2- 多箱
            if ((int)$productData['packing_method'] == 1) {
                $orderData['goods_code'] = $productData['code'];
            } else {
                $orderData['goods_code'] = $orderData['goods_group_name'];
            }

            $orderBuyerRecords = new OrderBuyerRecords;
            // 查询订单邮编
            $orderCode = $orderBuyerRecords->where(['order_record_id' => $id, 'type' => 0])->value('address_postalcode');

            $orsModel = new orsModel;
            // 获取原始订单订单类型
            $order_type = $orsModel->where('id', $orderData['order_record_id'])->value('order_type');
            // 查询仓库商品是否足够
            $productNum = $this->orderModel->findWarehouseProduct($data['warehouse_fictitious_id'], $orderData['goods_code']);
            // var_dump('$productNum', $productNum->toArray()); exit;
            if ($productNum && ((int)$productNum <  (int)$orderData['number'])) {
                return CatchResponse::fail('仓库商品不足，请切换仓库', Code::FAILED);
            }
            // 第三方发货
            if ((int)$deliver_type === 2 || (int)$orderData['order_type'] === 3) {
                $warehouses = new Warehouses;
                // 验证仓库
                if (!$warehouse = $warehouses->where('id', $data['warehouse_fictitious_id'])->find()) {
                    return CatchResponse::fail('请检查选择仓库是否存在', Code::FAILED);
                }
            }
            $this->orderModel->startTrans();
            $shopWarehouse = new ShopWarehouse;
            $viId = $data['warehouse_fictitious_id'];
            // 选择自有发货
            if ((int)$deliver_type === 1) {
                // 获取 实体仓下虚拟仓  $data['warehouse_id']
                if ((int)$orderData['order_type'] !== 3) {
                    if (!$viId = $shopWarehouse->getShopWarehouseId($orderData['shop_basics_id'], $data['warehouse_id'])) {
                        return CatchResponse::fail('该实体仓库下没有符合条件虚拟仓，请切换仓库', Code::FAILED);
                    }
                    // 虚拟仓不属于客户
                    if (!$shopWarehouse->getShopWarehouse($orderData['shop_basics_id'], $data['warehouse_fictitious_id'])) {
                        // 生成调拨单进行发货
                        $allotOrders = new AllotOrders;
                        $dataAllot = [
                            // 实体仓id
                            'entity_warehouse_id' => $data['warehouse_id'],
                            // 调入仓库
                            'transfer_in_warehouse_id' => $viId,
                            // 调出仓库
                            'transfer_out_warehouse_id' => $data['warehouse_fictitious_id'],
                            // 调拨原因
                            'notes' => '发货调拨',
                            // 审核状态
                            'audit_status' => 3,
                            // 创建人
                            'created_by' => $data['creator_id']
                        ];
                        $products = new Product;
                        $productData = $products->where('id', $orderData['goods_id'])->find();
                        $category = new Category;
                        $categoryData = $category->where('id', $productData['category_id'])->find();
                        $dataAllot['products'][0] = [
                            'goods_id' => $orderData['goods_id'], // 多箱商品id
                            'goods_code' => $orderData['goods_code'], // 多箱分组code
                            'category_name' => $categoryData['parent_name'] . $categoryData['name'],
                            'goods_name' => $productData['name_ch'],
                            'goods_name_en' => $productData['name_en'],
                            'goods_pic' => $productData['image_url'],
                            'packing_method' => $productData['packing_method'],
                            'number' => $orderData['number'],
                            'type' => 1,
                        ];
                        // 新增调拨单
                        $allotOrdersId = $allotOrders->add($dataAllot);
                        // 出库单
                        $outboundOrders = new OutboundOrders;
                        // $order = $allotOrders->findBy($allotOrdersId);
                        //计算出库批次
                        $productsAllot1 = $allotOrders->products($allotOrdersId)->toArray();
                        $productsAllot = $allotOrders->getOutboundOrderProducts($data['warehouse_id'], $data['warehouse_fictitious_id'], $productsAllot1, $orderData['number']);
                        $dataOutbound = [
                            'entity_warehouse_id' => $data['warehouse_id'],
                            'virtual_warehouse_id' => $data['warehouse_fictitious_id'],
                            'source' => 'allot',
                            'audit_status' => 2,   //调拨出库单默认已审核
                            'outbound_status' => 1, //调拨出库单默认已出库
                            'outbound_time' => date('Y-m-d H:i:s'),
                            'created_by' => $data['creator_id'],
                            'notes' => '手工发货调拨出库',
                            'products' => $productsAllot
                        ];
                        $warehouseStockModel = new WarehouseStock;
                        // 查询实时库存
                        $num = $warehouseStockModel->where(
                            [
                                'goods_code' => $productsAllot[0]['goods_code'],
                                'batch_no' => $productsAllot[0]['batch_no'],
                                'virtual_warehouse_id' => $data['warehouse_fictitious_id'],
                                'entity_warehouse_id' => $data['warehouse_id']
                            ],
                        )->sum('number');
                        if ((int)$num < (int)$orderData['number']) {
                            // 删除调拨单
                            $allotOrders->deleteBy($allotOrdersId);
                            return CatchResponse::fail('选择仓库库存/批次不足', Code::FAILED);
                        }
                        //变更库存 减库存
                        $warehouseStock = new WarehouseStock;
                        $dataOutbound['created_by'] = $data['creator_id'];
                        $dataOutbound['source'] = 'allot'; // 调拨出库
                        $idOutboundOrders =  $outboundOrders->createOutOrder($dataOutbound);
                        foreach ($productsAllot as $product) {
                            $warehouseStock->reduceStock(
                                $data['warehouse_id'],
                                $data['warehouse_fictitious_id'],
                                $product['goods_code'],
                                $product['batch_no'],
                                $product['number'],
                                $product['type'],
                                'delivery',
                                $idOutboundOrders ?? $id,
                                $id ?? 0
                            );
                        }

                        // 入库单
                        $warehouseOrders = new WarehouseOrders;
                        $dataWarehouse = [
                            'code' => $warehouseOrders->createOrderNo(),
                            'entity_warehouse_id' => $data['warehouse_id'],
                            'virtual_warehouse_id' => $viId,
                            'source' => 'allot',
                            'notes' => '手工发货调拨入库',
                            'audit_status' => 2,
                            'audit_notes' => '自动通过',
                            'audit_by' => $data['creator_id'],
                            'audit_time' => date('Y-m-d H:i:s'),
                            'warehousing_status' => 1,
                            'warehousing_time' => date('Y-m-d H:i:s'),
                            'created_by' => $data['creator_id'],
                            'products' => $productsAllot
                        ];
                        //变更库存 增加库存
                        $idWarehouseStock = $warehouseOrders->createWarehouseOrder($dataWarehouse);
                        $warehouseStock = new WarehouseStock;
                        foreach ($productsAllot as $product) {
                            $warehouseStock->increaseStock(
                                $data['warehouse_id'],
                                $viId,
                                $product['goods_code'],
                                $product['batch_no'],
                                $product['number'],
                                $product['type'],
                                'delivery',
                                $idWarehouseStock ?? $id,
                                $id ?? 0
                            );
                        }
                    }
                } else {
                    $viId = $data['warehouse_fictitious_id'];
                }
            }


            $orderItemRecords = new OrderItemRecords;
            $productDataItem = $orderItemRecords->alias('g')
                ->where('order_record_id', $orderData['order_record_id'])
                ->find();
            if (!empty($orderData['goods_group_id'])) {
                $goods_id = $orderData['goods_group_id'];
                $type = 1;
            } else {
                $goods_id = $orderData['goods_id'];
                $type = 0;
            }
            $orderFee = $this->goodsFee($orderCode, $orderData['company_id'], $goods_id, $data['zone'], $type);
            // var_dump($orderFee); exit;
            $company = new Company();

            $productData = Product::where('id', $orderData['goods_id'])->find();
            $orderData['transaction_price_value'] = $productDataItem['transaction_price_value'];
            $orderData['tax_amount_value'] = $productDataItem['tax_amount_value'];
            $orderData['packing_method'] = $productData['packing_method'];

            $orderRecords = new orsModel;
            $list = [$orderData];

            // $this->orderModel->startTrans();
            $warehouseData = [
                'warehouse_id' => $data['warehouse_id'],
                'warehouse_fictitious_id' => $viId,
                'zone' => $data['zone']
            ];
            // 商品出库
            if ($isDelivery = $orderRecords->deliveryOther($orderData, $warehouseData, $list)) {
                $orderData['en_id'] = $data['warehouse_id'];
                $orderData['vi_id'] = $viId;
            }

            if (!$isDelivery) {
                $this->orderModel->rollback();
                return CatchResponse::fail('手工发货失败', Code::FAILED);
            } else {
                $this->createdDeliverProducts($list, $id);
                // 修改发货单发货状态
                $this->orderModel->where('id', $id)
                    ->update([
                        'status' => 1, 'updater_id' => $data['creator_id'],
                        'updated_at' => time(),
                        'en_id' => $data['warehouse_id'],
                        'vi_id' => $viId,
                        'freight_weight_price' => $orderFee['freight_weight_price'],
                        'freight_additional_price' => $orderFee['freight_additional_price'],
                        'order_price' => $orderFee['order_price'] ?? 0,
                        'postcode_fee' => $orderFee['postcode_fee'],
                        'hedge_fee' => $orderFee['hedge_fee'] ?? 0,
                        'logistics_status' => 1,
                        'zone' => $data['zone'],
                        'deliver_type' => $deliver_type == 1 ? 0 : 1,
                        'logistics_type' => $deliver_type == 1 ? 1 : 0
                    ]); // 发货后转为成功发货单
                // 扣除客户金额
                if ((int)$order_type === 3) {
                    $total = bcmul($orderFee['total'], $orderData['number'], 2);
                    if (!empty((float)$total)) {
                        $company->amountDeduction($total, $orderData['company_id']);
                    }
                }
                // 生成报表订单信息
                $reportOrder = new ReportOrder;
                if (!$reportOrder->where('order_no', $orderData['order_no'])->find()) {
                    $reportOrder->saveOrder($orderData['order_no']);
                }
                $this->orderModel->commit();
                return CatchResponse::success(true);
            }
        } catch (\Exception $exception) {
            $this->orderModel->rollback();

            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage() . $exception->getLine(). '异常', $code);
        }
    }
    /**
     * 生成发货单商品
     * @param $list 商品信息
     * @param $id 发货单id
     */
    public function createdDeliverProducts($list, $id)
    {
        // 生成发货订单 关联商品
        $orderDeliverProducts = new OrderDeliverProducts;
        foreach ($list as $value) {
            $row = [
                'goods_id' => $value['product']['goods_id'],
                'goods_code' => $value['product']['goods_code'],
                'type' => $value['product']['type'],
                'number' => $value['product']['number'],
                'category_name' => $value['product']['category_name'],
                'goods_name' => $value['product']['goods_name'],
                'goods_name_en' => $value['product']['goods_name_en'],
                'goods_pic' => $value['product']['goods_pic'],
                'batch_no' => $value['product']['batch_no'],
                'order_deliver_id' => $id,
                'order_id' => $value['order_record_id'],
                'transaction_price_currencyid' => 'USD',
                'transaction_price_value' => $value['transaction_price_value'],
                'tax_amount_value' => $value['tax_amount_value'],
                'freight_fee' => $value['freight_fee'] ?? 0,
                'tax_amount_currencyid' => 'USD',
                'warehouses_id' => $value['vi_id'],
                'goods_group_id' => $value['goods_group_id'] ?? 0,
                'goods_group_name' => $value['goods_group_name'] ?? ''
            ];

            $orderDeliverProducts->createBy($row);
        }
        return true;
    }
    /**
     * 选择查看第三方仓库商品数量
     * @param $id 订单id
     */
    public function warehouseStockListOther(Request $request, $id)
    {
        $orderData = $this->orderModel->where('id', $id)->find();
        if (!$orderData) {
            return CatchResponse::fail('订单不存在', Code::FAILED);
        }
        // 如果是多箱商品
        if (!empty($orderData['goods_group_id'])) {
            $goodsCode = $orderData['goods_group_name'];
        } else {
            $goodsCode = Product::where('id', $orderData['goods_id'])->value('code');
        }

        // $goodsCode = $orderData['goods_code'];
        $list = $this->getWarehouseProductList($goodsCode);
        return CatchResponse::success($list);
    }
    public function getWarehouseProductList($goodsCode)
    {
        // 获取第三方仓库
        $warehouse  = new warModel;
        // 查询第三方仓库
        $warehouseData = $warehouse->where(['is_active' => 1, 'is_third_part' => 1, 'type' => 2])->select();
        $warehouseStock = new WarehouseStock;
        $list = [];
        foreach ($warehouseData as $key => $value) {
            $value['product'] = $warehouseStock->field('ws.*')
                ->alias('ws')
                ->where('ws.goods_code', $goodsCode)
                ->where('ws.virtual_warehouse_id', $value['id'])
                ->select()->each(function ($item) use (&$value) {
                    $value['entity_warehouse'] = $item['entity_warehouse'];
                    $value['virtual_warehouse'] = $item['virtual_warehouse'];
                    $value['entity_warehouse_id'] = $item['entity_warehouse_id'];
                    $value['virtual_warehouse_id'] = $item['virtual_warehouse_id'];
                });
            $arr = $value['product']->toArray();

            $listNumber = array_column($arr, 'number');
            $value['product_number'] = array_sum($listNumber);
            $value['goods_code'] = $goodsCode;
            $list[] = $value;
        }
        return $list;
    }
    /**
     * 可选择发货仓库
     * WarehouseStock
     * @param $id 订单id
     */
    public function warehouseStockList(Request $request, $id)
    {
        $data = $request->param();
        $list = [];
        // 补发补货
        if (isset($data['type']) && (int)$data['type'] == 1) {
            // 售后订单信息
            $afterSaleOrder = new AfterSaleOrder;

            $orderData = $afterSaleOrder->where([
                'id' => $id, 'status' => 1,
                'type' => 3
            ])
                ->find();

            $shop_basics_id = $orderData['shop_id'];
            // 订单地址邮编
            $orderBuyerRecords = new OrderBuyerRecords;
            $orderAddressCode = $orderBuyerRecords->where([
                'type' => 1, 'is_disable' => 1,
                'after_sale_id' => $id
            ])
                ->value('address_postalcode');
            $orderItemRecords = new OrderItemRecords;
            $orderItem = $orderItemRecords->where(['type' => 1, 'after_order_id' => $id])
                ->select();
            $orderType = $orderData['order_type'];
            $companyId = $orderData->company_id;
            foreach ($orderItem as $key => $value) {
                $list[$value['goods_code']] =  $this->getShopWarehouseProductList($orderAddressCode, $shop_basics_id, $value['goods_code'], $orderType);
            }
            return CatchResponse::success($list);
        } else {
            $orderData = $this->orderModel->where('id', $id)->find();
            if (!$orderData) {
                return CatchResponse::fail('订单不存在', Code::FAILED);
            }
            // var_dump($orderData);exit;
            $orderAddressCode = OrderBuyerRecords::where([
                'type' => 0,  'is_disable' => 1,
                'order_record_id' => $orderData['order_record_id']
            ])
                ->value('address_postalcode');
            $shop_basics_id = $orderData->shop_basics_id;
            $companyId = $orderData->company_id;
            $goodsCode = $orderData['goods_group_name'] != '' ? $orderData['goods_group_name'] : $orderData['goods_code'];
            $orderType = $orderData['order_type'];
            $list = $this->getShopWarehouseProductList($orderAddressCode, $shop_basics_id, $goodsCode, $orderType, $companyId);
            return CatchResponse::success($list);
        }
    }
    /**
     * 通过店铺id 和订单邮编 获取可选择仓库
     * @param $orderAddressCode 订单邮编
     * @param $shop_basics_id // 店铺id
     * @param $goodsCode 商品code
     * @param $orderType  订单类型
     */
    public function getShopWarehouseProductList($orderAddressCode, $shop_basics_id, $goodsCode, $orderType, $companyId = 0)
    {
        // $warehouseData = [];
        $listWarehouse = [];
        // 客户订单
        if ((int)$orderType == 3) {
            $warehouseData = $this->matchCompanyWarehouse($orderAddressCode, $companyId);
            // var_dump('$warehouseData', $warehouseData->toArray());
            // exit;
            if ($warehouseData) {
                $warehouseData = $warehouseData->toArray();
                $news_key = array_filter($warehouseData, function ($item) {
                    return $item['zone'] !== 0;
                });
                foreach ($news_key as $value) {
                    $value['arr'] =  Warehouses::field('name, code, id, parent_id, zipcode')
                        ->where([
                            'parent_id' => $value['warehouse_id'], 'type' => 2,
                            'is_active' => 1, 'is_third_part' => 0, 'company_id' => $companyId
                        ])->select()->each(function (&$item) use ($value) {
                            $item['isShopWarehouse'] = $item['id'] == $value['warehouse_fictitious_id'] ? 1 : 0;
                        })->toArray();


                    $listWarehouse[] = $value;
                }
            }
        } else {
            $warehouseData = $this->matchShopWarehouse($orderAddressCode, $shop_basics_id);
            if ($warehouseData) {
                $warehouseData = $warehouseData->toArray();
                // 过滤掉数组中没有邮编分区的
                $news_key = array_filter($warehouseData, function ($item) {
                    return $item['zone'] !== 0;
                });
                // 获取店铺绑定虚拟仓id集合
                // $ids = array_filter($news_key, 'warehouse_fictitious_id');
                // 获取仓库实体仓下虚拟仓
                foreach ($news_key as $value) {
                    $value['arr'] =  Warehouses::field('name, code, id, parent_id, zipcode')
                        ->where([
                            'parent_id' => $value['warehouse_id'], 'type' => 2,
                            'is_active' => 1, 'is_third_part' => 0
                        ])->select()->each(function (&$item) use ($value) {
                            $item['isShopWarehouse'] = $item['id'] == $value['warehouse_fictitious_id'] ? 1 : 0;
                        })->toArray();

                    $listWarehouse[] = $value;
                }
            }
        }
        $list = [];
        // 获取仓库邮编
        if ($listWarehouse && count($listWarehouse) > 0) {

            $zons = array_column($listWarehouse, 'zone');
            // 获取该仓库商品库存
            $warehouseStock = new WarehouseStock;
            foreach ($listWarehouse as $val) {
                foreach ($val['arr'] as $value) {
                    # code...
                    $value['product'] = $warehouseStock->field('ws.*')
                        ->alias('ws')
                        ->where('ws.goods_code', $goodsCode)
                        ->where('ws.virtual_warehouse_id', $value['id'])
                        ->select()->each(function ($item) use (&$value) {
                            $value['entity_warehouse'] = $item['entity_warehouse'];
                            $value['virtual_warehouse'] = $item['virtual_warehouse'];
                        });
                    $arr = $value['product']->toArray();

                    $listNumber = array_column($arr, 'number');
                    $value['product_number'] = array_sum($listNumber);

                    $warehousesData = Warehouses::where('id', $value['id'])->find();
                    $value['warehouse_fictitious_name'] = $warehousesData['name'];
                    $value['warehouse_name'] = $warehousesData['parent_warehouse'];
                    $value['zone'] = $val['zone'];
                    $value['zipId'] = $val['zipId'];
                    $value['orderZip'] = $orderAddressCode;
                    $value['warehouse_id'] = $value['parent_id'];
                    $value['warehouse_fictitious_id'] = $value['id'];
                    $val['options'][] = $value;
                    $val['label'] = $value['warehouse_name'];
                }
                unset($val['arr']);
                $list[] = $val;
            }
            array_multisort($zons, SORT_ASC, $list);
        }
        return $list;
    }

    /**
     * 商品费用计算
     * @param $orderCode 订单邮编
     * @param $company_id 客户id
     * @param $goods_id 商品信息
     * @param $type 1-多箱 0-普通
     * @param $goods_type
     */
    public function goodsFee($orderCode, $company_id, $goodsId, $zone, $type = 0, $goodsType = 1)
    {
        // 多箱包装
        $hedge_price = 0;
        if ((int)$type == 1) {
            $productGroup = ProductGroup::where('id', $goodsId)->find();
            $goodsDatas['company_id'] = Product::where('id', $productGroup['product_id'])->value('company_id');
            $goodsDatas['weight_gross_AS'] = $productGroup['weight_gross_AS'];
            $goodsDatas['volume_weight_AS'] = $productGroup['volume_weight_AS'];
            $goodsDatas['length_AS'] = $productGroup['length_AS'];
            $goodsDatas['width_AS'] = $productGroup['width_AS'];
            $goodsDatas['height_AS'] = $productGroup['height_AS'];
            $goodsDatas['oversize'] = $productGroup['oversize'];
            $hedge_price = !empty($productGroup['hedge_price']) ? $productGroup['hedge_price'] : 0;
        } else {
            if ($goodsType == 1) {
                $goodsDatas = Product::alias('p')->field('p.*, pf.*')->where('p.id', $goodsId)
                    ->leftJoin('product_info pf', 'pf.product_id = p.id')
                    ->find();
                $hedge_price = !empty($goodsDatas['hedge_price']) ? $goodsDatas['hedge_price'] : 0;
            } else {
                $goodsDatas = Parts::alias('p')->field('p.*')->where('p.id', $goodsId)
                    ->find();
            }
        }
        $list = [];
        // 商品毛重
        $weight_gross = ceil($goodsDatas['weight_gross_AS']);
        // 商品体积重
        $volume_weight_AS = ceil($goodsDatas['volume_weight_AS']);

        $lengData = [$goodsDatas['length_AS'], $goodsDatas['width_AS'], $goodsDatas['height_AS']];
        rsort($lengData); // 降序排序
        // 商品oversize
        $oversize = $goodsDatas['oversize'];
        // 规则一 计费重量取的是毛重(美制)和体积重（美制）的取大的那一个
        if ((float)$weight_gross > (float)$volume_weight_AS) {
            $width = $weight_gross;
        } else {
            $width = $volume_weight_AS;
        }
        // 规则二 若商品的计费重量<90lbs,但是商品oversize参数>130英寸，则计费重量按照90lbs计算
        if ($width < 90 && $oversize > 130) {
            $width = 90;
        }
        // 物流模板匹配
        $logisticsFeeConfig = new LogisticsFeeConfig;
        $logisticsData = $logisticsFeeConfig->where(['company_id' => $company_id, 'is_status' => 1])
            ->find();
        if (!$logisticsData) {
            // 物流费
            $list['freight_weight_price'] = 0;
            $list['freight_additional_price'] = 0;
            $list['postcode_fee'] = 0;
            $list['hedge_fee'] = 0; // 保费 保费=保费单价*(商品保值/100）* 发货单商品数量
        } else {
            $logisticsFeeConfigInfo = new LogisticsFeeConfigInfo;
            $dataLF = $logisticsFeeConfigInfo->where([
                'logistics_fee_id' => $logisticsData['id'],
                'weight' => $width
            ])
                ->find();
            $amountList = [];
            // 基础操作费
            $list['freight_weight_price'] = $dataLF['zone' . $zone] ?? 0;

            // 毛重金额
            if ($weight_gross > (float)$logisticsData['gross_weight']) {
                $amountList[0] = $logisticsData['gross_weight_fee'];
            } else {
                $amountList[0] = 0;
            }
            // 最长边 金额
            if ((float)$lengData[0] > (float)$logisticsData['big_side_length']) {
                $amountList[1] = $logisticsData['big_side_length_fee'];
            } else {
                $amountList[1] = 0;
            }
            // 次长边  金额
            if ((float)$lengData[1] > (float)$logisticsData['second_side_length']) {
                $amountList[2] = $logisticsData['second_side_length_fee'];
            } else {
                $amountList[2] = 0;
            }
            // oversize 金额
            if ((float)$logisticsData['oversize_max_size'] > (float)$oversize && (float)$oversize > (float)$logisticsData['oversize_min_size']) {
                $amountList[3] = $logisticsData['oversize_fee'];
            } else {
                $amountList[3] = 0;
            }
            // 超过 oversize 金额
            if ((float)$oversize > (float)$logisticsData['oversize_other_size']) {
                $amountList[4] = $logisticsData['oversize_other_size_fee'];
            } else {
                $amountList[4] = 0;
            }
            rsort($amountList); // 降序排序
            $list['freight_additional_price'] = $amountList[0] ?? 0;
            // 查询是否偏远计算偏远费用 $orderCode
            $zipCodeSpecial = new ZipCodeSpecial;
            if ($feeType = $zipCodeSpecial->where('zipCode', $orderCode)->value('type')) {
                // 1：偏远邮编，2：超偏远邮编
                if ((int)$feeType == 1) {
                    $list['postcode_fee'] = $logisticsData['remote_fee'];
                }
                if ((int)$feeType == 2) {
                    $list['postcode_fee'] = $logisticsData['super_remote_fee'];
                }
            } else {
                $list['postcode_fee'] = 0;
            }
            // 保费 insurance_fee 保费 保费=保费单价*(商品保值/100）* 发货单商品数量
            $list['hedge_fee'] = bcmul($logisticsData['insurance_fee'], ceil($hedge_price / 100), 2);
        }
        // 获取订单操作费模板
        $orderFeeSetting = new OrderFeeSetting;
        // if ((int)$type == 1) {
        //     $widthOrder = $goodsDatas['weight_gross_AS'];
        // } else {
        //     $widthOrder = $width;
        // }
        $feeData = $orderFeeSetting->getUserOrderAmount($goodsDatas['company_id'], $goodsDatas['weight_gross_AS']);
        // 订单操作费
        $list['order_price'] = $feeData['fee'] ?? 0;

        // 仓储费
        $list['warehouse_price'] = 0;
        $list['total'] = array_sum([
            $list['warehouse_price'],
            $list['freight_additional_price'], $list['freight_weight_price'], $list['order_price']
        ]);
        return $list;
    }

    /**
     * 补货发货
     * @param $id 售后订单
     * @param $zone[]
     */
    public function partDelivery(Request $request, $id)
    {
        try {
            // return CatchResponse::success('开发中');

            $data = $request->post();
            $afterSaleOrder = new AfterSaleOrder;
            // 售后订单信息
            $orderAfter = $afterSaleOrder->alias('oa')->field('oa.*, or.*')
                ->where(['oa.id' => $id, 'oa.status' => 1, 'oa.type' => 3])
                ->leftJoin('order_records or', 'or.id = oa.order_id')
                ->find();
            if (!$orderAfter) {
                return CatchResponse::fail('订单不存在', Code::FAILED);
            }
            // var_dump('>>>>', $orderAfter['is_warehousing']); exit;
            if ($orderAfter['is_warehousing'] == 1) {
                return CatchResponse::fail('订单已发货，请勿重复', Code::FAILED);
            }
            // 客户 id
            $companyId = Shop::where('id', $orderAfter['shop_id'])->value('company_id');

            // $shopWarehouse = new ShopWarehouse;
            // if (!$shopWarehouse->getShopWarehouse($orderAfter['shop_id'], $data['warehouse_fictitious_id'])) {
            //     return CatchResponse::fail('店铺仓库不匹配,请重新选择', Code::FAILED);
            // }

            $orderItemRecords = new OrderItemRecords;

            // 查询客户可用余额
            $company = new Company;
            $amount = $company->where('id', $companyId)->value('overage_amount');
            if (empty((float)$amount)) {
                return CatchResponse::fail('客户金额不足，不能发货', Code::FAILED);
            }
            // 订单邮编
            $orderBuyerRecords = new OrderBuyerRecords;
            // 查询订单邮编
            if (!$orderCode = $orderBuyerRecords->where(['after_sale_id' => $id, 'type' => 1])->value('address_postalcode')) {
                return CatchResponse::fail('订单地址邮编异常', Code::FAILED);
            }

            // var_dump('>>>>'); exit;
            $failData = [];
            // 商品或者配件
            if (isset($data['product'])) {
                foreach ($data['product'] as $value) {
                    // 配件信息
                    if ((int)$orderAfter['replenish_type'] == 2) {
                        // 获取补货订单， 当前售后单商品信息
                        $orderGoods = $orderItemRecords->alias('g')->field('g.*, pf.*')
                            ->where([
                                'type' => 1, 'after_order_id' => $id,
                                'goods_code' => $value['code']
                            ])
                            ->leftJoin('parts pf', 'pf.id=g.goods_id')
                            ->find();
                    } else {

                        // 获取补货订单，当前售后单商品信息
                        $orderGoods = $orderItemRecords->alias('g')->field('g.*, pf.*, p.image_url, p.company_id,
                        p.packing_method, p.merge_num, p.code as goods_code1')
                            ->where([
                                'g.type' => 1,
                                'g.goods_code' => $value['code'],
                                'g.after_order_id' => $id
                            ])
                            ->leftJoin('product p', 'p.code=g.goods_code')
                            ->leftJoin('product_info pf', 'pf.product_id=p.id')
                            ->find();
                    }
                    $idRedis = 'completeOrder|' . $value['code'];
                    $idsValue = Cache::get($idRedis);
                    if ($idsValue) {
                        $failData[] = $id . $value['code'];
                        continue;
                    } else {
                        $idsValue = Cache::set($idRedis, $value['code'], 2);
                    }
                    // var_dump('$orderGoods', $orderGoods);
                    // exit;
                    // 发货订单参数拼装
                    $row = [
                        'order_type' => $orderAfter['order_type'],
                        'after_order_id' => $id,
                        'order_record_id' => $orderAfter['order_id'],
                        'goods_id' => $orderGoods['goods_id'],
                        'goods_code' => $orderGoods['goods_code'],
                        'order_no' => $orderAfter['platform_order_no'],
                        'order_delivery_type' => 1, // 发货类型 1-整单发货 2-拆分发货
                        'platform_no' => $orderAfter['platform_order_no2'], // 平台订单编号1
                        'platform_no_ext' => $orderAfter['platform_no_ext'], // 平台订单编号1
                        // 'shipping_method' => '', // 运输方式
                        'goods_pic' => $orderGoods['image_url'], // 商品缩率图
                        'platform_id' => $orderAfter['platform_id'], // 平台ID
                        'company_id' => $companyId, // 所属客户id
                        'shop_basics_id' => $orderAfter['shop_id'], // 店铺ID
                        'status' => 0, // 待审核
                        'transaction_price_value' => $orderGoods['transaction_price_value'],
                        'tax_amount_value' => $orderGoods['tax_amount_value'],
                        'freight_fee' => $orderGoods['freight_fee'],
                        'creator_id' => $data['creator_id'],
                        'en_id' => $value['warehouse_id'],
                        'vi_id' => $value['warehouse_fictitious_id'],
                        'goods_type' => $orderAfter['replenish_type'],
                        'zone' => $value['zone'],
                        'order_code' => $orderCode, // 订单邮编
                        'packing_method' => 1,
                        'logistics_type' => 1, //发货物流类型 0-未设置 1-自有物流 2-它有物流
                    ];
                    $row['order_type_source'] = 2; // 补货订单
                    $row['is_warehousing'] = 1; // 是否生成入库（出库）单子 0-否 1-是
                    $type = Product::where('id', $orderGoods['goods_id'])->value('packing_method');
                    if ((int)$type == 2) {
                        // 获取多箱商品id
                        $goods_group_id = ProductGroup::where(['product_id' => $orderGoods['goods_id'], 'name' => $value['code']])->value('id');
                        // $row['goods_group_name'] = $value['code'];
                        $row['goods_group_id'] = $goods_group_id;
                    }
                    $row['packing_method_new'] = $type;
                    // var_dump('===', $orderAfter['replenish_type']); exit;
                    // 商品id (正常商品)
                    // $goodsId = $orderGoods->goods_id;
                    // 普通包装商品
                    $numOrder = 1;
                    if ($orderAfter['replenish_type'] == 2) { // 配件
                        $merge_num = $orderGoods->quantity_purchased;
                        $total_num = $orderGoods->quantity_purchased;
                        $numOrder = 1;
                        $bcmod = 0;
                        $this->normalGoodsDataPart($row, $numOrder, $bcmod, $merge_num, $orderGoods);
                        return CatchResponse::success('发货成功');
                    } else {
                        // var_dump('>>>>>>>11', $orderGoods->packing_method); exit;
                        // if ((int)$orderGoods->packing_method == 1) {
                        // 1-整件补货 2-配件补货
                        if ((int)$orderAfter['replenish_type'] == 1) {
                            // 商品合计发货数量
                            $merge_num = $orderGoods->merge_num < 1 ? 1 : $orderGoods->merge_num;
                            // 商品总数量
                            $total_num = $orderGoods->quantity_purchased;
                            // 余数
                            $bcmod = bcmod($total_num, $merge_num);
                            // 拆分后订单数量
                            $numOrder = floor(bcdiv($total_num, $merge_num, 2));
                            // 当订单商品总数大于 合并发货数量 发货类型 拆分发货
                            if ((int) $total_num > (int) $merge_num) {
                                $row['order_delivery_type'] = 2;
                            }
                        }
                        // var_dump($row, $numOrder, $bcmod, $merge_num, $orderGoods); exit;
                        $this->normalGoodsDataPart($row, $numOrder, $bcmod, $merge_num, $orderGoods);

                        return CatchResponse::success('发货成功');
                        // } else {
                        //     // 多箱包装商品
                        //     $productGroup = new ProductGroup;
                        //     $orderGoodsData = $productGroup->where('product_id', $goodsId)->select();
                        //     // 当订单商品总数大于 合并发货数量 发货类型 拆分发货
                        //     if (count($orderGoodsData) > 1) {
                        //         $row['order_delivery_type'] = 2;
                        //     }
                        //     // 发货总数量等于 订单商品数量*分组商品类型数量
                        //     $row['number'] = $orderGoods->quantity_purchased;
                        //     // 发货单查单
                        //     $this->multiBoxGoodsDataPart($row, $orderGoodsData, $orderGoods->quantity_purchased, $orderGoods);
                        // }
                    }
                }
            }
            return CatchResponse::success($orderAfter);
        } catch (\Exception $exception) {
            // $this->orderModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage() . '异常', $code);
        }
    }
    /**
     * 售后订单多箱发货
     */
    public function multiBoxGoodsDataPart(&$row, $orderGoodsData, $total_num, $orderGoods)
    {
        $list = [];
        // 发货单数量
        foreach ($orderGoodsData as $value) {
            for ($i = 1; $i <= $total_num; $i++) {
                $row['length_AS_total'] = $value['length_AS'];
                $row['width_AS_total'] = $value['width_AS'];
                $row['height_AS_total'] = $value['height_AS'];
                $row['weight_AS_total'] = $value['weight_gross_AS'];
                $row['logistics_status'] = 2; // 1-成功发货订单，2-异常发货订
                $row['goods_number'] = 1;
                $row['number'] = 1;
                $row['goods_group_id'] = $value['id'];
                $row['goods_group_name'] = $value['name'];
                $list[] = $row;
            }
        }
        $company = new Company();

        $orderRecords = new orsModel;
        $warehouseData = [
            'warehouse_id' => $row['en_id'],
            'warehouse_fictitious_id' => $row['vi_id']
        ];
        // 商品类型
        $goodsType = $row['goods_type'];
        $orderFee = $this->goodsFee($row['order_code'], $row['company_id'], $row['goods_id'], $row['zone'], $orderGoods->packing_method, $goodsType);
        $listNew = $list[0];
        $listNew = [$listNew];
        if (!$orderRecords->deliveryOther($row, $warehouseData, $list, $row['goods_type'])) {
            throw new \Exception("发货单出库失败");
        } else {
            // 生成正常订单
            foreach ($list as $key => $value) {
                $value['logistics_status'] = 1;
                $value['freight_weight_price'] = $orderFee['freight_weight_price'];
                $value['freight_additional_price'] = $orderFee['freight_additional_price'];
                $value['order_price'] = $orderFee['order_price'] ?? 0;
                $value['postcode_fee'] = $orderFee['postcode_fee'];
                $value['hedge_fee'] = $orderFee['hedge_fee'] ?? 0;
                $value['zone'] = $row['zone'];
                $id = $this->orderModel->createBy($value);

                $orderDeliverProducts = new OrderDeliverProducts;
                $value['product']['order_deliver_id']  = $id;
                $value['product']['order_id']  = $row['order_record_id'];
                $value['product']['transaction_price_currencyid'] = 'USD';
                $value['product']['transaction_price_value'] = $value['transaction_price_value'];
                $value['product']['freight_fee'] = $value['freight_fee'] ?? 0;
                $value['product']['tax_amount_value']  = $value['tax_amount_value'];
                $value['product']['tax_amount_currencyid']  = 'USD';
                $value['product']['type']  = $row['goods_type']; // 1-普通商品 2-配件
                $value['product']['warehouses_id'] = $value['vi_id'];
                $value['product']['goods_group_id'] = $value['goods_group_id'] ?? 0;
                $value['product']['goods_group_name'] = $value['goods_group_name'] ?? '';
                $orderDeliverProducts->createBy($value['product']);
                // 扣除费用 amountDeduction
                // 扣除客户金额
                if ((int)$row['order_type'] == 3) {
                    $amount = bcmul($orderFee['total'], $value['number'], 2);
                    if (!empty((float)$amount)) {
                        $company->amountDeduction($amount, $row['company_id']);
                    }
                }
            }
            // 修改售后订单
            $AfterSaleOrder = new AfterSaleOrder;
            $AfterSaleOrder->where('id', $row['after_order_id'])->update([
                'is_warehousing' => 1,
                'updated_at' => time(), 'storage_id' => $value['vi_id']
            ]);

            // 修改订单商品发货情况
            $orderItemRecords = new OrderItemRecords;
            $orderItemRecords->where([
                'type' => 1, 'order_record_id' => $row['after_order_id'],
                'goods_code' => $row['goods_code']
            ])
                ->update(['is_delivery' => 1]);
        }
    }
    /**
     * 售后订单普通商品配件发货单
     * @param $num 订单数量
     * @param $row 数据
     * @param $merge_num 合并发货数量
     * @param $bcmod 余数
     * @param $orderGoods 商品信息
     */
    public function normalGoodsDataPart(&$row, $num, $bcmod, $merge_num, $orderGoods)
    {
        $arr = [$orderGoods->length_AS, $orderGoods->width_AS, $orderGoods->height_AS];
        $listObj = [
            'length_AS' => $orderGoods->length_AS,
            'width_AS' => $orderGoods->width_AS,
            'height_AS' => $orderGoods->height_AS,
        ];
        $listNew = $this->compareSize($listObj, $arr, $merge_num);
        $list = [];
        for ($i = 0; $i < $num; $i++) {
            $row['number'] = $merge_num;
            $row['length_AS_total'] = $listNew['length_AS'];
            $row['width_AS_total'] = $listNew['width_AS'];
            $row['height_AS_total'] = $listNew['height_AS'];
            $row['weight_AS_total'] = number_format(round(bcmul($orderGoods->weight_gross_AS, $merge_num, 6), 6), 6);
            $row['volume_weight_AS'] = $orderGoods->volume_weight_AS;
            $row['height_AS'] = $orderGoods->height_AS;
            $row['width_AS'] = $orderGoods->width_AS;
            $row['length_AS'] = $orderGoods->length_AS;
            $row['logistics_status'] = 2;
            $list[] = $row;
        }
        if ((int) $bcmod > 0) {
            $listNew2 = $this->compareSize($listObj, $arr, $bcmod);
            $row['number'] = $bcmod;
            $row['length_AS_total'] = $listNew2['length_AS'];
            $row['width_AS_total'] = $listNew2['width_AS'];
            $row['height_AS_total'] = $listNew2['height_AS'];
            $row['weight_AS_total'] = number_format(round(bcmul($orderGoods->weight_gross_AS, $bcmod, 6), 6), 6);
            $row['logistics_status'] = 2;
            $row['volume_weight_AS'] = $orderGoods->volume_weight_AS;
            $row['height_AS'] = $orderGoods->height_AS;
            $row['width_AS'] = $orderGoods->width_AS;
            $row['length_AS'] = $orderGoods->length_AS;
            $list[] = $row;
        }
        $company = new Company();

        $orderRecords = new orsModel;
        $warehouseData = [
            'warehouse_id' => $row['en_id'],
            'warehouse_fictitious_id' => $row['vi_id']
        ];
        // 商品类型 1-商品 2-配件
        $goodsType = $row['goods_type'];
        $goods_id = $row['goods_id'];
        $packing_method_bh = '0';
        if ((int)$row['packing_method_new'] == 2) {
            $goods_id = $row['goods_group_id'];
            $packing_method_bh = '1';
        }
        $orderFee = $this->goodsFee($row['order_code'], $row['company_id'], $goods_id, $row['zone'], $packing_method_bh, $goodsType);
        if (!$orderRecords->deliveryOther($row, $warehouseData, $list, $row['goods_type'])) {
            throw new \Exception("发货单出库失败");
        } else {
            // 生成正常订单
            foreach ($list as $key => $value) {
                $value['logistics_status'] = 1;
                $value['freight_weight_price'] = $orderFee['freight_weight_price'];
                $value['freight_additional_price'] = $orderFee['freight_additional_price'];
                $value['order_price'] = $orderFee['order_price'] ?? 0;
                $value['postcode_fee'] = $orderFee['postcode_fee'];
                $value['hedge_fee'] = $orderFee['hedge_fee'] ?? 0;
                $value['zone'] = $row['zone'];
                $id = $this->orderModel->createBy($value);

                $orderDeliverProducts = new OrderDeliverProducts;
                $value['product']['order_deliver_id']  = $id;
                $value['product']['order_id']  = $row['order_record_id'];
                $value['product']['transaction_price_currencyid'] = 'USD';
                $value['product']['transaction_price_value'] = $value['transaction_price_value'];
                $value['product']['tax_amount_value']  = $value['tax_amount_value'];
                $value['product']['freight_fee'] = $value['freight_fee'];
                $value['product']['tax_amount_currencyid']  = 'USD';
                $value['product']['type']  = $row['goods_type']; // 1-普通商品 2-配件
                $value['product']['warehouses_id'] = $value['vi_id'];
                $value['product']['goods_group_id'] = $row['goods_group_id'] ?? 0;
                $value['product']['goods_group_name'] = $row['goods_group_name'] ?? '';
                $orderDeliverProducts->createBy($value['product']);
                // 扣除费用 amountDeduction
                // 扣除客户金额
                if (((int)$row['order_type']) == 3) {
                    $amount = bcmul($orderFee['total'], $value['number'], 2);
                    if (!empty((float)$amount)) {
                        $company->amountDeduction($amount, $row['company_id']);
                    }
                }
            }
            // 修改售后订单
            $AfterSaleOrder = new AfterSaleOrder;
            $AfterSaleOrder->where('id', $row['after_order_id'])->update([
                'is_warehousing' => 1,
                'updated_at' => time(), 'storage_id' => $value['vi_id']
            ]);

            // 修改订单商品发货情况
            $orderItemRecords = new OrderItemRecords;
            $orderItemRecords->where([
                'type' => 1, 'after_order_id' => $row['after_order_id'],
                'goods_code' => $row['goods_code']
            ])
                ->update(['is_delivery' => 1]);
        }
    }



    /**
     * 导出发货单
     */
    public function importDeliverOrder(Request $request)
    {
        $type = $request->param('delivery_state');
        $res = $this->orderModel->getExportLists($type);
        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        $data = $request->post();
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->orderModel->exportField();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '发货单导出');

        return  CatchResponse::success($url);
    }
    /**
     * 导出拣货单
     */
    public function importPickOrder(Request $request)
    {
        $type = $request->param('delivery_state');
        $res = $this->orderModel->getExportLists($type);

        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        $data = $request->post();
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->orderModel->exportFieldPick();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '拣货单导出');

        return  CatchResponse::success($url);
    }

    /**
     * 异常物流导出
     */
    public function importAbnormalLogistics(Request $request)
    {
        $type = $request->param('delivery_state');
        $res = $this->orderModel->getExportLists($type);

        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        $data = $request->post();
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->orderModel->exportFieldLogistics();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '异常物流单导出');

        return  CatchResponse::success($url);
    }
    /**
     * 导出第三方发货列表 export
     */
    public function exportThirdPartOrder(Request $request)
    {
        $type = $request->param('delivery_state');
        $res = $this->orderModel->getExportLists($type);

        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        $data = $request->post();
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->orderModel->exportFieldThirdPart();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '第三方发货单导出');

        return  CatchResponse::success($url);
    }

    /**
     * 下载第三方模板
     */
    public function template()
    {
        return download(public_path() . 'template/logisticsOrderNoImport.xlsx')->force(true);
    }
    /**
     * 导入第三方物流单号
     */
    public function importLogisticsOrderNo(Request $request, ZipCodeImport $import)
    {
        $file = $request->file();
        $data = $import->read($file['file']);
        $dataList = [];
        foreach ($data as $value) {
            if (!$orderId = $this->orderModel->where(['invoice_no' => $value[0], 'status' => 1])->find()) {
                $dataList['empty'][] = $value[0];
            } else {
                if ((int)$orderId['logistics_type'] == 0) {
                    $dataList['repeat'][] = $value[0];
                } else {
                    $dataList['success'][] = $value[0];
                    $row = [
                        'shipping_code' => $value[1], // 物流运单号
                        'shipping_name' => $value[2], // 物流公司名称
                        'delivery_state' => 3, // 已发货
                        'delivery_process_status' => 2 // 已打印发货单
                    ];
                    $this->orderModel->updateBy($orderId['id'], $row);

                    //判断是否全部发货
                    $odCount = orderModel::where('order_record_id', $orderId['order_record_id'])->count();
                    $hasTrackingCount = orderModel::where('order_record_id', $orderId['order_record_id'])->whereNotIn('delivery_state', '1')->count();
                    $orderStatus = 2; //默认部分发货 如果全部发货单都已经获取ups运单号 则置为全部发货
                    $orderStatus = $odCount == $hasTrackingCount ? 3 : 2;
                    // 获取已打印物流单
                    $deliveryCount = $this->orderModel->where(['order_record_id' => $orderId['order_record_id'], 'order_type_source' => 1])
                        ->where('delivery_process_status', '2')
                        ->whereNotIn('delivery_state', '6')
                        ->count();
                    OrderRecords::where('id', $orderId['order_record_id'])
                        ->update(['status' => $orderStatus, 'print_delivery_num' => $deliveryCount]);
                }
            }
        }
        return CatchResponse::success($dataList);
    }

    /**
     * 导入物流发货单 deliveryOrders/exportLogisticsOrder
     */
    public function importLogisticsOrder(Request $request, ZipCodeImport $import)
    {
        $file = $request->file();
        $data = $import->read($file['file']);
        $dataList = [];
        foreach ($data as $value) {
            if (!$orderId = $this->orderModel->where([
                'invoice_no' => $value[0], 'deliver_type' => 0,
                'delivery_state' => 1, 'status' => 1
            ])->value('id')) {
                $dataList['empty'][] = $value[0];
            } else {
                $dataList['success'][] = $value[0];
                $row = [
                    'shipping_code' => $value[1], // 物流运单号
                    'shipping_name' => $value[2], // 物流公司名称
                    'delivery_state' => 3 // 运输中
                ];
                $this->orderModel->updateBy($orderId, $row);
                //判断是否全部发货
                $odCount = orderModel::where('order_record_id', $orderId)->count();
                $hasTrackingCount = orderModel::where('order_record_id', $orderId)->whereNotIn('delivery_state', '1')->count();
                $orderStatus = 2; //默认部分发货 如果全部发货单都已经获取ups运单号 则置为全部发货
                $orderStatus = $odCount == $hasTrackingCount ? 3 : 2;
                OrderRecords::where('id', $orderId)->update(['status' => $orderStatus]);
            }
        }
        return CatchResponse::success($dataList);
    }

    /**
     * 导出发货单
     */
    public function exportDeliveryOrder(Request $request)
    {
        $type = $request->param('delivery_state');
        $res = $this->orderModel->getExportLists($type);

        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        $data = $request->post();
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->orderModel->exportFieldThirdPart();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '第三方发货单导出');

        return  CatchResponse::success($url);
    }
    /**
     * 确认发货物流类型
     */
    public function updateLogisticsType(Request $request)
    {

        $data = $request->post();
        $ids = implode(',', $data['ids']);
        if (empty($data['ids']) || empty($data['type'])) {
            return CatchResponse::fail('参数不正确');
        }
        if (!in_array($data['type'], [1, 2])) {
            return CatchResponse::fail('参数type值不正确');
        }
        $this->orderModel->whereIn('id', $ids)->update(['logistics_type' => $data['type']]);
        return CatchResponse::success('修改成功');
    }

    /**
     * 客户订单批量发货
     */
    public function ordersDeliverCustomer(Request $request)
    {
        $data = $request->post();
        $ids = $data['ids'];
        $successData = [];
        $failData = [];
        $userMoneyFail = [];
        try {
            // 发货单
            foreach ($ids as $value) {
                // 获取订单信息(未发货)
                $orderRecords = new orsModel;
                $orderData = $orderRecords->where(['id' => $value, 'status' => 1])->find();
                // 订单已发货
                if (!$orderData && $orderData['logistics_status'] != 0) {
                    $failData[] = $value;
                } else {
                    // 查询数据是否存在
                    $idRedis = 'completeOrder|' . $orderData['id'];
                    $idsValue = Cache::get($idRedis);
                    if ($idsValue) {
                        $failData[] = $value;
                    } else {
                        $idsValue = Cache::set($idRedis, $orderData['id'], 2);
                        $successData[] = $value;
                        // 订单商品信息
                        $orderItemRecords = new OrderItemRecords;
                        $orderGoods = $orderItemRecords->alias('g')->field('g.*, pf.*, p.image_url, p.company_id,
                    p.packing_method, p.merge_num, p.code as goods_code1')
                            ->where('order_record_id', $value)
                            ->leftJoin('product_info pf', 'pf.product_id=g.goods_id')
                            ->leftJoin('product p', 'p.id=g.goods_id')
                            ->find();
                        // 查询客户可用余额
                        $company = new Company;
                        $amount = $company->where('id', $orderGoods['company_id'])->value('overage_amount');
                        if ((float)$amount <= 0 && $orderData['order_type'] == 3) {
                            $userMoneyFail[] = $value . '订单编码：' . $orderData['order_no'];
                        } else {
                            // 发货订单参数拼装
                            $row = [
                                'idRedis' => $idRedis,
                                'logistics_type' => 1,
                                'deliver_type' => 0, // 0-自有发货 1-第三方发货
                                'order_type' => $orderData['order_type'],
                                'order_record_id' => $value,
                                'goods_id' => $orderGoods['goods_id'],
                                'goods_code' => $orderGoods['goods_code'],
                                'order_no' => $orderData['order_no'],
                                'order_delivery_type' => 1, // 发货类型 1-整单发货 2-拆分发货
                                'platform_no' => $orderData['platform_no'], // 平台订单编号1
                                'platform_no_ext' => $orderData['platform_no_ext'], // 平台订单编号1
                                // 'shipping_method' => '', // 运输方式
                                'goods_pic' => $orderGoods['image_url'], // 商品缩率图
                                'platform_id' => $orderData['platform_id'], // 平台ID
                                'company_id' => $orderGoods['company_id'], // 所属客户id
                                'shop_basics_id' => $orderData['shop_basics_id'], // 店铺ID
                                'status' => 0, // 待审核
                                'transaction_price_value' => $orderGoods['transaction_price_value'],
                                'tax_amount_value' => $orderGoods['tax_amount_value'],
                                'freight_fee' => $orderGoods['freight_fee'],
                                'creator_id' => $data['creator_id'],
                                'packing_method' => $orderGoods->packing_method
                            ];
                            // 商品id
                            $goodsId = $orderGoods->goods_id;
                            // 普通包装商品
                            $numOrder = 1;
                            if ((int)$orderGoods->packing_method == 1) {
                                // 商品合计发货数量
                                $merge_num = $orderGoods->merge_num;
                                // 商品总数量
                                $total_num = $orderGoods->quantity_purchased;
                                // 余数
                                $bcmod = bcmod($total_num, $merge_num);
                                // 拆分后订单数量
                                $numOrder = floor(bcdiv($total_num, $merge_num, 2));
                                // 当订单商品总数大于 合并发货数量 发货类型 拆分发货
                                if ((int) $total_num > (int) $merge_num) {
                                    $row['order_delivery_type'] = 2;
                                }
                                $this->normalGoodsData($row, $numOrder, $bcmod, $merge_num, $orderGoods);
                            } else {
                                // 多箱包装商品
                                $productGroup = new ProductGroup;
                                $orderGoodsData = $productGroup->where('product_id', $goodsId)->select();
                                // 当订单商品总数大于 合并发货数量 发货类型 拆分发货
                                // if (count($orderGoodsData) > 1) {
                                //     $row['order_delivery_type'] = 2;
                                // }
                                if (count($orderGoodsData) > 1 || (int)$orderGoods['quantity_purchased'] > 1) {
                                    $row['order_delivery_type'] = 2;
                                }

                                // 发货总数量等于 订单商品数量*分组商品类型数量
                                // $row['number'] = $orderGoods->quantity_purchased;
                                // 发货单拆弹
                                $this->multiBoxGoodsData($row, $orderGoodsData, $orderGoods->quantity_purchased, $orderGoods);
                            }
                        }
                    }
                }
            }
            $dataArr = [];
            $dataArr['failData'] = $failData;
            $dataArr['successData'] = $successData;
            $dataArr['userMoneyFail'] = $userMoneyFail;
            return CatchResponse::success($dataArr);
        } catch (\Exception $e) {
            $message = sprintf($e->getCode() . ':' . $e->getMessage() .
                ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            Log::info($message);
            return CatchResponse::fail('操作异常，请稍后再试');
        }
    }
    /**
     * 作废发货单
     */
    public function voidOrder(Request $request, $id)
    {
        try {
            $data = $request->post();
            // 查询订单状态
            $order = $this->orderModel->where(['id' => $id])
                ->where('delivery_process_status', 1) // 未打印
                ->where('order_type_source', 1) // 1-正常订单 2-补货订单
                ->whereNotIn('delivery_state', '5,6')
                ->find();
            if (!$order) {
                return CatchResponse::fail('订单不存在或状态不正确/不能作废', Code::FAILED);
            }
            $count = $this->orderModel->where(['order_record_id' => $order['order_record_id']])
                ->where('delivery_process_status', '2')
                ->count();
            if ($count > 0) {
                return CatchResponse::fail('订单存在已打印发货单不能作废', Code::FAILED);
            }
            // 获取所有的订单订单关联发货单
            $allDeliveryData = $this->orderModel->where(['order_record_id' => $order['order_record_id']])
                ->where('order_type_source', '1') // 正常发货单
                ->whereNotIn('delivery_state', '6')
                ->select()->toArray();
            $this->orderModel->startTrans();
            if ($allDeliveryData && count($allDeliveryData) > 0) {
                foreach ($allDeliveryData as $key => $orderData) {
                    $orderItem = OrderDeliverProducts::where('order_deliver_id', $orderData['id'])->find();
                    $updater_id = $data['creator_id'];
                    $productsAllot = [];
                    // 成功发货单
                    if ((int)$orderData['logistics_status'] == 1) {
                        // 商品
                        if (empty($orderData['goods_group_id'])) {
                            $goodsId = $orderData['goods_code'];
                        } else {
                            $goodsId = $orderData['goods_group_name'];
                        }
                        // $goodsId = $orderData['goods_group_name'] ?? $orderData['goods_code'];
                        if ((int)$orderItem['type'] == 1) {
                            // 库存退回
                            $products = new Product;
                            $productData = $products->where('code', $goodsId)->find();
                            $category = new Category;
                            $categoryData = $category->where('id', $productData['category_id'])->find();
                            $productsAllot[0] = [
                                'goods_id' => $productData['id'], // 多箱商品id
                                'goods_code' => $goodsId, // 多箱分组code
                                'category_name' => $categoryData['parent_name'] . $categoryData['name'],
                                'goods_name' => $productData['name_ch'],
                                'goods_name_en' => $productData['name_en'],
                                'goods_pic' => $productData['image_url'],
                                'packing_method' => $productData['packing_method'],
                                'number' => $orderData['number'],
                                'type' => 1,
                                'batch_no' => $orderItem['batch_no']
                            ];
                        } else {
                            // 库存退回
                            $parts = new Parts;
                            $partsData = $parts->where('id', $orderData['goods_id'])->find();
                            $category1 = new Category;
                            $categoryData1 = $category1->where('id', $partsData['category_id'])->find();
                            $productsAllot[0] = [
                                'goods_id' => (int)$orderData['goods_id'], // 多箱商品id
                                'goods_code' => $orderData['goods_code'], // 多箱分组code
                                'category_name' => $categoryData1['parent_name'] . $categoryData1['name'],
                                'goods_name' => $partsData['name_ch'] ?? '',
                                'goods_name_en' => $partsData['name_en'] ?? '',
                                'goods_pic' => $partsData['image_url'] ?? '',
                                'packing_method' => 1,
                                'number' => $orderData['number'],
                                'type' => 2,
                                'batch_no' => $orderItem['batch_no']
                            ];
                        }
                        // var_dump('$productsAllot', $productsAllot);
                        // exit;
                        // 入库单
                        $warehouseOrders = new WarehouseOrders;
                        $dataWarehouse = [
                            'code' => $warehouseOrders->createOrderNo(),
                            'entity_warehouse_id' => $orderData['en_id'],
                            'virtual_warehouse_id' => $orderData['vi_id'],
                            'source' => 'void',
                            'notes' => '发货单作废退货入库',
                            'audit_status' => 2,
                            'audit_notes' => '自动通过',
                            'audit_by' => $data['creator_id'],
                            'audit_time' => date('Y-m-d H:i:s'),
                            'warehousing_status' => 1,
                            'warehousing_time' => date('Y-m-d H:i:s'),
                            'created_by' => $data['creator_id'],
                            'products' => $productsAllot
                        ];
                        //变更库存 增加库存 // 单入库
                        $idWarehouseStock = $warehouseOrders->createWarehouseOrder($dataWarehouse);
                        $warehouseStock = new WarehouseStock;
                        foreach ($productsAllot as $product) {
                            $warehouseStock->increaseStock(
                                $orderData['en_id'],
                                $orderData['vi_id'],
                                $product['goods_code'],
                                $product['batch_no'],
                                $product['number'],
                                $product['type'],
                                'delivery',
                                $idWarehouseStock ?? $id,
                                $id
                            );
                        }
                    }
                    // 修改发货单状态
                    $this->orderModel->updateBy($orderData['id'], ['delivery_state' => 6, 'updater_id' => $updater_id, 'updated_at' => time()]);
                    // 查询修改关联订单状态
                    //判断是否全部发货
                    // $odCount = orderModel::where('order_record_id', $orderData->order_record_id)->count();
                    // $hasTrackingCount = orderModel::where('order_record_id', $orderData->order_record_id)->whereIn('delivery_state', '6')->count();
                    // if ($odCount == $hasTrackingCount) {
                    // }
                }
                // 修改关联发货单状态
                OrderRecords::where('id', $order->order_record_id)->update([
                    'status' => 1, // 恢复订单状态为 待发货状态
                    'updated_at' => time(),
                    'updater_id' => $updater_id,
                    'is_delivery' => 0,
                    'print_delivery_num' => 0,
                    'logistics_status' => 0 //恢复订单发货状态未发货
                ]);
                // 作废发货单关联 报表
                $reportOrder = new ReportOrder;
                if ($id = $reportOrder->where('order_no', $order['order_no'])->value('id')) {
                    $reportOrder->deleteBy(strval($id), true);
                }
            }
            $this->orderModel->commit();
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $this->orderModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }
    /**
     * 同步物流单号到第三方平台
     * $ids
     */
    public function orderSynchronous(Request $request)
    {
        try {
            $data = $request->post();
            $listData = [];
            if ($data['ids'] && count($data['ids']) > 0) {
                foreach ($data['ids'] as $key => $value) {
                    $orderData = $this->orderModel->where('id', $value)->where('order_type_source', '1')
                        ->whereIn('order_type', '0,4')->find();
                    if (!$orderData) {
                        $listData['fail'][] = $value;
                    } else {
                        // 物流公司名称
                        $shipping_name = strtoupper($orderData['shipping_name']);
                        if (strtolower($shipping_name) == 'fedex') {
                            $shipping_name = 'FedEx';
                        } elseif ($shipping_name == 'USPS_CUBIC_PM' || $shipping_name == 'USPS_FCM') {
                            $shipping_name = 'USPS';
                        }
                        // 物流单号
                        $shipping_code = $orderData['shipping_code'];
                        // 平台编号
                        $platform_no = $orderData['platform_no'];
                        if (!$platform_no) {
                            $listData['fail'][] = $value;
                            continue;
                        }
                        // $platform_no = '100022222-198327432847632765453453465346544365';
                        // 店铺id
                        $shop_basics_id = $orderData['shop_basics_id'];
                        switch ((int)$orderData['platform_id']) {
                            case Code::EBAY:
                                // 店铺ID，第三方订单号，TN号，物流渠道
                                // sync_logistics
                                // $platform_no = $orderData['platform_no'];
                                $ebay = new EbayService();
                                $res = $ebay->shipping($shop_basics_id, $platform_no, $shipping_code, $shipping_name);
                                $this->orderModel->updateBy($value, ['sync_logistics' => 1]);
                                if ($res) {
                                    $listData['success'][] = $orderData['invoice_no'] . '原因' . $res;
                                } else {
                                    $listData['fail'][] = $orderData['invoice_no'] . '原因' . $res;
                                }
                                break;
                            case Code::OVERSTOCK:
                                $overstock = new OverstockService();
                                $data = $this->updateExtendOVERSTOCK($orderData, $platform_no);
                                $number = $data['number'];
                                $extend = $data['extend'];
                                $res = $overstock->shipping($shop_basics_id, $platform_no, $shipping_code, $shipping_name, $extend, $number);
                                if ($res) {
                                    $this->orderModel->updateBy($value, ['sync_logistics' => 1]);
                                    $listData['success'][] = $orderData['invoice_no'] . '原因' . $res;
                                } else {
                                    $listData['fail'][] = $orderData['invoice_no'] . '原因' . $res;
                                }
                                break;
                            case Code::WALMART:
                                // // 查询 item 商品信息
                                // $orderItemRecords = new OrderItemRecords();
                                // $extend = $orderItemRecords->where(['order_record_id' => $orderData['order_record_id'], 'type' => 0])->value('extend_1');
                                $platform_no = $orderData['platform_no'];
                                $walmart = new WalmartService();
                                if (empty($orderData['extend_1'])) {
                                    $extend = $this->updateExtend($orderData);
                                    $sucessData = [];
                                    $failData = [];
                                    if (count($extend) > 0 && $extend !== '') {
                                        foreach ($extend as $val) {
                                            if ($res = $walmart->shipping($shop_basics_id, $platform_no, $shipping_code, $shipping_name, $val)) {
                                                if ($res == true) {
                                                    $sucessData[] = $res;
                                                } else {
                                                    $failData[] = $orderData['invoice_no'] . '原因' . $res;
                                                }
                                            }
                                        }
                                    }
                                    if (count($extend) != count($sucessData) && $extend !== '') {
                                        $listData['fail'][] = $orderData['invoice_no'] . '原因' . implode(',', $failData);
                                    } else {
                                        $this->orderModel->updateBy($value, ['sync_logistics' => 1]);
                                        $listData['success'][] = $orderData['invoice_no'] . '原因' . $res;
                                    }
                                } else {
                                    $sucessData = [];
                                    $failData = [];
                                    if ($orderData['extend_1'] !== '') {
                                        $extend = explode(',', $orderData['extend_1']);
                                    } else {
                                        $listData['fail'][] = $value;
                                        break;
                                    }
                                    foreach ($extend as $val) {
                                        $res = $walmart->shipping($shop_basics_id, $platform_no, $shipping_code, $shipping_name, $val);
                                        if ($res == true) {
                                            $sucessData[] = $res;
                                        } else {
                                            $failData[] = $orderData['invoice_no'] . '原因' . $res;
                                        }
                                    }
                                    if (count($extend) != count($sucessData) && $extend !== '') {
                                        $listData['fail'][] = $orderData['invoice_no'] . '原因' . implode(',', $failData);
                                    } else {
                                        $this->orderModel->updateBy($value, ['sync_logistics' => 1]);
                                        $listData['success'][] = $orderData['invoice_no'] . '原因' . $res;
                                    }
                                }
                                break;
                            case Code::SHOPIFY:
                                $platform_no = $orderData['platform_no_ext'];
                                $shopify = new ShopifyService();
                                $res = $shopify->shipping($shop_basics_id, $platform_no, $shipping_code, $shipping_name);
                                if ($res) {
                                    $this->orderModel->updateBy($value, ['sync_logistics' => 1]);
                                    $listData['success'][] = $orderData['invoice_no'] . '原因' . $res;
                                } else {
                                    $listData['fail'][] = $orderData['invoice_no'] . "平台订单编码不存在";
                                }
                                break;
                            case Code::AMAZON:
                                if ($platform_no) {
                                    $amazon = new AmazonSpService();
                                    // ucwords(strtolower($shipping_name)); 首字母大写
                                    $res = $amazon->shipping($shop_basics_id, $platform_no, $shipping_code, $shipping_name);
                                    if ($res) {
                                        $this->orderModel->updateBy($value, ['sync_logistics' => 1]);
                                        $listData['success'][] = $orderData['invoice_no'] . '原因' . $res;
                                    } else {
                                        $listData['fail'][] = $orderData['invoice_no'];
                                    }
                                } else {
                                    $listData['fail'][] = $orderData['invoice_no'] . "平台订单编码不存在";
                                }
                                break;
                            default:
                                $res = '暂不支持同步';
                                $listData['fail'][] = $orderData['invoice_no'] . '原因' . $res;
                                break;
                        }
                    }
                }
                if (isset($listData['fail'])) {
                    $message = implode(',', $listData['fail']);
                    Log::error('orderSynchronous error' . $message);
                }

                return CatchResponse::success($listData);
            } else {
                return CatchResponse::fail('请传入ids');
            }
        } catch (\Exception $e) {
            return CatchResponse::fail($e->getCode() . ':' . $e->getMessage() .
                ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        }
    }
    /**
     * 更新发货单 OVERSTOCK 
     */
    public function updateExtendOVERSTOCK($orderData, $platform_no)
    {
        $ordersTempModel = new OrdersTempModel();
        $orderItemRecords = new OrderItemRecords();
        $product_code = $orderItemRecords->where(['order_record_id' => $orderData['order_record_id'], 'type' => 0])->value('sku');
        $order = $ordersTempModel->where(['order_no' => $platform_no])->find()['order_info'];
        $extend_1 = 0;
        $number = 0;
        $processedSalesOrderLine = $order['processedSalesOrderLine'];
        if (is_array($processedSalesOrderLine) && !isset($processedSalesOrderLine['salesChannelLineNumber'])) {
            foreach ($processedSalesOrderLine as $key => $value) {
                if ($value['salesChannelSKU'] == $product_code) {
                    $salesChannelLineNumber = $value['salesChannelLineNumber'];
                    $number = $value['quantity'];
                    $this->orderModel->updateBy($orderData['id'], ['extend_1' => $salesChannelLineNumber]);
                    $extend_1 = $salesChannelLineNumber;
                    break;
                }
            }
        } else {
            $this->orderModel->updateBy($orderData['id'], ['extend_1' => $processedSalesOrderLine['salesChannelLineNumber']]);
            $number = $processedSalesOrderLine['quantity'];
            $extend_1 = $processedSalesOrderLine['salesChannelLineNumber'];
        }
        return ['extend' => $extend_1, 'number' => $number];
    }
    /**
     * 更新发货单extend
     */
    public function updateExtend($orderData)
    {
        // 查询 item 商品信息
        $orderItemRecords = new OrderItemRecords();
        $extend = $orderItemRecords->where(['order_record_id' => $orderData['order_record_id'], 'type' => 0])->value('extend_1');
        $extendArr = explode(',', $extend);
        if (!empty($orderData['goods_group_id'])) {
            // 获取相关联发货单
            $orderDelivery = $this->orderModel->field('id, number, order_record_id, extend_1, goods_group_id, goods_id')->where([
                'order_type_source' => '1',
                'order_record_id' => $orderData['order_record_id']
            ])
                ->whereNotIn('delivery_state', '6')
                ->group('goods_group_id')
                ->order('number', 'desc')
                ->select()->toArray();
        } else {
            // 获取相关联发货单
            $orderDelivery = $this->orderModel->field('id, number, order_record_id, extend_1, goods_group_id, goods_id')->where([
                'order_type_source' => '1',
                'order_record_id' => $orderData['order_record_id']
            ])
                ->whereNotIn('delivery_state', '6')
                ->order('number', 'desc')
                ->select()->toArray();
        }

        $extendData = array_chunk($extendArr, $orderDelivery[0]['number']);
        $extend_1 = [];
        foreach ($orderDelivery as $key => $value) {
            $dataKey = !empty($extendData[$key]) ? $extendData[$key] : [1];
            // 更新商品
            $str = implode(',',  $dataKey);
            // 多箱商品
            if (!empty($orderData['goods_group_id'])) {
                $this->orderModel->where([
                    'order_record_id' => $value['order_record_id'],
                    'goods_group_id' => $value['goods_group_id'], 'order_type_source' => '1'
                ])->update(['extend_1' => $str]);
                // 返回更新发货单的extend_1
                if ($value['goods_group_id'] == $orderData['goods_group_id']) {
                    $extend_1 = $dataKey;
                }
            } else {
                // 更新发货单的extend_1 值
                $this->orderModel->updateBy($value['id'], ['extend_1' => $str]);
                // 返回更新发货单的extend_1
                if ($value['id'] == $orderData['id']) {
                    $extend_1 = $dataKey;
                }
            }
        }
        return $extend_1;
    }
    /**
     * 批量确认发货按钮
     * confirmDeliver
     */
    public function confirmDeliverBatch(Request $request)
    {

        $data = $request->post();
        $list = [];
        if ($data['ids']) {
            foreach ($data['ids'] as $key => $value) {
                if ($item = $this->orderModel->findBy($value)) {
                    // 生成报表订单信息
                    $reportOrder = new ReportOrder;
                    if (!$id = $reportOrder->where('order_no', $item['order_no'])->value('id')) {
                        if (!$bool = $reportOrder->saveOrder($item['order_no'])) {
                            $list['fail'][] = $value;
                            continue;
                        }
                    }
                    $this->orderModel->updateBy($value, [
                        'status' => 1,
                        'updater_id' => $data['creator_id'], //审核人id
                        'updated_at' => time()
                    ]);
                    $list['success'][] = $value . $id;
                } else {
                    $list['fail'][] = $value;
                }
            }
            return CatchResponse::success($list);
        } else {
            return CatchResponse::fail('请传入ids');
        }
    }
    /**
     * 异常发货单批量发货
     * warehouse_id 实体仓库
     * warehouse_fictitious_id 虚拟仓库
     * type  1- 自有仓库 2-第三方仓库
     * zone  待确认  （费用计算怎么实现，扣除又怎么搞）物流台阶费用
     * 异常发货单增加批量选择一个仓库功能：
     *1、批量发货页面展示和手工发货弹窗页面展示相同
     *2、批量选择仓库数据来源为：开启状态下的虚拟仓，
     *3、提交时，如果所有发货单商品均有库存，则提示批量发货成功；
     *如果有部分发货单有库存，部分发货单没有库存，提示由于“某某仓库某商品sku1、某商品sku2库存不足”，
     *部分发货单批量发货失败。（部分有库存的可以直接选择仓库成功，转为正常发货单）
     */
    public function batchManualShipment(Request $request)
    {
        $data = $request->post();
        $deliver_type = $data['type']; // 1- 自有仓库 2-第三方仓库
        if (!$data['ids'] || empty($data['ids'])) {
            return CatchResponse::fail('请传入ids');
        }
        if (!$data['warehouse_id'] || !$data['warehouse_fictitious_id'] || !$data['type']) {
            return CatchResponse::fail('参数不全', Code::FAILED);
        }
        $orderRecords = new orderRecords;
        $dataMessage = [
            'failData' => [],
            'success' => []
        ];
        foreach ($data['ids'] as $key => $value) {
            // 查询订单是否存在
            if (!$orderData = $this->orderModel->where(['id' => $value, 'logistics_status' => 2])->find()) {
                $dataMessage['failData'][] = $value;
            } else {
                // 查询数据是否存在
                $idRedis = 'shipmentOrder|' . $value;
                $idsValue = Cache::get($idRedis);
                if ($idsValue) {
                    $dataMessage['failData'][] = $value;
                } else {
                    $warehouses = new Warehouses;
                    // 验证仓库是否可用
                    if (!$warehouse = $warehouses->where('id', $data['warehouse_fictitious_id'])->find()) {
                        $dataMessage['failData'][] = $value . '仓库不存在';
                        Cache::delete($idRedis);
                        continue;
                    }
                    $idsValue = Cache::set($idRedis, $value, 2);
                    $productData = Product::where('id', $orderData['goods_id'])->find();
                    // 商品类型 1-普通 2- 多箱
                    if ((int)$productData['packing_method'] == 1) {
                        $orderData['goods_code'] = $productData['code'];
                    } else {
                        $orderData['goods_code'] = $orderData['goods_group_name'];
                    }
                    $orderItemRecords = new OrderItemRecords;
                    $productDataItem = $orderItemRecords->alias('g')
                        ->where('order_record_id', $orderData['order_record_id'])
                        ->find();
                    if (!empty($orderData['goods_group_id'])) {
                        $goods_id = $orderData['goods_group_id'];
                        $type = 1;
                    } else {
                        $goods_id = $orderData['goods_id'];
                        $type = 0;
                    }
                    $orderBuyerRecords = new OrderBuyerRecords;
                    // 查询订单邮编
                    $orderCode = $orderBuyerRecords->where(['order_record_id' => $orderData['order_record_id'], 'type' => 0])->value('address_postalcode');

                    // 验证是否有邮政zone
                    if ((int)$deliver_type == 1) {
                        $dest = new ZipCode;
                        $destData = $dest->selZipzone($orderCode, $warehouse['zipcode']);
                        if (!$destData) {
                            $dataMessage['failData'][] = $value . '邮编分区不存在';
                            Cache::delete($idRedis);
                            continue;
                        }
                        $data['zone'] = $destData['zone'];
                    } else {
                        $data['zone'] = 0;
                    }
                    // 计算订单费用
                    $orderFee = $this->goodsFee($orderCode, $orderData['company_id'], $goods_id, $data['zone'], $type);

                    $company = new Company();
                    $orderData['transaction_price_value'] = $productDataItem['transaction_price_value'];
                    $orderData['tax_amount_value'] = $productDataItem['tax_amount_value'];
                    $orderData['packing_method'] = $productData['packing_method'];

                    $orderRecords = new orsModel;
                    $list = [$orderData];
                    // $this->orderModel->startTrans();
                    // 商品出库
                    if ($isDelivery = $orderRecords->deliveryOther($orderData, $data, $list)) {
                        $orderData['en_id'] = $data['warehouse_id'];
                        $orderData['vi_id'] = $data['warehouse_fictitious_id'];
                    }

                    if (!$isDelivery) {
                        // $this->orderModel->rollback();
                        $dataMessage['failData'][] = '订单【' . $value . '】,仓库【' . $warehouse['name'] . '】,商品【' . $orderData['goods_code'] . '】不足';
                        Cache::delete($idRedis);
                    } else {
                        $dataMessage['success'][] = $value;
                        // 获取原始订单订单类型
                        $order_type = $orderRecords->where('id', $orderData['order_record_id'])->value('order_type');
                        $this->createdDeliverProducts($list, $value);
                        // 修改发货单发货状态
                        $this->orderModel->where('id', $value)
                            ->update([
                                'status' => 1, 'updater_id' => $data['creator_id'],
                                'updated_at' => time(),
                                'en_id' => $data['warehouse_id'],
                                'vi_id' => $data['warehouse_fictitious_id'],
                                'freight_weight_price' => $orderFee['freight_weight_price'],
                                'freight_additional_price' => $orderFee['freight_additional_price'],
                                'order_price' => $orderFee['order_price'] ?? 0,
                                'postcode_fee' => $orderFee['postcode_fee'],
                                'hedge_fee' => $orderFee['hedge_fee'] ?? 0,
                                'logistics_status' => 1,
                                'zone' => $data['zone'],
                                'deliver_type' => $deliver_type == 1 ? 0 : 1,
                                'logistics_type' => $deliver_type == 1 ? 1 : 0
                            ]); // 发货后转为成功发货单
                        // 扣除客户金额
                        if ((int)$order_type === 3) {
                            $total = bcmul($orderFee['total'], $orderData['number'], 2);
                            if (!empty((float)$total)) {
                                $company->amountDeduction($total, $orderData['company_id']);
                            }
                        }
                        // 生成报表订单信息
                        $reportOrder = new ReportOrder;
                        if (!$reportOrder->where('order_no', $orderData['order_no'])->find()) {
                            $reportOrder->saveOrder($orderData['order_no']);
                        }
                    }
                }
            }
        }
        return CatchResponse::success($dataMessage);
    }

    /**
     * 获取可发货仓库
     */
    public function getWarehouse(Request $request)
    {
        $warehouses = new Warehouses;
        $data = $warehouses->where(['is_active' => 1, 'type' => 2])->select();
        return CatchResponse::success($data);
    }

    /**
     * 修改地址
     */
    public function updateAddress(Request $request, $id)
    {
        $orderBuyerRecords = new OrderBuyerRecords;
        if (!$orderBuyerRecords->where('id', $id)->find()) {
            return CatchResponse::fail('地址不存在不能修改');
        }
        $data = $request->post();
        $data['updated_at'] = time();
        $data['updated_id'] = $data['creator_id'];
        $updateId = $orderBuyerRecords->updateBy($id, $data);
        return CatchResponse::success($updateId);
    }

    /**
     * 批量送达第三方发货单
     * ids 发货单id集合
     */
    public function batchThridOrderDeliver(Request $request)
    {

        $data = $request->post();
        if (!$data['ids']) {
            return CatchResponse::fail('请传入ids');
        }
        $dataArr = [];
        foreach ($data['ids'] as $val) {
            // 查询订单是否存在 1-他有物流单 2-配送中，运输中状态 3-物流单号存在
            if (!$order = $this->orderModel->where(['id' => $val])->find()) {
                $dataArr['fail'][] = $val;
                continue;
            } else {
                // 判断订单状态
                if ((int)$order['delivery_state'] == 5 && (int)$order['delivery_state'] == 6) {
                    $dataArr['fail'][] = $order['invoice_no'];
                    continue;
                }
                // 判断是否为它有物流发货单
                if ((int)$order['logistics_type'] != 2) {
                    $dataArr['fail'][] = $order['invoice_no'];
                    continue;
                }
                // 判断是否有发货单号
                if (empty($order['shipping_code'])) {
                    $dataArr['fail'][] = $order['invoice_no'];
                    continue;
                }
                // 更新状态
                $this->orderModel->updateBy($val, [
                    'delivery_state' => 5,
                    'updated_at' => time(),
                    'updater_id' => $data['creator_id']
                ]);
                $dataArr['success'][] = $order['invoice_no'];
            }
        }
        return CatchResponse::success($dataArr);
    }

    /**
     * 管理员作废已打印发货订单（仅限于用户未发货异常订单）
     */
    public function delOrderPrinted(Request $request, $id)
    {
        try {
            $data = $request->post();
            $userId = $data['creator_id'];
            // if($userId !== config('catch.permissions.super_admin_id')) {
            //     return CatchResponse::fail('只有admin管理员可操作');
            // }
            if (empty($data['type'])) {
                return CatchResponse::fail('请传入操作类型');
            }
            // 查询订单状态
            $orderData = $this->orderModel->where(['id' => $id])
                ->where('delivery_process_status', 2) // 已打印
                // ->where('order_type_source', 1)       // 1-正常订单 2-补货订单
                ->whereNotIn('delivery_state', '5,6')
                ->find();
            if (!$orderData) {
                return CatchResponse::fail('订单不存在或状态不正确/不能作废', Code::FAILED);
            }
            $updater_id = $data['creator_id'];
            $reportOrder = new ReportOrder;
            // 作废
            if ((int)$data['type'] === 1) {
                // 修改发货单状态
                $this->orderModel->updateBy($orderData['id'], ['delivery_state' => 6, 'updater_id' => $updater_id, 'updated_at' => time()]);
                //判断是否全部发货
                $odCount = $this->orderModel->where('order_record_id', $orderData['order_record_id'])->whereNotIn('delivery_state', '6')->count();
                $hasTrackingCount = $this->orderModel->where('order_record_id', $orderData['order_record_id'])->whereNotIn('delivery_state', '1,6')->count();
                if (!empty($odCount)) {
                    $orderStatus = 2; //默认部分发货 如果全部发货单都已经获取ups运单号 则置为全部发货
                    $orderStatus = $odCount == $hasTrackingCount ? 3 : 2;
                    OrderRecords::where('id', $orderData['order_record_id'])->update(['status' => $orderStatus]);
                } else {
                    OrderRecords::where('id', $orderData['order_record_id'])->update([
                        'status' => 1, 'updated_at' => time(),
                        'updater_id' => $updater_id,
                        'is_delivery' => 0,
                        'print_delivery_num' => 0,
                        'logistics_status' => 0 //恢复订单发货状态未发货
                    ]);
                    // 作废发货单关联 报表
                    if ($id = $reportOrder->where('order_no', $orderData['order_no'])->value('id')) {
                        $reportOrder->deleteBy(strval($id), true);
                    }
                }
                return CatchResponse::success('作废成功');
            } elseif ((int)$data['type'] === 2) { // 已收货
                // 修改发货单状态
                $this->orderModel->updateBy($orderData['id'], ['delivery_state' => 5, 'updater_id' => $updater_id, 'updated_at' => time()]);

                return CatchResponse::success('确认收获成功');
            }
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage() . '异常', $code);
        }
    }
}
