<?php


namespace catchAdmin\delivery\common;


use catcher\CatchResponse;
use catcher\CatchUpload;
use catcher\Code;
use catcher\facade\Http;
use catcher\Utils;
use Spatie\ArrayToXml\ArrayToXml;
use think\facade\Filesystem;
use think\facade\Log;

class DeliveryUspsCommon
{

    const LIVE_API_URL = [
        'API' => ['eVS', 'TrackV2', 'eVSCancel'],
        'URL' => 'https://secure.shippingapis.com/ShippingAPI.dll'
    ];
    const TEST_API_URL = [
        'API' => ['eVSCertify', 'TrackV2', 'eVSCancel'],
        'URL' => 'https://stg-secure.shippingapis.com/ShippingApi.dll'
    ];
    const SERVICE_TYPE = [
        'PM' => [
            'ServiceType' => 'PRIORITY MAIL CUBIC',
            'Container' => 'CUBIC PARCELS',
            'Machinable' => 'true',
            'PriceOptions' => 'Commercial Plus'
        ],
        'FCM' => [
            'ServiceType' => 'FIRST CLASS',
            'Container' => 'VARIABLE',
            'Machinable' => 'false',
            'PriceOptions' => 'Commercial Base'
        ]
    ];

    public static $testMode = false;
    /**
     * 获取快递单号
     * @time 2021年10月14日
     *
     */
    public function shippment($order, $serviceType = 'PM')
    {
        try {
            if(!$order->warehouse->usps_json) {
                return ['code'=> false, 'message' => '获取失败：'.$order['invoice_no'].'→仓库未定义usps账号'];
            }

            // 设置调试模式
            $this->setTestMode(env('APP_ENV') == 'production' ? false : true);
            $API = $this->getEndpoint();
            $xml = ArrayToXml::convert($this->shippingRequest($order, $serviceType), [
                'rootElementName' => $API['API'][0].'Request',
                '_attributes' => [
                    'USERID' => $order->warehouse->usps_json['WebtoolsID'],
                ],
            ]);
            //get请求接口
            $response = Http::headers([])->query([
                'API' => $API['API'][0],
                'XML' => $xml
            ])->get($API['URL']);
            $response = json_decode(json_encode(simplexml_load_string($response->contents())), true);
            // print_r($response);exit();
            // 没有返回物流单号则失败
            if (!isset($response['BarcodeNumber'])){
                return ['code'=> false, 'message' => '获取失败：'.$order['invoice_no'].'→'.$response['Description']];
                // throw new \Exception('获取失败：' . $response['Description']);
            }
            $pathDate = date('Ymd');
            $path = Utils::publicPath('images/uspslabel/'.$pathDate);
            !is_dir($path) && mkdir($path, 0777, true);
            $label_file = $path . $response['BarcodeNumber'] . '_origin.png';

            $imagick = new \Imagick();
            //设置图像分辨率
            $imagick->setResolution(210,210);
            $imagick->readImageBlob(base64_decode($response['LabelImage']));
            $imagick->writeImage($label_file);

            // 重新生成Label图片
            $newpath = $path.$response['BarcodeNumber'] . '.png';
            $im = imagecreatetruecolor(840, 1260);
            $background = imagecolorallocate($im, 255, 255, 255);
            imagefill($im, 0, 0, $background);
            $bg = imagecreatefromstring(file_get_contents($label_file));   // 设置背景图片
            imagecopy($im, $bg, 0, 0, 0, 0, 840, 1260);             // 将背景图片拷贝到画布相应位置
            //选择字体
            $font = Filesystem::disk('public')->path('fonts/FangZhengKaiTiJianTi-1.ttf');

            $black = imagecolorallocate($im, 0x00, 0x00, 0x00); //字体颜色

            $orderProduct = $order->product;
            $goodsName = $orderProduct->goods_group_id == 0 ? $orderProduct->goods_code : $orderProduct->goods_group_name;
            $referenceNo = $order->platform_id == 3 ? $order->platform_no : $order->order_no;

            // $goodsName = iconv('gbk','utf-8//TRANSLIT//IGNORE', $goodsName);
            // $goodsName=mb_convert_encoding($goodsName, "html-entities", "utf-8"); //转成html编码

            imagettftext($im, 22, 0, 540, 1210, $black, $font, $goodsName . '*' . $orderProduct->number);
            imagettftext($im, 22, 0, 540, 1210, $black, $font, $goodsName . '*' . $orderProduct->number);
            imagettftext($im, 22, 0, 540, 1210, $black, $font, $goodsName . '*' . $orderProduct->number);
            imagettftext($im, 22, 0, 540, 1210, $black, $font, $goodsName . '*' . $orderProduct->number);
            imagettftext($im, 22, 0, 540, 1210, $black, $font, $goodsName . '*' . $orderProduct->number);

            imagettftext($im, 22, 0, 30, 1210, $black, $font, date('Y-m-d H:i:s'));
//            imagettftext($im, 18, 0, 1270, 770, $black, $font, 'reference no:' . $referenceNo);
            imagedestroy($bg);
            imagepng($im, $newpath);

            return ['code'=> true, 'message' => [
                'trackingNumber' => $response['BarcodeNumber'],
                'tracking_date' =>  $pathDate
            ]];
        } catch (\Exception $e) {
            return CatchResponse::fail('操作失败：' . $e->getMessage());
        }
    }

