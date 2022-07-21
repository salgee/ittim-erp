<?php


namespace catchAdmin\supply\controller;

use catchAdmin\basics\model\Company;
use catchAdmin\finance\model\PurchasePayment;
use catchAdmin\supply\excel\CommonExport;
use catchAdmin\supply\model\PurchaseOrderProducts;
use catchAdmin\supply\model\PurchaseOrders;
use catchAdmin\supply\model\Supply;
use catchAdmin\supply\model\PurchaseContracts;
use catchAdmin\supply\model\PurchaseContractProducts;
use catchAdmin\supply\model\TranshipmentOrderProducts;
use catchAdmin\warehouse\model\WarehouseOrders;
use catcher\base\CatchRequest as Request;
use catcher\CatchResponse;
use catcher\base\CatchController;
use catcher\Code;
use catcher\CatchUpload;
use Exception;
use Picqer\Barcode\BarcodeGeneratorPNG;
use Spipu\Html2Pdf\Html2Pdf;
use think\facade\Filesystem;
use setasign\Fpdi\Tcpdf\Fpdi;
use think\facade\View;
use ZipArchive;
use catcher\Utils;

class PurchaseContract extends CatchController
{
    protected $purchaseOrdersModel;
    protected $purchaseOrderProductsModel;
    protected $supplyModel;
    protected $purchaseContractModel;
    protected $purchaseContractProductsModel;
    protected $warehouseOrderModel;
    protected $purchasePaymentModel;
    protected $transhipmentOrderProductsModel;

    public function __construct(
        PurchaseOrders $purchaseOrders,
        PurchaseOrderProducts $purchaseOrderProducts,
        Supply $supplyModel,
        PurchaseContracts $purchaseContractModel,
        PurchaseContractProducts $purchaseContractProductsModel,
        WarehouseOrders $warehouseOrders,
        PurchasePayment $purchasePayment,
        TranshipmentOrderProducts $transhipmentOrderProducts
    ) {

        $this->supplyModel                   = $supplyModel;
        $this->purchaseOrdersModel           = $purchaseOrders;
        $this->purchaseOrderProductsModel    = $purchaseOrderProducts;
        $this->purchaseContractModel         = $purchaseContractModel;
        $this->purchaseContractProductsModel = $purchaseContractProductsModel;
        $this->warehouseOrderModel           = $warehouseOrders;
        $this->purchasePaymentModel          = $purchasePayment;
        $this->transhipmentOrderProductsModel = $transhipmentOrderProducts;
    }

    /**
     * 列表
     * @return \think\response\Json
     */
    public function index()
    {
        try {

            $list = $this->purchaseContractModel->dataRange([], 'created_by')->catchSearch()->order('id', 'desc')->paginate();

            //临时处理 计算合同转运状态
            foreach ($list as $val) {
                //获取合同商品转运商品及数量
                $products = PurchaseOrderProducts::alias('op')
                    ->leftJoin(
                        'purchase_contract_products cp',
                        'cp.purchase_product_id = op.id'
                    )
                    ->where('cp.purchase_contract_id', $val->getAttr('id'))
                    ->field('op.*')
                    ->select()
                    ->toArray();

                $status = 2;
                foreach ($products as $product) {
                    $tranNum = TranshipmentOrderProducts::where(
                        'purchase_product_id',
                        $product['id']
                    )
                        ->sum('trans_number');
                    if ($tranNum == 0) {
                        $status = 0;
                        continue;
                    }
                    if ($tranNum < $product['number']) {
                        $status = 1;
                    }
                }
                PurchaseContracts::where('id', $val->getAttr('id'))
                    ->update(['transshipment' => $status]);
            }
            return CatchResponse::paginate($list);
        } catch (Exception $e) {
            echo $e->getFile() . "=" . $e->getLine() . "=" . $e->getMessage();
        }
    }

    /**
     * 导出
     *
     * @param Request $request
     * @return \think\response\Json
     */
    public function export(Request $request)
    {

        $data = $request->param();

        $exportFiled = [];


        $query = $this->purchaseContractModel->catchSearch();
        if (!empty($data['ids'])) {
            $query->whereIn('id', $data['ids']);
        }
        $res = $query->order('id', 'desc')->select();


        $data = $request->post();
        if (isset($data['exportField'])) {
            $exportField = $data['exportField'];
        } else {
            $exportField = $this->purchaseContractModel->exportField();
        }

        $excel = new CommonExport();
        $url = $excel->export($res, $exportField, '采购合同');
        return  CatchResponse::success($url);
    }


