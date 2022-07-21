<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-03-08 15:55:58
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-07-09 15:00:59
 * @Description: 
 */

namespace catchAdmin\product\request;

use catcher\base\CatchRequest;
use CatchAdmin\product\model\ProductCombination;

class ProductCombinationRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            // 'name_ch|中文名称' => 'require|unique:' . ProductCombination::class,
            // 'name_en|中文名称' => 'require|unique:' . ProductCombination::class,
            // 'code|编码' => 'require|unique:' . ProductCombination::class,
            'shop_id|店铺ID' => 'require'
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
