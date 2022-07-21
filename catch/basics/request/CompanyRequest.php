<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 10:51:04
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-06-02 15:08:01
 * @Description: 
 */

namespace catchAdmin\basics\request;

use catchAdmin\basics\model\Company;
use catcher\base\CatchRequest;

class CompanyRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'code|客户编码' => 'require|max:30|unique:'. Company::class,
            'name|客户名称' => 'require|max:50',
            'type|客户仓库类型' => 'require|in:2,1,0',
            'user_type|客户类型' => 'require|in:1,0'
            // 'contacts|联系人' => 'require|max:10',
            // 'mobile|手机号码' => 'require',
            // 'telephone|座机' => 'require',
            // 'salesman_username|业务员名称' => 'require',
            // 'bank_name|银行名称' => 'require|max:50',
            // 'bank_number|银行卡号' => 'require|max:50',
            // 'fax|传真' => 'max:20',
            // 'zip_code|邮编' => 'max:20',
            // 'address|地址' => 'max:100',
            // 'remarks|备注' => 'max:500'

        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
