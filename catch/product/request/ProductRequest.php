<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-19 10:21:46
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-06-02 18:24:20
 * @Description: 
 */

namespace catchAdmin\product\request;

use catcher\base\CatchRequest;
use catcher\CatchAdmin;
use CatchAdmin\product\model\Product;

class ProductRequest extends CatchRequest
{

    protected function rules(): array
    {
        // TODO: Implement rules() method.
        return [
            'type|类型' => 'require|in:1,0',
            // 'image_url|商品图片' => 'require|max:225',
            'category_id|二级分类id' => 'require',
            'code|编码' => 'unique:' . Product::class,
            'name_ch|中文名称' => 'require|max:125',
            // 'name_en|英文名称' => 'require|max:225',
            'operate_type|运营类型' => 'require|in:1,2',
            'ZH_HS|国内(HS)' => 'require',
            'EN_HS|国外(HS)' => 'require',
            'tax_rebate_rate|国内退税率' => 'require',
            'tax_tariff_rate|国外关税税率' => 'require',
            'bar_code_upc|upc条码' => 'require',
            // 'bar_code|产品条码' => 'require',
            'supplier_id|供应商id' => 'require',
            // 'purchase_name|采购员' => 'require',
            'company_id|所属客户id' => 'require',
            'purchase_price_rmb|价格' => 'require',
            'purchase_price_usd|价格' => 'require',
            'insured_price|是否保价' => 'require|in:1,0',
            'hedge_price|保值' => 'require',
            'packing_method|包装方式' => 'require|in:1,2'
            
        ];
    }

    protected function message(): array
    {
        // TODO: Implement message() method.
        return [];
    }
}
