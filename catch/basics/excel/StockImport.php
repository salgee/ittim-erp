<?php
/*
 * @Version: 1.0

 * @Date: 2021-03-23 14:10:17
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-05-24 16:03:39
 * @Description:
 */

namespace catchAdmin\basics\excel;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\IOFactory;
use catcher\exceptions\FailedException;
use think\facade\Log;


class StockImport
{

    /**
     *
     *
     * @time 2021年04月08日
     * @return array
     */
    public function read($file)
    {
        return $this->loadFile($file);
    }




    /**
     * 根据单一sheet读取Excel内容
     * @param $file
     * @param string $sheet
     * @param int $line 起始行
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function loadFile($file, $sheet = '', $line = 1){
        //实例化阅读器对象
        $reader = IOFactory::createReader('Xlsx');
        // $reader->setReadDataOnly(true);
        try {
            if (!$reader->canRead($file)) {
                throw new \Exception('只支持导入Excel文件！');
            }
            // 设置读取指定的sheet
            if (!empty($sheet)){
                $reader->setLoadSheetsOnly([$sheet]);
            }
            $spreadsheet = $reader->load($file);
            #读取表格内容数据

            $sheetCount = $spreadsheet->getSheetCount();

            $data = [];
            for($i=0; $i< $sheetCount; $i++) {
                $sheet = $spreadsheet->getSheet($i);
                $res = array();
                foreach ($sheet->getRowIterator($line) as $row) {
                    $tmp = array();
                    foreach ($row->getCellIterator() as $cell) {
                        $tmp[] = $cell->getFormattedValue();
                    }
                    $res[$row->getRowIndex()] = $tmp;
                }
                $this->filterData($res);
                $data[] =  $res;
            }
            return $data;

        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            throw new FailedException($e->getMessage());
        }
    }

    /**
     * 过滤空数据
     * @param $row_data
     */
    private function filterData(&$row_data)
    {
        foreach ($row_data as $key => $item){
            $is_null = false;
            foreach ($item as $iv) {
                if(!empty(trim($iv))) {
                    $is_null = true;    break;
                }
            }
            if($is_null == false ) unset($row_data[$key]);
        }
    }
}
