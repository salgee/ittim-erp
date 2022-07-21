<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-04 15:43:29
 * @LastEditors:
 * @LastEditTime: 2021-03-10 11:24:48
 * @Description: 
 */
namespace catchAdmin\basics\request;


use catcher\base\CatchRequest;

class SenderRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'shop_id|店铺id' => 'require',
            'warehouse_name|仓库id' => 'require',
            'company|寄件人公司' => 'require',
            'name|寄件人姓名' => 'require',
            'phone|电话' => 'require',
            'mobile|手机' => 'require',
            'street|街道'  => 'require',
            'city_code|州/省代码' => 'require',
            'post_code|邮编' => 'require',
            'is_default|默认状态' => 'require|in:1,0'
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
