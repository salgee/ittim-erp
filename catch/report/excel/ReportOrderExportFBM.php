<?php
/*
 * @Date: 2021-09-27 10:00:23
 * @LastEditTime: 2021-11-25 16:16:45
 */

namespace catchAdmin\report\excel;

use catchAdmin\product\model\Category as categoryModel;
use catcher\library\excel\ExcelContract;
use catchAdmin\order\model\OrderRecords as orderRecordsModel;
use catchAdmin\report\model\ReportOrder as reportOrderModel;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReportOrderExportFBM implements ExcelContract
{
    public $memory = '1024M';

    public function headers(): array
    {
        $all = [
            '下单时间', '订单编号', '平台订单编号', 'T/N', '物流公司', '店铺', '平台', '平台SKU', '系统SKU',
            'SKU中文名称', '产品类别', '数量', '销售金额', '税费', '采购基准价', '海运费', '关税', '订单处理费', '仓储费',
            '快递费', '快递增值附加费', '售后类型', '售后产生费用', '备注'
        ];
        $title = [];
        foreach ($this->keys() as $key => $value) {
            $title[] = $all[$key];
        }
        return $title;
    }

    public function sheets()
    {
        // 导出数据
        $reportOrderModel = new reportOrderModel();
        $reports = $reportOrderModel->getOrderList('fbm', 'export');
        foreach ($reports as &$report) {
            // 采购价+单位
            // $report->purchase_amount = $report->purchase_amount.'/'.$report->purchase_amount_currencyid;
            // $report->purchase_amount = $report->purchase_amount;
            $report->storage_fee = ($report->storage_fee * $report->quantity);
            $report->purchase_amount = ($report->purchase_amount * $report->quantity);

            // 遍历数据添加一级商品分类
            $report->category_name = $report->category_parent_name . '-' . $report->category_name;
        }
        return $reports;
    }

    public function keys(): array
    {
        // 筛选字段
        // $field = explode(',', request()->param('field'));
        $field = array_column(request()->param('exportField'), 'filed');
        $all = [
            'created_at', 'order_no', 'platform_no', 'shipping_code', 'shipping_company', 'shop_name',
            'platform_name', 'platform_sku', 'product_sku', 'product_name', 'category_name', 'quantity', 'price_amount',
            'tax_amount', 'purchase_amount', 'freight_fee', 'tariff_fee', 'order_operation_fee', 'storage_fee', 'express_fee',
            'express_surcharge_fee', 'type', 'amount', 'remark'
        ];
        $all = count(array_intersect($all, $field)) == 0 ? $all : array_intersect($all, $field);
        return $all;
    }

    /**
     * 设置导出标题
     * @return array
     */
    public function setTitle()
    {
        return [
            'A1:W1', 'FBM报表', Alignment::HORIZONTAL_CENTER
        ];
    }

    /**
     * 设置开始行
     * @return int
     */
    public function setRow()
    {
        return 2;
    }
}
