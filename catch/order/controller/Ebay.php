<?php

namespace catchAdmin\order\controller;

use catchAdmin\product\model\ProductPresaleInfo;
use catcher\base\CatchController;
use catcher\base\CatchRequest;
use catcher\CatchResponse;
use catcher\exceptions\FailedException;
use catcher\platform\AmazonService;
use catcher\platform\AmazonSpService;
use catcher\platform\OpenCartService;
use catcher\platform\OverstockService;
use catcher\platform\ShopifyService;
use catcher\platform\WayfairService;
use catcher\platform\WalmartService;
use catcher\platform\EbayService;
use catcher\platform\HouzzService;

use \DTS\eBaySDK\Constants;
use \DTS\eBaySDK\Trading\Services;
use \DTS\eBaySDK\Trading\Types;
use \DTS\eBaySDK\Trading\Enums;
use catcher\Utils;
use catcher\Code;
use catchAdmin\report\model\ReportOrder;
use catchAdmin\report\model\ReportOrderAfterSale;
use catchAdmin\order\model\OrderEbayRecords as orderEbayRecordsModel;
use catchAdmin\order\model\OrderEbayItemRecords as orderEbayItemRecordsModel;
use think\facade\Log;
use catcher\base\CatchRequest as Request;


use USPS\RatePackage;

class Ebay extends CatchController
{
    protected $orderEbayRecordsModel;
    protected $orderEbayItemRecordsModel;

    public function __construct(
        orderEbayRecordsModel $orderEbayRecordsModel,
        orderEbayItemRecordsModel $orderEbayItemRecordsModel
    ) {
        $this->orderEbayRecordsModel = $orderEbayRecordsModel;
        $this->orderEbayItemRecordsModel = $orderEbayItemRecordsModel;
    }

