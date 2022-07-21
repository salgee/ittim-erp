-- 数据表清除

-- UPDATE fg_user SET fgu_promoter=0,fgu_promoter_id=0,fgu_bond=0,fgu_balance=0,fgu_integral=0;
-- TRUNCATE TABLE fg_user;

-- 基础数据表
TRUNCATE TABLE  attachments; -- 附件管理
TRUNCATE TABLE  company; -- 客户表
TRUNCATE TABLE  company_amount_log; -- 扣除客户余额记录表
TRUNCATE TABLE  company_quota; -- 客户币别额度表
TRUNCATE TABLE  currency; -- 币别表
TRUNCATE TABLE  zip_code_division; -- 邮编分区表
TRUNCATE TABLE  zip_code_special; -- 偏远-超偏远邮编表
TRUNCATE TABLE  lforwarder_company; -- 物流-货代公司
TRUNCATE TABLE  lforwarder_company_currency; -- 物流-货代公司账户表
TRUNCATE TABLE  logistics_fee_config; -- 物流台阶费用
TRUNCATE TABLE  logistics_fee_config_info; -- 物流台阶费用详情
TRUNCATE TABLE  notice; -- 系统公告表
TRUNCATE TABLE  order_fee_setting; -- 订单费用设置表
TRUNCATE TABLE  storage_fee_config; -- 仓储台阶费用
TRUNCATE TABLE  storage_fee; -- 仓储费
TRUNCATE TABLE  login_log; -- 登录日志
TRUNCATE TABLE  operate_log; -- 操作日志

-- 店铺数据
TRUNCATE TABLE  shop_basics; -- 店铺表
TRUNCATE TABLE  shop_user; -- 店铺管理用户表
TRUNCATE TABLE  shop_warehouse; -- 店铺管理仓库表

-- 商品管理
TRUNCATE TABLE  category; -- 商品分类
TRUNCATE TABLE  parts; -- 配件管理
TRUNCATE TABLE  product; -- 商品基础表
TRUNCATE TABLE  product_combination; -- 商品组合表
TRUNCATE TABLE  product_combination_info; -- 商品组合-关联商品
TRUNCATE TABLE  product_group; -- 多箱包装商品分组表
TRUNCATE TABLE  product_group_annex; -- 商品产品以及附件信息
TRUNCATE TABLE  product_info; -- 商品包装详情表
TRUNCATE TABLE  product_platform_sku; -- 平台sku和内部商品映射
TRUNCATE TABLE  product_presale; -- 预售活动商品管理
TRUNCATE TABLE  product_presale_info; -- 预售活动商品管理-关联商品
TRUNCATE TABLE  product_price; -- 商品价格表
TRUNCATE TABLE  product_sales_price; -- 商品促销价格模板
TRUNCATE TABLE  product_sales_price_info; -- 商品促销价格模板-关联商品

-- 订单
TRUNCATE TABLE  after_sale_order; -- 售后订单
TRUNCATE TABLE  order_deliver; -- 订单发货包裹表
TRUNCATE TABLE  order_deliver_products; -- 发货订单关联商品
TRUNCATE TABLE  order_buyer_records; -- 订单收货人表
TRUNCATE TABLE  order_get_records; -- 第三方平台订单拉取日志
TRUNCATE TABLE  order_item_records; -- 订单商品表
TRUNCATE TABLE  order_records; -- 订单表
TRUNCATE TABLE  orders_temp; -- 临时订单表

-- 仓库
TRUNCATE TABLE  allot_order_products; -- 调拨单商品表
TRUNCATE TABLE  check_order_products; -- 盘库单商品
TRUNCATE TABLE  check_order_warehouse_products; -- 盘库单录入结果
TRUNCATE TABLE  check_orders; -- 盘库单
TRUNCATE TABLE  transhipment_order_products; -- 出运单商品表
TRUNCATE TABLE  transhipment_orders; -- 出运单表
TRUNCATE TABLE  utility_bills; -- 费用单表
TRUNCATE TABLE  warehouse_order_products; -- 入库单商品表
TRUNCATE TABLE  warehouse_orders; -- 入库单表
TRUNCATE TABLE  warehouse_stock; -- 库存表
TRUNCATE TABLE  warehouse_stock_logs; -- 库存备份表
TRUNCATE TABLE  warehouses; -- 仓库表
TRUNCATE TABLE  fba_allot_order_products; -- FBA调拨单商品表
TRUNCATE TABLE  fba_allot_orders; -- FBA调拨单表
TRUNCATE TABLE  outbound_order_products; -- 出库单商品表
TRUNCATE TABLE  outbound_orders; -- 出库单表
TRUNCATE TABLE  replenishment_warning; -- 库存补货预警表
TRUNCATE TABLE  sales_forecast; -- 销量预计表
TRUNCATE TABLE  sales_forecast_products; -- 销量预计商品表
TRUNCATE TABLE  storage_product_fee; -- 商品仓储费
TRUNCATE TABLE  sub_orders; -- 预分仓表
TRUNCATE TABLE  allot_orders; -- 调拨单表
TRUNCATE TABLE  stock_change_log; -- 库存变化日志

-- 供应链
TRUNCATE TABLE  purchase_contract_products; -- 采购合同商品表
TRUNCATE TABLE  purchase_contracts; -- 采购合同表
TRUNCATE TABLE  purchase_invoice; -- 采购发票
TRUNCATE TABLE  purchase_order_products; -- 采购单商品表
TRUNCATE TABLE  purchase_orders; -- 采购单表
TRUNCATE TABLE  purchase_payment; -- 采购付款单
TRUNCATE TABLE  supplies; -- 供应商表
TRUNCATE TABLE  supply_bank_accounts; -- 供应商账号表

-- 财务
TRUNCATE TABLE  logistics_payaway_order; -- 物流付款单
TRUNCATE TABLE  logistics_transport_order; -- 物流应付账款订单表
TRUNCATE TABLE  freight_bill; -- 货代应付账款
TRUNCATE TABLE  freight_bill_order; -- 货代应付订单

-- 报表
TRUNCATE TABLE  report_order; -- 订单报表
TRUNCATE TABLE  report_order_after_sale; -- 订单售后信息报表




