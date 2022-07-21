<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-09 15:26:36
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-06-03 16:36:19
 * @Description: 
 */

namespace catchAdmin\product\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catcher\exceptions\FailedException;
use catchAdmin\product\model\ProductPrice as productPriceModel;
use catchAdmin\product\model\Product;
use catchAdmin\product\model\ProductInfo;
use catchAdmin\supply\excel\CommonExport;
use catcher\Code;
use catchAdmin\basics\model\LogisticsFeeConfig;
use catchAdmin\basics\model\OrderFeeSetting;



class ProductPrice extends CatchController
{
    protected $productPriceModel;
    
    public function __construct(ProductPriceModel $productPriceModel)
    {
        $this->productPriceModel = $productPriceModel;
    }
    
    /**
     * 列表
     * @time 2021年02月09日 15:26
     * @param Request $request 
     */
    public function index(Request $request) : \think\Response
    {
        return CatchResponse::paginate($this->productPriceModel->getList());
    }
    
    /**
     * 保存信息
     * @time 2021年02月09日 15:26
     * @param Request $request 
     */
    public function save(Request $request) : \think\Response
    {
        return CatchResponse::success($this->productPriceModel->storeBy($request->post()));
    }
    
    /**
     * 读取
     * @time 2021年02月09日 15:26
     * @param $id 
     */
    public function read($id) : \think\Response
    {
        return CatchResponse::success($this->productPriceModel->findBy($id));
    }
    
    /**
     * 更新
     * @time 2021年02月09日 15:26
     * @param Request $request 
     * @param $id
     */
    public function update(Request $request, $id) : \think\Response
    {
        return CatchResponse::success($this->productPriceModel->updateBy($id, $request->post()));
    }
    
    /**
     * 删除
     * @time 2021年02月09日 15:26
     * @param $id
     */
    public function delete($id) : \think\Response
    {
        return CatchResponse::success($this->productPriceModel->deleteBy($id));
    }

    /**
     * 商品价格审核
     * @time 2021年02月22日 16:16
     * @param Request $request
     * @param $id
     */
    public function examine(Request $request, $id): \think\Response
    {
        $data = $request->post();
        if (!in_array($data['status'], [1, 2])) {
            throw new FailedException('参数不正确', Code::FAILED);
        }
        $dataPrice = $this->productPriceModel->findBy($id);
        if(!$dataPrice) {
            throw new FailedException('数据不存在');
        }
        if($data['status'] == 1) {
            $product = new Product;
            // 更新商品基准价格
            $product->where('id', $dataPrice['product_id'])->update(['benchmark_price' => $dataPrice['benchmark_price']]);
        }
        
        return CatchResponse::success($this->productPriceModel->examineBy($id, $request->post()));
    }
    /**
     * 禁用启用
     * @time 2021年02月22日 16:44
     * @param Request
     * @param $id
     */
    public function verify(Request $request, $id): \think\Response
    {
        if (!in_array($request->param('is_status'), [0, 1])) {
            throw new FailedException('参数不正确');
        }
        // disOrEnable
        return CatchResponse::success($this->productPriceModel->updateBy($id, $request->param()));
    }

    /**
     * 基准价格导出 
     */
    public function export(Request $request) {
        $res = $this->productPriceModel->exportList();
       
        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }
        
        $data = $request->post();
        if(isset($data['exportField'])) {
            $exportField = $data['exportField'];
        }else{
            $exportField = $this->productPriceModel->exportField();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '成本分析');

        return  CatchResponse::success($url);

    }

    /**
     * 修改配置后同步修改商品价格
     */
    public function updateProductPrice (Request $request) {
        $data = $request->post();
        if(!(int)$data['type']) {
            return  CatchResponse::fail('请查传入类型');
        }
        $this->productPriceModel->startTrans();
        $productList = [];
        // 修改系统参数
        if((int)$data['type'] == 1) {
            // 获取适合条件的商品
            $productList = Product::where(['type' => 0, 'status' => 1])
                ->where('category_id', '>', 0)
                ->where('company_id', '>', 0)
                ->select()
                ->toArray();
        }
        // 修改分类参数
        if ((int)$data['type'] == 2) {
            if(!isset($data['category_id'])) {
                return  CatchResponse::fail('请检查分类id');
            }

            // 获取适合条件的商品
            $productList = Product::where(['type' => 0, 'status' => 1, 'category_id' => $data['category_id']])
                ->select()
                ->toArray();
        }
        // 修改订单操作费 company_id
        if((int)$data['type'] == 3) {
            if (!isset($data['company_id']) || empty($data['company_id'])) {
                return  CatchResponse::fail('请检查客户id');
            }
            if (!isset($data['ids']) || empty($data['ids'])) {
                return  CatchResponse::fail('请检查id');
            }

            $productList = Product::where(['type' => 0, 'status' => 1])
                ->whereIn('company_id', strval($data['company_id']))
                ->select()
                ->toArray();
            // 修改物流价格状态
            OrderFeeSetting::whereIn('id', $data['ids'])->update(['is_update_price' => 0, 'updated_at' => time()]);

        }

        // 物流台阶费修改
        if ((int)$data['type'] == 4) {
            if (!isset($data['company_id']) || empty($data['company_id'])) {
                return  CatchResponse::fail('请检查客户id');
            }
            if(!isset($data['ids']) || empty($data['ids'])) {
                return  CatchResponse::fail('请检查id');
            }
            // var_dump('>>>>>'); exit;
            $productList = Product::where(['type' => 0, 'status' => 1])
                        // ->where(['id' => '2883'])
                        ->whereIn('company_id', strval($data['company_id']))
                        ->select()
                        ->toArray();
            // 修改物流价格状态
            LogisticsFeeConfig::whereIn('id', $data['ids'])->update(['is_update_price' => 0, 'updated_at' => time()]);
        }
        
        if(count($productList) > 0) {
            // 循环当前商品进行价格修改
            foreach ($productList as $key => $value) {
                # 查询当前商是否有待审核价格
                $productPriceData = $this->productPriceModel->where([['product_id', '=', $value['id']], ['is_status', '=', 0]])->find();
                // 商品详情信息
                $arr = ProductInfo::where('product_id', $value['id'])->select()->toArray();
                $arr[0]['packing_method'] = $value['packing_method'];
                $arr[0]['product_id'] = $value['id'];
                
                // 商品价格生成
                $priceData = $this->productPriceModel->addPrice($value, $arr);
                
                if (!empty($productPriceData['id'])) {
                    $priceData['id'] = $productPriceData['id'];
                    // 当存在未审核价格
                    $this->productPriceModel->updateBy($productPriceData['id'], $priceData);
                } else {
                    // 当不存在未审核价格
                    $priceData['product_id'] = $value['id'];
                    // 新增基准价格
                    $this->productPriceModel->createBy($priceData);
                }
            }
        }
        
        $this->productPriceModel->commit();
        //  return  CatchResponse::success($arr);

        return  CatchResponse::success('价格生成成功');

    }
    
}