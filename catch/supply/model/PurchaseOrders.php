<?php

namespace catchAdmin\supply\model;

use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\permissions\model\Department;
use catchAdmin\permissions\model\Users;
use catcher\base\CatchModel as Model;
use catcher\traits\db\BaseOptionsTrait;
use catcher\traits\db\ScopeTrait;
use catchAdmin\supply\model\search\PurchaseOrderSearch;
use catchAdmin\supply\model\PurchaseOrderProducts;

class PurchaseOrders extends Model
{
    use BaseOptionsTrait, ScopeTrait, PurchaseOrderSearch, DataRangScopeTrait;
    // 表名
    public $name = 'purchase_orders';
    // 数据库字段映射
    public $field
    = array(
        'id',
        // 采购申请单编码
        'code',
        // 采购金额
        'amount',
        // 审核状态，0-未审核 1-已审核
        'audit_status',
        //审核意见
        'audit_notes',
        // 是否生成合同，0-未生成 1-已生成
        'contract_status',
        // 合同编码
        'contract_code',
        // 所属组织
        'organization',
        //备注
        'notes',
        // products json
        'products',
        // parts
        'parts',
        //币别 可用值： rmb , usd
        'currency',
        //采购员id
        'purchase_id',
        //采购员名称
        'purchase_name',
        // 创建人
        'created_by',
        // 修改人
        'updated_by',
        // 创建时间
        'created_at',
        // 修改时间
        'updated_at',
        // 删除时间
        'deleted_at',
    );


    protected $json = ['products', 'parts'];
    protected $jsonAssoc = true;

    protected $append
    = [
        // 'created_by_name', 'updated_by_name',
        'audit_status_text', 'contract_status_text',
        'contract_code',
        // 'buyer'
    ];

