<?php

/*通用导出类
 * @Version: 1.0

 * @Date: 2021-03-22 10:03:32
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-03-22 17:32:44
 * @Description:
 */

namespace catchAdmin\warehouse\excel;

use catcher\Utils;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class CommonExport
{

    public function export($res, $exportField, $fileName)
    {
        $newExcel = new Spreadsheet();  //创建一个新的excel文档
        $objSheet = $newExcel->getActiveSheet();  //获取当前操作sheet的对象
        $objSheet->setTitle($fileName);  //设置当前sheet的标题
        $start = 'A';
        foreach ($exportField as $key => $item) {

            //设置第一栏的标题
            $objSheet->setCellValue($start . '1', $item['title']);

            //设置宽度为true,不然太窄了
            $newExcel->getActiveSheet()->getColumnDimension($start)->setAutoSize(true);

            $start++;
        }


        $i = 2;
        foreach ($res as $k => $val) {
            $start = 'A';
            foreach ($exportField as $v) {

                if (in_array($v['filed'], ['goods_name', 'goods_code', 'number', 'label_price', 'pallet_number', 'pallet_price', 'outbound_price'])) {
                    $j = $i;
                    if ($val['products']) {
                        foreach ($val['products'] as $p) {
                            $value = $p[$v['filed']];
                            // $objSheet->setCellValue($start . $j, $value."\t");
                            $objSheet->setCellValueExplicit($start . $j, $value, DataType::TYPE_STRING);

                            $j++;
                        }
                    }
                    if ($val['parts']) {
                        foreach ($val['parts'] as $p) {
                            $value = $p[$v['filed']];
                            // $objSheet->setCellValue($start . $j, $value."\t");
                            $objSheet->setCellValueExplicit($start . $j, $value, DataType::TYPE_STRING);

                            $j++;
                        }
                    }
                } else {
                    $value = $val[$v['filed']];
                    if (is_array($val[$v['filed']])) {
                        $value = implode(", ", $val[$v['filed']]);
                    }
                    // $objSheet->setCellValue($start . $i, $value."\t");
                    $objSheet->setCellValueExplicit($start . $i, $value, DataType::TYPE_STRING);
                    if (count($val['products']) > 0) {
                        $dataArr = $val['products'];
                    }
                    if (count($val['parts']) > 0) {
                        $dataArr = $val['parts'];
                    }
                    $objSheet->mergeCells($start . $i . ':' . $start . ($i + count($dataArr) - 1));
                }
                $start++;
            }
            if (count($val['products']) > 0) {
                $dataList = $val['products'];
            }
            if (count($val['parts']) > 0) {
                $dataList = $val['parts'];
            }

            $i = $i + count($dataList);
        }

        $name = $fileName . date('Ymdhis') . ".Csv";
        $path = Utils::publicPath('exports') . $name;

        ob_start();
        $objWriter = IOFactory::createWriter($newExcel, 'Csv');
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
