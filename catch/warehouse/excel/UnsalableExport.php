<?php
/*
 * @Version: 1.0

 * @Date: 2021-03-22 10:03:32
 * @LastEditors:
 * @LastEditTime: 2021-03-22 17:32:44
 * @Description:
 */

namespace catchAdmin\warehouse\excel;

use catcher\Utils;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class UnsalableExport {

    public function title () {
        return [
          '实体仓',
          '库存所属方',
          '系统SKU',
          '中文名称',
          '分类',
          '采购单价（RMB）',
          '采购单价（USD）',
          '采购总价',
          '海运费',
          '总关税',
          '供应商',
          '总体积',
          '即时库存',
          '库存账龄'
        ];
    }

    public function secondTitle () {
        return [
          '0-30天',
          '31-60天',
          '61-90天',
          '91-120天',
          '121-150天',
          '151-360天',
          '1年-2年',
          '2年以上',
        ];
    }

    public function dateFiled() {
            return [
                'entity_warehouse',
                'virtual_warehouse',
                'goods_code',
                'goods_name_ch',
                'category_name',
                'purchase_price_rmb',
                'purchase_price_usd',
                'amount',
                'shippfee',
                'tax_fee',
                'supply',
                'volume',
                'number',
                'one_months',
                'two_months',
                'three_months',
                'four_months',
                'five_months',
                'one_year',
                'two_year',
                'three_year'
            ];
    }



    public function getTitle($data) {
          $title = $this->title();

          if (empty($data)) {
              return $title;
          }

          $res  = [];
          foreach ($data AS $val) {
              foreach ($title AS $v) {
                  if ($val == $v['title']) {
                      $res[] = $v;
                  }
              }
          }
          return $res;
    }




    public function export ($res) {
        $newExcel = new Spreadsheet();  //创建一个新的excel文档
        $objSheet = $newExcel->getActiveSheet();  //获取当前操作sheet的对象
        $objSheet->setTitle('库存预警（滞销）');  //设置当前sheet的标题

        $exportFiled = $this->title();
        $start = 'A';
        foreach ($exportFiled as $key => $item) {
            //设置第一栏的标题
            $objSheet->setCellValue($start . '1', $item);

            //设置宽度为true,不然太窄了
            $newExcel->getActiveSheet()->getColumnDimension($start)->setAutoSize(true);
            $start++;
        }
        $newExcel->getActiveSheet()->mergeCells('N1:U1');
        $newExcel->getActiveSheet()->getStyle('N1:U1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);


        $start = 'N';
        foreach ($this->secondTitle() as $key => $item) {
            //设置第一栏的标题
            $objSheet->setCellValue($start . '2', $item);

            //设置宽度为true,不然太窄了
            $newExcel->getActiveSheet()->getColumnDimension($start)->setAutoSize(true);
            $start++;
        }


        foreach ($res as $k => $val) {

            $k = $k + 3;
            $start = 'A';
            foreach ($this->dateFiled() as $v) {
                $value = $val[$v];
                $objSheet->setCellValue($start . $k, $value);
                $start++;
            }
        }

        $name = "库存预警（滞销）" . date('Ymdhis') . ".xlsx";
        $path = Utils::publicPath('exports') . $name;

        ob_start();
        $objWriter = IOFactory::createWriter($newExcel, 'Xlsx');
        $objWriter->save($path);
        //释放内存
        $newExcel->disconnectWorksheets();
        unset($newExcel);
        ob_end_flush();

        $url = config('filesystem.disks.local')['domain']
               . '/'
               . str_replace('\\', '/', str_replace(Utils::publicPath(), '', $path));
        return $url;
    }
}