    /**
     * 列表
     * @return mixed|\think\Paginator
     * @throws \think\db\exception\DbException
     */
    public function getList($action = 'list')
    {
        $users = new Users;
        $whereOr = [];
        $prowerData = $users->getRolesList();
        // 采购员
        if ($prowerData['is_buyer_staff']) {
            $whereOr = [
                'purchase_id' => $prowerData['user_id']
            ];
        }

        $order =  $this
            ->dataRange([], 'created_by')
            ->field('w.id, w.code,w.amount,w.audit_status, w.audit_notes, w.contract_status,
                w.contract_code, w.organization, w.notes, w.products, w.parts, w.currency, w.purchase_id,
                w.purchase_name as buyer, w.created_by, w.updated_by, w.created_at, w.updated_at,
                IFNULL(usa.username, "-") as audit_by_name, d.department_name,
                u.username as created_by_name, IFNULL(us.username, "-") as updated_by_name')
            ->alias('w')
            ->catchSearch()
            ->whereOr(function ($query) use ($whereOr) {
                if (count($whereOr) > 0) {
                    $query->where($whereOr)
                        ->catchSearch();
                }
            })
            ->leftJoin('users u', 'u.id = w.created_by')
            ->leftJoin('users us', 'us.id = w.updated_by')
            ->leftJoin('users usa', 'usa.id = w.purchase_id')
            ->leftJoin('departments d', 'd.id = usa.department_id');

        if ($action == 'list') { //列表
            $order = $order->order('w.id', 'desc')->paginate();
        } elseif ($action == 'export') { //导出
            $order = $order->order('w.id', 'desc')->select();
        }
        return $order;
    }


    public function exportField()
    {
        return [
            [
                'title' => 'ID',
                'filed' => 'id',
            ],
            [
                'title' => '采购申请单编码',
                'filed' => 'code',
            ],
            [
                'title' => '采购总金额',
                'filed' => 'amount',
            ],
            [
                'title' => '审核状态',
                'filed' => 'audit_status_text',
            ],
            [
                'title' => '是否生成采购合同',
                'filed' => 'contract_status_text',
            ],
            [
                'title' => '合同编码',
                'filed' => 'contract_code',
            ],
            [
                'title' => '所属组织',
                'filed' => 'organization',
            ],
            [
                'title' => '创建人',
                'filed' => 'created_by_name',
            ],
            [
                'title' => '创建时间',
                'filed' => 'created_at',
            ],
            [
                'title' => '修改人',
                'filed' => 'updated_by_name',
            ],
            [
                'title' => '修改时间',
                'filed' => 'updated_at',
            ],
        ];
    }
    public function products($id)
    {
        return PurchaseOrderProducts::where([
            'purchase_order_id' => $id,
            'type' => 1
        ])
            ->select()->each(function (&$item) {
                if (!empty($item->arrive_date)) {
                    $item->arrive_date = date('Y-m-d', strtotime($item->arrive_date));
                }
            });
    }

    public function parts($id)
    {
        return PurchaseOrderProducts::where([
            'purchase_order_id' => $id,
            'type' => 2
        ])
            ->select()->each(function (&$item) {
                if (!empty($item->arrive_date)) {
                    $item->arrive_date = date('Y-m-d', strtotime($item->arrive_date));
                }
            });
    }


    public function getContractCodeAttr()
    {
        return PurchaseContracts::where('purchase_order_id', $this->getAttr('id'))->column('code');
    }

    public function getCreatedByName()
    {
        return Users::where('id', $this->getAttr('created_by'))->value('username') ?? '';
    }

    public function getUpdatedByName()
    {
        return Users::where('id', $this->getAttr('updated_by'))->value('username') ?? '';
    }

    public function getAmountAttr()
    {
        return PurchaseOrderProducts::where('purchase_order_id', $this->getAttr('id'))
            ->sum('amount');
    }


    public function getAuditStatusTextAttr()
    {

        switch ($this->getAttr('audit_status')) {
            case '-1':
                return '采购员审核驳回';
                break;
            case '-2':
                return '运营员审核驳回';
                break;
            case 0:
                return '待提交';
                break;
            case 1:
                return '待采购员审核';
                break;
            case 2:
                return '待运营审核';
                break;
            case 3:
                return '运营审核通过';
                break;
            default:
                return '';
                break;
        }
    }


    public function getContractStatusTextAttr()
    {

        switch ($this->getAttr('contract_status')) {

            case 0:
                return '未生成';
                break;
            case 1:
                return '已生成';
                break;
            default:
                return '';
                break;
        }
    }

    public function getBuyer()
    {
        return PurchaseOrderProducts::where('purchase_order_id', $this->getAttr('id'))->value('buyer');
    }

    public function getOrganizationAttr()
    {
        $buyer = PurchaseOrderProducts::where('purchase_order_id', $this->getAttr('id'))->value('buyer') ?? '';
        $deparmentId =  Users::where('username', $buyer)->value('department_id') ?? '';
        return Department::where('id', $deparmentId)->value('department_name') ?? '';
    }

    public function createOrderNo()
    {
        $date  = date('Ymd');
        $time  = strtotime($date);
        $count = PurchaseOrders::where('created_at', '>', $time)->count();
        $str   = sprintf("%04d", $count + 1);
        return "PP" . $date . $str;
    }

    /**
     * 生成入库单json数据
     */
    public function fixProduct($id = null)
    {
        $where = [];
        if (!empty($id)) {
            $where = [
                'id' => $id
            ];
        }
        $dataAll = $this->where($where)->order('products','asc')->select();
        $model = new PurchaseOrderProducts();
        $data = [];
        foreach ($dataAll as $key => $value) {
            $product = $model->field(['id', 'goods_name', 'goods_code', 'number', 'arrive_date'])->where(['type' => 1, 'purchase_order_id' => $value['id']])->select();

            $parts = $model->field(['id', 'goods_name', 'goods_code', 'number', 'arrive_date'])->where(['type' => 2, 'purchase_order_id' => $value['id']])->select();
            if (!empty($product)) {
                $data['products'] =  json_encode($product);
            }
            if (!empty($parts)) {
                $data['parts'] =  json_encode($parts);
            }
            $this->where(['id' => $value['id']])->update($data);
        }
        return true;
    }
}
