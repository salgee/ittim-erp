<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 10:51:04
 * @LastEditors:
 * @LastEditTime: 2021-03-22 12:16:24
 * @Description: 
 */
namespace catchAdmin\basics\request;

use catcher\base\CatchRequest;

class AddressRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'city|城市' => 'require',
            'city_code|城市编码' => 'require',
            'state|州' => 'require',
            'state_code|州编码' => 'require',
            'area_code|邮政区号' => 'require',
            'street|街道' => 'require'
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
