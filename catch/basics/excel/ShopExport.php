<?php
/*
 * @Version: 1.0
 
 * @Date: 2021-03-22 10:03:32
 * @LastEditors:
 * @LastEditTime: 2021-03-22 17:32:44
 * @Description: 
 */

namespace catchAdmin\basics\excel;

use catchAdmin\basics\model\Shop;
use catchAdmin\basics\model\Company;
use catchAdmin\store\model\Platforms;
use catcher\library\excel\ExcelContract;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class ShopExport implements ExcelContract
{

    /**
     * 设置头部
     *
     * @time 2020年09月08日
     * @return string[]
     */
    public function headers(): array
    {
        // TODO: Implement headers() method.
        return [
            '创建日期', 'id', '运营类型', '名称', '编码', '状态', '平台名称','客户名称'
        ];
    }

    /**
     * 处理数据
     *
     * @time 2020年09月08日
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @return \think\Collection
     */
    public function sheets()
    {
        // $fileList = [];
        // foreach ($file as $value) {
        //     $fileList[] = App(Shop::class)->aliasField($value);
        // }
        // TODO: Implement sheets() method.
        $shops = Shop::field(
            // $fileList
            [
                App(Shop::class)->aliasField('created_at'),
                App(Shop::class)->aliasField('id'),
                App(Shop::class)->aliasField('type'),
                App(Shop::class)->aliasField('shop_name'),
                App(Shop::class)->aliasField('code'),
                App(Shop::class)->aliasField('is_status')
            ]
        )  
        ->catchJoin(Platforms::class, 'id', 'platform_id', ['name as platform_name'], 'LEFT')
        ->catchJoin(Company::class, 'id', 'company_id', ['name as company_name'], 'LEFT')
        ->select();

        foreach ($shops as &$shop) {
            $shop->is_status = $shop->is_status == Shop::ENABLE ? '启用' : '停用';
            $shop->type = $shop->type == Shop::ENABLE ? '自营' : '代储存';
        }
        var_dump('$users', $shops); exit;
        return $shops;
    }

    /**
     * 活动的列
     */
    public function getWorksheet($sheet)
    {
        $sheet->getStyle('D')->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_NUMBER);
        return $sheet;
    }

    /**
     * 设置开始行
     *
     * @time 2020年09月08日
     * @return int
     */
    public function setRow()
    {
        return 2;
    }
    /**
     * 设置对应列的宽度
     */
    public function setWidth()
    {
        return [
            'A' => 150,
            'B' => 150,
            'C' => 150,
            'D' => 150,
            'E' => 150
        ];
    }

    /**
     * 设置标题
     *
     * @time 2020年09月08日
     * @return array
     */
    public function setTitle()
    {
        return [
            'A1:H1', '导出店铺', Alignment::HORIZONTAL_CENTER
        ];
    }
}
