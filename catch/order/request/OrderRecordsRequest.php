<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 10:51:04
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-04-29 19:27:15
 * @Description: 
 */

namespace catchAdmin\order\request;

use catcher\base\CatchRequest;

use CatchAdmin\order\model\OrderRecords;

class OrderRecordsRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'platform_no|平台订单编号' => 'require|unique:' . OrderRecords::class,
            'platform_id|平台ID' => 'require|number'
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
