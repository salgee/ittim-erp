<?php
/*
 * @Version: 1.0

 * @Date: 2021-02-04 09:50:34
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-08-28 15:45:19
 * @Description:
 */

namespace catchAdmin\basics\model;

use catchAdmin\basics\model\search\ShopSearch;
use catchAdmin\store\model\Platforms;
use catcher\base\CatchModel as Model;
use catchAdmin\basics\model\ShopWarehouse;
use catchAdmin\permissions\model\Users;
use catchAdmin\permissions\model\DataRangScopeTrait;

class Shop extends Model
{
  use DataRangScopeTrait;
  use ShopSearch;
  // 表名
  public $name = 'shop_basics';
  // 数据库字段映射
  public $field = array(
    'id',
    // 状态，1：启用，2：禁用
    'is_status',
    // 编码
    'code',
    // 名称
    'shop_name',
    // 关联人员id 关联平台用户表users
    'user_id',
    // 所属平台id  关联 platform
    'platform_id',
    // 订单来源  1-download 2-import 3-create
    'order_origin',
    // 运营类型  1-自营 2-代储存
    'type',
    // 客户id 关联表 company
    'company_id',
    // 备注
    'remarks',
    // 归属平台参数集合 使用json 拼接
    'platform_parameters',
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

  // 设置 json 字段
  protected $json = ['platform_parameters'];

  // 把 json 返回格式转换为数组
  protected $jsonAssoc = true;

  /**
   * get list
   *
   * @time 2021/2/3
   * @param $params
   * @throws \think\db\exception\DbException
   * @return \think\Paginator
   */
  public function getList()
  {
    $users = new Users;
    $prowerData = $users->getRolesList();
    $where = [];
    $whereOr = [];
    if (!$prowerData['is_admin']) {
      if ($prowerData['is_company']) {
        $where = [
          's.company_id' => $prowerData['company_id']
        ];
      }
      if ($prowerData['shop_ids'] && !$prowerData['is_company']) {
        $whereOr = [
          ['s.id', 'in',  $prowerData['shop_ids']]
        ];
      }
    }
    $lists = $this->dataRange()
      ->field('s.id,s.is_status, s.code, s.shop_name, p.name as platform_name, p.name_ch as platform_name_ch, s.user_id,
            s.platform_id, s.order_origin, s.type, s.company_id, c.name as company_name, c.code as company_code,
            s.remarks, s.platform_parameters, s.update_by, s.creator_id, s.created_at, s.updated_at,
            u.username as creator_name, IFNULL(us.username, "-") as update_name,
            count(sw.id) as warehouse_num')
      ->alias('s')
      // ->whereOr($where)
      // ->whereOr($whereOr)
      ->whereOr(function ($query) use ($whereOr, $where) {
        $query->where($whereOr)
          ->where($where)
          ->catchSearch();
      })
      ->catchSearch()
      ->order('s.id', 'desc')
      ->leftJoin('platform p', 'p.id = s.platform_id')
      ->leftJoin('company c', 'c.id = s.company_id')
      ->leftJoin('users u', 'u.id = s.creator_id')
      ->leftJoin('users us', 'us.id = s.update_by');

    $ListData = $lists->leftJoin('shop_warehouse sw', 'sw.shop_id=s.id');
    $ListData = $lists->group('s.id');
    $ListData = $lists->paginate();
    return $ListData;
  }

  /**
   * 查询平台下店铺
   * @param $id 平台id
   */
  public function getShopPlatform($id)
  {
    return $this->field('s.id, s.shop_name, s.is_status, s.type, s.company_id, c.name as company_name')
      ->alias('s')
      ->where('platform_id', $id)
      ->leftJoin('company c', 'c.id = s.company_id')
      ->select();
  }

  /**
   * 获取借卖订单店铺
   */
  public function getBorrowSellShopList()
  {
    return $this->field('s.id, s.shop_name, s.is_status, s.type, s.company_id')
      ->alias('s')
      ->where(['platform_id' => 11]) // 借卖平台
      ->select();
  }

  /**
   * 导出
   * @param $fileData  导出字段集合
   */
  public function getExportList()
  {

    $users = new Users;
    $prowerData = $users->getRolesList();
    $where = [];
    $whereOr = [];
    if (!$prowerData['is_admin']) {
      if ($prowerData['is_company']) {
        $where = [
          's.company_id' => $prowerData['company_id']
        ];
      }
      if ($prowerData['shop_ids'] && !$prowerData['is_company']) {
        $whereOr = [
          ['s.id', 'in',  $prowerData['shop_ids']]
        ];
      }
    }
    $fileList = ['s.*', 's.type as type_shop', 'c.name as company_name', 'p.name as platform_name'];
    // foreach ($fileData  as $value) {
    //     if ($value == 'platform_name') {
    //         $fileList[] = 'p.name as platform_name';
    //     } else if ($value == 'company_name') {
    //         $fileList[] = 'c.name as company_name';
    //     } else {
    //         $fileList[] = 's.' . $value;
    //     }
    // }
    return $this->dataRange()
      ->field($fileList)
      ->alias('s')
      ->catchSearch()
      ->whereOr(function ($query) use ($whereOr, $where) {
        $query->where($whereOr)
          ->where($where)
          ->catchSearch();
      })
      ->leftJoin('platform p', 'p.id= s.platform_id')
      ->leftJoin('company c', 'c.id= s.company_id')
      ->select()->each(function (&$item) {
        $item->is_status = $item->is_status == $this::ENABLE ? '启用' : '停用';
        $item->type_text = $item->type_shop == $this::ENABLE ? '自营' : '代储存';
      })->toArray();
  }
  /**
   * 店铺导出字段集合
   */
  public function exportField()
  {
    return [
      [
        'title' => 'ID',
        'filed' => 'id',
      ],
      [
        'title' => '店铺名称',
        'filed' => 'shop_name',
      ],
      [
        'title' => '编码',
        'filed' => 'code',
      ],
      [
        'title' => '状态',
        'filed' => 'is_status',
      ],
      [
        'title' => '运营类型',
        'filed' => 'type_text',
      ],
      [
        'title' => '所属平台',
        'filed' => 'platform_name',
      ],
      [
        'title' => '客户名称',
        'filed' => 'company_name',
      ],
      [
        'title' => '创建时间',
        'filed' => 'created_at',
      ]
    ];
  }
}
