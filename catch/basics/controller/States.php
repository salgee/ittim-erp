<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-03-20 11:31:25
 * @LastEditors:
 * @LastEditTime: 2021-03-20 18:53:27
 * @Description: 
 */

namespace catchAdmin\basics\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\basics\model\States as statesModel;
use catchAdmin\basics\model\City;
class States extends CatchController
{
    protected $statesModel;
    protected $city;
    
    public function __construct(StatesModel $statesModel, City $city)
    {
        $this->statesModel = $statesModel;
        $this->city = $city;
    }
    
    /**
     * 列表
     * @time 2021年03月20日 11:31
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->statesModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年03月20日 11:31
     * @param Request $request 
     */
    public function save(Request $request) : \think\Response
    {
        $param = $request->file();
     
        $json_state = file_get_contents($param['data'][1]);
        $json_city = file_get_contents($param['data'][0]);

        $data_state = json_decode($json_state, true);
        $data_city = json_decode($json_city, true);

        $list = [];
        foreach ($data_state['RECORDS'] as $value) {
            $row =  [
                'id' => $value['id'],
                'code' => $value['code'],
                'name' => $value['name'],
                'cname' => $value['cname'],
                'lower_name' => $value['lower_name'],
                'code_full' => $value['code_full']
            ];
            $list[] = $row;
            
            $state_id = $this->statesModel->storeBy($row);
            // var_dump($state_id); exit;

            foreach ($data_city['RECORDS'][0] as $keys => $valueson) {
                var_dump($valueson['state_id'], $value['id']);exit;
                if($valueson['state_id'] == $value['id']) {
                    // var_dump('$state_id', $state_id); exit;
                    $rowSon =  [
                        'states_id' => $state_id,
                        'code' => $valueson['code'],
                        'name' => $valueson['name'],
                        'cname' => $valueson['cname'],
                        'lower_name' => $valueson['lower_name'],
                        'code_full' => $valueson['code_full']
                    ];
                    $this->city->storeBy($rowSon);
                }
            }
        }
        // $this->city->insertAllBy($list);
        return CatchResponse::success(true);
        // return CatchResponse::success($this->statesModel->insertAllBy($list));
        // return CatchResponse::success($this->statesModel->storeBy($request->post()));
    }
    
    /**
     * 读取
     * @time 2021年03月20日 11:31
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->statesModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年03月20日 11:31
     * @param Request $request 
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        return CatchResponse::success($this->statesModel->updateBy($id, $request->post()));
    }
    
    /**
     * 删除
     * @time 2021年03月20日 11:31
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->statesModel->deleteBy($id));
    }
}