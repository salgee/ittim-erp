<?php

/**
 *附加增值费管理
 */

namespace catchAdmin\settlement\controller;

use catchAdmin\basics\excel\ZipCodeImport;
use catchAdmin\basics\model\Company;
use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\finance\model\LogisticsTransportOrder as logisticsTransportOrderModel;
use catchAdmin\basics\model\Shop;
use catchAdmin\permissions\model\Users;
use catchAdmin\settlement\model\ThirdpartTransportOrders;
use catchAdmin\supply\excel\CommonExport;
use catcher\Code;
use catcher\exceptions\FailedException;
use think\facade\Db;

class LogisticsTransportOrder extends CatchController
{
    protected $logisticsTransportOrderModel;
    protected $thirdpartTransportOrderModel;

    public function __construct(LogisticsTransportOrderModel $logisticsTransportOrderModel, ThirdpartTransportOrders $thirdpartTransportOrderModel)
    {
        $this->logisticsTransportOrderModel = $logisticsTransportOrderModel;
        $this->thirdpartTransportOrderModel = $thirdpartTransportOrderModel;
    }

    /**
     * 列表
     * @time 2021年04月22日 15:47
     * @param Request $request
     */
    public function index(Request $request): \think\Response
    {

        $users = new Users;
        $whereOr = [];
        $prowerData = $users->getRolesList();
        if (!$prowerData['is_admin']) {
            if ($prowerData['shop_ids']) {
                $whereOr = [
                    ['shop_id', 'in',  $prowerData['shop_ids']]
                ];
            }
        }

        return CatchResponse::paginate($this->logisticsTransportOrderModel->dataRange([])->whereOr($whereOr)->catchSearch()->paginate()->each(function ($item) {
            $item['shop_name'] = Shop::where('id', $item['shop_id'])->value('shop_name');
        }));
    }

    /**
     * 导出
     *
     * @param Request $request
     * @return \think\response\Json
     */
    public function export(Request $request)
    {
        $data = $request->post();

        $users = new Users;
        $whereOr = [];
        $prowerData = $users->getRolesList();

        if (!$prowerData['is_admin']) {
            if ($prowerData['shop_ids']) {
                $whereOr = [
                    ['shop_id', 'in',  $prowerData['shop_ids']]
                ];
            }
        }

        $res = $this->logisticsTransportOrderModel->dataRange([])->whereOr($whereOr)->catchSearch()->select()->toArray();
        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }



        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->logisticsTransportOrderModel->exportField();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '附加增值费管理');
        return  CatchResponse::success($url);
    }

    /**
     * 费用确认
     *
     * @param Request $request
     * @return \think\response\Json
     */
    public function confirm(Request $request)
    {
        try {
            $data = $request->param();

            if (!isset($data['ids'])) {
                return CatchResponse::fail('请选择附加费用', Code::FAILED);
            }


            foreach ($data['ids'] as $id) {
                $order = $this->logisticsTransportOrderModel->findBy($id);

                if (!$order) {
                    continue;
                }

                if ($order->is_confirm == 1) {
                    continue;
                }

                $company = Company::find($order->company_id);
                $company->amountDeduction($order->surcharge, $order->company_id);
                $this->logisticsTransportOrderModel->updateBy($id, ['is_confirm' => 1]);
            }
        } catch (\Exception $e) {
            return CatchResponse::fail($e->getMessage(), Code::FAILED);
        }

        return CatchResponse::success(true);
    }


    /**
     * 导入第三方费用
     *
     * @param Request $request
     * @param ZipCodeImport $import
     * @param \catcher\CatchUpload $upload
     * @return void
     */
    public function importOrder(Request $request, ZipCodeImport $import, \catcher\CatchUpload $upload)
    {
        try {
            $file = $request->file();
            $data = $import->read($file['file']);
            $user = request()->user();
            $this->thirdpartTransportOrderModel->startTrans();
            $dataObj = [];
            $rows = [];
            foreach ($data as $key => $value) {
                $shipping_price_total = 0;
                for ($i = 13; $i <= 34; $i++) {
                    $shipping_price_total += $value[$i];
                }
                $row = [
                    'send_date' => $value[0],
                    // 发货日期
                    'city' => $value[1],
                    // 联系地址
                    'address' => $value[2],
                    // 收货人邮编
                    'zipcode' => $value[3],
                    // 运输方式
                    'shippment' => $value[4],
                    // 参考号
                    'reference_number' => $value[5],
                    // 跟踪号
                    'tracking_number' => $value[6],
                    // 仓库代码
                    'warehouse_code' => $value[7],
                    // 订单号
                    'order_no' => $value[8],
                    // SKU
                    'sku' => $value[9],
                    // Actual Weight
                    'actual_weight' => $value[10],
                    // Billed Weight
                    'billed_weight' => $value[11],
                    // Zone
                    'zone' => $value[12],
                    // 报价基础运费
                    'shipping_price' => $value[13],
                    // 出库处理费
                    'outbound_price' => $value[14],
                    // Fuel surchage/燃油附加费
                    'fuel_surchage' => $value[15],
                    // Residential Delivery/住宅地址附加费
                    'residential_delivery' => $value[16],
                    // DAS Comm/偏远 地区附加费-商业
                    'das_comm' => $value[17],
                    // DAS Extended Comm/超偏远 地区附加费-商业
                    'das_extended_comm' => $value[18],
                    // DAS resi/偏远 地区附加费-住宅
                    'das_resi' => $value[19],
                    // DAS  Extended  resi/超偏远 地区附加费-住宅
                    'das_extended_resi' => $value[20],
                    // AHS报价
                    'ahs' => $value[21],
                    // // 报价金额
                    'price' => 0,
                    // Address Correction/地址修正
                    'address_correction' => $value[22],
                    // Oversize Charge/超长超尺寸费
                    'oversize_charge' => $value[23],
                    // Peak - Oversize Charge/高峰期超尺寸附加费
                    'peak_oversize_charge' => $value[24],
                    // Weekday Delivery/工作日派送
                    'weekday_delivery' => $value[25],
                    // Direct Signature/签名费
                    'direct_signature' => $value[26],
                    // Unauthorized OS/不可发
                    'unauthorized_os' => $value[27],
                    // Peak - Unauth Charge
                    'peak_unauth_charge' => $value[28],
                    // Courier Pickup Charge/快递取件费
                    'ourier_pickup_charge' => $value[29],
                    // Print Return Label/打印快递面单费用
                    'print_return_label' => $value[30],
                    // Return Pickup Fee/退件费
                    'return_pickup_fee' => $value[31],
                    // NDOC P/U- Auto Comm
                    'ndoc' => $value[32],
                    'date_certain' => $value[33],
                    'return_label' => $value[34],
                    'shipping_price_total' => $shipping_price_total,
                    'created_by' => $user['id']
                ];
                $rows[] = $row;
            }
            $this->thirdpartTransportOrderModel->saveAll($rows);
            $this->thirdpartTransportOrderModel->commit();
            return CatchResponse::success($dataObj);
        } catch (\Exception $e) {
            $this->thirdpartTransportOrderModel->rollback();
            throw new FailedException($e->getMessage());
        }
    }


    /**
     * 第三方费用列表
     *
     * @param Request $request
     * @return void
     */
    public function thirdPartLogisticsFee(Request $request)
    {
        return CatchResponse::paginate($this->thirdpartTransportOrderModel->getList());
    }
}
