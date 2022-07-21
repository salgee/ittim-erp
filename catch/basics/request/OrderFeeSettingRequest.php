<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-07 14:49:48
 * @LastEditors:
 * @LastEditTime: 2021-02-07 15:13:42
 * @Description: 
 */


namespace catchAdmin\basics\request;

use catcher\base\CatchRequest;

class OrderFeeSettingRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'company_id|客户id' => 'require|number',
            'name|模板名称' => 'require|max:50',
            'dataJson|保存数据不能为空' => 'require',
            // 'min_weight|最小重量' => 'require',
            // 'max_weight|最大重量' => 'require',
            // 'fee|费用(USD)/每件' => 'require',
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
