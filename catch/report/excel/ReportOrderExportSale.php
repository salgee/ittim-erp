<?php
/*
 * @Date: 2021-09-27 10:00:23
 * @LastEditTime: 2021-10-20 16:53:44
 */

namespace catchAdmin\report\excel;

use catchAdmin\product\model\Category as categoryModel;
use catcher\library\excel\ExcelContract;
use catchAdmin\order\model\OrderRecords as orderRecordsModel;
use catchAdmin\report\model\ReportOrder as reportOrderModel;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ReportOrderExportSale implements ExcelContract
{
    public $memory = '1024M';

    public function headers(): array
    {
        // TODO: Implement headers() method.
        $all = [
            '店铺', '平台名称', 'SKU', '中文名称', '产品分类', '订单类型', '币别', '单价', '销售数量', '销售总金额',
            '上月销售数量', '上月销售总金额', '上月同期增长率', '动销率', '库转'
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
        return $reportOrderModel->getSaleOrderList('export');
    }

    public function keys(): array
    {
        // 筛选字段
        // $field = explode(',', request()->param('field'));
        $field = array_column(request()->param('exportField'), 'filed');

        $all = [
            'shop_name', 'platform', 'goods_code', 'name_ch', 'category_name', 'order_type', 'currency',
            'price', 'sales_numer', 'sales_amount', 'pre_month_sales_numer', 'pre_month_sales_amount',
            'pre_month_growth_rate', 'turnover_rate', 'stock_transfer'
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
            'A1:W1', '销售报表', Alignment::HORIZONTAL_CENTER
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
