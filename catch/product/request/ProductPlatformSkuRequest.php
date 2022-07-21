<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-24 10:49:52
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-07-20 13:32:04
 * @Description: 
 */

namespace catchAdmin\product\request;

use catcher\base\CatchRequest;
use catcher\CatchAdmin;
use CatchAdmin\product\model\ProductPlatformSku;

class ProductPlatformSkuRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'product_id|ERP平台产品id' => 'require',
            // 'platform_code|第三方平台商品编码' => 'require|unique:' . ProductPlatformSku::class,
            'company_id|客户（公司）id ' => 'require',
            'shop_id|店铺id' => 'require'
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
