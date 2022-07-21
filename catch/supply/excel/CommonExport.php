<?php
/*
 * @Version: 1.0
 * @Date: 2021-05-31 09:26:24
 * @LastEditTime: 2022-01-20 11:13:22
 * @Description:
 */

/*通用导出类
 * @Version: 1.0

 * @Date: 2021-03-22 10:03:32
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-03-22 17:32:44
 * @Description:
 */

namespace catchAdmin\supply\excel;

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


        foreach ($res as $k => $val) {

            $k = $k + 2;
            $start = 'A';
            foreach ($exportField as $v) {


                $value = $val[$v['filed']];
                if (is_array($val[$v['filed']])) {
                    $value = implode(", ", $val[$v['filed']]);
                }
                if ($v['filed'] == 'platform_no' || $v['filed'] == 'bl_no' || $v['filed'] == 'shipping_code') {
                    // 防止科学计数
                    // $objSheet->setCellValue($start . $k, $value . "\t");
                    $objSheet->setCellValueExplicit($start . $k, $value, DataType::TYPE_STRING);
                } else {
                    $objSheet->setCellValue($start . $k, $value);
                }

                $start++;
            }
        }
        // 如果这是csv  DataType::TYPE_STRING 将失效
        $name = $fileName . date('Ymdhis') . ".xlsx";
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