    /**
     * 列表
     *
     * @time 2020年02月02日
     * @param CatchRequest $request
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     * @param Request $request
     */
    public function index(Request $request): \think\response\Json
    {

        try {
            $type = $request->param('type');
            switch ((int)$type) {
                case Code::AMAZON:
                    $amazonSp = new AmazonSpService('ListOrders', '2021-07-27 10:18:43');
                    $amazonSp->setShop();
                    // 同步orderTemp的订单到ERP系统的order
                    $amazonSp->syncOrderByAccount([Code::AMAZON]);
                    break;
                case Code::EBAY:
                    $ebay = new EbayService('getOrders');
                    // 拉取订单
                    //                    $ebay->setShop();
                    //                    // 同步orderTemp的订单到ERP系统的order
                    //                    $ebay->syncOrderByAccount([Code::EBAY]);
                    $res = $ebay->shipping(19, '18-07302-08435', '12112121212', 'UPS');
                    print_r($res);
                    exit();
                    break;
                case Code::OPENCART:
                    $opencart = new OpenCartService('getOrderList');
                    // 拉取订单
                    $opencart->setShop();
                    // 同步orderTemp的订单到ERP系统的order
                    $opencart->syncOrderByAccount([Code::OPENCART]);
                    break;
                case Code::OVERSTOCK:
                    $overstock = new OverstockService();
                    // 拉取订单
                    //                    $overstock->setShop();
                    // 同步orderTemp的订单到ERP系统的order
                    $overstock->syncOrderByAccount([Code::OVERSTOCK]);
                    break;
                case Code::SHOPIFY:
                    $shopify = new ShopifyService('getOrders');
                    // 拉取订单
                    //                    $shopify->setShop();
                    //                    // 同步orderTemp的订单到ERP系统的order
                    //                    $shopify->syncOrderByAccount([Code::SHOPIFY]);
                    $res = $shopify->shipping(29, '18-07302-08435', '12112121212', 'UPS');
                    print_r($res);
                    exit();
                    break;
                case Code::WALMART:
                    $walmart = new WalmartService('AllOrdersss', '2021-08-18 14:40:06');
                    //                    // 拉取订单
                    $walmart->setShop();
                    // 同步orderTemp的订单到ERP系统的order
                    //                    $walmart->syncOrderByAccount([Code::WALMART]);
                    //                    $res = $walmart->shipping(20, '1811944610955', '12112121212','UPS');
                    //                    print_r($res);exit();
                    break;
                case Code::WAYFAIR:
                    // WayfairService
                    $wayfair = new WayfairService('AllOrderss');
                    //                    $wayfair->setShop();
                    $wayfair->syncOrderByAccount([Code::WAYFAIR]);
                    break;
                case Code::HOUZZ:
                    $houzz = new HouzzService('getOrders');
                    // 拉取订单
                    $houzz->setShop();
                    // 同步orderTemp的订单到ERP系统的order
                    //                    $houzz->syncOrderByAccount([Code::HOUZZ]);
                    break;
            }

            return CatchResponse::success(true);


            //            $productPresaleInfo = new ProductPresaleInfo();
            //            $productPresaleInfo->createProductPreSale();

            //            $this->usps();
            // $wayfair = new WalmartService('AllOrderss','2021-05-23 00:00:00');
            //            $res = $wayfair->shipping(31, '69451367-1', '12112121212','UPS');
            //            print_r($res);
            //            $wayfair = new ReportOrder();
            //            $wayfair->saveOrder('O2021042200060');
            // $wayfair->setShop();
            //            $wayfair->syncOrderByAccount([Code::WAYFAIR]);

            //            $file = runtime_path().'wayfair/'.date('Ymd').'/outgoing/67283_2021.04.08_366972047_8300.csv';
            //            $contents = file_get_contents($file);
            //            $contents = explode("\r\n", $contents);
            ////            print_r(pathinfo($file)['filename']);exit();
            //            // 定义订单数组
            //            $order = [];
            //            $o = 0; $i = 0;
            //            // 重新组合订单数据
            //            foreach ($contents as $content){
            //                $content = explode("|", $content);
            //                // 订单基本信息
            //                if ($content[0] == 'IH'){
            //                    $o++;// 新订单
            //                    $i = 0;// 重置商品数量
            //                    $order[$o] = $content;
            //                    continue;
            //                }
            //                // 订单商品信息
            //                if ($content[0] == 'ID'){
            //                    $order[$o]['item'][$i] = $content;
            //                    $i++;
            //                    continue;
            //                }
            //            }
            //            print_r($order);exit();
        } catch (\Exception $e) {
            $message = sprintf(" 同步订单，异常信息:【%s】", $e->getCode() . ':' . $e->getMessage() .
                ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            print_r($message);
            exit();
        }

        //        $wayfair->getOrderList();
        //        $wayfair->syncOrderByAccount();
        exit();
        /**
         * 实例化EBay
         */
        $service = new Services\TradingService(array(
            'credentials' => config('catch.ebay.production.credentials'),
            'siteId' => Constants\SiteIds::US
        ));
        /**
         * 实例化EBay的请求对象
         */
        $request = new Types\GetMyeBaySellingRequestType();

        /**
         * 请求参数：
         * 具体可参考 https://developer.ebay.com/Devzone/XML/docs/Reference/eBay/GetOrders.html
         */
        $args = array(
            "OrderStatus"   => "Completed",
            "SortingOrder"  => "Ascending",
            //"OrderRole"     => "Seller",
            "CreateTimeFrom"   => new \DateTime('2021-02-01'),
            "CreateTimeTo"   => new \DateTime('2021-02-04'),
        );
        $getOrders = new Types\GetOrdersRequestType($args);
        $getOrders->RequesterCredentials = new Types\CustomSecurityHeaderType();
        $getOrders->RequesterCredentials->eBayAuthToken = config('catch.ebay.production.authToken');
        $getOrders->IncludeFinalValueFee = true;
        $getOrders->Pagination = new Types\PaginationType();
        $getOrders->Pagination->EntriesPerPage = 100;
        $getOrders->OrderIDArray = new Types\OrderIDArrayType();
        $getOrders->OrderIDArray->OrderID[] = '293965587888-1888011379019';
        $pageNum = 1;
        //循环分页读取
        do {
            $getOrders->Pagination->PageNumber = $pageNum;
            $response = $service->getOrders($getOrders);
            print_r($response->OrderArray->Order[0]);
            exit();
            $this->saveAllOrders($response->OrderArray->Order);
            $pageNum += 1;
        } while ($response->HasMoreOrders && $pageNum <= $response->PaginationResult->TotalNumberOfPages);

        return CatchResponse::success();
    }

    /**
     * EBay订单列表数据入库
     * @param $orderList
     *
     */
    protected function saveAllOrders($orderList)
    {
        // 开启事务
        $this->orderEbayRecordsModel->startTrans();
        foreach ($orderList as $order) {
            // 订单状态为已完成的订单入库
            if ($order->OrderStatus === 'Completed') {
                // 循环入库Ebay订单记录表
                $id = $this->orderEbayRecordsModel->saveRecord($order);
                // 循环入库Ebay订单商品记录表
                $transaction = $this->orderEbayItemRecordsModel
                    ->createDateRecord($id, $order->TransactionArray->Transaction);
                if (!$this->orderEbayItemRecordsModel->insertAllBy($transaction)) {
                    $this->orderEbayRecordsModel->rollback();
                    throw new FailedException('上传失败');
                } else {
                    $this->orderEbayRecordsModel->commit();
                }
            }
        }
    }

    public function usps()
    {

        // Initiate and set the username provided from usps
        $label = new \USPS\OpenDistributeLabel(config('catch.usps.username'));
        $label->setTestMode(true);
        $label->setFromAddress('John', 'Doe', '', '5161 Lankershim Blvd', 'North Hollywood', 'CA', '91601', '# 204');
        $label->setToAddress('Vincent Gabriel', '5440 Tujunga Ave', 'North Hollywood', 'CA', '91601', '707');
        $label->setWeightOunces(1);

        // Perform the request and return result
        $label->createLabel();

        //print_r($label->getArrayResponse());
        print_r($label->getPostData());
        //var_dump($label->isError());

        // See if it was successful
        if ($label->isSuccess()) {
            echo 'Done';
            echo "\n Confirmation:" . $label->getConfirmationNumber();

            $label = $label->getLabelContents();
            if ($label) {
                $contents = base64_decode($label);
                header('Content-type: application/pdf');
                header('Content-Disposition: inline; filename="label.pdf"');
                header('Content-Transfer-Encoding: binary');
                header('Content-Length: ' . strlen($contents));
                echo $contents;
                exit;
            }
        } else {
            echo 'Error: ' . $label->getErrorMessage();
        }
    }
}
