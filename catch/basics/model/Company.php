<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-06 15:43:14
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-08-26 16:41:22
 * @Description:
 */

namespace catchAdmin\basics\model;

// use CatchAdmin\basics\model\search\CompanySearch;
use catchAdmin\basics\model\CompanyAmountLog as cal;
use catchAdmin\basics\model\search\CompanySearch;
use catcher\base\CatchModel as Model;
use catcher\exceptions\FailedException;
use catchAdmin\permissions\model\Users;
use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\basics\model\Shop;


class Company extends Model
{
    use DataRangScopeTrait;
    use CompanySearch;

    // 表名
    public $name = 'company';
    // 数据库字段映射
    public $field = array(
        'id',
        // 状态，1：正常，2：禁用
        'is_status',
        // 客户编码(代码)
        'code',
        // 客户名称
        'name',
        // 客户仓库类型，1：代仓储，0：自营 2-代运营
        'type',
        // 登录账户id
        'account_id',
        // 联系人
        'contacts',
        // 手机号码
        'mobile',
        // 座机
        'telephone',
        // 业务员名称
        'salesman_username',
        // 银行名称
        'bank_name',
        // 银行卡号
        'bank_number',
        // 传真
        'fax',
        // 邮编
        'zip_code',
        // 地址
        'address',
        // 备注说明
        'remarks',
        // 客户类型，1：外部客户，0：内部客户
        'user_type',
        // 总金额
        'amount',
        // 剩余金额
        'overage_amount',
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
     * get list
     *
     * @time 2021/2/7
     * @param $params
     * @return \think\Paginator
     *
     * @throws \think\db\exception\DbException
     */
    public function getList()
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['is_company']) {
                $where = [
                    'c.id' => $prowerData['company_id']
                ];
            }
            if ($prowerData['shop_ids'] && !$prowerData['is_company'] && !$prowerData['is_buyer_staff']) {
                $company_ids = Shop::where('id', 'in', $prowerData['shop_ids'])->value('company_id');
                $where = [
                    'c.id' => ['in', $company_ids]
                ];
            }
        }
        if ($prowerData['is_buyer_staff']) {
            return $this
                ->field('c.id, c.is_status, c.code, c.name, c.contacts, c.mobile, c.salesman_username, c.type, c.user_type,
            c.updated_at, c.created_at, c.amount, c.overage_amount, c.account_id, ua.phone, ua.name as account_name, ua.email,
            u.username as creator_name, IFNULL(us.username, "-") as update_name, ua.status as user_status')
                ->alias('c')
                ->catchSearch()
                ->order('c.updated_at', 'desc')
                ->leftJoin('users ua', 'ua.id = c.account_id')
                ->leftJoin('users u', 'u.id = c.creator_id')
                ->leftJoin('users us', 'us.id = c.update_by')
                ->paginate();
        } else {
            return $this->dataRange()
                ->field('c.id, c.is_status, c.code, c.name, c.contacts, c.mobile, c.salesman_username, c.type, c.user_type,
            c.updated_at, c.created_at, c.amount, c.overage_amount, c.account_id, ua.phone, ua.name as account_name, ua.email,
            u.username as creator_name, IFNULL(us.username, "-") as update_name, ua.status as user_status')
                ->alias('c')
                ->catchSearch()
                // ->whereOr($where)
                ->whereOr(function ($query) use ($where) {
                    if (count($where) > 0) {
                        $query->where($where)
                            ->catchSearch();
                    }
                })
                ->order('c.updated_at', 'desc')
                ->leftJoin('users ua', 'ua.id = c.account_id')
                ->leftJoin('users u', 'u.id = c.creator_id')
                ->leftJoin('users us', 'us.id = c.update_by')
                ->paginate();
        }
    }

    public function getUserAmount($id)
    {
        return $this->where('id', $id)
            ->where('overage_amount', '>', 0)
            ->find();
    }


    public function amountDeduction($amount, $companyId)
    {

        $user_amount = $this->where('id', $companyId)->decrement('overage_amount', (float)sprintf("%.2f", $amount));
        $dataObj = $this->where('id', $companyId)->find();
        //余额判断扣除成功进行记录操作
        if (isset($user_amount) && $user_amount > 0) {
            $user_log = new CompanyAmountLog();
            $user_log->company_id = $dataObj['id'];
            $user_log->before_modify_amount = $dataObj['overage_amount'];
            $user_log->subtract_amount = (float)sprintf("%.2f", $amount);
            $user_log->charge_balance = bcsub(strval($dataObj['overage_amount']), strval((float)sprintf("%.2f", $amount)), 4);
            $user_log->type = 1;
            $user_log->created_at = time();
            $user_log->save();
            if (!isset($user_log)) {
                throw new FailedException('扣费日志记录失败');
            }
        } else {
            throw new FailedException('扣费失败');
        }
    }

    /**
     * 获取所有客户列表
     */
    public function getCompanyList()
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['is_company']) {
                $where = [
                    'c.id' => $prowerData['company_id']
                ];
            }
            if ($prowerData['shop_ids'] && !$prowerData['is_company']) {
                $company_ids = Shop::where('id', 'in', $prowerData['shop_ids'])->value('company_id');
                $where = [
                    'c.id' => ['in', $company_ids]
                ];
            }
        }

        return $this->dataRange()
            ->field('c.id, c.is_status, c.code, c.name, c.contacts, c.mobile, c.salesman_username, c.type, c.user_type,
            c.updated_at, c.created_at, c.amount, c.overage_amount, c.account_id, ua.phone, ua.name as account_name, ua.email,
            u.username as creator_name, IFNULL(us.username, "-") as update_name, ua.status as user_status')
            ->alias('c')
            ->catchSearch()
            // ->whereOr($where)
            ->whereOr(function ($query) use ($where) {
                if (count($where) > 0) {
                    $query->where($where)
                        ->catchSearch();
                }
            })
            ->order('c.updated_at', 'desc')
            ->leftJoin('users ua', 'ua.id = c.account_id')
            ->leftJoin('users u', 'u.id = c.creator_id')
            ->leftJoin('users us', 'us.id = c.update_by')
            ->select();
    }

    /**
     * 获取借卖订单选择客户
     */
    public function getBorrowSellCompanyList()
    {
        return $this->field('id, code, name, type, is_status, account_id')
            ->where('is_status', 1)
            ->select();
    }
}