    /**
     * 取消物流label
     * @param $track
     * @param $userID
     */
    public function cancel($track, $userID){
        // 设置调试模式
        $this->setTestMode(env('APP_ENV') == 'production' ? false : true);
        $API = $this->getEndpoint();
        $xml = $this->cancelRequest($track, $userID);
        //get请求接口
        $response = Http::headers([])->query([
            'API' => $API['API'][2],
            'XML' => $xml
        ])->get($API['URL']);
        $response = simplexml_load_string($response->contents());
        if ($response->Status[0] == 'Cancelled'){
            return true;
        }else{
            return $response->Reason;
        }
    }

    /**
     * 查询物流信息
     * @param $tracks
     * @param $userID
     */
    public function tracking($tracks, $userID){
        // 设置调试模式
        $this->setTestMode(env('APP_ENV') == 'production' ? false : true);
        $API = $this->getEndpoint();
        $xml = $this->trackRequest($tracks, $userID);
        //get请求接口
        $response = Http::headers([])->query([
            'API' => $API['API'][1],
            'XML' => $xml
        ])->get($API['URL']);
        $response = json_decode(json_encode(simplexml_load_string($response->contents())), true);
        if (isset($response['TrackInfo']['Error'])){
            return $response['TrackInfo']['Error']['Description'];
        }
        return $response['TrackInfo'];
    }

    //usps调用
    public function addressVerify(){
        $verify = new \USPS\AddressVerify(config('catch.usps.username'));

        $address = new \USPS\Address();
        $address->setFirmName('测试');
        $address->setApt('100');
        $address->setAddress('3012 JAUQUET DR');
        $address->setCity('green bay');
        $address->setState('WI');
        $address->setZip5(54324);
        $address->setZip4('');

        // Add the address object to the address verify class
        $verify->addAddress($address);

        // Perform the request and return result
        var_dump($verify->verify());
        var_dump($verify->getArrayResponse());

        var_dump($verify->isError());
    }

    public function trackconfirm($tracks = '', $userID){
        // Initiate and set the username provided from usps
        $tracking = new \USPS\TrackConfirm($userID);

        // During test mode this seems not to always work as expected
        $tracking->setTestMode(true);

        // Add the test package id to the trackconfirm lookup class
        $tracking->addPackage($tracks);

        // Perform the call and print out the results
        print_r($tracking->getTracking());
        print_r($tracking->getArrayResponse());

        // Check if it was completed
        if ($tracking->isSuccess()) {
            echo 'Done';
        } else {
            echo 'Error: '.$tracking->getErrorMessage();
        }
    }

    public function getEndpoint()
    {
        return self::$testMode ? self::TEST_API_URL : self::LIVE_API_URL;
    }

    public function setTestMode($value)
    {
        self::$testMode = (bool) $value;
    }

