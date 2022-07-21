<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 10:51:04
 * @LastEditors:
 * @LastEditTime: 2021-02-06 18:37:58
 * @Description: 
 */

namespace catchAdmin\basics\request;

use catcher\base\CatchRequest;

class CompanyQuotaRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'company_id|客户id' => 'integer',
            'currency_id|币别id' => 'integer',
            'currency_name|币别名称' => 'require',
            'quota|额度' => 'require'
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
