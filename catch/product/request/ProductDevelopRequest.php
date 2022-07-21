<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-19 10:21:46
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-05-26 18:22:20
 * @Description: 
 */

namespace catchAdmin\product\request;

use catcher\base\CatchRequest;
use catcher\CatchAdmin;
use CatchAdmin\product\model\Product;

class ProductDevelopRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'category_id|二级分类id' => 'require',
            'name_ch|中文名称' => 'require|max:125',
            // 'name_en|英文名称' => 'require|max:225',
            'supplier_id|供应商id' => 'require',
            // 'packing_method|包装方式' => 'require|in:1,2'

        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
