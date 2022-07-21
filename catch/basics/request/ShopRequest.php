<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-04 09:50:34
 * @LastEditors:
 * @LastEditTime: 2021-03-09 18:07:20
 * @Description: 
 */

namespace catchAdmin\basics\request;

use catchAdmin\basics\model\Shop;
use catcher\base\CatchRequest;

class ShopRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'code|编码' => 'require|max:30|unique:' . Shop::class,
            'shop_name|名称' => 'require|max:50|unique:' . Shop::class,
            // 'is_status|状态' => 'require|in:1,2',
            'platform_id|所属平台id' => 'require|integer',
            // 'order_origin|订单来源' => 'require',
            'type|运营类型'  => 'require|in:1,2',
            'company_id|客户id' => 'require|integer',
            'platform_parameters|归属平台参数集合' => 'require',
            'remarks|备注' => 'max:500'
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}