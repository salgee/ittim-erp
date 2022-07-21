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


class ZipCodeImport
{

    /**
     * 上传邮编,上传物流应付账款
     *
     * @time 2021年04月08日
     * @return array
     */
    public function read($file)
    {
        return $this->loadFile($file);
    }

    /**
     * 上传商品
     * @time 2021年04月17日
     * @return array
     */
    public function product($file)
    {
        //实例化阅读器对象
        $reader = IOFactory::createReader('Xlsx');
        try {
            if (!$reader->canRead($file)) {
                throw new \Exception('只支持导入Excel文件！');
            }
            $roleId = request()->user()->getRoles()[0]['id'];
            // 客户上传商品
            if ($roleId == config('catch.permissions.company_role')) {
                // 设置读取指定的sheet
                $reader->setLoadSheetsOnly(['客户商品','客户多箱包装商品']);
            }else{
                // 设置读取指定的sheet
                $reader->setLoadSheetsOnly(['内部商品','内部多箱包装商品']);
            }
            // 将excel读取到到$spreadsheet对象中
            $spreadsheet = $reader->load($file);
            $res = array();
            #读取多个表格内容数据
            foreach ($spreadsheet->getWorksheetIterator() as $key => $worksheet) {
                $list = [];
                // 每个sheet都只从第三行开始处理,即去掉每一列的标题
                if($worksheet->getHighestRow() >= 3) {
                    foreach ($worksheet->getRowIterator(3) as $keys => $row) {
                        $tmp = array();
                        // 获取每一行数据
                        foreach ($row->getCellIterator() as $cell) {
                            $tmp[] = trim($cell->getFormattedValue());
                        }
                        $list[$row->getRowIndex()] = $tmp;
                    }
                }
                $this->filterData($list);
                $res[$key] = $list;
            }
            return $res;
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            $message = sprintf("商品导入有误，错误信息:【%s】"
                , $e->getCode(). ':'. $e->getMessage().
                ' in '. $e->getFile(). ' on line '. $e->getLine());
            Log::info($message);
            throw new FailedException($message);
        }
    }

    /**
     * 上传订单
     * @param $file
     * @time 2021年04月27日
     */
    public function order($file){
        return $this->loadFile($file, '订单列表',3);
    }

    /**
     * 导入亚马逊订单用户缺失地址
     * @param $file
     * @time 2021年06月29日
     */
    public function orderAmazon($file){
        return $this->loadFile($file, '亚马逊地址',2);
    }

    /**
     * 根据单一sheet读取Excel内容
     * @param $file
     * @param string $sheet
     * @param int $line 起始行
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function loadFile($file, $sheet = '', $line = 2){
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
            $sheet = $spreadsheet->getActiveSheet();
            $res = array();
            foreach ($sheet->getRowIterator($line) as $row) {
                $tmp = array();
                foreach ($row->getCellIterator() as $cell) {
                    $tmp[] = $cell->getFormattedValue();
                }
                $res[$row->getRowIndex()] = $tmp;
            }
            $this->filterData($res);
            return $res;
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

    /**
     * 上传商品
     * @time 2020年09月08日
     * @return array
     */
    public function readTwo($file)
    {
        //导入excel文件内容
        $reader = IOFactory::createReaderForFile($file);
        // $reader->setReadDataOnly(true);
        try {
            $spreadsheet = $reader->load($file);
            // 获取表格工作区域（sheet）数量
            // $sheetCount = $spreadsheet->getSheetCount();
            $res = array();
            #读取多个表格内容数据
            foreach ($spreadsheet->getWorksheetIterator() as $key => $worksheet) {
                $list = [];
                foreach ($worksheet->getRowIterator() as $keys => $row) {
                    // 只从第二行开始处理,即去掉每一列的标题
                    if ($row->getRowIndex() < 2) {
                        continue;
                    }
                    $tmp = array();
                    // 获取每一行数据
                    foreach ($row->getCellIterator() as $cell) {
                        $tmp[] = $cell->getFormattedValue();
                    }
                    $list[] = $tmp;
                }
                $res[$key] = $list;
           }
            return $res;
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            throw new FailedException($e->getMessage());
        }
    }

    /**
     * 多箱包装商品数据导入（客户模板）
     */
    public function productGroupTest($file)
    {
        return $this->loadFile($file, '多箱包装', 2);
    }

    /**
     * 组合商品数据导入（客户模板）
     */
    public function productCombination($file)
    {
        return $this->loadFile($file, '组合套餐', 2);
    }
}
