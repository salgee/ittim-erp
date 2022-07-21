<?php


namespace catchAdmin\settlement\controller;

use catchAdmin\finance\model\LogisticsTransportOrder;
use catchAdmin\order\model\OrderDeliver;
use catchAdmin\order\model\OrderRecords;
use catchAdmin\permissions\model\Users;
use catchAdmin\product\model\Product;
use catchAdmin\settlement\model\StorageFee;
use catchAdmin\settlement\model\StorageProductFee;
use catchAdmin\supply\excel\CommonExport;
use catcher\base\CatchController;
use catcher\base\CatchRequest;
use catcher\CatchResponse;
use catcher\Code;
use think\facade\Db;
use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\product\model\Parts;
use catchAdmin\basics\model\Company;
use catchAdmin\warehouse\model\Warehouses;

class Settlement extends CatchController
{

    use DataRangScopeTrait;
    protected $storageFeeModel;
    protected $storageProductFeeModel;

    public function __construct(StorageFee $storageFee, StorageProductFee $storageProductFee)
    {
        $this->storageFeeModel        = $storageFee;
        $this->storageProductFeeModel = $storageProductFee;
    }

    /**
     * 物流费/订单处理费查询
     *
     * @param CatchRequest $request
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderFee(CatchRequest $request)
    {

        $users = new Users;
        $whereOr = [];
        $prowerData = $users->getRolesList();

        if (!$prowerData['is_admin']) {
            // $whereOr = [
            //     'od.creator_id' => $prowerData['user_id']
            // ];
            // 客户
            if ($prowerData['is_company']) {
                if ($prowerData['company_id']) {
                    $whereOr = [
                        'od.company_id' => $prowerData['company_id']
                    ];
                }
            }
            if ($prowerData['shop_ids'] && !$prowerData['is_company']) {
                $whereOr = [
                    ['od.shop_basics_id', 'in',  $prowerData['shop_ids']]
                ];
            }
        }
        $orderDeliver = new OrderDeliver;
        $query = $orderDeliver->dataRange()->alias('od')
            ->where('od.logistics_status', '1')
            ->whereNotIn('od.delivery_state', '6')
            ->leftJoin('order_deliver_products odp', 'odp.order_deliver_id = od.id')
            ->leftJoin('order_records or', 'or.id = od.order_record_id')
            ->leftJoin('shop_basics sp', 'sp.id = od.shop_basics_id')
            ->field('od.shop_basics_id, sp.shop_name, odp.type, or.id, od.goods_group_name, od.goods_code, od.order_type_source, od.id as order_deliver_id, 
            or.order_no, (od.order_price*od.number ) as order_operation_fee, od.company_id, od.hedge_fee,
            (od.freight_weight_price + od.freight_additional_price + od.hedge_fee) *od.number + postcode_fee as order_logistics_fee,
            postcode_fee, od.number, od.shipping_code, od.goods_id, or.created_at, od.zone, od.platform_no, od.platform_id')
            // ->whereNotIn('od.platform_id', [3, 4])

            ->whereRaw("od.deliver_type = 0 or od.logistics_type < 2");

        $params = $request->param();
        if (isset($params['order_no']) && $params['order_no']) {

            $query->whereLike('or.order_no', $params['order_no']);
        }

        $res = $query
            // ->where($whereOr)
            ->whereOr(function ($query) use ($whereOr, $params) {
                if (count($whereOr) > 0) {
                    if (isset($params['order_no']) && $params['order_no']) {
                        $query->whereLike('or.order_no', $params['order_no']);
                    }

                    $query->where($whereOr)
                        ->whereRaw("od.deliver_type = 0 or od.logistics_type < 2")
                        ->where('od.logistics_status', '1')
                        ->whereNotIn('od.delivery_state', '6')
                        ->catchSearch();
                }
            })
            ->order('or.id', 'desc')
            ->paginate();
        foreach ($res as &$val) {
            if ((int)$val->type == 1) {
                $product = Product::find($val->goods_id);
                //获取商品sku
                if (empty($val->goods_group_name)) {
                    if ((int)$val->order_type_source == 2) {
                        $val->goods_code = $val->goods_code;
                    } else {
                        $val->goods_code = $product->code ?? '';
                    }
                } else {
                    $val->goods_code = $val->goods_group_name;
                }

                $val->name_ch = $product->name_ch ?? '';
                $val->category =  $product->category->getAttr('parent_name')   . '-' . $product->category->getAttr('name');
                //客户
                $val->company = $product->company->getAttr('name');
                //计算费用合计

                if (in_array($val->platform_id, [3, 4])) {
                    //wayfair和overstock订单不计算物流费
                    $val->order_logistics_fee = 0;
                }

                $val->total_fee = sprintf("%.2f", $val->order_operation_fee + $val->order_logistics_fee);
                $val->quota     = $val->total_fee;
            } else {
                $parts = Parts::find($val->goods_id);
                //获取商品sku
                $val->goods_code = $parts->code ?? '';
                $val->name_ch = $parts->name_ch ?? '';
                $val->category =  $parts->category->getAttr('parent_name')   . '-' . $parts->category->getAttr('name');
                // //客户
                $val->company = Company::where('id', $val->company_id)->value('name');
                // //计算费用合计

                if (in_array($val->platform_id, [3, 4])) {
                    //wayfair和overstock订单不计算物流费
                    $val->order_logistics_fee = 0;
                }

                $val->total_fee = sprintf("%.2f", $val->order_operation_fee + $val->order_logistics_fee);
                $val->quota     = $val->total_fee;
            }
        }
        return CatchResponse::paginate($res);
    }

    /**
     * 费用单导出
     *
     * @param CatchRequest $request
     * @return void
     */
    public function orderFeeExport(CatchRequest $request)
    {
        $exportField =  [
            [
                'title' => '商品SKU',
                'filed' => 'goods_code',
            ],
            [
                'title' => '商品名称',
                'filed' => 'name_ch',
            ],
            [
                'title' => '分类',
                'filed' => 'category',
            ],
            [
                'title' => '所属客户',
                'filed' => 'company',
            ],
            [
                'title' => '物流单号',
                'filed' => 'shipping_code',
            ],
            [
                'title' => '订单编号',
                'filed' => 'order_no',
            ],
            [
                'title' => '物流费用（USD)',
                'filed' => 'order_logistics_fee',
            ],
            [
                'title' => '订单操作费用（USD)',
                'filed' => 'order_operation_fee',
            ],
            [
                'title' => '费用合计',
                'filed' => 'total_fee',
            ],
            [
                'title' => '扣减额度',
                'filed' => 'quota',
            ],
        ];

        $users = new Users;
        $whereOr = [];
        $prowerData = $users->getRolesList();

        // if (!$prowerData['is_admin']) {
        //     $whereOr = [
        //         'od.creator_id' => $prowerData['user_id']
        //     ];
        // }
        if (!$prowerData['is_admin']) {
            // $whereOr = [
            //     'od.creator_id' => $prowerData['user_id']
            // ];
            // 客户
            if ($prowerData['is_company']) {
                if ($prowerData['company_id']) {
                    $whereOr = [
                        'od.company_id' => $prowerData['company_id']
                    ];
                }
            }
            if ($prowerData['shop_ids'] && !$prowerData['is_company']) {
                $whereOr = [
                    ['od.shop_basics_id', 'in',  $prowerData['shop_ids']]
                ];
            }
        }

        $query = OrderDeliver::alias('od')
            ->leftJoin('order_records or', 'or.id = od.order_record_id')
            ->leftJoin('product p', 'p.id = od.goods_id')
            ->leftJoin('category c', 'p.category_id = c.id')
            ->leftJoin('company company', 'company.id = od.company_id')
            ->field('or.id, od.id as order_deliver_id, or.order_no, 
            (od.order_price*od.number ) as order_operation_fee, 
            (od.freight_weight_price + od.freight_additional_price + od.hedge_fee) *od.number + postcode_fee as order_logistics_fee , 
            od.number, od.shipping_code, od.goods_id,od.platform_id, or.created_at,
            od.goods_code, p.name_ch, c.parent_name,c.name, company.name as company')
            ->whereNotIn('od.platform_id', [3, 4])
            ->whereNotIn('od.delivery_state', '6')
            ->whereRaw("od.deliver_type = 0 or od.logistics_type < 2");
        $params = $request->param();
        if (isset($params['order_no']) && $params['order_no']) {

            $query->whereLike('or.order_no', $params['order_no']);
        }

        $res = $query->where($whereOr)->select()->toArray();

        foreach ($res as &$val) {
            $val['category'] =  $val['parent_name'] . '-' . $val['name'];
            //计算费用合计
            if (in_array($val['platform_id'], [3, 4])) {
                //wayfair和overstock订单不计算物流费
                $val->order_logistics_fee = 0;
            }
            $val['total_fee'] = sprintf("%.2f", $val['order_operation_fee'] + $val['order_logistics_fee']);
            $val['quota']     = $val['total_fee'];
        }

        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        if (isset($params['exportField'])) {
            $exportField = $params['exportField'];
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '订单处理费');
        return  CatchResponse::success($url);
    }

    /**
     * 物流费/订单处理费详情
     *
     * @param CatchRequest $request
     * @param              $id
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function orderFeeInfo(CatchRequest $request, $id)
    {

        $orderDeliver = OrderDeliver::find($id);
        if (!$orderDeliver) {
            return CatchResponse::fail('发货单不存在', Code::FAILED);
        }

        $order = OrderRecords::with(['shop', 'buyer'])->find($orderDeliver->order_record_id);
        $order->get_at =  $order->get_at == '' ? '' : date('Y-m-d H:i:s', $order->get_at);
        $order->paid_at =  $order->paid_at == '' ? '' : date('Y-m-d H:i:s', $order->paid_at);
        $order->shipped_at = $order->shipped_at == '' ? '' : date('Y-m-d H:i:s', $order->shipped_at);
        $order->created_by_name = Users::where('id', $order->creator_id)->value('username') ?? '';
        $order->updated_by_name = Users::where('id', $order->updater_id)->value('username') ?? '';

        if (!$order) {
            return CatchResponse::fail('订单不存在', Code::FAILED);
        }

        if ($orderDeliver->product) {
            $lo = LogisticsTransportOrder::where('invoice_order_no', $orderDeliver->invoice_no)->find();
            $orderDeliver->product->company_name = $lo->company_name ?? $orderDeliver->product->product->company->getAttr('name') ?? '';
            $orderDeliver->product->basics_fee = $lo->basics_fee ?? 0;
            $orderDeliver->product->fuel_surchage = $lo->fuel_surchage ?? 0;
            $orderDeliver->product->AHS = $lo->AHS ?? 0;
            $orderDeliver->product->oversize_charge = $lo->oversize_charge ?? 0;
            $orderDeliver->product->address_correction = $lo->address_correction ?? 0;
            $orderDeliver->product->direct_signature = $lo->direct_signature ?? 0;
            $orderDeliver->product->DAS_comm = $lo->DAS_comm ?? 0;
            $orderDeliver->product->DAS_extended_comm = $lo->DAS_extended_comm ?? 0;
            $orderDeliver->product->residential_delivery = $lo->residential_delivery ?? 0;
            $orderDeliver->product->unauthorized_OS = $lo->unauthorized_OS ?? 0;
            $orderDeliver->product->return_pickup_fee = $lo->return_pickup_fee ?? 0;
            $orderDeliver->product->total_fee =  $lo == null  ? 0 : $lo->basics_fee + $lo->fuel_surchage + $lo->AHS + $lo->oversize_charge + $lo->address_correction + $lo->direct_signature + $lo->DAS_comm + $lo->DAS_extended_comm +  $lo->residential_delivery + $lo->unauthorized_OS
                + $lo->return_pickup_fee;

            $order->product = $orderDeliver->product;
        }
        return CatchResponse::success($order);
    }

    /**
     * 仓储费查询
     *
     * @param CatchRequest $request
     * @param CatchAuth $auth
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function storageFee(CatchRequest $request)
    {
        $id = request()->user()['department_id'] ?? 0;

        $whereOr = [];
        $group = 'spf.storage_fee_id, spf.department_id';
        $users = new Users();
        $prowerData = $users->getRolesList();
        if (!$prowerData['is_admin']) {
            if ($prowerData['is_company']) { // 如果是客户
                if ($prowerData['company_id']) {
                    // 查看看自己仓储费
                    // $whereOr = [
                    //     'sf.company_id' => $prowerData['company_id']
                    // ];
                    $group = 'spf.storage_fee_id';
                    // 计算相对应仓库金额
                    $warehouseIds = Warehouses::where('company_id', $prowerData['company_id'])->column('id');
                    if (!empty($warehouseIds)) {
                        $whereOr = [['spf.virtual_warehouse_id', 'in', $warehouseIds]];
                    } else {
                        $whereOr = [['spf.virtual_warehouse_id', 'in', $warehouseIds]];
                    }
                }
            } else {
                // 客户查看自己的
                if ($id > 1) {
                    $whereOr = [
                        ['spf.department_id', '=', $id],
                    ];
                    $group = 'spf.storage_fee_id,  spf.department_id';
                } else {
                    $whereOr = [
                        ['spf.department_id', '<', 0]
                    ];
                }
            }
        }
        // 组织维度
        $list = $this->storageFeeModel->alias('sf')
            ->leftJoin('storage_product_fee spf', 'spf.storage_fee_id = sf.id')
            ->field('sf.*, spf.storage_fee_id, spf.department_id,  sum(spf.fee)  as storage_fee')
            ->where($whereOr)
            ->catchSearch()
            ->group($group)
            ->paginate();
        //     ->fetchSql()->find();
        // var_dump($list);
        // exit;

        return CatchResponse::paginate($list);
    }

    /**
     * 仓储费详情
     *
     * @param CatchRequest $request
     * @param int $id
     * @return \think\response\Json
     */
    public function storageProductFee(CatchRequest $request, $id, $deparmentId)
    {
        $whereOr = [];
        $users = new Users();
        $prowerData = $users->getRolesList();
        if ($prowerData['is_company']) { // 如果是客户
            if ($prowerData['company_id']) {
                // 获取客户仓库
                $warehouseIds = Warehouses::where('company_id', $prowerData['company_id'])->column('id');
                if (!empty($warehouseIds)) {
                    $whereOr = [['virtual_warehouse_id', 'in', $warehouseIds]];
                } else {
                    $whereOr = [['virtual_warehouse_id', 'in', $warehouseIds]];
                }
            }
        } else {
            $whereOr = [['department_id', '=', $deparmentId]];
        }

        $list = $this->storageProductFeeModel
            ->where('storage_fee_id', $id)
            // ->where('department_id', $deparmentId)
            ->where($whereOr)
            ->catchSearch()
            ->order('created_at', 'desc')
            ->paginate();
        return CatchResponse::paginate($list);
    }

    /**
     * 仓储费列表导出
     *
     * @return \think\response\Json
     */
    public function storageFeeExport(CatchRequest $request)
    {

        $id = request()->user()['department_id'] ?? 0;

        $whereOr = [];
        $whereOr = [];
        $users = new Users();
        $prowerData = $users->getRolesList();
        $group = 'spf.storage_fee_id,  spf.department_id';
        if (!$prowerData['is_admin']) {
            if ($prowerData['is_company']) { // 如果是客户
                if ($prowerData['company_id']) {
                    // 查看看自己仓储费
                    $whereOr = [
                        'sf.company_id' => $prowerData['company_id']
                    ];
                    $group = 'spf.storage_fee_id';
                    // 计算相对应仓库金额
                    $warehouseIds = Warehouses::where('company_id', $prowerData['company_id'])->column('id');
                    if (!empty($warehouseIds)) {
                        $whereOr = [['spf.virtual_warehouse_id', 'in', $warehouseIds]];
                    } else {
                        $whereOr = [['spf.virtual_warehouse_id', 'in', $warehouseIds]];
                    }
                }
            } else {
                // 客户查看自己的
                if ($id > 1) {
                    $whereOr = [
                        'spf.department_id' => $id
                    ];
                    $group = 'spf.storage_fee_id,  spf.department_id';
                } else {
                    $whereOr = [
                        ['spf.department_id', '<', 0]
                    ];
                }
            }
        }


        $res = $this->storageFeeModel->alias('sf')
            ->leftJoin('storage_product_fee spf', 'spf.storage_fee_id = sf.id')
            ->field('sf.*, spf.storage_fee_id, spf.department_id,  sum(spf.fee)  as storage_fee')
            ->group($group)
            ->where($whereOr)
            ->catchSearch()
            ->order('created_at', 'desc')
            ->select();
        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        $data = $request->post();

        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->storageFeeModel->exportField();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '仓储费');
        return  CatchResponse::success($url);
    }

    /**
     * 仓储费详情导出
     *
     * @param CatchRequest $request
     * @param int $id
     * @return \think\response\Json
     */
    public function storageProductFeeExport(CatchRequest $request, $id)
    {
        $data = $request->post();
        $whereOr = [];
        $users = new Users();
        $prowerData = $users->getRolesList();
        if ($prowerData['is_company']) { // 如果是客户
            if ($prowerData['company_id']) {
                // 获取客户仓库
                $warehouseIds = Warehouses::where('company_id', $prowerData['company_id'])->column('id');
                if (!empty($warehouseIds)) {
                    $whereOr = [['virtual_warehouse_id', 'in', $warehouseIds]];
                } else {
                    $whereOr = [['virtual_warehouse_id', 'in', $warehouseIds]];
                }
            }
        } else {
            $whereOr = [['department_id', '=', $data['department_id']]];
        }
        $res = $this->storageProductFeeModel
            ->where('storage_fee_id', $id)
            // ->where('department_id', $deparmentId)
            ->where($whereOr)
            ->order('created_at', 'desc')
            ->catchSearch()
            ->select();

        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }


        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->storageProductFeeModel->exportField();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '仓储费详情');
        return  CatchResponse::success($url);
    }
}
