<?php

/**
 * @filesource  :  ups.php
 * @Author      :  justcode.ikeepstudying.com
 * @copyright   :  Copyright (C) 2017-2018 GLS IT Studio NY
 * @version     :  Created on Dec 6, 2017 10:12:19 AM
 * @Github      :  https://github.com/gabrielbull/php-ups-api
 *
 */

namespace catchAdmin\delivery\common;

include_once rtrim(dirname(__FILE__), '/') . '/../../../vendor/autoload.php';

use DateTime;
use think\Exception;
use think\facade\Filesystem;
use think\facade\Log;
use Ups\Entity\InsuredValue;
use catcher\Utils;


class DeliveryUpsCommon
{
    private $ups_account;
    private $ups_access_key;
    private $ups_user_id;
    private $ups_user_password;
    private $shipment;
    private $service;
    private $package;
    private $dimensions;
    private $address_from;
    private $address_to;
    private $ship_from;
    private $ship_to;
    private $pickup;
    private $request;
    private $type;
    private $services = array(
        '01' => 'UPS Next Day Air',
        '02' => 'UPS Second Day Air',
        '03' => 'UPS Ground',
        '07' => 'UPS Worldwide Express',
        '08' => 'UPS Worldwide Expedited',
        '11' => 'UPS Standard',
        '12' => 'UPS Three-Day Select',
        '13' => 'Next Day Air Saver',
        '14' => 'UPS Next Day Air Early AM',
        '54' => 'UPS Worldwide Express Plus',
        '59' => 'UPS Second Day Air AM',
        '65' => 'UPS Saver',
        '70' => 'UPS Access Point Economy',
        '93' => 'UPS Sure Post',
    );
    private $useIntegration;

    function __construct($param = array())
    {
        $this->ups_account       = $param['ups_account']       ?? config('catch.ups.account');
        $this->ups_access_key    = $param['ups_access_key']    ?? config('catch.ups.access_key');
        $this->ups_user_id       = $param['ups_user_id']       ?? config('catch.ups.user_id');
        $this->ups_user_password = $param['ups_user_password'] ??  config('catch.ups.password'); //"$2021&MyAccount*1030";

        $this->request    = new \Ups\Entity\TimeInTransitRequest;
        $this->shipment   = new \Ups\Entity\Shipment();
        $this->ship_from  = new \Ups\Entity\ShipFrom();
        $this->ship_to    = new \Ups\Entity\ShipTo();
        $this->service    = new \Ups\Entity\Service;
        $this->package    = new \Ups\Entity\Package();
        $this->dimensions = new \Ups\Entity\Dimensions();
        $this->useIntegration =  env('APP_ENV') == 'production' ? false : true;
        return $this;
    }

    public function __set($property, $value)
    {
        $this->{$property} = $value;
    }

    function shipper($param = array())
    {

        $shipper = $this->shipment->getShipper();
        if (isset($param['number'])) $shipper->setShipperNumber($param['number']);
        if (isset($param['att']))    $shipper->setAttentionName($param['att']);
        if (isset($param['name']))   $shipper->setName($param['name']);
        $shipperAddress = $shipper->getAddress();
        $shipper->setAddress($shipperAddress);
        $this->shipment->setShipper($shipper);
        return $this;
    }

    function type($type = '')
    {
        $this->type = $type;
        return $this;
    }

    function from($param = array(), $type = '')
    {
        $type = $type ? $type : $this->type;

        switch ($type) {
            case 'artifact':

                $address_from = new \Ups\Entity\AddressArtifactFormat;
                if (isset($param['city']))     $address_from->setPoliticalDivision3($param['city']);
                if (isset($param['zip']))      $address_from->setPostcodePrimaryLow($param['zip']);
                if (isset($param['country']))  $address_from->setCountryCode($param['country']);
                $this->request->setTransitFrom($address_from);

                break;

            default:

                $address_from = new \Ups\Entity\Address();
                if (isset($param['name']))     $address_from->setAttentionName($param['name']);
                if (isset($param['address1'])) $address_from->setAddressLine1($param['address1']);
                if (isset($param['address2'])) $address_from->setAddressLine2($param['address2']);
                if (isset($param['address3'])) $address_from->setAddressLine3($param['address3']);
                if (isset($param['state']))    $address_from->setStateProvinceCode($param['state']);  // Required in US
                if (isset($param['city']))     $address_from->setCity($param['city']);
                if (isset($param['country']))  $address_from->setCountryCode($param['country']);
                if (isset($param['zip']))      $address_from->setPostalCode($param['zip']);

                $this->address_from = $address_from;
                $this->ship_from->setAddress($address_from);
                $this->shipment->setShipFrom($this->ship_from);

                break;
        }

        return $this;
    }

