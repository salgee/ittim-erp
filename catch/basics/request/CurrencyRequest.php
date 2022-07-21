<?php
/*
 * @Author:
 * @Date: 2021-02-04 18:08:04
 * @LastEditTime: 2021-03-11 14:18:15
 * @LastEditors:
 * @Description: In User Settings Edit
 * @FilePath: \erp\catch\basics\request\CurrencyRequest.php
 */

namespace catchAdmin\basics\request;

use catchAdmin\basics\model\Currency;
use catcher\base\CatchRequest;

class CurrencyRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'source_code|币种代码' => 'require|unique:'. Currency::class,
            'source_name|币种名称' => 'require|unique:'. Currency::class,
            'rate|汇率' => 'require',
            'remarks|备注' => 'max:500'
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
