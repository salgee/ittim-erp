<?php
/*
 * @Version: 1.0

 * @Date: 2021-03-22 10:03:32
 * @LastEditors:
 * @LastEditTime: 2021-03-22 17:32:44
 * @Description:
 */

namespace catchAdmin\supply\excel;

use catcher\Utils;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class PurchaseContractExport {

    public function title () {
        return [
            [
                'title' => 'ID',
                'filed' => 'id',
            ],
            [
                'title' => '合同编码',
                'filed' => 'code',
            ],
            [
                'title' => '所属供应商',
                'filed' => 'supply_name',
            ],
            [
                'title' => '采购总金额',
                'filed' => 'amount',
            ],
            [
                'title' => '审核状态',
                'filed' => 'audit_status_text',
            ],
            [
                'title' => '转出运状态',
                'filed' => 'transshipment_text',
            ],
            [
                'title' => '创建人',
                'filed' => 'created_by_name',
            ],
            [
                'title' => '创建时间',
                'filed' => 'created_at',
            ],
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




    public function export ($res, $exportFiled) {
        $newExcel = new Spreadsheet();  //创建一个新的excel文档
        $objSheet = $newExcel->getActiveSheet();  //获取当前操作sheet的对象
        $objSheet->setTitle('采购合同');  //设置当前sheet的标题

        $exportFiled = $this->getTitle($exportFiled);
        $start = 'A';
        foreach ($exportFiled as $key => $item) {

            //设置第一栏的标题
            $objSheet->setCellValue($start . '1', $item['title']);

            //设置宽度为true,不然太窄了
            $newExcel->getActiveSheet()->getColumnDimension($start)->setAutoSize(true);
            $start++;
        }


        foreach ($res as $k => $val) {

            $k = $k + 2;
            $start = 'A';
            foreach ($exportFiled as $v) {


                $value = $val[$v['filed']];
                if (is_array($val[$v['filed']])) {
                    $value = implode(", ", $val[$v['filed']]);
                }

                $objSheet->setCellValue($start . $k, $value);
                $start++;
            }
        }

        $name = "采购合同" . date('Ymdhis') . ".xlsx";
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