    function to($param = array(), $type = '')
    {
        $type = $type ? $type : $this->type;

        switch ($type) {
            case 'artifact':

                $address_to = new \Ups\Entity\AddressArtifactFormat;
                if (isset($param['city']))     $address_to->setPoliticalDivision3($param['city']);
                if (isset($param['zip']))      $address_to->setPostcodePrimaryLow($param['zip']);
                if (isset($param['country']))  $address_to->setCountryCode($param['country']);
                $this->request->setTransitTo($address_to);

                break;

            default:

                $address_to = new \Ups\Entity\Address();
                if (isset($param['name']))     $address_to->setAttentionName($param['name']);
                if (isset($param['address1'])) $address_to->setAddressLine1($param['address1']);
                if (isset($param['address2'])) $address_to->setAddressLine2($param['address2']);
                if (isset($param['address3'])) $address_to->setAddressLine3($param['address3']);
                if (isset($param['state']))    $address_to->setStateProvinceCode($param['state']);  // Required in US
                if (isset($param['city']))     $address_to->setCity($param['city']);
                if (isset($param['country']))  $address_to->setCountryCode($param['country']);
                if (isset($param['zip']))      $address_to->setPostalCode($param['zip']);

                $this->address_to = $address_to;
                $this->ship_to->setAddress($address_to);
                $this->shipment->setShipTo($this->ship_to);

                break;
        }

        return $this;
    }

    /**
     * ups/vendor/gabrielbull/ups-api/src/Entity/Service.php
    $services = array(
    '1' => S_AIR_1DAY,
    '2' => S_AIR_2DAY,
    '3' => S_GROUND,
    '7' => S_WW_EXPRESS,
    '8' => S_WW_EXPEDITED,
    '11' => S_STANDARD,
    '12' => S_3DAYSELECT,
    '13' => S_AIR_1DAYSAVER,
    '14' => S_AIR_1DAYEARLYAM,
    '54' => S_WW_EXPRESSPLUS,
    '59' => S_AIR_2DAYAM,
    '65' => S_SAVER,
    '70' => S_ACCESS_POINT,
    '93' => S_SURE_POST,
    );
     */
    function service($class = '03')
    {
        if ($class * 1 < 10) $class = '0' . abs($class);
        $this->service->setCode($class);
        $this->service->setDescription($this->service->getName());
        $this->shipment->setService($this->service);

        return $this;
    }

    function weight($lbs, $type = '')
    {
        $type = $type ? $type : $this->type;
        $unit = new \Ups\Entity\UnitOfMeasurement;
        $unit->setCode(\Ups\Entity\UnitOfMeasurement::UOM_LBS);

        switch ($type) {
            case 'artifact':

                $shipmentWeight = new \Ups\Entity\ShipmentWeight;
                $shipmentWeight->setWeight($lbs);
                $shipmentWeight->setUnitOfMeasurement($unit);
                $this->request->setShipmentWeight($shipmentWeight);

                break;

            default:

                $this->package->getPackagingType()->setCode(\Ups\Entity\PackagingType::PT_PACKAGE);
                $this->package->getPackageWeight()->setWeight($lbs);
                $this->package->getPackageWeight()->setUnitOfMeasurement($unit);

                break;
        }

        return $this;
    }

    function size($length = '', $width = '', $height = '')
    {
        if (is_array($length) and $length) {
            $length = $length['length'] ?? $length;
            $width  = $length['width']  ?? $width;
            $height = $length['height'] ?? $height;
        }

        $this->dimensions->setLength($length);
        $this->dimensions->setWidth($width);
        $this->dimensions->setHeight($height);
        $unit = new \Ups\Entity\UnitOfMeasurement;
        $unit->setCode(\Ups\Entity\UnitOfMeasurement::UOM_IN);
        $this->dimensions->setUnitOfMeasurement($unit);

        return $this;
    }

    function packages($number = 1)
    {
        $this->request->setTotalPackagesInShipment($number);
        return $this;
    }