    /**
     * 保存信息
     * @time 2021年01月23日 14:55
     *
     * @param Request $request
     */
    public function save(Request $request): \think\Response
    {
        try {

            $this->purchaseContractModel->startTrans();
            $data = $request->param();
            $id   = $data['id'];
            $companyId = $data['company_id'] ?? 0;
            //检查采购单是否存在
            $order = $this->purchaseOrdersModel->findBy($id);
            if (!$order) {
                return CatchResponse::fail('订单不存在', Code::FAILED);
            }

            //检查客户是否存在
            $company = Company::find($companyId);
            if (!$company) {
                return CatchResponse::fail('客户不存在', Code::FAILED);
            }

            //判断采购单状态是否为运营已审核 ，否则不可以生成合同
            if ($order->audit_status < 3) {
                return CatchResponse::fail('订单未通过运营审核，不能创建合同', Code::FAILED);
            }

            //检查是否已经生成合同
            $contract = $this->purchaseContractModel->where('purchase_order_id', $id)->find();
            if ($contract) {
                // return CatchResponse::fail('合同已创建，不能重复创建', Code::FAILED);
            }

            //获取采购单商品并分组
            $supplyIds = $this->purchaseOrderProductsModel->where('purchase_order_id', $id)
                ->group('supply_id')
                ->column('supply_id');


            foreach ($supplyIds as $supplyId) {
                $supply = $this->supplyModel->findBy($supplyId);

                $amount = $this->purchaseOrderProductsModel
                    ->where('purchase_order_id', $id)
                    ->where('supply_id', $supplyId)
                    ->sum('amount');

                //创建合同
                $contractNo = $this->purchaseContractModel->createContractNo($company->code, $supply->code);
                $batchNo    = $this->warehouseOrderModel->createBatchNo();
                if ($supply->pay_ratio == 100) {
                    $text = '预付比例100%，出货前付清';
                } else {
                    $text = "预付比例{$supply->pay_ratio}%，出货后{$supply->billing_cycles}天付清";
                }

                $content = str_replace("{{selletment_date}}",  $text, $supply->getAttr('contract_template'));
                $content = str_replace("{{total_amount}}", $amount, $content);

                $contract   = $this->purchaseContractModel->createBy([
                    'purchase_order_id' => $order->getAttr('id'),
                    'purchase_order_code' => $order->getAttr('code'),
                    'supply_id' => $supplyId,
                    'supply_name' => $supply->getAttr('name'),
                    'company_id' => $companyId,
                    'company_name' => $company->getAttr('name'),
                    'company_contacts' => $company->getAttr('contacts'),
                    'company_mobile' => $company->getAttr('mobile'),
                    'company_address' => $company->getAttr('address'),
                    'code' => $contractNo,
                    'batch_no' => $batchNo,
                    'amount' => $amount,
                    'content' => $content,
                    'created_by' => $data['creator_id']
                ]);

                $productIds = $this->purchaseOrderProductsModel
                    ->where('purchase_order_id', $id)
                    ->where('supply_id', $supplyId)
                    ->column('id');

                $list = [];
                foreach ($productIds as $productId) {
                    $row = [
                        'purchase_contract_id' => $contract,
                        'purchase_product_id' => $productId
                    ];

                    $list[] = $row;
                }

                $this->purchaseContractProductsModel->saveAll($list);
            }

            $order->contract_status = 1;
            $order->save();
            $this->purchaseContractModel->commit();
            return CatchResponse::success(true);
        } catch (\Exception $exception) {
            $this->purchaseOrdersModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail(
                $exception->getLine() . ':' . $exception->getMessage(),
                $code
            );
        }
    }


