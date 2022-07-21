<?php

/*通用导出类
 * @Version: 1.0

 * @Date: 2021-03-22 10:03:32
 * @LastEditors:
 * @LastEditTime: 2021-03-22 17:32:44
 * @Description:
 */

namespace catchAdmin\product\excel;

use catcher\Utils;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class CommonExportCombination
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
      if (substr_count($item['title'], '产品图片') >= 1) {
        $newExcel->getActiveSheet()->getColumnDimension($start)->setWidth(15);
      } else {
        $newExcel->getActiveSheet()->getColumnDimension($start)->setAutoSize(true);
      }
      $start++;
    }


    foreach ($res as $k => $val) {
      $k = $k + 2;
      $start = 'A';
      foreach ($exportField as $v) {
        $value = $val[$v['filed']];
        if (is_array($val[$v['filed']])) {
          $value = implode(",", $val[$v['filed']]);
        }

        if ($k > 2) { // 大于第二条数据进行判断
          if ($res[$k - 3]['code'] != $val['code']) {
            if (substr_count($v['title'], '产品图片') >= 1) {
              self::createdImage($k, $start, $objSheet, $newExcel, $val[$v['filed']]);
              $start++;
            } else {
              $objSheet->setCellValue($start . $k, $value);
              $start++;
            }
          } else {
            // 判断是否需要合并单元格
            if (
              substr_count($v['title'], '商品SKU') < 1 && substr_count($v['title'], '商品中文名称') < 1
              && substr_count($v['title'], '商品包装方式') < 1
              && substr_count($v['title'], '商品分类') < 1
              && substr_count($v['title'], '基准价格') < 1
              && substr_count($v['title'], '商品数量') < 1
              && substr_count($v['title'], '创建时间') < 1
            ) {
              $objSheet->mergeCells($start . ($k - 1) . ':' . $start . $k);
            }
            $objSheet->setCellValue($start . $k, $value);
            $start++;
          }
        } else { // 当一条数据的时候
          if (substr_count($v['title'], '产品图片') >= 1) {
            self::createdImage($k, $start, $objSheet, $newExcel, $val[$v['filed']]);
            $start++;
          } else {
            $objSheet->setCellValue($start . $k, $value);
            $start++;
          }
        }

        // $objSheet->setCellValue($start . $k, $value);
        // $start++;
      }
    }

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

  public static function dealImgType($url)
  {
    $image = [];
    $list = getimagesize($url);
    $imageType = $list['mime'];
    if ($imageType == 'image/jpg' || $imageType == 'image/jpeg') {
      $image = \imagecreatefromjpeg($url);
    } elseif ($imageType == 'image/png') {
      $image = \imagecreatefrompng($url);
    } elseif ($imageType == 'image/webp') {
      $image = \imagecreatefromwebp($url);
    } elseif ($imageType == 'image/gif') {
      $image = \imagecreatefromgif($url);
    }
    return $image;
  }

  public static function curlGet($url)
  {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 这个是重点 请求https。
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
  }

  /**
   * 转化图片
   */
  public static function createdImage($k, $start, $objSheet, $newExcel, $filed)
  {
    // ***** 方法一， 直接下载网络照片 暂时不兼容 php 7.3 *******

    // if ($val[$v['filed']] && count(explode('http', $val[$v['filed']])) > 1) { //过滤非文件类型
    //     $img = self::dealImgType($val[$v['filed']]);

    //     $drawing[$k] = new MemoryDrawing();
    //     $drawing[$k]->setImageResource($img);
    //     $drawing[$k]->setRenderingFunction(
    //         $drawing[$k]::RENDERING_DEFAULT
    //     );
    //     $drawing[$k]->setMimeType($drawing[$k]::MIMETYPE_DEFAULT);
    //     $drawing[$k]->setHeight(50);
    //     $drawing[$k]->setWidth(50);
    //     $drawing[$k]->setCoordinates($start .$k);
    //     $drawing[$k]->setWorksheet($newExcel->getActiveSheet());
    //     // 表格高度
    //     $objSheet->getRowDimension($k)->setRowHeight(40);
    // } else {
    //     $objSheet->setCellValue($start . $k, '');
    // }

    $dir = public_path('/images/');
    $file_info = pathinfo($filed);

    // 过滤非文件类型
    if (!empty($file_info['basename'])) {
      $basename = $file_info['basename'];
      // 获取 文件夹名称 dirname
      $publicDataArray = explode('/', $file_info['dirname']);
      $publicData = array_pop($publicDataArray);
      // var_dump('>>>>', $dir.$publicData, is_dir($dir.$publicData)); exit;
      // 进行检测文件是否存在
      if (!is_dir($dir . $publicData . '/' . $basename)) {
        if (!is_dir($dir . $publicData)) {
          mkdir($dir . $publicData, 0777, true);
        }
        $img = self::curlGet($filed);
        file_put_contents($dir . $publicData . '/' . $basename, $img);
      }
      // 引入操作图片类
      $drawing[$k] = new Drawing();
      $drawing[$k]->setPath($dir . $publicData . '/' . $basename);
      $drawing[$k]->setWidth(50);
      $drawing[$k]->setHeight(50);
      $drawing[$k]->setOffsetX(10);
      $drawing[$k]->setOffsetY(10);
      $drawing[$k]->setCoordinates($start . $k);
      $drawing[$k]->setWorksheet($newExcel->getActiveSheet());
      // 表格高度
      $objSheet->getRowDimension($k)->setRowHeight(50);
    } else {
      $objSheet->setCellValue($start . $k, '');
    }
  }
}
