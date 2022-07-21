<?php
namespace catchAdmin\supply\request;

use catchAdmin\supply\model\Supply;
use catcher\base\CatchRequest;

class SupplyUpdateRequest extends CatchRequest
{
    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'name|供应商名称' => 'require|max:100|unique:' .Supply::class,
            'code|供应商编码' => 'require|max:20|unique:' .Supply::class,
        ];
    }
}
