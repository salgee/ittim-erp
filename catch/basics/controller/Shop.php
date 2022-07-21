<?php
/*
 * @Author: your name
 * @Date: 2021-02-03 16:30:03
 * @LastEditTime: 2021-09-14 16:42:40
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \erp\catch\basics\controller\Shop.php
 */

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\basics\request\ShopRequest;
use catchAdmin\basics\model\Sender as senderModel;
use catchAdmin\basics\model\Shop as shopModel;
use catchAdmin\basics\model\ShopWarehouse;
use catchAdmin\basics\model\ShopUser;
use catcher\Code;
use catchAdmin\permissions\model\Users;
use catcher\library\excel\Excel;

use catchAdmin\order\model\OrderRecords;
use catchAdmin\product\model\ProductPlatformSku;
use catchAdmin\supply\excel\CommonExport;
use catcher\platform\AmazonSpService;
use catcher\platform\OpenCartService;
use catcher\platform\OverstockService;
use catcher\platform\ShopifyService;
use catcher\platform\WayfairService;
use catcher\platform\WalmartService;
use catcher\platform\EbayService;
use catcher\platform\HouzzService;


class Shop extends CatchController
{
    protected $shopModel;
    protected $senderModel;
    protected $shopWarehouse;
    protected $users;
    protected $shopUser;

    public function __construct(
        ShopModel $shopModel,
        SenderModel $senderModel,
        ShopWarehouse $shopWarehouse,
        Users $users,
        ShopUser $shopUser
    ) {
        $this->shopModel = $shopModel;
        $this->senderModel = $senderModel;
        $this->shopWarehouse = $shopWarehouse;
        $this->users = $users;
        $this->shopUser = $shopUser;
    }
    /**
     *
     * @time 2020年04月28日
     * @param Log $log
     * @throws \think\db\exception\DbException
     * @return \think\response\Json
     */
    // public function list(shopModel $shopModel)
    // {
    //     return CatchResponse::paginate($shopModel->getList());
    // }

    /**
     * 列表
     * @time 2021年02月03日 16:30
     */
    public function index(Request $request)
    {
        return CatchResponse::paginate($this->shopModel->getList());
    }

    /**
     * 保存信息
     * @time 2021年02月03日 16:30
     * @param Request $request 
     */
    public function save(ShopRequest $request): \think\Response

    {
        $this->senderModel->startTrans();
        $shop = $this->shopModel->storeBy($request->post());
        if (!empty($request->param('sender_data'))) {
            $lists = json_decode($request->param('sender_data'), true);
            $arr = [];
            foreach ($lists as $key => $id) {
                $arr[$key] = $id;
                $arr[$key]['shop_id'] = $shop;
            }
            if (!$this->senderModel->insertAllBy($arr)) {
                $this->senderModel->rollback();
            } else {
                $this->senderModel->commit();
                return CatchResponse::success($shop);
            }
        }
        $this->senderModel->commit();
        return CatchResponse::success($shop);
    }

    /**
     * 读取
     * @time 2021年02月03日 16:30
     * @param $id 
     */
    public function read($id): \think\Response
    {
        $data = [];
        $dataShopData = $this->shopModel->findBy($id);
        if (!$dataShopData) {
            return CatchResponse::fail('数据不存在', Code::FAILED);
        }
        $data = $dataShopData;

        $userList = $this->senderModel->where('shop_id', '=', $id)->select();
        $data['userList'] = $userList;
        $data['senderList'] = $this->senderModel->where('shop_id', $id)->select();

        return CatchResponse::success($data);
    }

    /**
     * 更新
     * @time 2021年02月03日 16:30
     * @param Request $request 
     * @param $id
     */
    public function update(ShopRequest $request, $id): \think\Response
    {
        $data = $request->post();
        $data['update_by'] = $data['creator_id'];
        $data['updated_at'] = time();

        return CatchResponse::success($this->shopModel->updateBy($id, $data));
    }

