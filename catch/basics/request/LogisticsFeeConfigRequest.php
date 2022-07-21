<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-05 14:37:38
 * @LastEditors:
 * @LastEditTime: 2021-02-25 15:20:30
 * @Description: 
 */

namespace catchAdmin\basics\request;

use catcher\base\CatchRequest;

class LogisticsFeeConfigRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'name|模板名称' => 'require|max:50',
            'company_id|客户公司ID' => 'require|number',
            'insurance_fee|保价费' => 'require|max:10',
            'gross_weight|毛重(lbs)' => 'require|max:10',
            'gross_weight_fee' => 'require|max:10',
            'big_side_length|最大边长' => 'require|max:10',
            'big_side_length_fee' => 'require|max:10',
            'second_side_length|次长边' => 'require|max:10',
            'second_side_length_fee' => 'require|max:10',
            'oversize_min_size|最小英寸' => 'require|max:10',
            'oversize_max_size|最大英寸' => 'require|max:10',
            'oversize_fee' => 'require|max:10',
            'oversize_other_size' => 'require|max:10',
            'oversize_other_size_fee' => 'require|max:10',
            'remote_fee' => 'max:10',
            'super_remote_fee' => 'max:10',
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