    function invoice($amount, $currency = 'USD')
    {
        $invoiceLineTotal = new \Ups\Entity\InvoiceLineTotal;
        $invoiceLineTotal->setMonetaryValue($amount);
        $invoiceLineTotal->setCurrencyCode($currency);
        $this->request->setInvoiceLineTotal($invoiceLineTotal);
        return $this;
    }

    function pickup($date = '')
    {
        $this->pickup = date('Y-m-d', ($date ? strtotime(str_replace('-', '/', $date)) : time()));
        return $this;
    }


    /**
     * 获取物流信息
     *
     * @param [type] $tracking_number
     * @return void
     */
    function tracking($tracking_number)
    {
        $tracking = new \Ups\Tracking(
            $this->ups_access_key,
            $this->ups_user_id,
            $this->ups_user_password,
            $this->useIntegration
        );
        try {
            $shipment = $tracking->track($tracking_number);

            $response = $shipment->Package->Activity;
        } catch (Exception $e) {
            $response = $e->getMessage();
            $message = sprintf(
                "获取物流信息失败，物流单号【%s】, 异常信息:【%s】",
                $tracking_number,
                $e->getCode() . ':' . $e->getMessage() .
                    ' in ' . $e->getFile() . ' on line ' . $e->getLine()
            );
            Log::error($message);
        }

        return $response;
    }

    function ratetime($zip_from, $zip_to)
    {
        $rate = new \Ups\RateTimeInTransit($this->ups_access_key, $this->ups_user_id, $this->ups_user_password);

        $address = new \Ups\Entity\Address();
        $address->setPostalCode($zip_from);
        $shipFrom = new \Ups\Entity\ShipFrom();
        $shipFrom->setAddress($address);
        $this->shipment->setShipFrom($shipFrom);

        $shipTo = $this->shipment->getShipTo();
        $shipToAddress = $shipTo->getAddress();
        $shipToAddress->setPostalCode($zip_to);

        $this->package->setDimensions($this->dimensions);
        $this->shipment->addPackage($this->package);

        $deliveryTimeInformation = new \Ups\Entity\DeliveryTimeInformation();
        $deliveryTimeInformation->setPackageBillType(\Ups\Entity\DeliveryTimeInformation::PBT_NON_DOCUMENT);

        $this->shipment->setDeliveryTimeInformation($deliveryTimeInformation);

        try {
            $response = array();
            $result = $rate->shopRatesTimeInTransit($this->shipment)->RatedShipment;
            if ($result) foreach ($result as $row) {
                $service_summary = $row->TimeInTransit->ServiceSummary;
                $service_arrival = $service_summary->getEstimatedArrival();
                $response[] = array(
                    'service' => $service_summary->Service->getDescription(),
                    'fee' => $row->TotalCharges->MonetaryValue,
                    'currency' => $row->TotalCharges->CurrencyCode,
                    'business' => $service_arrival->getBusinessDaysInTransit(),
                    'delivery' => date('Y-m-d H:i:s', strtotime($service_arrival->getArrival()->getDate() . ' ' . $service_arrival->getArrival()->getTime())),
                );
            }
        } catch (Exception $e) {
            $response = $e->getMessage();
        }

        return $response;
    }

    function rate()
    {
        $rate = new \Ups\Rate($this->ups_access_key, $this->ups_user_id, $this->ups_user_password);
        $this->shipment->addPackage($this->package);

        try {
            $response = array();
            $result = $rate->getRate($this->shipment)->RatedShipment;

            if ($result) foreach ($result as $row) {
                $response[] = array(
                    'service' => $this->services[$row->Service->getCode()] ?? '--',
                    'fee' => $row->TotalCharges->MonetaryValue,
                    'currency' => $row->TotalCharges->CurrencyCode,
                    'business' => $row->GuaranteedDaysToDelivery,
                    $delivery = array('delivery' => date('Y-m-d', strtotime('+' . $row->GuaranteedDaysToDelivery . ' days')) . ' ' . $row->ScheduledDeliveryTime)
                );
            }
            $response = count($response) > 1 ? $response : $response[0];
        } catch (Exception $e) {
            $response = $e->getMessage();
        }

        return $response;
    }

