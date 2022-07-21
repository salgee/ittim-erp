<?php
namespace catchAdmin\warehouse\request;

use catchAdmin\warehouse\model\Warehouses;
use catcher\base\CatchRequest;

class WarehouseUpdateRequest extends CatchRequest
{
    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'name|仓库名称' => 'require|max:100|unique:' .Warehouses::class,
        ];
    }
}
