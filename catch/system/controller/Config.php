<?php
/*
 * @Version: 1.0
 * @Date: 2021-01-23 09:25:36
 * @LastEditTime: 2021-06-18 16:21:57
 * @Description: 
 */
namespace catchAdmin\system\controller;

use app\Request;
use catcher\base\CatchController;
use catchAdmin\system\model\Config as ConfigModel;
use catcher\CatchResponse;
use think\response\Json;
use catchAdmin\product\model\ProductInfo;
use catchAdmin\product\model\ProductGroup;


class Config extends CatchController
{
    protected $configModel;

    public function __construct(ConfigModel $configModel)
    {
        $this->configModel = $configModel;
    }

    /**
     * 获取父级别配置
     *
     * @time 2020年04月17日
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @return Json
     */
    public function parent()
    {
        return CatchResponse::success($this->configModel->getParentConfig());
    }

    /**
     * 存储配置
     *
     * @time 2020年04月17日
     * @param Request $request
     * @return Json
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\db\exception\DataNotFoundException
     */
    public function save(Request $request)
    {
        $data = $request->param();
        if($data['parent'] == 'product') {
            // 获取原始值
            $productConfig = $this->configModel->getConfig('product');
            if($productConfig['product']['volume_factor'] != $data['product']['volume_factor']) {
               $productInfo = new ProductInfo;
               $productList = $productInfo->field(['volume_AS', 'id'])->select();
               $list = [];
               foreach ($productList as $key => $value) {
                   $row = [
                       'volume_weight_AS' => bcdiv($value['volume_AS'], $data['product']['volume_factor'], 6),
                       'id' => $value['id']
                   ];
                   $list[] = $row;
               }
               $productInfo->saveAll($list);
               // 修改多箱商品
               $productGroup = new ProductGroup;
               $productListMore = $productGroup->field(['volume_AS', 'id'])->select();
               $list = [];
               foreach ($productListMore as $key => $value) {
                   $row = [
                       'volume_weight_AS' => bcdiv($value['volume_AS'], $data['product']['volume_factor'], 6),
                       'id' => $value['id']
                   ];
                   $list[] = $row;
               }
               $productGroup->saveAll($list);
            }
        }
        
        return CatchResponse::success([
            'id' => $this->configModel->storeBy($request->param()),
            'parents' => $this->configModel->getParentConfig(),
        ]);
    }

    /**
     * 获取配置
     *
     * @time 2020年04月20日
     * @param $id
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @return Json
     */
    public function read($id)
    {
        return CatchResponse::success($this->configModel->getConfig($id));
    }

    /**
     * 根据id获取配置
     */
    public function info($id) {
        return CatchResponse::success(($this->configModel->findBy($id)));
    }

    /**
     * 根据ID修改当前配置
     */
    public function update(Request $request, $id): \think\Response
    {
        return CatchResponse::success($this->configModel->updateBy($id, $request->post()));
    }
}