    /**
     * The TimeInTransit Class allow you to get all transit times using the UPS TimeInTransit API.
     * /var/www/html/system/libraries/calculators/ups/vendor/gabrielbull/ups-api/src/Entity/EstimatedArrival.php
     * you need to manually change private vars to public vars
     *
     * @return array|string
     */
    function timein()
    {
        $timeInTransit = new \Ups\TimeInTransit($this->ups_access_key, $this->ups_user_id, $this->ups_user_password);

        // Pickup date
        $this->request->setPickupDate(new DateTime($this->pickup));

        try {
            $response = array();
            $result = $timeInTransit->getTimeInTransit($this->request)->ServiceSummary;

            if ($result) foreach ($result as $row) {
                $response[] = array(
                    'service' => $row->Service->getDescription(),
                    'pickup' => $row->EstimatedArrival->getPickupDate() . ' ' . $row->EstimatedArrival->getPickupTime(),
                    'business' => $row->EstimatedArrival->getBusinessTransitDays(),
                    'delivery' => $row->EstimatedArrival->getDate() . ' ' . $row->EstimatedArrival->getTime(),
                );
            }
        } catch (Exception $e) {
            $response = $e->getMessage();
        }

        return $response;
    }

    function isAddr($param = array())
    {
        $address = new \Ups\Entity\Address();
        if (isset($param['address1'])) $address->setAddressLine1($param['address1']);
        if (isset($param['address2'])) $address->setAddressLine2($param['address2']);
        if (isset($param['address3'])) $address->setAddressLine3($param['address3']);
        if (isset($param['city'])) $address->setCity($param['city']);
        if (isset($param['state'])) $address->setStateProvinceCode($param['state']);
        if (isset($param['country'])) $address->setCountryCode($param['country']);
        if (isset($param['zip'])) $address->setPostalCode($param['zip']);

        $xav = new \Ups\AddressValidation($this->ups_access_key, $this->ups_user_id, $this->ups_user_password);
        $xav->activateReturnObjectOnValidate(); //This is optional
        try {
            $response = $xav->validate($address, $requestOption = \Ups\AddressValidation::REQUEST_OPTION_ADDRESS_VALIDATION, $maxSuggestion = 15);
        } catch (Exception $e) {
            $response = $e->getMessage();
        }

        return $response;
    }


