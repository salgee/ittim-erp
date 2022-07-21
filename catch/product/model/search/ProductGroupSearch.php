<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-22 11:09:28
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-07-23 16:49:52
 * @Description: 
 */

namespace catchAdmin\product\model\search;

use catchAdmin\supply\model\Supply;
use catchAdmin\basics\model\Company;

trait ProductGroupSearch
{
  public function searchKeysAttr($query, $value)
  {
    $map1 = [
      ['p.code', 'like',  "%{$value}%"]
    ];
    $map2 = [
      ['p.name_ch', 'like',  "%{$value}%"]
    ];
    $map3 = [
      ['pg.name', 'like',  "%{$value}%"]
    ];
    return $query->where(function ($query)  use ($map1, $map2, $map3) {
      $query->whereOr([$map1, $map2, $map3]);
    });
  }
}
