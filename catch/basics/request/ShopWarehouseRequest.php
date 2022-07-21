<?php

namespace catchAdmin\basics\request;

use catcher\base\CatchRequest;

class ShopWarehouseRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'shop_id|关联店铺id' => 'require|integer',
            'data_json| 参数集合' => 'require'
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
