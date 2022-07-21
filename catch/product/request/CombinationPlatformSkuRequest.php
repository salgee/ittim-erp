<?php
/*
 * @Version: 1.0

 * @Date: 2021-02-24 10:49:52
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-08-16 15:24:11
 * @Description:
 */

namespace catchAdmin\product\request;

use catcher\base\CatchRequest;
use CatchAdmin\product\model\ProductPlatformSku;

class CombinationPlatformSkuRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'product_id|ERP平台组合商品id' => 'require',
            // 'platform_code|第三方平台商品编码' => 'require|unique:' . ProductPlatformSku::class
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