    /**
     * 更新
     * @time 2021年01月23日 14:55
     *
     * @param Request $request
     * @param         $id
     */
    public function update(Request $request, $id): \think\Response
    {
        try {
            $this->purchaseContractModel->startTrans();

            $data               = $request->post();
            $data['updated_by'] = $data['creator_id'];
            $data['amount']     = 0;

            if (isset($data['products'])) {
                //更新商品采购数量
                foreach ($data['products'] as $val) {
                    $product        = $this->purchaseOrderProductsModel->findBy($val['id']);
                    $row            = [
                        'price' => $val['price'],
                        'number' => $val['number'],
                        // 'amount' => $val['number'] * $product->getAttr('price')
                        'amount' => $val['number'] * $val['price']
                    ];
                    $data['amount'] += $row['amount'];
                    $this->purchaseOrderProductsModel->updateBy($val['id'], $row);
                }
            }

            $res = $this->purchaseContractModel->updateBy($id, $data);
            $this->purchaseContractModel->commit();

            return CatchResponse::success($res);
        } catch (\Exception $exception) {
            $this->purchaseContractModel->rollback();
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
    }

    /**
     * 详情
     *
     * @param $id
     *
     * @return \think\response\Json
     */
    public function read($id)
    {

        $order = $this->purchaseContractModel->with(['supply'])->find($id);

        if (!$order) {
            return CatchResponse::fail('订单不存在', Code::FAILED);
        }
        $order->products    = $this->purchaseContractModel->products($id, 1);
        $order->parts       = $this->purchaseContractModel->products($id, 2);

        if ($order->supply->pay_ratio == 100) {
            $text = '预付比例100%，出货前付清';
        } else {
            $text = "预付比例{$order->supply->pay_ratio}%，出货后{$order->supply->billing_cycles}天付清";
        }

        $order->content = str_replace("{{selletment_date}}", $text, $order->content);
        $order->content = str_replace("{{total_amount}}", $order->amount, $order->content);
        // $order->demand_info = config('const.demand_info');
        return CatchResponse::success($order);
    }


    /**
     * 修改审核状态
     *
     * @param CatchRequest $request
     *
     * @return \think\response\Json
     */
    public function changeAuditStatus(Request $request)
    {
        try {
            $data = $request->post();

            $contract = $this->purchaseContractModel->findBy($data['id']);
            if (!$contract) {
                return CatchResponse::fail('合同不存在', Code::FAILED);
            }

            if ($contract->audit_status == 1) {
                return CatchResponse::fail('合同已审核通过', Code::FAILED);
            }

            $this->purchaseContractModel->updateBy($data['id'], $data);

            //审核通过后生成付款单
            if ($data['audit_status'] == 1) {
                $payment =  [
                    'payment_no' => $this->purchasePaymentModel->createPaymentNo(),
                    'source' => '合同预付款',
                    'contract_code' => $contract->code,
                    'supply_id' => $contract->supply_id,
                    'supply_name' => $contract->supply_name,
                    'order_amount' => $contract->prepayAmount(),
                    'creator_id' => $data['creator_id']
                ];
                $this->purchasePaymentModel->storeBy($payment);
                $this->supplyModel->updateBy($contract->supply_id, ['cooperation_status' => 1]);
            }
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }


    /**
     * 上传附件
     *
     * @param Request     $request
     * @param CatchUpload $upload
     *
     * @return \think\response\Json
     */
    public function uploadAttachment(Request $request): \think\response\Json
    {
        try {
            $data = $request->param();

            $supply = $this->purchaseContractModel->findBy($data['id']);
            if (!$supply) {
                return CatchResponse::fail('合同不存在', Code::FAILED);
            }

            $this->purchaseContractModel->updateBy($data['id'], $data);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            return CatchResponse::fail($exception->getMessage(), $code);
        }
        return CatchResponse::success(true);
    }

    /**
     * 根据合同号获取合同商品
     *
     * @param Request $request
     *
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getProducts(Request $request)
    {
        $ids      = $request->param('ids');
        $products = $this->purchaseContractProductsModel::join(
            'purchase_order_products',
            'purchase_order_products.id=purchase_contract_products.purchase_product_id'
        )
            ->whereIn('purchase_contract_id', $ids)
            ->where('type', 1)
            ->field('purchase_order_products.*, purchase_contract_products.purchase_contract_id')
            ->select();
        foreach ($products as $key => &$val) {
            $val->purchase_order_code = PurchaseOrders::where('id', $val->purchase_order_id)
                ->value('code');
            $transNum = $this->transhipmentOrderProductsModel
                ->where('purchase_product_id', $val['id'])
                ->sum('trans_number');
            $val->left_number = $val->number - $transNum;

            if ($val->left_number == 0) {
                unset($products[$key]);
            }
        }


        $parts = $this->purchaseContractProductsModel::join(
            'purchase_order_products',
            'purchase_order_products.id=purchase_contract_products.purchase_product_id'
        )
            ->whereIn('purchase_contract_id', $ids)
            ->where('type', 2)
            ->field('purchase_order_products.*, purchase_contract_products.purchase_contract_id')
            ->select();
        foreach ($parts as $key => &$val) {
            $val->purchase_order_code = PurchaseOrders::where('id', $val->purchase_order_id)
                ->value('code');
            $transNum = $this->transhipmentOrderProductsModel
                ->where('purchase_product_id', $val['id'])
                ->sum('trans_number');
            $val->left_number = $val->number - $transNum;
            if ($val->left_number == 0) {
                unset($parts[$key]);
            }
        }

        return CatchResponse::success(['products' => $products, 'parts' => $parts]);
    }


    /**
     * 打印商品码
     *
     * @param Request $request
     * @param $id
     * @return \think\response\Json
     */
    public function createBarCode(Request $request, $id)
    {
        $productId = $request->param('purchase_product_id');
        // 获取合同数据
        if (!$contract = $this->purchaseContractModel->where(['audit_status' => 1, 'id' =>  $id])->find()) {
            return CatchResponse::fail('合同不存在或未审核通过');
        }
        // 获取合同商品数据
        $dataContractProducts = $this->purchaseContractProductsModel->where(['purchase_contract_id' => $id, 'purchase_product_id' => $productId])->find();
        if (empty($dataContractProducts)) {
            return CatchResponse::fail('打印数据不存在');
        }
        if (!$purchaseOrderProducts = $this->purchaseOrderProductsModel->where(['id' => $productId])->find()) {
            return CatchResponse::fail('打印数据不存在');
        }
        $data = [];
        $pdfs = [];
        // 当已经生成过条码时候
        if (!empty($dataContractProducts['upc_only']) && !empty($dataContractProducts['date_path'])) {
            for ($i = 0; $i < $purchaseOrderProducts['number']; $i++) {
                $fileName = sprintf("%s%s", $dataContractProducts['upc_only'], str_pad($i, 4, "0", STR_PAD_LEFT));
                $data['images'][$i] = env('APP.DOMAIN') . '/images/barcode/' . $dataContractProducts['date_path'] . '/' . $fileName . '.png';
            }
            $data['pdfAll'] = env('APP.DOMAIN') . '/images/barcode/' . $dataContractProducts['date_path'] . '/' . $dataContractProducts['upc_only'] . '.pdf';
            // 获取pdf
            $data['pdf'] = env('APP.DOMAIN') . '/images/barcode/' . $dataContractProducts['date_path'] . '/' . $dataContractProducts['upc_only'] . '.zip';
        } else {
            // 条形码唯一id 合同商品表 purchase_product_id（采购单关联商品表自增id）默认补齐 8 位 + 数量id数字自增 补齐4位
            $only_id = sprintf("%s", str_pad($productId, 8, "0", STR_PAD_LEFT));
            // 当前时间
            $pathDate = date('Ymd');
            $path = Utils::publicPath('images/barcode/' . $pathDate);
            !is_dir($path) && mkdir($path, 0777, true);
            // 生成pdf
            for ($i = 0; $i < $purchaseOrderProducts['number']; $i++) {
                $number = str_pad($i, 4, "0", STR_PAD_LEFT);
                $code = sprintf("%s%s", $only_id, $number);
                $fileName = $code;
                $generator = new BarcodeGeneratorPNG();
                $html2pdf = new Html2Pdf('L', [60, 100]);

                $barCode = $generator->getBarcode($code, $generator::TYPE_CODE_128);
                $label_file    = Filesystem::disk('local')->path('barcode/' . $pathDate . '/' . $fileName . '_origin.png');

                file_put_contents($label_file, $barCode);

                $imagePath = Filesystem::disk('local')->path('barcode/' . $pathDate . '/' . $fileName . ".png");


                $im         = imagecreatetruecolor(400, 300);
                $background = imagecolorallocate($im, 255, 255, 255);
                imagefill($im, 0, 0, $background);
                $bg = imagecreatefromstring(file_get_contents($label_file));   // 设置背景图片
                imagecopy($im, $bg, 20, 30, 0, 0, 202, 30);             // 将背景图片拷贝到画布相应位置
                //选择字体
                $font = Filesystem::disk('public')->path('fonts/SourceSansPro-Regular.ttf');
                $str = '';
                for ($t = 0; $t < strlen($code); $t++) {
                    $str .= $code[$t] . "      ";
                }
                $black = imagecolorallocate($im, 0x00, 0x00, 0x00); //字体颜色
                imagettftext($im, 8, 0, 20, 75, $black, $font, "*  " . $str . "*");
                imagettftext($im, 8, 0, 20, 95, $black, $font, 'SKU:' . $purchaseOrderProducts->goods_code);
                imagettftext($im, 8, 0, 20, 115, $black, $font, 'LOT NO:' . $contract->code);
                imagettftext($im, 8, 0, 20, 135, $black, $font, 'MADE IN CHINA');

                // imagedestroy($bg);
                imagepng($im, $imagePath);
                $data['images'][$i] = env('APP.DOMAIN') . '/images/barcode/' . $pathDate . '/' . $fileName . '.png';
                $data['imagespath'][$i] = $fileName;

                $content = '<barcode dimension="1D" type="C39+" value="' . $code . '" label="label" style="width:79mm; height:18mm; color: #000000; font-size: 4mm; margin:30 0 0 10"></barcode>
                            <p style="font-size:12px;margin:8 0 0 28">SKU: ' . $purchaseOrderProducts->goods_code . '</p>
                            <p style="font-size:12px;margin:8 0 0 28">LOT NO:' . $contract->code . '</p>
                            <p style="font-size:12px;margin:8 0 0 28">MADE IN CHINA</p>

                            ';
                $html2pdf->writeHTML($content);

                $newpath = Filesystem::disk('local')->path("barcode/{$pathDate}/{$fileName}.pdf");
                $html2pdf->output($newpath, 'F');
                // $imagePathnew = Filesystem::disk('local')->path('barcode/' . $pathDate . '/' . $fileName . ".png");
                // $img = new \Imagick($imagePathnew);
                // $newpathnew = Filesystem::disk('local')->path("barcode/{$pathDate}/{$fileName}.pdf");
                // $img->setImageFormat('pdf');
                // $img->writeImage($newpathnew);
                $pdfs[$i] = $newpath;
            }
            $pdf = new Fpdi();
            // 載入現在 PDF 檔案
            for ($ii = 0; $ii < count($pdfs); $ii++) {
                $page_count = $pdf->setSourceFile($pdfs[$ii]);
                for ($pageNo = 1; $pageNo <= $page_count; $pageNo++) {
                    //一页一页的读取PDF，添加到新的PDF
                    $templateId = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($templateId);
                    $pdf->AddPage($size['orientation'], $size);
                    $pdf->useTemplate($templateId);
                    $pdf->SetFont('Helvetica');
                    $pdf->SetXY(5, 5);
                }
            }
            $mergePdf = $only_id . ".pdf";
            $mergePdfPath = Filesystem::disk('local')->path('barcode/' . $pathDate . '/' . $mergePdf);
            $pdf->output($mergePdfPath, "F");
            $data['pdfAll'] = env('APP.DOMAIN') . '/images/barcode/' . $pathDate . '/' . $mergePdf;

            // 压缩多个文件
            $filename = Filesystem::disk('local')->path("barcode/{$pathDate}/{$only_id}.zip"); // 压缩包所在的位置路径
            $zip = new ZipArchive();
            $zip->open($filename, ZipArchive::CREATE);   //打开压缩包
            // $mergepdf = Filesystem::disk('local')->path('barcode/' . $pathDate . '/' . $mergePdf);
            foreach ($data['imagespath'] as $file) {
                $imagePath = Filesystem::disk('local')->path("barcode/{$pathDate}/{$file}.png");
                $zip->addFile($imagePath, basename($file . ".png"));   //向压缩包中添加文件
            }
            $zip->close();  //关闭压缩包
            $data['pdf'] = env('APP.DOMAIN') . '/images/barcode/' . $pathDate . '/' . $only_id . '.zip';

            // // 修改数据
            $this->purchaseContractProductsModel->where(['purchase_contract_id' => $id, 'purchase_product_id' => $productId])
                ->update(['upc_only' => $only_id, 'date_path' => $pathDate]);
        }
        return CatchResponse::success($data);
    }


    /**
     * 打印合同
     *
     * @param Request $request
     * @param  $id
     * @return void
     */
    public function contractToPdf(Request $request, $id)
    {
        try {
            $contract =  $this->purchaseContractModel->with(['supply'])->find($id);
            $contract->products    = $this->purchaseContractModel->products($id, 1);
            $contract->parts       = $this->purchaseContractModel->products($id, 2);
            $formatAmount = $this->num_to_rmb($contract->amount);

            $html = View::fetch('contract', [
                'contract'  => $contract,
                'format_amount' => $contract->order->currency == 'rmb' ? '人民币' . $formatAmount : '美元'  . $formatAmount,
                'currency' => $contract->order->currency,
            ]);
            $html = str_replace('<p', "<div", $html);
            $html = str_replace('/p', '/div', $html);
            $html = str_replace("color:rgb(0,0,0);", "color: rgb(0,0,0);word-wrap: break-word;word-break: break-all;white-space: normal;font-size: 12px;marigin", $html);

            // print_r($html);exit;
            $html2pdf = new Html2Pdf('P', 'A4', 'cn');
            $html2pdf->setDefaultFont('stsongstdlight');
            $html2pdf->writeHTML($html);

            $fileName = $contract->code . time();
            $newpath = Filesystem::disk('local')->path("barcode/{$fileName}.pdf");
            $html2pdf->output($newpath, 'F');
            return CatchResponse::success(['file' => env('APP.DOMAIN') . '/images/barcode/' . $fileName . '.pdf']);
        } catch (Exception $e) {
            $code = $e->getCode();
            return CatchResponse::fail($e->getMessage(), $code);
        }
    }


    /**
     *数字金额转换成中文大写金额的函数
     *String Int  $num  要转换的小写数字或小写字符串
     *return 大写字母
     *小数位为两位
     **/
    function num_to_rmb($num)
    {
        $c1 = "零壹贰叁肆伍陆柒捌玖";
        $c2 = "分角元拾佰仟万拾佰仟亿";
        //精确到分后面就不要了，所以只留两个小数位
        $num = round($num, 2);
        //将数字转化为整数
        $num = $num * 100;
        if (strlen($num) > 10) {
            return "金额太大，请检查";
        }
        $i = 0;
        $c = "";
        while (1) {
            if ($i == 0) {
                //获取最后一位数字
                $n = substr($num, strlen($num) - 1, 1);
            } else {
                $n = $num % 10;
            }
            //每次将最后一位数字转化为中文
            $p1 = substr($c1, 3 * $n, 3);
            $p2 = substr($c2, 3 * $i, 3);
            if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
                $c = $p1 . $p2 . $c;
            } else {
                $c = $p1 . $c;
            }
            $i = $i + 1;
            //去掉数字最后一位了
            $num = $num / 10;
            $num = (int)$num;
            //结束循环
            if ($num == 0) {
                break;
            }
        }
        $j = 0;
        $slen = strlen($c);
        while ($j < $slen) {
            //utf8一个汉字相当3个字符
            $m = substr($c, $j, 6);
            //处理数字中很多0的情况,每次循环去掉一个汉字“零”
            if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
                $left = substr($c, 0, $j);
                $right = substr($c, $j + 3);
                $c = $left . $right;
                $j = $j - 3;
                $slen = $slen - 3;
            }
            $j = $j + 3;
        }
        //这个是为了去掉类似23.0中最后一个“零”字
        if (substr($c, strlen($c) - 3, 3) == '零') {
            $c = substr($c, 0, strlen($c) - 3);
        }
        //将处理的汉字加上“整”
        if (empty($c)) {
            return "零元整";
        } else {
            return $c . "整";
        }
    }
}