    /**
     * 删除
     * @time 2021年02月03日 16:30
     * @param $id
     */
    public function delete($id): \think\Response
    {
        $shopData = $this->shopModel->findBy($id);
        if (!$shopData) {
            return CatchResponse::fail('数据不存在', Code::FAILED);
        }
        // 关联映射导航品不可删除
        $productNum = ProductPlatformSku::where('shop_id', $id)->count();
        if (!empty($productNum)) {
            return CatchResponse::fail('有关联商品不可删除', Code::FAILED);
        }
        // 判断时候有关联订单 shop_basics_id
        $orderNum = OrderRecords::where('shop_basics_id', $id)->count();
        if (!empty($orderNum)) {
            return CatchResponse::fail('有业务订单不可删除', Code::FAILED);
        }
        return CatchResponse::success($this->shopModel->deleteBy($id));
    }
    /**
     * 批量禁用
     * @time 2020/09/16
     * @param Request $request  
     */
    public function disable(Request $request): \think\Response
    {
        $data = $request->param();
        $list = [];
        if (isset($data['ids'])) {
            foreach ($data['ids'] as $val) {
                $row =  [
                    'id' => $val,
                    'is_status' => 2
                ];
                $list[] = $row;
            }
            $this->shopModel->saveAll($list);
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }
    /**
     * 批量启用 enable
     */
    public function enable(Request $request): \think\Response
    {
        $data = $request->param();
        $list = [];
        if (isset($data['ids'])) {
            foreach ($data['ids'] as $val) {
                $row =  [
                    'id' => $val,
                    'is_status' => 1
                ];
                $list[] = $row;
            }
            $this->shopModel->saveAll($list);
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }

    /**
     * 仓库绑定
     */
    public function bindWarehouse(Request $request, $id)
    {
        try {
            $data = $request->param();
            $list = [];
            $this->shopWarehouse->startTrans();
            // 删除之前绑定
            $dataAll = $this->shopWarehouse->getAllDelect($id);
            if ($dataAll) {
                // 物理删除
                $this->shopWarehouse->deleteBy($dataAll,  $force = true);
            }
            if (isset($data['warehouse'])) {
                foreach ($data['warehouse'] as $valson) {
                    $row =  [
                        'shop_id' => $id,
                        'warehouse_id' => $valson['warehouse_id'] ?? '',
                        'warehouse_fictitious_id' => $valson['warehouse_fictitious_id'] ?? ''
                    ];
                    $list[] = $row;
                }
            }
            if (!$this->shopWarehouse->insertAllBy($list)) {
                $this->shopWarehouse->rollback();
            }
            $this->shopWarehouse->commit();
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $this->shopWarehouse->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }
    /**
     * 查看绑定仓库列表
     */
    public function seeBindWarehouse(Request $request, $id)
    {
        $data = $this->shopWarehouse->where('shop_id', $id)->select();
        return CatchResponse::success($data);
    }

    /**
     * 查看绑定用户
     */
    public function seeBindUser(Request $request, $id)
    {
        $data = $this->shopUser->where('shop_id', $id)->select();
        return CatchResponse::success($data);
    }
    /**
     * 绑定用户
     */
    public function bindUser(Request $request, $id)
    {
        //shopUser
        try {
            $data = $request->param();
            $list = [];
            $this->shopUser->startTrans();
            // 删除之前绑定
            $dataAll = $this->shopUser->getAllDelect($id);
            if ($dataAll) {
                // 物理删除
                $this->shopUser->deleteBy($dataAll,  $force = true);
            }
            if (isset($data['users'])) {
                foreach ($data['users'] as $val) {
                    $row =  [
                        'shop_id' => $id,
                        'user_id' =>  $val
                    ];
                    $list[] = $row;
                }
            }
            if (!$this->shopUser->insertAllBy($list)) {
                $this->shopUser->rollback();
            }
            $this->shopUser->commit();
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $this->shopUser->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }
    /**
     * 用户列表
     */
    public function userList(Request $request)
    {
        return CatchResponse::paginate($this->users->getListAdmin());
    }

    /**
     *  获取采购员列表
     */
    public function buyers()
    {
        $res = $this->users->join('user_has_roles', 'user_has_roles.uid=users.id')
            ->where('user_has_roles.role_id', 3)
            // ->column('users.username');
            ->field(['username', 'users.id as user_id', 'status'])
            ->select();

        return CatchResponse::success(['buyers' => $res]);
    }

    /**
     * 根据平台查询店铺列表
     */
    public function getShopPlatform($id)
    {
        return CatchResponse::success($this->shopModel->getShopPlatform($id));
    }

    /**
     * 导出
     *
     * @time 2020年09月08日
     * @param Excel $excel
     * @param UserExport $userExport
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @return \think\response\Json
     */
    public function export(Request $request)
    {
        $data = $request->post();
        $res = $this->shopModel->getExportList();
        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        $excel = new CommonExport();
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->shopModel->exportField();
        }

        $url = $excel->export($res, $exportField, '店铺导出');

        return  CatchResponse::success($url);
    }

    /**
     * 手动拉去订单
     * @param $id 店铺id
     */
    public function manualPull(Request $request, $id)
    {
        $data = $request->post();
        if (empty($data['startTime'])) {
            return CatchResponse::fail("时间不能为空", Code::FAILED);
        }
        if (!$shopData = $this->shopModel->findBy($id)) {
            return CatchResponse::fail("店铺不存在", Code::FAILED);
        }
        if ($shopData['is_status'] !== 1) {
            return CatchResponse::fail("店铺禁用状态不可拉去", Code::FAILED);
        }

        try {
            $type = $shopData['platform_id'];
            switch ((int)$type) {
                case Code::AMAZON:
                    $amazonSp = new AmazonSpService('ListOrders-mamual', $data['startTime']);
                    $amazonSp->setShop($id);
                    break;
                case Code::EBAY:
                    $ebay = new EbayService('getOrders-mamual', $data['startTime']);
                    // 拉取订单
                    $ebay->setShop($id);
                    break;
                    // case Code::WAYFAIR:
                    //     $wayfair = new WayfairService('outgoing-mamual', $data['startTime']);
                    //     $wayfair->setShop($id);
                    //     break;
                case Code::OVERSTOCK:
                    $overstock = new OverstockService('salesOrders-mamual', $data['startTime']);
                    // 拉取订单
                    $overstock->setShop($id);
                    break;
                case Code::WALMART:
                    $walmart = new WalmartService('AllOrders-mamual', $data['startTime']);
                    // 拉取订单
                    $walmart->setShop($id);
                    break;
                case Code::OPENCART:
                    $opencart = new OpenCartService('getOrderList-mamual', $data['startTime']);
                    // 拉取订单
                    $opencart->setShop($id);
                    break;
                case Code::SHOPIFY:
                    $shopify = new ShopifyService('getOrders-mamual', $data['startTime']);
                    // 拉取订单
                    $shopify->setShop($id);
                    break;
                case Code::HOUZZ:
                    $houzz = new HouzzService('getOrders-mamual', $data['startTime']);
                    // 拉取订单
                    $houzz->setShop($id);
                    break;
            }
            return CatchResponse::success(true);
        } catch (\Exception $e) {
            $message = sprintf(" 拉去订单，异常信息:【%s】", $e->getCode() . ':' . $e->getMessage() .
                ' in ' . $e->getFile() . ' on line ' . $e->getLine());
            return CatchResponse::fail($message, Code::FAILED);
        }
    }
}
