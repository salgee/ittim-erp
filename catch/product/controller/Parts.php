<?php

namespace catchAdmin\product\controller;

use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catchAdmin\product\model\Parts as partsModel;
use catchAdmin\product\model\ViewParts as viewPartsModel;
use catchAdmin\product\request\PartsRequest;
use catcher\Code;
use catchAdmin\product\model\Product;
use catchAdmin\supply\model\PurchaseOrderProducts;
use catcher\base\CatchRequest;
// use catchAdmin\supply\excel\CommonExport;
use catchAdmin\product\excel\CommonExport;
use catchAdmin\basics\excel\PartImport;
use catchAdmin\system\model\Config;
use catchAdmin\product\model\Category;
use catchAdmin\supply\model\Supply;
use catchAdmin\permissions\model\Users;



class Parts extends CatchController
{
    protected $partsModel;
    protected $product;
    protected $viewPartsModel;
    protected $supply;

    public function __construct(
        PartsModel $partsModel,
        Product $product,
        viewPartsModel $viewPartsModel,
        Supply $supply
    ) {
        $this->partsModel = $partsModel;
        $this->product = $product;
        $this->viewPartsModel = $viewPartsModel;
        $this->supply = $supply;
    }

    /**
     * 列表
     * @time 2021年03月12日 12:30
     * @param Request $request
     */
    public function index(Request $request): \think\Response
    {
        return CatchResponse::paginate($this->viewPartsModel->getList()->each(function ($item) {
            $item['category_name'] = $item['parent_name'] . '-' . $item['category_names'];
        }));
    }

    /**
     * 保存信息
     * @time 2021年03月12日 12:30
     * @param Request $request
     */
    public function save(PartsRequest $request): \think\Response
    {
        $data = $request->post();
        // 生成编码
        $data['code'] = $this->partsModel->createOrderNo($data['category_id']);
        if ($id = $this->partsModel->storeBy($data)) {
            $this->partsModel->updateOrderNo($data['category_id'], $data['code']);
        }

        return CatchResponse::success($id);
    }

    /**
     * 读取
     * @time 2021年03月12日 12:30
     * @param $id
     */
    public function read($id): \think\Response
    {
        $data = $this->partsModel->findByInfo($id);
        if (!$data) {
            return CatchResponse::fail('数据不存在', Code::FAILED);
        }
        if (isset($data['product_id'])) {
            $data['list'] = $this->product->groupInfo($data['product_id']);
        }

        return CatchResponse::success($data);
    }

    /**
     * 更新
     * @time 2021年03月12日 12:30
     * @param Request $request
     * @param $id
     */
    public function update(Request $request, $id): \think\Response
    {
        if (!$partData = $this->partsModel->findBy($id)) {
            return CatchResponse::fail('信息不存在', Code::FAILED);
        }
        if ($this->partsModel->where(['name_ch' => $partData['name_ch']])->whereNotIn('id', $id)->value('id')) {
            return CatchResponse::fail('配件名称已存在', Code::FAILED);
        }
        $data = $request->post();
        unset($data['code']);
        unset($data['category_id']);
        unset($data['created_at']);
        $user = request()->user();
        $data['update_by'] = $user['id'];
        $data['updated_at'] = time();
        return CatchResponse::success($this->partsModel->updateBy($id, $data));
    }

