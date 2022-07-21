<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 10:51:04
 * @LastEditors:
 * @LastEditTime: 2021-03-25 16:01:18
 * @Description: 
 */

namespace catchAdmin\order\request;

use catcher\base\CatchRequest;

class AfterSaleOrderRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'type|售后类型' => 'require|in:1,2,3,4,5'
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
