<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 10:51:04
 * @LastEditors:
 * @LastEditTime: 2021-02-06 14:35:29
 * @Description: 
 */

namespace catchAdmin\basics\request;

use catchAdmin\basics\model\ZipCodeSpecial;
use catcher\base\CatchRequest;

class ZipCodeSpecialRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'zipCode|邮编' => 'require|max:50|unique:' . ZipCodeSpecial::class,
            'type|类型' => 'require|in:1,2'
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
