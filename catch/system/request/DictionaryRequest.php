<?php
namespace catchAdmin\system\request;

use catchAdmin\system\model\Dictionary;
use catcher\base\CatchRequest;

class DictionaryRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'dict_name|字典名称' => 'require|max:50',
            'dict_data|字典值' => 'require',
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}