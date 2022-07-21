<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-19 10:21:46
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-06-17 16:48:16
 * @Description: 
 */

namespace catchAdmin\product\request;

use catcher\base\CatchRequest;
use CatchAdmin\product\model\Parts;

class PartsRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'image_url|配件主图' => 'require|max:225',
            'category_id|二级分类id' => 'require',
            // 'code|编码' => 'require|unique:' . Parts::class,
            'name_ch|名称' => 'require|max:125|unique:' . Parts::class,
            'flow_to|流向' => 'require|in:1,2',
            'purchase_name|采购员' => 'require',
            'purchase_id|采购员' => 'require',
            // 'length|长' => 'require',
            // 'width|宽' => 'require',
            // 'height|高' => 'require',
            // 'volume|体积' => 'require',
            'weight|重' => 'require',
            // 'length_outside|外箱长' => 'require',
            // 'width_outside|外箱宽' => 'require',
            // 'height_outside|外箱高' => 'require',
            // 'volume_outside|外箱体积' => 'require',
            // 'box_rate|箱率' => 'require',

        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
