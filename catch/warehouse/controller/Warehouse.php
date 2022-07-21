<?php


namespace catchAdmin\warehouse\controller;


use catchAdmin\basics\model\Company;
use catchAdmin\delivery\common\DeliveryUpsCommon;
use catchAdmin\permissions\model\Users;
use catchAdmin\product\model\Category;
use catchAdmin\product\model\Parts;
use catchAdmin\product\model\Product;
use catchAdmin\product\model\ProductInfo;
use catchAdmin\supply\model\Supply;
use catchAdmin\warehouse\model\OutboundOrders;
use catchAdmin\warehouse\model\WarehouseOrders;
use catchAdmin\warehouse\model\Warehouses;
use catchAdmin\warehouse\model\WarehouseStock;
use catchAdmin\warehouse\request\WarehouseCreateRequest;
use catchAdmin\warehouse\request\WarehouseUpdateRequest;
use catcher\base\CatchController;
use catcher\base\CatchRequest;
use catcher\CatchResponse;
use catcher\Code;
use think\facade\Db;

class Warehouse extends CatchController
{
    protected $warehouseModel;

    public function __construct(Warehouses $warehouses)
    {
        $this->warehouseModel = $warehouses;
    }

    /**
     * 列表
     * @param  CatchAuth $auth
     * @return \think\response\Json
     */
    public function index()
    {

        return CatchResponse::paginate($this->warehouseModel->getList());
    }

    /**
     * 仓管管理列表
     * @param CatchRequest $request
     * @return \think\response\Json
     */
    public function tree(CatchRequest $request)
    {

        $query = Warehouses::order('id', 'desc');
        $name = $request->get('name', '');
        if ($name) {
            $query->whereLike('name', $name);
        } else {
            $query->where('parent_id', 0);
        }

        $is_third_part = $request->get('is_third_part', '');
        if (is_numeric($is_third_part)  && $is_third_part >= 0) {
            $query->where('is_third_part', $is_third_part);
        }

        $warehouses = $query->select()->toArray();

        foreach ($warehouses as $key => &$val) {
            $child = Warehouses::where('parent_id', $val['id'])->order('id', 'desc')->select()->toArray();
            $val['children'] = $child;
        }
        return CatchResponse::success($warehouses);
    }


