<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-23 09:59:14
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2022-01-20 10:22:10
 * @Description: 
 */

namespace catchAdmin\order\model\search;

use catchAdmin\order\model\OrderBuyerRecords;
use catchAdmin\basics\model\Shop;
use catchAdmin\order\model\OrderItemRecords;
use catchAdmin\order\model\OrderRecords;
use catchAdmin\product\model\Product;



trait OrderRecordsSearch
{
    // 所属客户
    public function searchCompanyIdAttr($query, $value)
    {

        return $query->where('o.company_id', (int)$value);
    }
    // 借卖订单发货方式 DeliveryMethod
    public function searchDeliveryMethodAttr($query, $value)
    {
        return $query->where('o.delivery_method', (int)$value);
    }
    // 开始时间
    public function searchStartAtAttr($query, $value)
    {
        return $query->whereTime('o.get_at', '>=', strtotime($value));
    }
    // 结束时间
    public function searchEndAtAttr($query, $value)
    {
        return $query->whereTime('o.get_at', '<=', strtotime($value));
    }

    // 订单类型
    public function searchTypeAttr($query, $value)
    {
        return $query->where('o.order_type', (int)$value);
    }

    // 店铺名称 shop_name
    public function searchShopNameAttr($query, $value)
    {
        // return $query->whereLike('shop_name', $value);
        $map1 = [
            [app(Shop::class)->getTable() . '.shop_name', 'like',  "%{$value}%"],
        ];
        return $query->where(function ($query)  use ($map1) {
            $query->whereOr([$map1]);
        });
    }

    // 店铺id批量搜索
    public function searchShopIdsAttr($query, $value)
    {
        return $query->whereIn('o.shop_basics_id', $value);
    }

    // 平台名称 
    public function searchPlatformNameAttr($query, $value)
    {
        return $query->whereLike('o.platform', $value);
    }

    // 邮编 
    public function searchPostalcodeAttr($query, $value)
    {
        $map1 = [
            ['oa.address_postalcode', 'like',  "%{$value}%"],
        ];
        return $query->where(function ($query)  use ($map1) {
            $query->whereOr([$map1]);
        });
    }

    // 订单编号
    public function searchOrderNoAttr($query, $value)
    {
        $map1 = [
            ['o.order_no', 'like',  "%{$value}%"]
        ];
        $map2 = [
            ['o.platform_no', 'like',  "%{$value}%"]
        ];
        $map3 = [
            ['o.platform_no_ext', 'like',  "%{$value}%"]
        ];
        return $query->where(function ($query)  use ($map1, $map2, $map3) {
            $query->whereOr([$map1, $map2, $map3]);
        });
    }

    // 订单状态
    public function searchStatusAttr($query, $value)
    {
        return $query->where('o.status', (int)$value);
    }

    // Fab 订单 出库 搜索
    public function searchIsDeliveryAttr($query, $value)
    {
        return $query->where('o.is_delivery', (int)$value);
    }

    // 异常订单状态 abnormal
    public function searchIsAbnormalAttr($query, $value)
    {
        return $query->whereIn('o.abnormal', $value);
    }

    // 来源筛选
    public function searchOrderSourceAttr($query, $value)
    {
        return $query->where('o.order_source', (int)$value);
    }

    // 商品编码
    public function searchGoodsCodeAttr($query, $value)
    {
        $map1 = [
            ['op.goods_code', 'like',  "%{$value}%"]
        ];
        $map2 = [
            ['p.code', 'like',  "%{$value}%"]
        ];
        return $query->where(function ($query)  use ($map1, $map2) {
            $query->whereOr([$map1, $map2]);
        });
        // return $query->whereLike('op.goods_code', $value);
    }
    // 异常类型
    public function searchaBnormalAttr($query, $value)
    {
        return $query->where('o.abnormal', (int)$value);
    }
    // 付款时间
    public function searchStartAtPayAttr($query, $value)
    {
        return $query->whereTime('o.paid_at', '>=', $value);
    }
    // 付款时间
    public function searchEndAtPayAttr($query, $value)
    {
        return $query->whereTime('o.paid_at', '<=', $value);
    }
    // 商品名称 c.name_ch
    public function searchNameChAttr($query, $value)
    {
        return $query->whereLike('p.name_ch', $value);
    }
    // 商品分类 category_id
    public function searchCategoryIdAttr($query, $value)
    {
        return $query->where('p.category_id', (int)$value);
    }
    // 州 state
    public function searchStateAttr($query, $value)
    {
        return $query->whereLike('oa.address_stateorprovince', $value);
    }
    // 城市 city_name
    public function searchCityNameAttr($query, $value)
    {
        return $query->whereLike('oa.address_cityname', $value);
    }
    // 买家姓名 user_name
    public function searchUserNameAttr($query, $value)
    {
        return $query->whereLike('oa.address_name', $value);
    }
    // 邮箱 email
    public function searchEmailAttr($query, $value)
    {
        return $query->whereLike('oa.address_email', $value);
    }
    // 创建日期
    public function searchStartAtCreatAttr($query, $value)
    {
        return $query->whereTime('o.created_at', '>=', strtotime($value));
    }
    // 创建日期
    public function searchEndAtCreatAttr($query, $value)
    {
        return $query->whereTime('o.created_at', '<=', strtotime($value));
    }
    // 预售时间开始 shipped
    public function searchStartAtShippedAttr($query, $value)
    {
        return $query->whereTime('o.pre_shipped_at', '>=', strtotime($value));
    }
    // 预售时间 结束 pre_shipped_at
    public function searchEndAtShippedAttr($query, $value)
    {
        return $query->whereTime('o.pre_shipped_at', '<=', strtotime($value));
    }
}
