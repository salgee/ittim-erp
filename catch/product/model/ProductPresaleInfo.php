<?php
/*
 * @Version: 1.0

 * @Date: 2021-03-09 14:57:12
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-10-15 19:11:58
 * @Description:
 */

namespace catchAdmin\product\model;

use catcher\base\CatchModel as Model;
use catcher\Code;
use think\facade\Cache;

class ProductPresaleInfo extends Model
{
    // 表名
    public $name = 'product_presale_info';
    // 数据库字段映射
    public $field = array(
        'id',
        // 预售活动商品管理ID 关联 product_presale
        'product_presale_id',
        // 商品ID
        'product_id',
        // 预计发货时间
        'estimated_delivery_time',
        // 修改人
        'update_by',
        // 创建人ID
        'creator_id',
        // 创建时间
        'created_at',
        // 更新时间
        'updated_at',
        // 软删除
        'deleted_at',
    );
    public function selectInfo($id)
    {
        return $this->field('p.*,c.name as category_name, c.parent_name, c.parent_name, pi.image_url, pi.name_ch, pi.name_en, pi.code, pi.type,
            pi.packing_method, pi.category_id, pi.benchmark_price,
            pi.operate_type, pi.bar_code_upc, pi.status, pi.is_disable')
            ->alias('p')
            ->where('p.product_presale_id', $id)
            ->leftJoin('product pi', 'pi.id = p.product_id')
            ->leftJoin('category c', 'c.id = pi.category_id')
            ->select();
    }

    // 获取所有子数组
    public function getAllDelect($id)
    {
        $list = $this->field('id')
            ->where('product_presale_id', $id)
            ->order('id', 'desc')
            ->select();
        $arr = [];
        foreach ($list as $key => $ids) {
            $arr[$key] = $ids['id'];
        }
        return $arr;
    }

    /**
     * 获取最新一条更新的预售活动写入缓存
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function createProductPreSale()
    {
        $productPresale = new ProductPresale();
        $now = time();
        $product = [];
        $this->field([
            $this->aliasField('product_id'), $this->aliasField('estimated_delivery_time'),
            $productPresale->aliasField('id'), $productPresale->aliasField(('is_disable')),
            $this->aliasField('product_presale_id')
        ])
            ->catchJoin(
                ProductPresale::class,
                'id',
                'product_presale_id',
                ['start_time, end_time, shop_id'],
                'LEFT'
            )
            ->whereTime($productPresale->aliasField('start_time'), '<=', $now)
            ->whereTime($productPresale->aliasField('end_time'), '>=', $now)
            // ->where($productPresale->aliasField('is_disable'), 1)
            ->order([
                $productPresale->aliasField('shop_id'),
                $this->aliasField('product_id'),
                $productPresale->aliasField('updated_at'),
                $productPresale->aliasField('end_time') => 'DESC'
            ])
            ->select()->each(function ($presale) use (&$product) {
                if (Cache::get(Code::CACHE_PRESALE . $presale['shop_id'] . '_' . $presale['product_id'])) {
                    Cache::delete(Code::CACHE_PRESALE . $presale['shop_id'] . '_' . $presale['product_id']);
                }
                if ((int)$presale['is_disable'] == 2) {
                    // Cache::delete(Code::CACHE_PRESALE . $presale['shop_id'] . '_' . $presale['product_id']);
                } else {
                    // 写入数据库查询的店铺第一条预售商品数据到Redis
                    // if (!isset($product[$presale['shop_id']][$presale['product_id']])) {
                    $product[$presale['shop_id']][$presale['product_id']] = json_decode($presale, true);
                    // 设置预售活动的缓存，同时自动失效时间为end_time
                    Cache::set(
                        Code::CACHE_PRESALE . $presale['shop_id'] . '_' . $presale['product_id'],
                        json_encode($presale),
                        (new \DateTime())->setTimestamp($presale['end_time'])
                    );
                    // }
                }
            });
        // todo 清空，只进缓存，其他暂时不用
        // unset($product);
    }
}
