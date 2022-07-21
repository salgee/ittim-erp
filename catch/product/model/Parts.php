<?php
/*
 * @Version: 1.0

 * @Date: 2021-03-12 12:30:58
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-08-26 09:58:42
 * @Description:
 */

namespace catchAdmin\product\model;

use catcher\base\CatchModel as Model;
use catchAdmin\product\model\search\PartsSearch;
use catchAdmin\supply\model\Supply;
use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\product\model\Product;
use think\facade\Cache;
use catcher\Code;
use catchAdmin\permissions\model\Users;
use catchAdmin\basics\model\Shop;


class Parts extends Model
{
  use DataRangScopeTrait;
  use PartsSearch;
  // 表名
  public $name = 'parts';
  // 数据库字段映射
  public $field = array(
    'id',
    // 配件主图
    'image_url',
    // 配件说明书照片
    'image_url_other',
    // 二级分类id 关联 category
    'category_id',
    // 状态 2-禁用 1-启用
    'is_status',
    // 配件编号(说明书内）
    'code_other',
    // 编码
    'code',
    // 中文名称
    'name_ch',
    // 流向 1-国内 2-国外
    'flow_to',
    // 采购员
    'purchase_name',
    // 采购员id 关联 users
    'purchase_id',
    // 供应商id
    'supplier_id',
    // 长cm
    'length',
    // 宽cm
    'width',
    // 高cm
    'height',
    // 美制长 英寸
    'length_AS',
    // 美制宽 英寸
    'width_AS',
    // 美制高 英寸
    'height_AS',
    // 毛重美制 lbs
    'weight_gross_AS',
    // 体积
    'volume',
    // 重
    'weight',
    // 外箱长cm
    'length_outside',
    // 外箱宽cm
    'width_outside',
    // 外箱高cm
    'height_outside',
    // 外箱体积
    'volume_outside',
    // 箱率
    'box_rate',
    // 商品id， 多个使用 ，号隔开
    'product_id',
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

  /**
   * 列表
   *
   * @return \think\Paginator
   */
  public function getList()
  {
    return $this
      ->dataRange()
      ->field('c.id, c.purchase_name,c.supplier_id, c.box_rate, c.purchase_id, c.image_url, c.code, c.name_ch, c.is_status,
            c.updated_at, c.created_at, su.name as supplier_name, c.flow_to,
            cg.name as category_names, cg.parent_name, c.created_at, c.updated_at,u.username as creator_name,  IFNULL(us.username, "-") as update_name')
      ->alias('c')
      ->catchSearch()
      ->leftJoin('supplies su', 'su.id = c.supplier_id')
      ->leftJoin('category cg', 'cg.id = c.category_id')
      ->leftJoin('users us', 'us.id = c.update_by')
      ->leftJoin('users u', 'u.id = c.creator_id')
      ->order('c.id', 'desc')
      ->paginate();
  }

  /**
   * 生成编码
   */
  // public  function createOrderNo($id)
  // {
  //     // $date = date('Ymd');
  //     // $twoTree = Category::where('id', $id)->find();
  //     // $oneTree = Category::where('id', $twoTree['parent_id'])->find();
  //     // $time = strtotime($date);
  //     // $count = $this->where('created_at', '>', $time)->count();
  //     // $str = sprintf("%05d", $count + 1);
  //     $twoTree = Category::where('id', $id)->find();
  //     $oneTree = Category::where('id', $twoTree['parent_id'])->find();

  //     $count = $this->where('created_at', '<', time())->count();

  //     $str = sprintf("%05d", $count + 1);
  //     return "PART".$oneTree['code'] . "-" . $twoTree['code'] . $str;
  // }
  /**
   * 配件编码
   */
  public  function createOrderNo($id)
  {

    $twoTree = Category::where('id', $id)->find();
    // $oneTree = Category::where('id', $twoTree['parent_id'])->find();
    // 获取编码缓存
    $count = Cache::get(Code::CACHE_PART . $id);

    $num = $count + 1;
    $str = sprintf("%04d", $num);

    return  "PART-" . $twoTree['code'] . $str;
  }
  /**
   * 更新当前配件编码
   */
  public function updateOrderNo($id, $code)
  {
    $count = substr($code, -3);
    // 存入编码更新缓存
    Cache::set(Code::CACHE_PART . $id, (int)$count);
    return true;
  }

  /**
   * 详情
   * findByInfo
   */
  public function findByInfo($id)
  {
    return $this->field('pa.*, su.name as supplier_name, ca.name as category_name, c.name as category_parent_name')
      ->alias('pa')
      ->where(['pa.id' => $id])
      ->leftJoin('supplies su', 'su.id = pa.supplier_id')
      ->leftJoin('category ca', 'ca.id = pa.category_id')
      ->leftJoin('category c', 'c.id = ca.parent_id')
      ->find();
  }

  /**
   * 导出数据
   * @param $fileData  导出字段集合
   */
  public function getExportList()
  {
    $users = new Users;
    $prowerData = $users->getRolesList();
    $where = [];
    if (!$prowerData['is_admin']) {
      // 如果账号有绑定店铺，那该账号可以查看绑定店铺的所属客户下的配件
      if ($prowerData['shop_ids']) {
        $company_ids = Shop::where('id', 'in', $prowerData['shop_ids'])->value('company_id');
        $where = [
          'p.company_id' => ['in', $company_ids]
        ];
      } else {
        // 判断是运营岗，只可以查看所有的内部客户的商品
        if ($prowerData['is_operation']) {
          $where = ['cp.user_type' => 0];
        }
      }
    }
    $fileList = [
      'c.*', 'su.name as supplier_name', 'cg.parent_name',
      'cg.name as category_name'
    ];
    return $this
      ->dataRange()
      ->field($fileList)
      ->alias('c')
      ->catchSearch()
      ->whereOr(function ($query) use ($where) {
        if (count($where) > 0) {
          $query->where($where)
            ->catchSearch();
        }
      })
      ->leftJoin('product p', 'FIND_IN_SET(p.id, c.product_id)')
      ->leftJoin('company cp', 'cp.id = p.company_id')
      // ->leftJoin('company cp', 'cp.id = c.company_id')
      ->leftJoin('supplies su', 'su.id= c.supplier_id')
      ->leftJoin('category cg', 'cg.id= c.category_id')
      ->group('c.id')
      ->select()->each(function (&$item) {
        $item['category_name_text'] = $item['parent_name'] . '-' . $item['category_name'];
        $item['flow_to_text'] = $item['flow_to'] == $this::ENABLE ? '国内' : '国外';
        $item['size_mailing'] = $item['length'] . '*' . $item['width'] . '*' . $item['height'];
        $item['size_pack'] = $item['length_outside'] . '*' . $item['width_outside'] . '*' . $item['height_outside'];
        $list = explode(',', $item['product_id']);
        $listProduct = Product::whereIN('id', $list)->column('code');
        $item['product_names'] = $listProduct;
        $item['packing_method'] = 1;
      })
      ->toArray();
  }

  /**
   * 获取商品配件列表
   * @param $id 商品id
   */
  public function partListProduct($id)
  {
    return $this->where('is_status', '=', 1)
      ->whereRaw("FIND_IN_SET(" . $id . ",product_id)")
      ->select();
  }

  public function category()
  {
    return $this->hasOne(Category::class, 'id', 'category_id');
  }

  /**
   * 配件导出字段
   */
  public function exportField()
  {
    return [
      [
        'title' => '产品图片',
        'filed' => 'image_url',
      ],
      [
        'title' => '所属分类',
        'filed' => 'category_name_text',
      ],
      [
        'title' => '配件编码',
        'filed' => 'code',
      ],
      [
        'title' => '配件名称',
        'filed' => 'name_ch',
      ],
      [
        'title' => '配件流向',
        'filed' => 'flow_to_text',
      ],
      [
        'title' => '所属供应商',
        'filed' => 'supplier_name',
      ],
      [
        'title' => '采购员',
        'filed' => 'purchase_name',
      ],
      [
        'title' => '邮购包装尺寸',
        'filed' => 'size_mailing',
      ],
      [
        'title' => '体积',
        'filed' => 'volume',
      ],
      [
        'title' => '美制毛重',
        'filed' => 'weight_gross_AS',
      ],
      [
        'title' => '外箱箱率',
        'filed' => 'box_rate',
      ],
      [
        'title' => '适用商品',
        'filed' => 'product_names',
      ]

    ];
  }
}
