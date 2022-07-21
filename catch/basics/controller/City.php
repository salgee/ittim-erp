<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-03-20 11:41:00
 * @LastEditors:
 * @LastEditTime: 2021-03-23 11:53:28
 * @Description: 
 */

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\basics\model\City as cityModel;
use catchAdmin\basics\model\CityZip;

class City extends CatchController
{
    protected $cityModel;
    protected $cityZip;
    
    public function __construct(CityModel $cityModel, CityZip $cityZip)
    {
        $this->cityModel = $cityModel;
        $this->cityZip = $cityZip;
    }
    
    /**
     * 列表
     * @time 2021年03月20日 11:41
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::success($this->cityModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年03月20日 11:41
     * @param Request $request 
     */
    public function save(Request $request) : \think\Response
    {
        return CatchResponse::success($this->cityModel->storeBy($request->post()));
    }
    
    /**
     * 读取
     * @time 2021年03月20日 11:41
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->cityModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年03月20日 11:41
     * @param Request $request 
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        return CatchResponse::success($this->cityModel->updateBy($id, $request->post()));
    }
    
    /**
     * 删除
     * @time 2021年03月20日 11:41
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->cityModel->deleteBy($id));
    }

    /**
     * 城市邮编
     * @param $id
     * @return array
     */
    public function cityPostalCode($id) {
        $data = CityZip::where('city_id', $id)->select();

        return CatchResponse::success($data);
    }
}