    public function shippment($order, $return = false)
    {
        $shipment = new \Ups\Entity\Shipment;
        $addressName = str_replace('&', ' ', $order->orderBuyRecord->address_name);
        $addressName = substr($addressName, 0, 35);
        // Set shipper
        $shipper = $shipment->getShipper();
        $shipper->setShipperNumber("");
        // $shipper->setName("");
        $shipper->setName($addressName);
        $shipper->setAttentionName($addressName);
        $shipperAddress = $shipper->getAddress();
        $shipperAddress->setAddressLine1($order->warehouse->street ?? '');
        // $shipperAddress->setAddressLine2('Suite 118');
        $shipperAddress->setPostalCode($order->warehouse->zipcode ?? '');
        $shipperAddress->setCity($order->warehouse->city ?? '');
        $shipperAddress->setCountryCode('');
        $shipperAddress->setStateProvinceCode($order->warehouse->state ?? '');
        $shipper->setAddress($shipperAddress);
        $shipper->setEmailAddress($order->warehouse->email ?? '');
        $shipper->setPhoneNumber($order->warehouse->phone ?? '');
        $shipment->setShipper($shipper);

        // To address
        // 替换买家地址中的多个空格
        $addressLine1 = $order->orderBuyRecord->address_street1 ?: $order->orderBuyRecord->ship_street1;
        $addressLine1 = preg_replace("/\s(?=\s)/", "\\1", $addressLine1);
        $addressLine2 =  $order->orderBuyRecord->address_street2;
        $address = new \Ups\Entity\Address();
        $address->setAddressLine1($addressLine1);
        if (!empty($addressLine2)) {
            $address->setAddressLine2($addressLine2);
        }
        $address->setPostalCode($order->orderBuyRecord->address_postalcode ?: $order->orderBuyRecord->ship_postalcode);
        $address->setCity($order->orderBuyRecord->address_cityname ?: $order->orderBuyRecord->ship_cityname);
        $address->setCountryCode('US');
        $address->setStateProvinceCode($order->orderBuyRecord->address_stateorprovince ?: $order->orderBuyRecord->ship_stateorprovince);
        $shipTo = new \Ups\Entity\ShipTo();
        $shipTo->setAddress($address);


        $shipTo->setCompanyName($addressName);
        $shipTo->setAttentionName($addressName);
        $shipTo->setEmailAddress($order->orderBuyRecord->address_email);
        $shipTo->setPhoneNumber(substr($order->orderBuyRecord->address_phone, 0, 15));
        $shipment->setShipTo($shipTo);

        // From address
        $address = new \Ups\Entity\Address();
        $address->setAddressLine1($order->warehouse->street);
        $address->setPostalCode($order->warehouse->zipcode);
        $address->setCity($order->warehouse->city);
        $address->setCountryCode('US');
        $address->setStateProvinceCode($order->warehouse->state);
        $shipFrom = new \Ups\Entity\ShipFrom();
        $shipFrom->setAddress($address);
        $shipFrom->setName($addressName);
        $shipFrom->setAttentionName($shipFrom->getName());
        $shipFrom->setCompanyName($shipFrom->getName());
        $shipFrom->setEmailAddress($order->warehouse->email ?? ' ');
        $shipFrom->setPhoneNumber($order->warehouse->phone ?? ' ');
        $shipment->setShipFrom($shipFrom);

        // // Sold to
        // $address = new \Ups\Entity\Address();
        // $address->setAddressLine1('350 5th Avenue');
        // $address->setPostalCode('10118');
        // $address->setCity('New York');
        // $address->setCountryCode('US');
        // $address->setStateProvinceCode('NY');
        // $soldTo = new \Ups\Entity\SoldTo;
        // $soldTo->setAddress($address);
        // $soldTo->setAttentionName('Joseph Daniel');
        // $soldTo->setCompanyName($soldTo->getAttentionName());
        // $soldTo->setEmailAddress('sibi.nandhu@gmail.com');
        // $soldTo->setPhoneNumber('8903444595');
        // $shipment->setSoldTo($soldTo);

        // Set service
        $service = new \Ups\Entity\Service;
        $service->setCode(\Ups\Entity\Service::S_GROUND);
        $service->setDescription($service->getName());
        $shipment->setService($service);

        // Mark as a return (if return) 退货面单
        if ($return) {
            $returnService = new \Ups\Entity\ReturnService;
            $returnService->setCode(\Ups\Entity\ReturnService::PRINT_RETURN_LABEL_PRL);
            $shipment->setReturnService($returnService);
        }

        // Set description
        $shipment->setDescription('');

        // Add Package
        $package = new \Ups\Entity\Package();
        $package->getPackagingType()->setCode(\Ups\Entity\PackagingType::PT_PACKAGE);
        $package->getPackageWeight()->setWeight($order->weight_AS_total);
        $unit = new \Ups\Entity\UnitOfMeasurement;
        $unit->setCode(\Ups\Entity\UnitOfMeasurement::UOM_LBS);
        $package->getPackageWeight()->setUnitOfMeasurement($unit);

        // Set Package Service Options
        $packageServiceOptions = new \Ups\Entity\PackageServiceOptions();
        //判断商品是否需要保价
        // 获取正常发货单
        // if ((int)$order->order_type_source == 1) {
        if (!$return) { // 非退款退货面单
            // 获取普通商品，（排除多箱，配件）
            if (empty($order->product['goods_group_id']) && (int)$order->product['type'] == 1) {
                // 获取保价商品
                if ($order->product->product->insured_price == 1) {
                    $insureValue = new InsuredValue();
                    $insureValue->setMonetaryValue($order->product->product->hedge_price * $order->product->number);
                    $insureValue->setCurrencyCode('USD');
                    $packageServiceOptions->setInsuredValue($insureValue);
                    $package->setPackageServiceOptions($packageServiceOptions);
                }
                // 多箱商品取值
            } elseif (!empty($order->product['goods_group_id']) && (int)$order->product['type'] == 1) {
                // 获取保价商品
                if ($order->product->product->insured_price == 1) {
                    // 判断保价为0时候不进行设置
                    if (!empty($order->productGroup->hedge_price)) {
                        $insureValue = new InsuredValue();
                        $insureValue->setMonetaryValue($order->productGroup->hedge_price * $order->product->number);
                        $insureValue->setCurrencyCode('USD');
                        $packageServiceOptions->setInsuredValue($insureValue);
                        $package->setPackageServiceOptions($packageServiceOptions);
                    }
                }
            }
        }
        if (!$return) {
            // 设置签名
            // $deliveryConfirmation = new \Ups\Entity\DeliveryConfirmation;
            // $deliveryConfirmation->setDcisType(1);
            // $packageServiceOptions->setDeliveryConfirmation($deliveryConfirmation);
            // $package->setPackageServiceOptions($packageServiceOptions);

            // 发货人放行
            // $packageServiceOptions->setShipperReleaseIndicator(true);
            // $package->setPackageServiceOptions($packageServiceOptions);
        }


        // Set dimensions
        $dimensions = new \Ups\Entity\Dimensions();
        $dimensions->setHeight($order->height_AS_total);
        $dimensions->setWidth($order->width_AS_total);
        $dimensions->setLength($order->length_AS_total);
        $unit = new \Ups\Entity\UnitOfMeasurement;
        $unit->setCode(\Ups\Entity\UnitOfMeasurement::UOM_IN);
        $dimensions->setUnitOfMeasurement($unit);
        $package->setDimensions($dimensions);

        // Add descriptions because it is a package
        if (!$return) {
            $package->setDescription('');
        } else {
            $package->setDescription('return');
        }

        // Set Reference Number
        // $referenceNumber = new \Ups\Entity\ReferenceNumber;
        if ($return) {
            // $referenceNumber->setCode(\Ups\Entity\ReferenceNumber::CODE_RETURN_AUTHORIZATION_NUMBER);
            // $referenceNumber->setValue($order->platform_no);
        } else {
            $orderProduct = $order->product;
            $goodsName = $orderProduct->goods_group_id == 0 ? $orderProduct->goods_code : (!empty($orderProduct->goods_group_name) ? $orderProduct->goods_group_name : $orderProduct->goods_code);
            $referenceNumber = new \Ups\Entity\ReferenceNumber;
            $referenceNumber->setCode(\Ups\Entity\ReferenceNumber::CODE_SOCIAL_SECURITY_NUMBER);
            $referenceNumber->setValue($goodsName);

            $package->setReferenceNumber($referenceNumber);

            $referenceNumber2 = new \Ups\Entity\ReferenceNumber;
            $referenceNumber2->setCode(\Ups\Entity\ReferenceNumber::CODE_SOCIAL_SECURITY_NUMBER);
            $referenceNumber2->setValue($order->platform_no);
            // $referenceNumber->setBarCodeIndicator(true);
            $package->setReferenceNumber2($referenceNumber2);
        }

        // Add this package
        $shipment->addPackage($package);




        if ($order->platForm && $order->platForm->platform_parameters != null) {
            $address = new \Ups\Entity\Address();
            $address->setCountryCode('US');
            $address->setPostalCode('30331');
            $info = $order->platForm->platform_parameters;
            $shipment->setPaymentInformation(new \Ups\Entity\PaymentInformation('billThirdParty', (object)array('AccountNumber' => $info['pay_account'], 'Address' => $address)));
        } else {

            $shipment->setPaymentInformation(new \Ups\Entity\PaymentInformation('prepaid', (object)array('AccountNumber' => $this->ups_account)));
        }

        // if ($order->platForm && $order->platForm->getAttr('name') == 'Wayfair') {
        //     // Set payment information
        //     $address = new \Ups\Entity\Address();
        //     $address->setCountryCode('US');
        //     $address->setPostalCode('30331');
        //     $info = config('catch.ups_wayfair');
        //     $shipment->setPaymentInformation(new \Ups\Entity\PaymentInformation('billThirdParty', (object)array('AccountNumber' => $info['pay_account'],'Address' => $address)));
        // }else if ($order->platForm && $order->platForm->getAttr('name') == 'Overstock') {
        //     // Set payment information
        //     $address = new \Ups\Entity\Address();
        //     $address->setCountryCode('US');
        //     $address->setPostalCode('30331');
        //     $info = config('catch.ups_overstockr');
        //     $shipment->setPaymentInformation(new \Ups\Entity\PaymentInformation('billThirdParty', (object)array('AccountNumber' => $info['pay_account'],'Address' => $address)));
        // }else {
        //     $shipment->setPaymentInformation(new \Ups\Entity\PaymentInformation('prepaid', (object)array('AccountNumber' => $this->ups_account)));
        // }


        // Ask for negotiated rates (optional)
        // $rateInformation = new \Ups\Entity\RateInformation;
        // $rateInformation->setNegotiatedRatesIndicator(1);
        // $shipment->setRateInformation($rateInformation);

        // Get shipment info
        try {
            $pathDateNow = date('Ymd');
            // $dirPath = public_path('/upslabel').$pathDate;
            $dirPath = runtime_path('/ups/' . $pathDateNow);
            // 存储文件夹不存在则创建
            if (!is_dir($dirPath)) {
                @mkdir($dirPath, 0755, true);
            }

            $log = new \Monolog\Logger('ups');

            $log->pushHandler(new \Monolog\Handler\StreamHandler(runtime_path() . '/ups/' . $pathDateNow . '/log.log', \Monolog\Logger::DEBUG));

            $api = new \Ups\Shipping($this->ups_access_key, $this->ups_user_id, $this->ups_user_password, $this->useIntegration, null, $log);
            $confirm = $api->confirm(\Ups\Shipping::REQ_VALIDATE, $shipment);

            if ($confirm) {
                $pathDate = date('Ymd');
                // $dirPath = public_path('/upslabel').$pathDate;
                $dirPath = Utils::publicPath('images/upslabel/' . $pathDate);
                // 存储文件夹不存在则创建
                if (!is_dir($dirPath)) {
                    @mkdir($dirPath, 0755, true);
                }

                $accept = $api->accept($confirm->ShipmentDigest);
                $label_file = $dirPath . $accept->PackageResults->TrackingNumber . '_origin.png';
                $base64_string = $accept->PackageResults->LabelImage->GraphicImage;
                $ifp = fopen($label_file, 'wb');
                fwrite($ifp, base64_decode($base64_string));
                fclose($ifp);

                // print_r($accept->PackageResults->TrackingNumber);
                $newpath = $dirPath . $accept->PackageResults->TrackingNumber . ".png";


                $im = imagecreatetruecolor(1400, 800);
                $background = imagecolorallocate($im, 255, 255, 255);
                imagefill($im, 0, 0, $background);
                $bg = imagecreatefromstring(file_get_contents($label_file));   // 设置背景图片
                imagecopy($im, $bg, 100, 0, 0, 0, 1400, 800);             // 将背景图片拷贝到画布相应位置
                //选择字体
                $font = Filesystem::disk('public')->path('fonts/FangZhengKaiTiJianTi-1.ttf');

                $black = imagecolorallocate($im, 0x00, 0x00, 0x00); //字体颜色

                $orderProduct = $order->product;
                $goodsName = $orderProduct->goods_group_id == 0 ? $orderProduct->goods_code : (!empty($orderProduct->goods_group_name) ? $orderProduct->goods_group_name : $orderProduct->goods_code);
                // $referenceNo = $order->platform_id == 3 ? $order->platform_no : $order->order_no;
                $referenceNo = $order->platform_no;

                // $goodsName = iconv('gbk','utf-8//TRANSLIT//IGNORE', $goodsName);
                // $goodsName=mb_convert_encoding($goodsName, "html-entities", "utf-8"); //转成html编码
                // $xNumber = empty($addressLine1) ? 460 :
                imagettftext($im, 40, 90, 475, 740, $black, $font, $goodsName . '*' . $orderProduct->number);
                imagettftext($im, 40, 90, 476, 740, $black, $font, $goodsName . '*' . $orderProduct->number);
                imagettftext($im, 40, 90, 477, 740, $black, $font, $goodsName . '*' . $orderProduct->number);
                imagettftext($im, 40, 90, 478, 740, $black, $font, $goodsName . '*' . $orderProduct->number);
                imagettftext($im, 40, 90, 479, 740, $black, $font, $goodsName . '*' . $orderProduct->number);

                imagettftext($im, 24, 90, 1160, 300, $black, $font, date('Y-m-d H:i:s'));
                // imagettftext($im, 18, 90, 1250, 770, $black, $font, 'Reference No.1:' . $goodsName);
                // imagettftext($im, 18, 90, 1270, 770, $black, $font, 'Reference No.2:' . $referenceNo);
                imagedestroy($bg);
                imagepng($im, $newpath);
                $data = [
                    'trackingNumber' => $accept->PackageResults->TrackingNumber,
                    'tracking_date' =>  $pathDate
                ];
                return $data;
            }
        } catch (\Exception $e) {
            throw  new Exception($e->getMessage());
        }
    }


    function __destruct()
    {
        unset($this->request);
        unset($this->shipment);
        unset($this->ship_from);
        unset($this->ship_to);
        unset($this->service);
        unset($this->package);
        unset($this->dimensions);
        unset($this->pickup);
        unset($this->type);
    }
}