    /**
     * 保存信息
     * @time 2021年01月23日 14:55
     *
     * @param Request $request
     */
    public function save(WarehouseCreateRequest $request): \think\Response
    {
        try {
            $data               = $request->param();
            $data['created_by'] = $data['creator_id'];

            if ($data['is_third_part']  == 1 && $data['type'] > 2) {
                return CatchResponse::fail('第三方仓库只能是实体仓或虚拟仓', Code::FAILED);
            }

            if ($data['type'] == 2) {
                $parentWarehouse = $this->warehouseModel->findBy($data['parent_id']);

                //如果添加虚拟仓 判断父级仓库是否是第三方仓库 如果是，只能添加一个虚拟仓
                if ($parentWarehouse->is_third_part == 1) {
                    // $hasThirdPart= $this->warehouseModel->where('type', $data['type'])->where('parent_id', $data['parent_id'])->count();
                    // if ($hasThirdPart) {
                    //     return CatchResponse::fail('当前仓库已有虚拟仓 ，不能添加', Code::FAILED);
                    // }

                    $data['is_third_part'] = 1;
                }
            }


            //判断是否有存在的FBA仓库 FBA仓库只能有一个
            if ($data['type'] == 4) {
                $hasFba = $this->warehouseModel->where('type', $data['type'])->count();
                if ($hasFba) {
                    return CatchResponse::fail('系统已存在FBA仓库 ，不能添加', Code::FAILED);
                }
            }

            //校验仓库地址
            $ups = new DeliveryUpsCommon();
            $params = $data;
            $params['address1'] = $data['street'];
            $params['country'] = 'US';
            $response = $ups->isAddr($params);
            if ($response->isValid()) {
                $validAddress = $response->getValidatedAddress();
                $data['state'] = $validAddress->politicalDivision1;
                $data['city'] = $validAddress->politicalDivision2;
                $data['street'] = $validAddress->addressLine;
                $data['zipcode'] = $validAddress->postcodePrimaryLow;
            }
            $data['usps_json'] = [];

            $this->warehouseModel->storeBy($data);
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 保存信息
     * @time 2021年01月23日 14:55
     *
     * @param Request $request
     */
    public function update(WarehouseUpdateRequest $request, $id): \think\Response
    {
        try {
            $data               = $request->post();
            $data['updated_by'] = $data['creator_id'];
            $warehouse          = $this->warehouseModel->findBy($id);
            if (!$warehouse) {
                return CatchResponse::fail('仓库不存在', Code::FAILED);
            }

            if ($warehouse->is_active == 1) {
                return CatchResponse::fail('仓库已启用，不能修改', Code::FAILED);
            }

            if ($data['is_third_part'] == 1 && $data['type'] > 2) {
                return CatchResponse::fail('第三方仓库只能是实体仓或虚拟仓', Code::FAILED);
            }
            if($warehouse['parent_id'] !== $data['parent_id']) {
                return CatchResponse::fail('所属实体仓库不可编辑', Code::FAILED);
            }

            if ($data['type'] == 2) {
                $parentWarehouse = $this->warehouseModel->findBy($data['parent_id']);

                //如果添加虚拟仓 判断父级仓库是否是第三方仓库 如果是，只能添加一个虚拟仓
                if ($parentWarehouse->is_third_part == 1) {
                    // $hasThirdPart= $this->warehouseModel->where('type', $data['type'])->where('parent_id', $data['parent_id'])->count();
                    // if ($hasThirdPart) {

                    //     return CatchResponse::fail('当前仓库已有虚拟仓', Code::FAILED);
                    // }
                    $data['is_third_part'] = 1;
                }
            }

            if ($data['type'] == 4) {
                $hasFba = $this->warehouseModel->where('type', $data['type'])->where('id', '<>', $id)->count();

                if ($hasFba) {
                    return CatchResponse::fail('系统已存在FBA仓库，不能改为FBA仓库', Code::FAILED);
                }
            }

            //校验仓库地址
            $ups = new DeliveryUpsCommon();
            $params = $data;
            $params['address1'] = $data['street'];
            $params['country'] = 'US';
            $response = $ups->isAddr($params);

            if ($response->isValid()) {
                $validAddress = $response->getValidatedAddress();
                $data['state'] = $validAddress->politicalDivision1;
                $data['city'] = $validAddress->politicalDivision2;
                $data['street'] = $validAddress->addressLine;
                $data['zipcode'] = $validAddress->postcodePrimaryLow;
            }

            $this->warehouseModel->updateBy($id, $data);

            //如果是实体仓，则修改所属虚拟仓的地址信息
            if ($data['type'] == 1) {
                Warehouses::update($data, ['parent_id' => $id], ['state', 'city', 'street', 'zipcode']);
            }
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }


    /**
     * 删除
     * @time 2021年01月23日 14:55
     *
     * @param $id
     */
    public function delete($id): \think\Response
    {
        $warehouse = $this->warehouseModel->findBy($id);

        if (!$warehouse) {
            return CatchResponse::fail('仓库不存在', Code::FAILED);
        }


        if ($warehouse->is_active == 1) {
            return CatchResponse::fail('仓库已启用，不能删除', Code::FAILED);
        }

        $childWarehouse  = $warehouse->childWarehouse();
        if (!$childWarehouse->isEmpty()) {
            return CatchResponse::fail('当前仓库下还有下级仓库，不能删除', Code::FAILED);
        }

        //查找仓库是否有存在业务
        $warehouseOrder = WarehouseOrders::where('entity_warehouse_id', $id)->whereOr('virtual_warehouse_id', $id)
            ->where('warehousing_status', 0)
            ->count();
        if ($warehouseOrder) {
            return CatchResponse::fail('当前仓库有未处理的入库单，不能删除', Code::FAILED);
        }


        $outBoundOrder = OutboundOrders::where('entity_warehouse_id', $id)->whereOr(
            'virtual_warehouse_id',
            $id
        )
            ->where('outbound_status', 0)
            ->count();
        if ($outBoundOrder) {
            return CatchResponse::fail('当前仓库有未处理的出库单，不能删除', Code::FAILED);
        }

        return CatchResponse::success($this->warehouseModel->deleteBy($id));
    }


    /**
     * 修改状态
     *
     * @param CatchRequest $request
     *
     * @return \think\response\Json
     */
    public function changeActiveStatus(CatchRequest $request)
    {
        $data = $request->param();
        $list = [];
        foreach ($data['ids'] as $val) {
            //一个实体仓下面只能有一个残品仓（启用的）
            //检查当前仓库是否为残品仓，如果是残品仓，则查找当前所属实体仓下是否还有已启用的残品仓

            $warehouse = $this->warehouseModel->findBy($val);

            if ($warehouse && $warehouse->type == 3 && $data['is_active'] == 1) {
                $count = $this->warehouseModel->where([
                    'parent_id' => $warehouse->parent_id,
                    'type' => 3,
                    'is_active' => 1
                ])->count();
                if ($count > 0) {
                    return CatchResponse::fail(
                        $warehouse->parent_warehouse . "只能有一个已启用的残品仓",
                        Code::FAILED
                    );
                }
            }


            $row['id']         = $val;
            $row['is_active']  = $data['is_active'];
            $row['updated_by'] = $data['creator_id'];
            $list[]            = $row;
        }
        $this->warehouseModel->saveAll($list);
        return CatchResponse::success(true);
    }

    /**
     * 获取仓库商品
     *
     * @param CatchRequest $request
     * @param              $id
     * @param              $type
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function products(CatchRequest $request, $id, $type)
    {

        $data = $request->param();

        if (!isset($data['id'])) {
            return CatchResponse::fail('请选择仓库', Code::FAILED);
        }

        if (!isset($data['type'])) {
            return CatchResponse::fail('请选择仓库类型', Code::FAILED);
        }

        $query = WarehouseStock::alias('ws')->leftJoin('product p', 'p.code = ws.goods_code')
            ->field('p.*, ws.entity_warehouse_id, ws.virtual_warehouse_id, ws.goods_code, sum(ws.number) as number,ws.goods_type');
        if ($data['type'] == 1) {
            $query->where('entity_warehouse_id', $data['id']);
        } else {
            $query->where('virtual_warehouse_id', $data['id']);
        }

        if (isset($data['name_ch']) && $data['name_ch']) {
            $query->whereLike('p.name_ch', $data['name_ch']);
        }

        if (isset($data['name_en']) && $data['name_en']) {
            $query->whereLike('p.name_en', $data['name_en']);
        }

        if (isset($data['code']) && $data['code']) {
            $query->whereLike('p.code', $data['code']);
        }

        if (isset($data['is_disable']) && $data['is_disable']) {
            $query->where('p.is_disable', $data['is_disable']);
        }



        $products = $query->where('packing_method', 1)->where('goods_type', 1)->where('ws.number', '>', 0)->group('ws.goods_code')
            ->paginate();
        foreach ($products as &$product) {
            $category = Category::where('id', $product->category_id)->find();
            $fisrtCategoryName = Category::where('id', $category->parent_id)->value('name');
            $product->category_name = $fisrtCategoryName . "-" . $category->getAttr('name');

            $product->unit = ProductInfo::where('product_id', $product->id)->value('unit');
            $product->created_by_name = Users::where('id', $product->creator_id)->value('username') ?? '';
            $product->updated_by_name = Users::where('id', $product->updated_by)->value('username') ?? '';
            $product->company = Company::where('id', $product->company_id)->value('name') ?? '';
            $product->supplier = Supply::where('id', $product->supplier_id)->value('name') ?? '';
        }
        return CatchResponse::paginate($products);
    }

    /**
     * 获取仓库商品
     *
     * @param CatchRequest $request
     * @param              $id
     * @param              $type
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function parts(CatchRequest $request, $id, $type)
    {

        $data = $request->param();

        if (!isset($data['id'])) {
            return CatchResponse::fail('请选择仓库', Code::FAILED);
        }

        if (!isset($data['type'])) {
            return CatchResponse::fail('请选择仓库类型', Code::FAILED);
        }

        $query = WarehouseStock::alias('ws')->leftJoin('parts p', 'p.code = ws.goods_code')
            ->field('p.*, ws.entity_warehouse_id, ws.virtual_warehouse_id, ws.goods_code, sum(ws.number) as number, ws.goods_type');
        if ($data['type'] == 1) {
            $query->where('entity_warehouse_id', $data['id']);
        } else {
            $query->where('virtual_warehouse_id', $data['id']);
        }

        if (isset($data['name_ch']) && $data['name_ch']) {
            $query->whereLike('p.name_ch', $data['name_ch']);
        }

        if (isset($data['code']) && $data['code']) {
            $query->whereLike('p.code', $data['code']);
        }

        if (isset($data['is_disable']) && $data['is_disable']) {
            $query->where('p.is_status', $data['is_disable']);
        }


        $products = $query->where('goods_type', 2)->where('ws.number', '>', 0)->group('ws.goods_code')
            ->paginate();

        foreach ($products as &$product) {
            $category = Category::where('id', $product->category_id)->find();
            $fisrtCategoryName = Category::where('id', $category->parent_id ?? 0)->value('name');
            $product->category_name = $fisrtCategoryName . "-" . $category->getAttr('name');
            $product->created_by_name = Users::where('id', $product->creator_id)->value('username') ?? '';
            $product->updated_by_name = Users::where('id', $product->updated_by)->value('username') ?? '';
            $product->company = Company::where('id', $product->company_id)->value('name') ?? '';
            $product->supplier = Supply::where('id', $product->supplier_id)->value('name') ?? '';
        }
        return CatchResponse::paginate($products);
    }

    /**
     * 编辑仓库 usps 账号
     * Webtools ID、Site/Barcode MID、Master MID、eVS permit 、CRID
     */
    public function updateUspsMessage(CatchRequest $request, $id)
    {

        $data = $request->post();
        if (!$warehouseData = $this->warehouseModel->findBy($id)) {
            return CatchResponse::fail('仓库不存在', Code::FAILED);
        }
        if ((int)$warehouseData['is_active'] == 1) {
            return CatchResponse::fail('仓库已启用，不可修改账号信息', Code::FAILED);
        }
        $dataObj = [
            'updated_at' => time(),
            'usps_json' => $data['usps_json']
        ];
        
        $this->warehouseModel->startTrans();
        try {
            if($this->warehouseModel->updateBy($id, $dataObj)) {
                // 更新子仓库账号数据
                if(!$this->warehouseModel->where(['parent_id' => $id])->update($dataObj)) {
                    $this->warehouseModel->rollback();
                    return CatchResponse::fail('更新失败');
                }
            }else{
                $this->warehouseModel->rollback();
                return CatchResponse::fail('更新失败');
            }
            $this->warehouseModel->commit();
            return CatchResponse::success('更新成功');

        } catch (\Exception $e) {
            $this->warehouseModel->rollback();
            return CatchResponse::fail('更新失败');
        }
    }
}
