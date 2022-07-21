<?php
/*
 * @Version: 1.0

 * @Date: 2021-03-23 14:10:17
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-08-09 13:57:54
 * @Description:
 */

namespace catchAdmin\basics\excel;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\IOFactory;
use catcher\exceptions\FailedException;
use think\facade\Log;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;


class PartImport
{

  /**
   * 上传邮编,上传物流应付账款
   *
   * @time 2021年04月08日
   * @return array
   */
  public function read($file)
  {
    $imageFilePath = public_path('/images/partImage/');
    $objRead = IOFactory::createReader('Xlsx');
    $objSpreadsheet = $objRead->load($file);
    $objWorksheet = $objSpreadsheet->getSheet(0);
    $data = $objWorksheet->toArray();

    foreach ($objWorksheet->getDrawingCollection() as $drawing) {
      list($startColumn, $startRow) = Coordinate::coordinateFromString($drawing->getCoordinates());
      $imageFileName = $drawing->getCoordinates() . mt_rand(1000, 9999);

      switch ($drawing->getExtension()) {
        case 'jpg':
        case 'jpeg':
          $imageFileName .= '.jpg';
          $source = imagecreatefromjpeg($drawing->getPath());
          imagejpeg($source, $imageFilePath . $imageFileName);
          break;
        case 'gif':
          $imageFileName .= '.gif';
          $source = imagecreatefromgif($drawing->getPath());
          imagegif($source, $imageFilePath . $imageFileName);
          break;
        case 'png':
          $imageFileName .= '.png';
          $source = imagecreatefrompng($drawing->getPath());
          imagepng($source, $imageFilePath . $imageFileName);
          break;
      }
      $startColumn = self::ABC2decimal($startColumn);
      $data[$startRow - 1][$startColumn] = 'http://' . $_SERVER['HTTP_HOST'] . '/images/partImage/' . $imageFileName;
    }
    return $data;
  }

  public function ABC2decimal($abc)
  {
    $ten = 0;
    $len = strlen($abc);
    for ($i = 1; $i <= $len; $i++) {
      $char = substr($abc, 0 - $i, 1); //反向获取单个字符

      $int = ord($char);
      $ten += ($int - 65) * pow(26, $i - 1);
    }
    return $ten;
  }
}
