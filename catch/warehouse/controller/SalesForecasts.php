<?php


namespace catchAdmin\warehouse\controller;


use catchAdmin\warehouse\model\SalesForecast;
use catchAdmin\warehouse\model\SalesForecastProducts;
use catcher\base\CatchController;
use catcher\base\CatchRequest;
use catcher\CatchResponse;
use catcher\Code;
use think\facade\Db;

class SalesForecasts extends CatchController {

    protected $salseForecastModel;
    protected $salesForecastProductsModel;

    public function __construct (SalesForecast $salesForecast,
        SalesForecastProducts $salesForecastProducts) {
        $this->salseForecastModel         = $salesForecast;
        $this->salesForecastProductsModel = $salesForecastProducts;
    }

    /**
     * 列表
     * @return \think\response\Json
     */
    public function index () {
        return CatchResponse::paginate($this->salseForecastModel->getList());
    }

    /**
     * 保存信息
     * @time 2021年01月23日 14:55
     *
     * @param CatchRequest $request
     */
    public function save (CatchRequest $request) {
        try {
            $data = $request->param();

            $this->salseForecastModel->startTrans();

            if (!isset($data['products'])) {
                return CatchResponse::fail('请选择商品', Code::FAILED);

            }
            //判断是否存在相同年份的数据
            $count = $this->salseForecastModel->where('year', $data['year'])->count();
            if ($count) {
                return CatchResponse::fail($data['year'] . "年的数据已经存在", Code::FAILED);
            }
            $data['created_by'] = $data['creator_id'];
            $res = $this->salseForecastModel->storeBy($data);

            foreach ($data['products'] as &$val) {
                $val['sales_forecast_id'] = $res;
            }
            $this->salesForecastProductsModel->saveAll($data['products']);
            $this->salseForecastModel->commit();
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $this->salseForecastModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }

    }

    /**
     * 修改
     * @time 2021年01月23日 14:55
     *
     * @param CatchRequest $request
     */
    public function update (CatchRequest $request, $id) {
        try {
            $data = $request->param();

            $this->salseForecastModel->startTrans();

            $res = $this->salseForecastModel->findBy($id);
            if (!$res) {
                return CatchResponse::fail('计划不存在', Code::FAILED);
            }


            if (!isset($data['products'])) {
                return CatchResponse::fail('请选择商品', Code::FAILED);
            }

            //判断是否存在相同年份的数据
            $count = $this->salseForecastModel->where('year', $data['year'])->where('id', '<>', $id)
                                              ->count();
            if ($count) {
                return CatchResponse::fail($data['year'] . "年的数据已经存在", Code::FAILED);
            }

            $data['updated_by'] = $data['creator_id'];
            $this->salseForecastModel->updateBy($id, $data);

            DB::table('sales_forecast_products')->where('sales_forecast_id', $id)->delete();

            foreach ($data['products'] as &$val) {
                $val['sales_forecast_id'] = $id;
            }
            $this->salesForecastProductsModel->saveAll($data['products']);
            $this->salseForecastModel->commit();

            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $this->salseForecastModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 详情
     * @param $id
     *
     * @return \think\response\Json
     */
    public function read($id)
    {
        $order = $this->salseForecastModel->with(['products'])->find($id);

        return CatchResponse::success($order);
    }
}
