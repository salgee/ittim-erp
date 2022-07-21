<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-02-09 15:26:36
 * @LastEditors: Please set LastEditors
 * @LastEditTime: 2021-08-26 16:53:32
 * @Description: 
 */

namespace catchAdmin\product\model;

use catcher\base\CatchModel as Model;
use catchAdmin\system\model\Config;
use catchAdmin\basics\model\OrderFeeSetting;
use catchAdmin\basics\model\LogisticsFeeConfigInfo;
use catchAdmin\basics\model\LogisticsFeeConfig;
use catchAdmin\product\model\search\ProductPriceSearch;
use catchAdmin\permissions\model\Users;
use catchAdmin\permissions\model\DataRangScopeTrait;
use catchAdmin\product\model\Category;
use catchAdmin\product\model\ProductGroup;
use catchAdmin\basics\model\Shop;


class ProductPrice extends Model
{
    use ProductPriceSearch;
    use DataRangScopeTrait;

    // 表名
    public $name = 'product_price';
    // 数据库字段映射
    public $field = array(
        'id',
        // 审核状态，0-待审核 1- 2-审核驳回
        'status',
        // 禁用启用 1-启用 2-禁用
        'is_status',
        // 驳回原因
        'reason',
        // 产品id 关联产品product
        'product_id',
        // 客户id
        'company_id',
        // 采购价格-rmb
        'purchase_price_rmb',
        // 采购价格-usd
        'purchase_price_usd',
        // 采购基准价格-若商品带出的是采购价(RMB),则采购基准价=采购价(RMB)/系数若商品带出的是采购价(USD),则采购基准价=采购价(USD)*系数这两个系数不是一个
        'purchase_benchmark_price',
        // 海运费
        'ocean_freight',
        // 关税税率
        'tariff_rate',
        // 关税杂费税率
        'tariff_rate_extras',
        // 额外税税率
        'additional_tax_rate',
        // 原关税
        'original_tariff',
        // 额外增加关税
        'additional_tariff_increase',
        // 总关税
        'all_tariff',
        // 仓储费
        'storage_fee',
        // 订单操作费
        'order_operation_fee',
        // 快递费
        'express_fee',
        // 高峰附加费
        'peak_surcharge',
        // 燃油附加费 原有的快递费+高峰期附加费
        'fuel_fee',
        // 合计
        'total',
        // 基准价系数
        'benchmark_price_coefficient',
        // 基准价
        'benchmark_price',
        // 修改人
        'update_by',
        // 创建人ID
        'creator_id',
        // 创建时间
        'created_at',
        // 更新时间
        'updated_at',
        // 软删除
        'deleted_at',
    );

