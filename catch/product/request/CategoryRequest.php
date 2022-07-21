<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 10:51:04
 * @LastEditors:
 * @LastEditTime: 2021-02-08 17:17:37
 * @Description: 
 */

namespace catchAdmin\product\request;

use catcher\base\CatchRequest;
use catcher\CatchAdmin;
use CatchAdmin\product\model\Category;
class CategoryRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'name|名称' => 'require|unique:'.Category::class,
            'code|编码' => 'require|unique:' . Category::class
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