    /**
     * eVS Label Shipping Data
     * @param $order
     * @param $serviceType
     * @return array
     */
    public function shippingRequest($order, $serviceType){
        $addressName = substr($order->orderBuyRecord->address_name, 0, 100);
        $toZip = explode('-',$order->orderBuyRecord->address_postalcode ?: $order->orderBuyRecord->ship_postalcode);
        $data = [
            'Option' => '',
            'Revision' => '',
            'ImageParameters' => [
                'ImageParameter' => '4X6LABELP',
                // 用于多个包的情况。
                // 不需要，只需要一个包裹。
                'LabelSequence' => [
                    'PackageNumber' => 1,
                    'TotalPackages' => 1
                ]
            ],
            // 必须发送发件人或公司的名字和姓氏的值
            'FromName' => $order->warehouse['name'],
            // 必须发送发件人或公司的名字和姓氏的值。
            'FromFirm' => '',
            'FromAddress1' => $order->warehouse->usps_json['WebtoolsID'] == ' ' ? 'SUITE 118' : '',
            'FromAddress2' => $order->warehouse->street ?? ' ',
            'FromCity' => $order->warehouse->city ?? ' ',
            'FromState' => $order->warehouse->state ?? ' ',
            'FromZip5' => $order->warehouse->zipcode ?? ' ',
            'FromZip4' => '',
            'FromPhone' => $order->warehouse->phone ?? ' ',
            'AllowNonCleansedOriginAddr' => 'false',
            'ToName' => $addressName,
            'ToFirm' => '',
            'ToAddress1' => $order->orderBuyRecord->address_street2 ?: $order->orderBuyRecord->ship_street2,
            'ToAddress2' => $order->orderBuyRecord->address_street1 ?: $order->orderBuyRecord->ship_street1,
            'ToCity' => $order->orderBuyRecord->address_cityname ?: $order->orderBuyRecord->ship_cityname,
            'ToState' => $order->orderBuyRecord->address_stateorprovince ?: $order->orderBuyRecord->ship_stateorprovince,
            'ToZip5' => $toZip[0] ?? '',
            'ToZip4' => $toZip[1] ?? '',
            'ToPhone' => '',
            'ToContactPreference' => empty($order->orderBuyRecord->address_email) ? 'WAIVED' :'EMAIL',
            'ToContactEMail' => $order->orderBuyRecord->address_email,
            'AllowNonCleansedDestAddr' => 'false',
            // 包裹重量。物品重量不得超过 70 磅（1120 盎司）
//            'WeightInOunces' => round($order->weight_AS_total,4),
            'WeightInOunces' => 1.8416,
            'ServiceType' => self::SERVICE_TYPE[$serviceType]['ServiceType'],
            'Container' => self::SERVICE_TYPE[$serviceType]['Container'],
//            'Width' => $order->width_AS_total,
//            'Length' => $order->length_AS_total,
//            'Height' => $order->height_AS_total,
            'Width' => 1.84252,
            'Length' => 1.84252,
            'Height' => 1.385828,
            'Machinable' => self::SERVICE_TYPE[$serviceType]['Machinable'],
            'PriceOptions' => self::SERVICE_TYPE[$serviceType]['PriceOptions'],
            'AddressServiceRequested' => 'false',
            'CustomerRefNo' => $order->platform_id == 3 ? $order->platform_no : $order->order_no,
            'CustomerRefNo2' => $order->platform_id == 3 ? $order->platform_no : $order->order_no,
            'ExtraServices' => [
                // 成人签名限制递送
                'ExtraService' => 155
            ],
            'CRID' => $order->warehouse->usps_json['CRID'],
            'MID' => $order->warehouse->usps_json['MasterMID'],
            // 发送 USPS 跟踪电子邮件通知的个人或公司的名称。
            'SenderName' => '',
            // 用于 USPS 跟踪电子邮件通知的发件人电子邮件地址。必须使用有效的电子邮件地址。
            'SenderEMail' => '',
            // 接收 USPS 跟踪电子邮件通知的个人或公司的名称。如果未提供收件人姓名，电子邮件将发送至提供的 < RecipientEMail > 值。
            'RecipientName' => '',
            // 接收 USPS 跟踪电子邮件通知的收件人的电子邮件地址。此字段是生成跟踪电子邮件所必需的。必须使用有效的电子邮件地址。
            'RecipientEMail' => '',
            'ReceiptOption' => 'SAME PAGE',
            'ImageType' => 'PDF',
            'ShipInfo' => 'true',
            'ReturnCommitments' => 'true',
            'PrintCustomerRefNo' => 'true',
            'PrintCustomerRefNo2' => 'false',
            'OptOutOfSPE' => 'false'
        ];
        return $data;
    }

    /**
     * @param array $tracks
     * @param $userID
     * @return string
     */
    public function trackRequest($tracks = [], $userID){
        $xml = '<TrackFieldRequest USERID="'.$userID.'">';
        foreach ($tracks as $val){
            $xml .='<TrackID ID="'.$val.'"></TrackID>';
        }
        $xml .= '</TrackFieldRequest>';
        return $xml;
    }

    /**
     * @param array $track
     * @param $userID
     * @return string
     */
    public function cancelRequest($track = '', $userID){
        $xml = '<eVSCancelRequest USERID="'.$userID.'">';
        $xml .= '<BarcodeNumber>'.$track.'</BarcodeNumber>';
        $xml .= '</eVSCancelRequest>';
        return $xml;
    }
}
