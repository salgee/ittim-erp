<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-09 15:05:24
 * @LastEditors:
 * @LastEditTime: 2021-03-16 14:25:03
 * @Description: 
 */

namespace catchAdmin\product\model;

use catcher\base\CatchModel as Model;

class ProductAnnex extends Model
{
    // 表名
    public $name = 'product_group_annex';
    // 数据库字段映射
    public $field = array(
        'id',
        // 产品id 关联产品product
        'product_id',
        // 尺寸
        'size',
        // 重量
        'weight',
        // 颜色
        'color',
        // 材质
        'material',
        // 配件
        'parts',
        // 用途
        'purpose',
        // 其他备注
        'other_remark',
        // 整箱产品组成图片多张使用 ","
        'pictures',
        // 整箱产品细节图片多张使用 ","
        'detail_pictures',
        // 说明书 文件 pdf
        'description',
        // 箱唛 文件 pdf
        'box_mark',
        // 其他内容 文件或 pdf
        'other',
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
}