    /**
     * 价格列表
     * @return \think\Paginator
     */
    public function getList()
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['is_company']) {
                $where = [
                    'p.company_id' => $prowerData['company_id']
                ];
            }
            // 如果是采购员
            if ($prowerData['is_buyer_staff']) {
                $where = [
                    'pd.purchase_id' => $prowerData['user_id']
                ];
            }
            // 如果绑定店铺
            if ($prowerData['shop_ids'] && !$prowerData['is_company']) {
                $company_ids = Shop::where('id', 'in', $prowerData['shop_ids'])->value('company_id');
                $where = [
                    'p.company_id' => ['in', $company_ids]
                ];
            } else {
                // 判断是运营岗，只可以查看所有的内部客户的商品
                if ($prowerData['is_operation']) {
                    $where = ['cp.user_type' => 0];
                }
            }
        }


        return $this->dataRange()
            ->field('p.*, pd.name_ch, pd.name_en, pd.code ,p.created_at, 
            p.updated_at,u.username as creator_name, IFNULL(us.username, "-") as update_name')
            ->alias('p')
            ->catchSearch()
            ->whereOr(function ($query) use ($where) {
                if (count($where) > 0) {
                    $query->where($where)
                        ->catchSearch();
                }
            })
            // ->where($where)
            ->order('p.id', 'desc')
            ->leftJoin('product pd', 'pd.id = p.product_id')
            ->leftJoin('company cp', 'cp.id = pd.company_id')
            ->leftJoin('users u', 'u.id = p.creator_id')
            ->leftJoin('users us', 'us.id = p.update_by')
            ->paginate();
    }
    /**
     * 价格审核
     */
    public function examineBy($id, $data)
    {
        return $this->where(['id' => $id])
            ->update([
                'status' => $data['status'],
                'is_status' => $data['status'],
                'reason' => $data['reason'],
                'update_by' => $data['creator_id'],
                'updated_at' => time()
            ]);
    }

    /**
     * 新增商品价格
     * @return Object
     */
    public function addPrice($data, $arr)
    {
        // 海运价
        $shipping_price = Config::where(['key' => 'product.shipping_price'])->value('value');
        $order_operation_fee = 0;
        $priceData['ocean_freight'] = 0;
        $priceData['express_fee'] = 0;

        // 单箱包装
        if ((int)$arr[0]['packing_method'] == 1) {
            $weight_gross_AS = $arr[0]['weight_gross_AS'] ?? 0;
            $volume_weight_AS = $arr[0]['volume_weight_AS'] ?? 0;
            $oversize = $arr[0]['oversize'] ?? 0;
            $volume = $arr[0]['volume'] ?? 0;
            // 订单规则查询
            $orderFeeSetting = new OrderFeeSetting;
            $feeData = $orderFeeSetting->getUserOrderAmount($data['company_id'], ($weight_gross_AS));
            $fee = $feeData['fee'];
            if ($fee) {
                $order_operation_fee = $fee;
            }

            // 规则一 计费重量取的是毛重(美制)和体积重（美制）的取大的那一个
            if ((float)$volume_weight_AS > (float) $weight_gross_AS) {
                $widthLogistic = ceil($volume_weight_AS);
            } else {
                $widthLogistic = ceil($weight_gross_AS);
            }
            // 规则二 若商品的计费重量<90lbs,但是商品oversize参数>130英寸，则计费重量按照90lbs计算
            if ($widthLogistic < 90 && $oversize > 130) {
                $widthLogistic = 90;
            }

            // 查看物流台阶费
            $logisticsFee = LogisticsFeeConfig::alias('l')->where([
                ['company_id', '=', $data['company_id']],
                ['is_status', '=', 1],
            ])
                ->leftJoin('logistics_fee_config_info lf', 'lf.logistics_fee_id = l.id and weight = ' . $widthLogistic)
                ->value('lf.zone6');
            $otherFee = $this->getOtherFee($arr[0], $data['company_id']);
            // 快递费 express_fee
            if ($logisticsFee) {
                $priceData['express_fee'] = bcadd($logisticsFee, $otherFee, 2);
            } else {
                $priceData['express_fee'] = $otherFee;
            }
            // 海运费 海运价*长*宽*高（公制）
            // $priceData['ocean_freight'] = bcmul($shipping_price, bcmul($length, bcmul($width, $height, 2), 2), 2);
            // 海运费 海运价格系数*体积（公制）
            $priceData['ocean_freight'] = bcmul($shipping_price, $volume, 2);
            // 高峰附加费
            if ($oversize > 130) {
                $priceData['peak_surcharge'] = Config::where(['key' => 'product.peak_surcharge'])->value('value');
            } else {
                $priceData['peak_surcharge'] = 0;
            }
            // 燃油附加费 原有的快递费+高峰期附加费
            $priceData['fuel_fee'] = bcadd($priceData['express_fee'], $priceData['peak_surcharge'], 2);
        } else {
            // 获取多箱包装商品信息
            $list = ProductGroup::field('length, width, height, volume_weight_AS, weight_gross_AS, volume,
            length_AS, width_AS,height_AS, oversize')
                ->where('product_id', $arr[0]['product_id'])->select()->toArray();
            $feeDataList = [];
            if (count($list) > 0) {
                foreach ($list as $value) {
                    $orderFeeSetting = new OrderFeeSetting;
                    // 订单操作费
                    $feeData1 = $orderFeeSetting->getUserOrderAmount($data['company_id'], ($value['weight_gross_AS'])) ?? 0;
                    $feeDataList['orderFee'][] = $feeData1['fee'];
                    // 物流台阶费
                    // if ((float)$value['volume_weight_AS'] > (float) $value['weight_gross_AS']) {
                    //     $widthLogistic = (int)ceil($value['volume_weight_AS']);
                    // } else {
                    //     $widthLogistic = (int)ceil($value['weight_gross_AS']);
                    // }
                    // 规则一 计费重量取的是毛重(美制)和体积重（美制）的取大的那一个
                    if ((float)$value['volume_weight_AS'] > (float) $value['weight_gross_AS']) {
                        $widthLogistic = ceil($value['volume_weight_AS']);
                    } else {
                        $widthLogistic = ceil($value['weight_gross_AS']);
                    }
                    // 规则二 若商品的计费重量<90lbs,但是商品oversize参数>130英寸，则计费重量按照90lbs计算
                    if ($widthLogistic < 90 && $value['oversize'] > 130) {
                        $widthLogistic = 90;
                    }
                    // 查看物流台阶费
                    $feeDataList['logisticsFee'][] = LogisticsFeeConfig::alias('l')->where([
                        ['company_id', '=', $data['company_id']],
                        ['is_status', '=', 1],
                    ])
                        ->leftJoin('logistics_fee_config_info lf', 'lf.logistics_fee_id = l.id and weight = ' . $widthLogistic)
                        ->value('lf.zone6') ?? 0;
                    // var_dump('$value', $value); exit;
                    // 其他增值费
                    $feeDataList['otherFee'][] = $this->getOtherFee($value, $data['company_id']);
                    // $feeDataList['ocean_freight'][] = bcmul($shipping_price, bcmul($value['length'], bcmul($value['width'], $value['height'], 2), 2), 2);
                    // 海运费 海运价格系数*体积（公制）
                    $feeDataList['ocean_freight'][] = bcmul($shipping_price, $value['volume'], 2);
                }
                $order_operation_fee = array_sum($feeDataList['orderFee']);
                $priceData['ocean_freight'] = array_sum($feeDataList['ocean_freight']);
                $priceData['express_fee'] = bcadd(array_sum($feeDataList['logisticsFee']), array_sum($feeDataList['otherFee']), 2);
            }
        }


        // 获取商品分类中税率
        $dataCategory = Category::where('id', $data['category_id'])->find();
        // var_dump('<>>>>>>', $priceData['express_fee'], $order_operation_fee); exit;
        // 基础价格与采购价格系数 rmb
        $price_factor = Config::where(['key' => 'product.price_factor'])->value('value');
        // 基础价格与采购价格系数 usd
        $price_factor_usd = Config::where(['key' => 'product.price_factor_usd'])->value('value');
        // 关税杂费税率
        // $tariff_rate = Config::where(['key' => 'product.tariff_rate'])->value('value');
        $tariff_rate = bcdiv($dataCategory['mix_tariff_rate'], 100, 6);
        // 额外税税率
        // $additional_tax_rate = Config::where(['key' => 'product.additional_tax_rate'])->value('value');
        $additional_tax_rate = bcdiv($dataCategory['additional_tax_rate'], 100, 6);
        // 仓储费比例
        $storage_fee_ratio = Config::where(['key' => 'product.storage_fee_ratio'])->value('value');
        // 基准价系数
        $benchmark_price_coefficient = Config::where(['key' => 'product.benchmark_price_coefficient'])->value('value');
        $priceData['purchase_price_rmb'] =  $data['purchase_price_rmb'];
        $priceData['purchase_price_usd'] =  $data['purchase_price_usd'];
        // 采购基准价格 若商品带出的是采购价(RMB),则采购基准价=采购价(RMB)/系数若商品带出的是采购价(USD),则采购基准价=采购价(USD)*系数这两个系数不是一个
        if (!empty((float)$data['purchase_price_usd'])) {
            $priceData['purchase_benchmark_price'] = bcmul($data['purchase_price_usd'], $price_factor_usd, 2);
        } else {
            $priceData['purchase_benchmark_price'] = bcdiv($data['purchase_price_rmb'], $price_factor, 2);
        }
        // 关税税率 tariff_rate 根据商品分类上的国外关税税率
        $priceData['tariff_rate'] = $dataCategory['tax_tariff_rate'] ?? 0;
        // 关税杂费税率
        $priceData['tariff_rate_extras'] = $dataCategory['mix_tariff_rate'] ?? 0;
        // 额外税税率
        $priceData['additional_tax_rate'] = $dataCategory['additional_tax_rate'] ?? 0;
        // 原关税 （关税税率+关税杂费税率）*采购基准价(USD)
        $priceData['original_tariff'] = bcmul(bcadd((bcdiv($dataCategory['tax_tariff_rate'], 100, 4)), ($tariff_rate), 4), $priceData['purchase_benchmark_price'], 4);
        // 额外增加关税  额外税税率*采购基准价(USD)
        $priceData['additional_tariff_increase'] = bcmul($additional_tax_rate, $priceData['purchase_benchmark_price'], 4);
        // 总关税 (原关税+额外增加关税) 
        $priceData['all_tariff'] = bcadd($priceData['original_tariff'], $priceData['additional_tariff_increase'], 4);
        // 仓储费 (采购基准价(USD)*比例)
        $priceData['storage_fee'] = bcmul($priceData['purchase_benchmark_price'], $storage_fee_ratio, 2);
        // 订单操作费 
        $priceData['order_operation_fee'] = $order_operation_fee;
        // 快递费 暂未开发
        // $priceData['express_fee'] = 0;
        // 合计  采购基准价(USD)+ 海运费+ 总关税+ 订单操作费+ 仓储费 + 快递费
        $priceData['total'] = bcadd(
            $priceData['purchase_benchmark_price'],
            bcadd(
                $priceData['ocean_freight'],
                bcadd(
                    $priceData['all_tariff'],
                    bcadd(
                        $priceData['storage_fee'],
                        bcadd($priceData['express_fee'], $order_operation_fee, 4),
                        4
                    ),
                    4
                ),
                4
            ),
            2
        );
        // 基准价系数 
        $priceData['benchmark_price_coefficient'] = $benchmark_price_coefficient;
        // 基准价 合计/基准价系数
        $priceData['benchmark_price'] = bcdiv($priceData['total'], $benchmark_price_coefficient, 2);
        $user = request()->user();
        $priceData['creator_id'] = $user['id']; // 添加用户ID
        $priceData['is_status'] = 0;
        $priceData['company_id'] = $data['company_id'];
        return $priceData;
    }
    /**
     * 算取物流台阶费用以及其他费用
     */
    public function getOtherFee($goodsDatas, $company_id)
    {
        // 商品毛重
        $weight_gross = ceil($goodsDatas['weight_gross_AS']);
        // 商品体积重
        $volume_weight_AS = ceil($goodsDatas['volume_weight_AS']);
        // 商品oversize
        $oversize = $goodsDatas['oversize'];
        // 规则一 计费重量取的是毛重(美制)和体积重（美制）的取大的那一个
        if ((float)$weight_gross > (float)$volume_weight_AS) {
            $width = $weight_gross;
        } else {
            $width = $volume_weight_AS;
        }
        // 规则二 若商品的计费重量<90lbs,但是商品oversize参数>130英寸，则计费重量按照90lbs计算
        if ($width < 90 && $oversize > 130) {
            $width = 90;
        }
        $lengData = [$goodsDatas['length_AS'], $goodsDatas['width_AS'], $goodsDatas['height_AS']];
        rsort($lengData); // 降序排序

        // 物流模板匹配
        $logisticsFeeConfig = new LogisticsFeeConfig;
        $logisticsData = $logisticsFeeConfig->where(['company_id' => $company_id, 'is_status' => 1])
            ->find();
        if (!$logisticsData) {
            // 物流费
            $freight_additional_price = 0;
        } else {
            $amountList = [];
            // 毛重金额
            if ($weight_gross > (float)$logisticsData['gross_weight']) {
                $amountList[0] = $logisticsData['gross_weight_fee'];
            } else {
                $amountList[0] = 0;
            }
            // 最长边 金额
            if ((float)$lengData[0] > (float)$logisticsData['big_side_length']) {
                $amountList[1] = $logisticsData['big_side_length_fee'];
            } else {
                $amountList[1] = 0;
            }
            // 次长边  金额
            if ((float)$lengData[1] > (float)$logisticsData['second_side_length']) {
                $amountList[2] = $logisticsData['second_side_length_fee'];
            } else {
                $amountList[2] = 0;
            }
            // oversize 金额
            if ((float)$logisticsData['oversize_max_size'] > (float)$oversize && (float)$oversize > (float)$logisticsData['oversize_min_size']) {
                $amountList[3] = $logisticsData['oversize_fee'];
            } else {
                $amountList[3] = 0;
            }
            // 超过 oversize 金额
            if ((float)$oversize > (float)$logisticsData['oversize_other_size']) {
                $amountList[4] = $logisticsData['oversize_other_size_fee'];
            } else {
                $amountList[4] = 0;
            }
            rsort($amountList); // 降序排序
            $freight_additional_price = $amountList[0] ?? 0;
        }
        return $freight_additional_price;
    }

    /**
     * 导出数据
     * 
     */
    public static $orderSource = array(
        0 => '待审核',
        1 => '审核通过',
        2 => '审核驳回'
    );

    public function exportList()
    {
        $users = new Users;
        $prowerData = $users->getRolesList();
        $where = [];
        if (!$prowerData['is_admin']) {
            if ($prowerData['is_company']) {
                $where = [
                    'p.company_id' => $prowerData['company_id']
                ];
            }
            // 如果是采购员
            if ($prowerData['is_buyer_staff']) {
                $where = [
                    'pd.purchase_id' => $prowerData['user_id']
                ];
            }
            // 如果绑定店铺
            if ($prowerData['shop_ids'] && !$prowerData['is_company']) {
                $company_ids = Shop::where('id', 'in', $prowerData['shop_ids'])->value('company_id');
                $where = [
                    'p.company_id' => ['in', $company_ids]
                ];
            } else {
                // 判断是运营岗，只可以查看所有的内部客户的商品
                if ($prowerData['is_operation']) {
                    $where = ['cp.user_type' => 0];
                }
            }
        }
        return $this->field('p.*, p.status as price_status, pd.*, pi.*')
            ->alias('p')
            ->catchSearch()
            ->whereOr(function ($query) use ($where) {
                if (count($where) > 0) {
                    $query->where($where)
                        ->catchSearch();
                }
            })
            ->leftJoin('product pd', 'pd.id = p.product_id')
            ->leftJoin('company cp', 'cp.id = pd.company_id')
            ->leftJoin('product_info pi', 'pi.product_id = p.product_id')
            ->order('p.id', 'desc')
            ->select()
            ->each(function ($item) {
                $item['purchase_status'] = $this::$orderSource[$item['price_status']];
            })->toArray();
    }
    /**
     * 导出参数
     */
    public function exportField()
    {
        return [
            [
                'title' => 'SKU(编码)',
                'filed' => 'code',
            ],
            [
                'title' => '中文名',
                'filed' => 'name_ch',
            ],
            [
                'title' => '英文名',
                'filed' => 'name_en',
            ],
            [
                'title' => '长（CM）',
                'filed' => 'length',
            ], [
                'title' => '宽（CM）',
                'filed' => 'width',
            ],
            [
                'title' => '高（CM）',
                'filed' => 'height',
            ],
            [
                'title' => '体积',
                'filed' => 'volume',
            ],
            [
                'title' => '毛重（KG）',
                'filed' => 'weight_gross',
            ],
            [
                'title' => '净重（KG）',
                'filed' => 'weight',
            ],
            [
                'title' => '长(inch）',
                'filed' => 'length_AS',
            ],
            [
                'title' => '宽(inch）',
                'filed' => 'width_AS',
            ],
            [
                'title' => '高(inch）',
                'filed' => 'height_AS',
            ],
            [
                'title' => '毛重（LBS)',
                'filed' => 'weight_gross_AS',
            ],
            [
                'title' => '净重（LBS)',
                'filed' => 'weight_AS',
            ],
            [
                'title' => '体积重',
                'filed' => 'volume_weight_AS',
            ],
            // [
            //    'title' => '计费重',
            //    'filed' => 'oversize', 
            // ],
            [
                'title' => '采购价(RMB)',
                'filed' => 'purchase_price_rmb',
            ],
            [
                'title' => '采购价(USD)',
                'filed' => 'purchase_price_usd',
            ],
            [
                'title' => '采购基准价（USD)',
                'filed' => 'purchase_benchmark_price',
            ],
            [
                'title' => '海运费',
                'filed' => 'ocean_freight',
            ],
            [
                'title' => '关税税率',
                'filed' => 'tariff_rate',
            ],
            [
                'title' => '关税杂费税率',
                'filed' => 'tariff_rate_extras',
            ],
            [
                'title' => '额外税税率',
                'filed' => 'additional_tax_rate',
            ],
            [
                'title' => '原关税',
                'filed' => 'original_tariff',
            ],
            [
                'title' => '额外增加关税',
                'filed' => 'additional_tariff_increase',
            ],
            [
                'title' => '总关税',
                'filed' => 'all_tariff',
            ],
            [
                'title' => '订单操作费',
                'filed' => 'order_operation_fee',
            ],
            [
                'title' => '仓储费',
                'filed' => 'storage_fee',
            ],
            [
                'title' => '快递费',
                'filed' => 'express_fee',
            ],
            [
                'title' => '合计',
                'filed' => 'total',
            ],
            [
                'title' => '基准价系数',
                'filed' => 'benchmark_price_coefficient',
            ],
            [
                'title' => '基准价',
                'filed' => 'benchmark_price',
            ],
            [
                'title' => '核价状态',
                'filed' => 'purchase_status',
            ],
        ];
    }
}
