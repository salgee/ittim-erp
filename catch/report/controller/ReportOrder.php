<?php

namespace catchAdmin\report\controller;

use catchAdmin\product\model\Product;
use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\report\model\ReportOrder as reportOrderModel;
use catchAdmin\report\excel\ReportOrderExport;
use catchAdmin\report\excel\ReportOrderExportFBA;
use catchAdmin\report\excel\ReportOrderExportFBM;
use catchAdmin\report\excel\ReportOrderExportSale;
use catcher\Code;
use catcher\library\excel\Excel;
use catcher\Utils;
use think\facade\Db;
use think\response\Json;
use Carbon\Carbon;
use catchAdmin\product\model\ProductPrice;

class ReportOrder extends CatchController
{
    protected $reportOrderModel;

    public function __construct(ReportOrderModel $reportOrderModel)
    {
        $this->reportOrderModel = $reportOrderModel;
    }

    /**
     * All列表
     * @time 2021年04月21日 16:25
     * @param Request $request
     */
    public function index(Request $request): \think\Response
    {
        return CatchResponse::paginate($this->reportOrderModel->getOrderList('all'));
    }

    /**
     * FBM列表
     * @param Request $request
     * @return \think\Response
     */
    public function fbmList(Request $request): \think\Response
    {
        return CatchResponse::paginate($this->reportOrderModel->getOrderList('fbm'));
    }

    /**
     * FBA列表
     * @param Request $request
     * @return \think\Response
     */
    public function fbaList(Request $request): \think\Response
    {
        return CatchResponse::paginate($this->reportOrderModel->getOrderList('fba'));
    }


    /**
     * 读取
     * @time 2021年04月21日 16:25
     * @param $id
     */
    public function read($id): \think\Response
    {
        return CatchResponse::success($this->reportOrderModel->findBy($id));
    }

    /**
     * 导出
     * @param Excel $excel
     * @param ReportOrderExport $reportOrderExport
     * @return \think\response\Json
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function export(Excel $excel, ReportOrderExport $reportOrderExport)
    {
        $data = $excel->save($reportOrderExport, Utils::publicPath('exports/reportOrder'));
        return CatchResponse::success($data['url']);
    }

    /**
     * 导出FBA
     * @param Excel $excel
     * @param ReportOrderExportFBA $reportOrderExportFBA
     * @return \think\response\Json
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportFBA(Excel $excel, ReportOrderExportFBA $reportOrderExportFBA)
    {
        $data = $excel->save($reportOrderExportFBA, Utils::publicPath('exports/reportOrder'));
        return CatchResponse::success($data['url']);
    }

    /**
     * 导出FBM
     * @param Excel $excel
     * @param ReportOrderExportFBM $reportOrderExportFBM
     * @return \think\response\Json
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportFBM(Excel $excel, ReportOrderExportFBM $reportOrderExportFBM)
    {
        $data = $excel->save($reportOrderExportFBM, Utils::publicPath('exports/reportOrder'));
        return CatchResponse::success($data['url']);
    }

    /**
     * 导出销售报表
     * @param Excel $excel
     * @param ReportOrderExportSale $reportOrderExportSale
     * @return \think\response\Json
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function exportSale(Excel $excel, ReportOrderExportSale $reportOrderExportSale)
    {
        $data = $excel->save($reportOrderExportSale, Utils::publicPath('exports/reportOrder'));
        return CatchResponse::success($data['url']);
    }

    /**
     * 销售统计
     *
     * @param Request $request
     * @return \think\response\Json
     */
    public function salseReport(Request $request)
    {
        return $this->reportOrderModel->getSaleOrderList('list');
    }


    /**
     * 修正订单采购基准价格取值
     */
    public function orderPriceFix(Request $request)
    {
        $data = $request->param();
        $productPrice = new ProductPrice();
        $userId = $data['creator_id'];
        $product = new Product();

        // 查询订单 fba
        if ((int)$data['type'] == 1) {
            $order = $this->reportOrderModel->where('order_type', '=', 5)
                ->where('updated_id', '0')
                ->select();
            foreach ($order as $key => $value) {
                $id = $product->where('code', $value['product_sku'])->value('id');
                $productPrices = $productPrice->where(['product_id' => $id, 'is_status' => 1, 'status' => 1])->value('purchase_benchmark_price');
                // $this->reportOrderModel->updateBy($value['id'], ['purchase_amount' => $productPrices, 'updated_id' => $userId]);
                if (!empty($productPrices)) {
                    $this->reportOrderModel->updateBy($value['id'], ['purchase_amount' => $productPrices, 'updated_id' => $userId]);
                } else {
                    $this->reportOrderModel->updateBy($value['id'], ['purchase_amount' => '0', 'updated_id' => $userId]);
                }
            }
        }
        // fam
        if ((int)$data['type'] == 2) {
            $order = $this->reportOrderModel->where('order_type', '<>', 5)
                ->where('updated_id', '0')
                ->select();
            foreach ($order as $key => $value) {
                $id = $product->where('code', $value['product_sku'])->value('id');
                $productPrices = $productPrice->where(['product_id' => $id, 'is_status' => 1, 'status' => 1])->value('purchase_benchmark_price');
                if (!empty($productPrices)) {
                    $this->reportOrderModel->updateBy($value['id'], ['purchase_amount' => $productPrices, 'updated_id' => $userId]);
                } else {
                    $this->reportOrderModel->updateBy($value['id'], ['purchase_amount' => '0', 'updated_id' => $userId]);
                }
            }
        }
        return CatchResponse::success(true);
    }
}