    /**
     * 删除
     * @time 2021年03月12日 12:30
     * @param $id
     */
    public function delete($id): \think\Response
    {
        // 是否关联采购单
        $num = PurchaseOrderProducts::where(['goods_id' => $id, 'type' => 2])->count();
        if (!empty($num)) {
            return CatchResponse::fail('有关联采购订单不可删除', Code::FAILED);
        }
        return CatchResponse::success($this->partsModel->deleteBy($id));
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
            $this->partsModel->saveAll($list);
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
            $this->partsModel->saveAll($list);
            return CatchResponse::success(true);
        } else {
            return CatchResponse::fail('请检查传入数据');
        }
    }

    /**
     * 配件导出
     * @time 2021年03月24日
     * @param Excel $excel
     * @param UserExport $userExport
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @return \think\response\Json
     */
    public function export(Request $request)
    {
        // 配件照片
        $res = $this->partsModel->getExportList();
        if (empty($res)) {
            return CatchResponse::fail("没有可导出的数据", Code::FAILED);
        }

        $data = $request->post();
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->partsModel->exportField();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '配件导出');

        return  CatchResponse::success($url);
    }

    /**
     * 配件商品列表
     * @param $id 商品id
     */
    public function partListProduct($id)
    {
        $list = $this->partsModel->partListProduct($id);
        return CatchResponse::success($list);
    }
    /**
     * 配件模板下载
     */
    public function template(Request $request)
    {
        return download(public_path() . 'template/partImport.xlsx', 'partImport.xlsx')->force(true);
    }
    /**
     * 导入配件
     * @param CatchRequest $request
     * @param CatchUpload $upload
     * @return \think\response\Json
     */
    public function importPart(Request $request, PartImport $import, \catcher\CatchUpload $upload)
    {
        $file = $request->file();
        $data = $import->read($file['file']);
        $dataList = [];

        // 获取当前尺寸
        $sizeRatio = Config::where(['key' => 'product.cm_to_in'])->value('value');
        // 公斤转英镑
        $widthRatio = Config::where(['key' => 'product.kg_to_pt'])->value('value');
        array_shift($data);

        foreach ($data as $value) {
            // 供应商
            if (!$supply_id = $this->supply
                ->where('name', trim($value[1]))
                ->value('id')) {
                $dataList['empty'][] = '供应商' . $value[1] . '不存在';
                continue;
            }
            // 采购员
            if (!$user_id = Users::where('username', trim($value[2]))->value('id')) {
                $dataList['empty'][] = '采购员' . $value[1] . '不存在';
                continue;
            }
            // 验证分类
            if (!$category_id = Category::where('name', trim($value[3]))->value('id')) {
                $dataList['empty'][] = '分类' . $value[3] . '不存在';
                continue;
            }
            // 验证编码是否存在
            if ($this->partsModel->where(['code' => trim($value[4])])
                ->find()
            ) {
                $dataList['repeat'][] = 'code' . $value[4];
                continue;
            }
            // 验证配件名称 
            if ($this->partsModel->where(['name_ch' => trim($value[5])])->value('id')) {
                $dataList['repeat'][] = '名称' . $value[3];
                continue;
            }

            // 验证商品写入
            if (trim($value[7])) {
                $list = $this->product->whereIn('code', trim($value[7]))->column('id');
                if (count($list) < 1) {
                    $dataList['empty'][] = '商品' . $value[7] . '不存在';
                    continue;
                } else {
                    $porductIds = implode(',', $list);
                }
            }

            // 重量公制转化
            $weight = bcdiv(trim($value[14]), 1000, 6);
            // 重量美制计算
            $weight_gross_AS = bcmul($weight, $widthRatio, 6);

            // 公制尺寸
            if (trim($value[10])) {
                $list = explode('x', $value[10]);
                $length = preg_replace('/[^\.0123456789]/s', '', $list[0]);
                $width = preg_replace('/[^\.0123456789]/s', '', $list[1]);
                $height = preg_replace('/[^\.0123456789]/s', '', $list[2]);

                // 美制尺寸
                $length_AS = bcmul($length, $sizeRatio, 6);
                $width_AS = bcmul($width, $sizeRatio, 6);
                $height_AS = bcmul($height, $sizeRatio, 6);

                // 体积立方米
                $volume = bcdiv(bcmul(bcmul($length, $width), $height, 6), '1000000', 6);
            }
            // 流向
            $flow_to = $value[15] == '国内' ? 1 : 2;
            // 分类
            // $data['category_id'] = 1;
            $row = [
                'supplier_id' => $supply_id,
                'purchase_id' => $user_id,
                'purchase_name' => trim($value[2]),
                'category_id' => $category_id,
                'code' => trim($value[4]), // sku
                'name_ch' => trim($value[5]) ?? '', // 名称
                'code_other' => $value[6] ?? '',
                'product_id' => $value[4], // 适用sku
                'image_url' => $value[8] ?? '', // 配件图片
                'image_url_other' => $value[9] ?? '', // 说明书照片
                'length' => $length ?? 0,
                'width' => $width ?? 0,
                'height' => $height ?? 0,
                'volume' => $volume ?? 0,
                'length_AS' => $length_AS ?? 0,
                'width_AS' => $width_AS ?? 0,
                'height_AS' => $height_AS ?? 0,
                'product_id' => $porductIds ?? 0,
                // 'flow_to' => $value[8], // 外箱包装尺寸
                'box_rate' => $value[12], // 外箱相率
                // 'length' => $value[12], // 长度
                'weight' => $weight, // 重量
                'weight_gross_AS' => $weight_gross_AS, //毛重美制 lbs
                'flow_to' => $flow_to, // 配件流向
                'creator_id' => $request->param('creator_id')
            ];

            $dataList['success'][] = $value[4];
            $this->partsModel->createBy($row);
        }
        return CatchResponse::success($dataList);
    }
}
