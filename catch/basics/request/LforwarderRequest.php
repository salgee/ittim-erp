<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-05 14:37:38
 * @LastEditors:
 * @LastEditTime: 2021-02-05 14:44:16
 * @Description: 
 */

namespace catchAdmin\basics\request;

use catchAdmin\basics\model\Lforwarder;
use catcher\base\CatchRequest;

class LforwarderRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'type|类型' => 'require|in:1,2',
            'code|公司代码' => 'require|max:30|unique:' . Lforwarder::class,
            'name|公司名称' => 'require|max:50|unique:' . Lforwarder::class,
            'settlement_cycle|结算周期' => 'require|max:3:' . Lforwarder::class,
            'remarks|备注' => 'max:500:' . Lforwarder::class
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
