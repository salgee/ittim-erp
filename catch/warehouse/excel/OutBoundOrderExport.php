<?php

namespace catchAdmin\warehouse\excel;

use catchAdmin\warehouse\model\OutboundOrders as outboundOrdersModel;
use catcher\library\excel\ExcelContract;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class OutBoundOrderExport implements ExcelContract
{
    public $memory = '2048M';

    public function headers(): array
    {
        // TODO: Implement headers() method.
        $all = [
            '出库单号', '出库实体仓', '出库虚拟仓', '审核状态', '商品名称', '商品SKU', '商品数量', '创建人', '创建时间'
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
        $outboundOrdersModel = new outboundOrdersModel();
        $reports = $outboundOrdersModel->getExportOrderList();
//        print_r(count($reports));exit();
        foreach ($reports as &$report) {
            foreach ($report['products'] as $product) {
//                print_r($product);exit();
                $report['number'] = $product->number;
                $report['goods_name'] = $product->goods_name;
                $report['goods_code'] = $product->goods_code;
            }
            unset($report['products']);
        }
//        print_r(count($reports));exit();
//        print_r($reports);exit();
        return $reports;
    }

    public function keys():array
    {
        // 筛选字段
         $field = explode(',', request()->param('field'));
//        $field = array_column(request()->param('exportField'), 'filed');
        $all = [
            'code', 'entity_warehouse', 'virtual_warehouse', 'audit_status_text', 'goods_name', 'goods_code', 'number',
            'created_by_name', 'created_at'
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
            'A1:I1', '出库单', Alignment::HORIZONTAL_CENTER
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
