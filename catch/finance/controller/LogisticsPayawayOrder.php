<?php
/*
 * @Version: 1.0
 * @Date: 2021-04-22 15:55:28
 * @LastEditTime: 2021-04-26 09:56:02
 * @Description:
 */

namespace catchAdmin\finance\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\finance\model\LogisticsPayawayOrder as logisticsPayawayOrderModel;
use catchAdmin\finance\model\LogisticsTransportOrder;
use catchAdmin\basics\model\Shop;
use catcher\Code;
use catcher\exceptions\FailedException;
use catchAdmin\permissions\model\Users;
use catchAdmin\basics\model\Lforwarder;


class LogisticsPayawayOrder extends CatchController
{
    protected $logisticsPayawayOrderModel;

    public function __construct(LogisticsPayawayOrderModel $logisticsPayawayOrderModel)
    {
        $this->logisticsPayawayOrderModel = $logisticsPayawayOrderModel;
    }

    /**
     * 列表
     * @time 2021年04月22日 15:55
     * @param Request $request
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->logisticsPayawayOrderModel->getList()->each(function($item){
            $item['status_text'] = logisticsPayawayOrderModel::$orderStatus[$item['status']];
            $item['update_name'] = Users::where('id', $item['update_by'])->value('username');
            if (!empty($item->pay_time)) {
                $item['pay_time_text'] = (new \Datetime())->setTimestamp($item->pay_time)->format('Y-m-d');
            }
        }));
    }

    /**
     * 读取
     * @time 2021年04月22日 15:55
     * @param $id
     */
    public function read($id) : \think\Response
    {
        $data = [];
        $data = $this->logisticsPayawayOrderModel->findBy($id);
        $data['status_text'] = logisticsPayawayOrderModel::$orderStatus[$data['status']];
        $data['transportList'] = LogisticsTransportOrder::where('payaway_order_id', $id)
            ->select()
            ->each(function($item){
                $item['shop_name'] = Shop::where('id', $item['shop_id'])->value('shop_name');
                $item['send_at_text'] = (new \Datetime())->setTimestamp($item->send_at)->format('Y-m-d');
                if(!empty($item->pay_time)) {
                   $item['pay_time_text'] = (new \Datetime())->setTimestamp($item->pay_time)->format('Y-m-d');
                }
                $item['settlement_cycle'] = Lforwarder::where(['id' => $item['logistics_id'], 'type' => 1])->value('settlement_cycle');
            });
        return CatchResponse::success($data);
    }


    /**
     * 审核付款单
     *
     * @param Request $request
     * @param int $id
     * @return \think\response\Json
     */
    public function examine(Request $request, $id) {
        try {
            if (!in_array($request->param('status'), [1, 2])) {
                throw new FailedException('参数不正确');
            }
            if(!$order = $this->logisticsPayawayOrderModel->findBy($id)) {
                return CatchResponse::fail('订单不存在', Code::FAILED);
            }
            if($order['status'] == 1) {
                return CatchResponse::fail('订单已审核通过，比不可重复审核', Code::FAILED);
            }
            $this->logisticsPayawayOrderModel->where('id', $id)->update(['status' => $request->param('status')]);
            return CatchResponse::success('审核成功');
        } catch (\Exception $e) {
            throw new FailedException($e->getMessage());
        }
    }

    /**
     * 录入实际付款金额
     *
     * @param Request $request
     * @param int $id
     * @return \think\response\Json
     */
    public function createActualPayment(Request $request, $id)
    {
        try {
            $data = $request->post();
            if (!$order = $this->logisticsPayawayOrderModel->findBy($id)) {
                return CatchResponse::fail('订单不存在', Code::FAILED);
            }
            if ($order['status'] !== 1) {
                return CatchResponse::fail('请先审核订单', Code::FAILED);
            }
            $user = request()->user();
            $data['update_by'] = $user['id'];
            $data['payaway_status'] = 1;
            $this->logisticsPayawayOrderModel->updateBy($id, $data);

            return CatchResponse::success('实际付款金额成功');

        } catch (\Exception $e) {
            throw new FailedException($e->getMessage());
        }

    }


    /**
     * 删除关联付款单编号以及id (编辑)
     *
     * @param Request $request
     * @param int $id
     * @return \think\response\Json
     */
    public function deleteTransportOrder(Request $request, $id) {
        try {
            $data = $request->post();
            if (!$order = $this->logisticsPayawayOrderModel->findBy($id)) {
                return CatchResponse::fail('订单不存在', Code::FAILED);
            }
            if ($order['status'] == 1) {
                return CatchResponse::fail('数据已审核通过不能编辑', Code::FAILED);
            }
            $ids = array_unique($data['ids']);
            $idsString = implode(",", $ids);
            $logisticsTransportOrder = new LogisticsTransportOrder;
            $list = $logisticsTransportOrder->where('payaway_order_id', $order['id'])
                ->whereNotIn('id', $idsString)
                ->column('payaway_order_no,payaway_order_id,id');
            // 获取当前剩余运单总金额
            $total = $logisticsTransportOrder->whereIn('id', $idsString)
                ->sum('total_fee');
            $listNew = [];
            foreach ($list as $value) {
                $row = [
                    'id' => $value['id'],
                    'payaway_order_no' => '',
                    'payaway_order_id' => 0,
                    'status' => 1
                ];
                $listNew[] = $row;
            }
            // 修改没有基础物流单的数据
            $logisticsTransportOrder->saveAll($listNew);
            if(!$idsString) {
                // 删除运单
                $this->logisticsPayawayOrderModel->deleteBy($id);
            }else{
                // 重新修改应付金额
                $this->logisticsPayawayOrderModel->where('id', $id)->update(['payaway_amount' => $total]);
            }
            return CatchResponse::success('编辑成功');
        } catch (\Exception $e) {
            throw new FailedException($e->getMessage());
        }
    }
}