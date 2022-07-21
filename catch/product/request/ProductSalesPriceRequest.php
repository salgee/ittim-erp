<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-03-09 10:46:59
 * @LastEditors:
 * @LastEditTime: 2021-03-09 10:52:16
 * @Description: 
 */

namespace catchAdmin\product\request;

use catcher\base\CatchRequest;
use CatchAdmin\product\model\ProductSalesPrice;

class ProductSalesPriceRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'name|模板名称' => 'require|unique:' . ProductSalesPrice::class,
            'start_time|开始时间' => 'require',
            'end_time|结束时间' => 'require',
            'shop_id|店铺ID' => 'require'
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
