<?php
/*
 * @Version: 1.0

 * @Date: 2021-03-20 11:41:00
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-12-17 16:40:59
 * @Description:
 */

namespace catchAdmin\basics\controller;

use catchAdmin\permissions\model\Users;
use think\facade\Db;
use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\system\model\Notice as noticeModel;
use catchAdmin\order\model\OrderRecords as orderRecordsModel;
use catchAdmin\supply\model\PurchaseOrders as purchaseOrdersModel;
use catchAdmin\settlement\model\StorageProductFee as storageProductFeeModel;
use catchAdmin\warehouse\model\Warehouses;
use catchAdmin\basics\model\Company;
use catchAdmin\basics\model\Shop;
use catchAdmin\product\model\Product;


class Home extends CatchController
{
    protected $noticeModel;
    protected $orderRecordsModel;
    protected $purchaseOrdersModel;
    protected $storageProductFeeModel;

    public function __construct(
        noticeModel $noticeModel,
        orderRecordsModel $orderRecordsModel,
        purchaseOrdersModel $purchaseOrdersModel,
        storageProductFeeModel $storageProductFeeModel
    ) {
        $this->noticeModel = $noticeModel;
        $this->orderRecordsModel = $orderRecordsModel;
        $this->purchaseOrdersModel = $purchaseOrdersModel;
        $this->storageProductFeeModel = $storageProductFeeModel;
    }

