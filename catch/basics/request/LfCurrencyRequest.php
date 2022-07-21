<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-05 14:37:38
 * @LastEditors:
 * @LastEditTime: 2021-02-05 16:03:02
 * @Description: 
 */

namespace catchAdmin\basics\request;

use catcher\base\CatchRequest;

class LfCurrencyRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'lforwarder_company_id|公司id' => 'require',
            'currency_id|币别ID' => 'require',
            'target_name|币别名称' => 'require',
            'bank_name|银行名称' => 'require',
            'bank_number|银行卡号' => 'require'
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