    /**
     * 列表
     * @time 2021年03月20日 11:41
     * @param Request $request
     */
    public function index(Request $request): \think\Response
    {
        $users = new Users;
        $whereOr = [];
        $companyData = [];
        $whereProduct = [];
        $whereCompany = [];
        $prowerData = $users->getRolesList();
        if (!$prowerData['is_admin']) {
            if ($prowerData['shop_ids'] && !$prowerData['is_company']) {
                $whereOr = [
                    ['shop_basics_id', 'in',  $prowerData['shop_ids']]
                ];
            }
            if ($prowerData['is_company']) {
                if ($prowerData['company_id']) {
                    $whereOr = [
                        'company_id' => $prowerData['company_id']
                    ];
                    $companyData = Company::where('id', $prowerData['company_id'])->find();
                }
            }
            if ($prowerData['is_company']) {
                $whereProduct = [
                    'p.company_id' => $prowerData['company_id']
                ];
                // 采购员角色
            } elseif ($prowerData['is_buyer_staff']) {
                $whereProduct = [
                    'p.purchase_id' => $prowerData['user_id']
                ];
            // 其他角色
            } else {
                // 如果账号有绑定店铺，那该账号可以查看绑定店铺的所属客户下的商品
                if ($prowerData['shop_ids']) {
                    $company_ids = Shop::where('id', 'in', $prowerData['shop_ids'])->value('company_id');
                    $whereProduct = [
                        'p.company_id' => ['in', $company_ids]
                    ];
                } else {
                    // 判断是运营岗，只可以查看所有的内部客户的商品
                    if ($prowerData['is_operation']) {
                        $whereProduct = ['cp.user_type' => 0];
                    }
                }
            }
            // 客户权限
            if ($prowerData['is_company']) {
                $whereCompany = [
                    'c.id' => $prowerData['company_id']
                ];
            }
            if ($prowerData['shop_ids'] && !$prowerData['is_company'] && !$prowerData['is_buyer_staff']) {
                $company_ids = Shop::where('id', 'in', $prowerData['shop_ids'])->value('company_id');
                $whereCompany = [
                    'c.id' => ['in', $company_ids]
                ];
            }

        }
        // 公告
        $notice = $this->noticeModel->where(['status' => 1])
            ->order(['updated_at', 'sort', 'id' => 'desc'])
            ->limit(10)
            ->select();
        // 客户查看客户订单借卖订单
        if ($prowerData['is_company']) {
            $where = [['order_type', 'in', '2,3'], ['status', 'in', '1,2,3,4,5'], ['abnormal', '=', '0']];
        } else {
            $where = [['order_type', 'in', '0,4,5'], ['status', 'in', '1,2,3,4,5'], ['abnormal', '=', '0']];
        }
        // 仓储费查询条件
        $whereOrStorage = [];
        $id = request()->user()['department_id'] ?? 0;
        if (!$prowerData['is_admin']) {
            if ($prowerData['is_company']) { // 如果是客户
                if ($prowerData['company_id']) {
                    // 计算相客户对应仓库金额
                    $warehouseIds = Warehouses::where('company_id', $prowerData['company_id'])->column('id');
                    if (!empty($warehouseIds)) {
                        $whereOrStorage = [['sf.virtual_warehouse_id', 'in', $warehouseIds]];
                    } else {
                        $whereOrStorage = [['sf.virtual_warehouse_id', 'in', $warehouseIds]];
                    }
                }
            } else {
                // 其他账号查看自己的部门
                if ($id > 1) {
                    $whereOrStorage = [
                        'sf.department_id' => $id
                    ];
                }
            }
        }
        /***********商品数量统计*************** */
        $product = new Product();
        $countProduct = $product->alias('p')
            ->leftJoin('company cp', 'cp.id = p.company_id')
            ->where(['p.source_status' => 2])->where(function ($query) use ($whereProduct) {
            if (count($whereProduct) > 0) {
                $query->where($whereProduct)
                ->where(['p.source_status' => 2]);
            }
        })->count();

        /***********客户数量统计************************* */
        $countCompany = Company::alias('c')->where($whereCompany)->count();
        /**********当天**********/
        // 订单数量
        $todayOrder = $this->orderRecordsModel->whereDay('created_at')->where($where)->where($whereOr)->count();
        // 仓储费
        $todayStorage = $this->storageProductFeeModel->alias('sf')
            ->whereDay('sf.created_at')
            ->where($whereOrStorage)
            ->sum('fee');
        // 订单销售额
        $todayPrice = $this->orderRecordsModel->whereDay('created_at')->where($where)->where($whereOr)->sum('total_price');
        /**********昨天**********/
        // 订单数量
        $yesterdayOrder = $this->orderRecordsModel->whereDay('created_at', 'yesterday')->where($where)->where($whereOr)->count();
        // 仓储费
        $yesterdayStorage = $this->storageProductFeeModel->alias('sf')
            ->whereDay('sf.created_at', 'yesterday')
            ->where($whereOrStorage)
            ->sum('fee');
        // 订单销售额
        $yesterdayPrice = $this->orderRecordsModel->whereDay('created_at', 'yesterday')->where($where)->where($whereOr)->sum('total_price');
        /**********待办事项**********/
        // 待发货订单
        if ($prowerData['is_company']) {
            $orderShip = $this->orderRecordsModel->where(['status' => 1, 'abnormal' => 0])->whereIN('order_type', '2,3')->where($whereOr)
                ->count();
        } else {
            $orderShip = $this->orderRecordsModel->where(['status' => 1, 'abnormal' => 0])->whereIN('order_type', '0,1,4,5')->where($whereOr)
                ->count();
        }
        // 采购员只能看到自己的采购单统计数据
        $purchaseWhere = [];
        if ($prowerData['is_buyer_staff']) {
            $purchaseWhere = [
                'purchase_id' => $prowerData['user_id']
            ];
        }
        // 待提交
        $toSubmitPurchase = $this->purchaseOrdersModel->dataRange([], 'created_by')
            ->where(['audit_status' => 0])
            ->whereOr(function ($query) use ($purchaseWhere) {
                if (count($purchaseWhere) > 0) {
                    $query->where($purchaseWhere)->where(['audit_status' => 0]);
                }
            })->count();
        // 待审核（待采购员审核）
        $pendingReviewPurchase = $this->purchaseOrdersModel->dataRange([], 'created_by')
            ->where(['audit_status' => 1])
            ->whereOr(function ($query) use ($purchaseWhere) {
                if (count($purchaseWhere) > 0) {
                    $query->where($purchaseWhere)->where(['audit_status' => 1]);
                }
            })->count();
        // 待复审（待运营审核）
        $pendingRehearPurchase = $this->purchaseOrdersModel->dataRange([], 'created_by')
            ->where(['audit_status' => 2])
            ->whereOr(function ($query) use ($purchaseWhere) {
                if (count($purchaseWhere) > 0) {
                    $query->where($purchaseWhere)->where(['audit_status' => 2]);
                }
            })->count();
        // 待下单（待转出运单）未生成合同
        $pendingOrderPurchase = $this->purchaseOrdersModel->dataRange([], 'created_by')
            ->where(['contract_status' => 0, 'audit_status' => 3])
            ->whereOr(function ($query) use ($purchaseWhere) {
                if (count($purchaseWhere) > 0) {
                    $query->where($purchaseWhere)->where(['contract_status' => 0, 'audit_status' => 3]);
                }
            })->count();
        $and = '';
        if ($prowerData['is_company']) {
            $order_type = '2,3';
            $and = 'company_id = (' . $prowerData['company_id'] . ')';
            $abnormal = '0';
        } else {

            $order_type = '0,4,5';
            if ($prowerData['shop_ids']) {
                $and = 'shop_basics_id IN (' . $prowerData['shop_ids'] . ')';
            }
            $abnormal = '0';
        }
        // 不是超级管理员
        if (!$prowerData['is_admin'] && ($prowerData['shop_ids'] || $prowerData['is_company'])) {
            // 15日内订单
            $order = Db::query("SELECT COUNT(1) AS total,FROM_UNIXTIME(created_at,'%Y-%m-%d') AS days FROM order_records 
            WHERE UNIX_TIMESTAMP(DATE_SUB(CURDATE(),INTERVAL 15 DAY))<=created_at 
            AND order_type IN (" . $order_type . ") AND `status` IN (1,2,3,4,5)
            AND abnormal = " . $abnormal . "
            AND $and GROUP BY days ASC");
            // 15日内销售额
            if (!$prowerData['is_company']) {
                $price = Db::query("SELECT SUM(total_price) AS price,FROM_UNIXTIME(created_at,'%Y-%m-%d') AS days FROM order_records 
                WHERE UNIX_TIMESTAMP(DATE_SUB(CURDATE(),INTERVAL 15 DAY))<=created_at 
                AND order_type IN (" . $order_type . ") AND `status` IN (1,2,3,4,5)
                AND abnormal = " . $abnormal . "
                AND shop_basics_id IN (" . $prowerData['shop_ids'] . ") GROUP BY days ASC;");
            }
        } else {
            // 15日内订单
            $order = Db::query("SELECT COUNT(1) AS total,FROM_UNIXTIME(created_at,'%Y-%m-%d') AS days FROM order_records 
            WHERE UNIX_TIMESTAMP(DATE_SUB(CURDATE(),INTERVAL 15 DAY))<=created_at 
            AND abnormal = " . $abnormal . "
            AND order_type IN (" . $order_type . ") AND `status` IN (1,2,3,4,5) GROUP BY days ASC");
            // 15日内销售额
            $price = Db::query("SELECT SUM(total_price) AS price,FROM_UNIXTIME(created_at,'%Y-%m-%d') AS days FROM order_records 
            WHERE UNIX_TIMESTAMP(DATE_SUB(CURDATE(),INTERVAL 15 DAY))<=created_at 
            AND abnormal = " . $abnormal . "
            AND order_type IN (" . $order_type . ") AND `status` IN (1,2,3,4,5) GROUP BY days ASC;");
        }
        // 剩余额度 
        $overage_amount = $companyData['overage_amount'] ?? 0;
        // 初始额度
        $amount = $companyData['amount'] ?? 0;


        return CatchResponse::success([
            'notice' => $notice,
            'todayOrder' => $todayOrder,
            'todayStorage' => $todayStorage,
            'todayPrice' => $todayPrice,
            'yesterdayOrder' => $yesterdayOrder,
            'yesterdayStorage' => $yesterdayStorage,
            'yesterdayPrice' => $yesterdayPrice,
            'orderShip' => $orderShip,
            'toSubmitPurchase' => $toSubmitPurchase,
            'pendingReviewPurchase' => $pendingReviewPurchase,
            'pendingRehearPurchase' => $pendingRehearPurchase,
            'pendingOrderPurchase' => $pendingOrderPurchase,
            'order' => $order,
            'price' => $price ?? 0,
            'overage_amount' => $overage_amount,
            'amount' => $amount,
            'countProduct' => $countProduct,
            'countCompany' => $countCompany
        ]);
    }
}
