`提姆跨境电商ERP`实现了多平台订单统一处理、多仓库商品统一管理、供应链流程控制，实现库存精准化、数据精细化管理。

![ittimerp](https://ittim.ltd/img/20220706154215.jpg "ERP方案架构")

<!--
 * @Author: salgee
 * @Date: 2021-01-23 09:25:36
 * @LastEditTime: 2022-06-24 12:11:12
 * @LastEditors: Please set LastEditors
 * @Description: In User Settings Edit
 * @FilePath: \erp\README.md
-->
<p align="center">
<a href="https://erp.admin.ittim.ltd">演示地址</a> |
<a href="https://gitee.com/salgee/ittim-erp">项目源码</a> |
<a href="https://www.catchadmin.com/docs/intro">依赖文档</a>
</p>


## 基本功能
- [x] `店铺管理` 多平台多店铺统一管理
- [x] `商品管理` 支持开发商品和正式商品
- [x] `订单管理` 包括正常订单、异常订单、FBA订单
- [x] `发货管理` 订单发货、售后
- [x] `供应链管理` 采购、出运
- [x] `仓储管理` 出仓、入仓、调拨、盘点、预警
- [x] `报表管理`
- [x] `财务管理`
- [x] `基础数据` 
- [x] `系统设置`
- [x] `多平台`
    - [x] `Amazon`
    - [x] `eBay`
    - [x] `Wayfair`
    - [x] `Overstock`
    - [x] `Walmart`
    - [x] `Opencart`
    - [x] `Shopify`
    - [x] `Houzz`
    - [ ] `Google`
    - [ ] `Lowes`
    - [ ] `Homedepot`
    - [ ] `NewEgg`
    - [ ] `Sears`
- [x] `catchadmin` 所有以下包括不限的基本功能均支持，可方便二开
  - [x] `用户管理` 后台用户管理
  - [x] `部门管理` 配置公司的部门结构，支持树形结构
  - [x] `岗位管理` 配置后台用户的职务
  - [x] `菜单管理` 配置系统菜单，按钮等等
  - [x] `角色管理` 配置用户担当的角色，分配权限
  - [x] `数据结构` 管理后台表结构
  - [x] `操作日志` 后台用户操作记录
  - [x] `登录日志` 后台系统用户的登录记录
  - [x] `敏感词`  支持敏感词配置
  - [x] `短信平台` 短信云管理，支持 阿里大于，腾讯云，Ucloud，Submail
  - [x] `云上传`  支持云上传，七牛，OSS，腾讯

#### 商品模块
![](https://ittim.ltd/img/20220706155801.jpg)

#### 订单模块
![](https://ittim.ltd/img/20220706155820.jpg)
![](https://ittim.ltd/img/20220706155833.jpg)

#### 供应链模块
![](https://ittim.ltd/img/20220706155855.jpg)

#### 仓储模块
![](https://ittim.ltd/img/20220706155911.jpg)

## 环境要求
- php7.1+ (需以下扩展)
    - [x] mbstring
    - [x] json
    - [x] openssl
    - [x] xml
    - [x] pdo
- nginx
- mysql
- redis

## 如何安装
> 安装之前请确保已安装 Composer

### 下载项目
- 通过 Git 下载(推荐)
  
```shell

git clone https://gitee.com/salgee/ittim-erp && cd ittim-erp

curl -sS https://install.phpcomposer.com/installer | php

composer config -g repo.packagist composer https://mirrors.aliyun.com/composer/
```

### 安装
下载完成之后通过命令来进行安装依赖
```shell
composer install --ignore-platform-reqs 
```
初始化数据库
```shell
php think catch:install
```

### 预览地址
[预览地址](https://erp.admin.ittim.ltd)


## Talking
- 加入 QQ 群 `192394918` 前请先 star 项目支持一下

## Thanks
- [jaguarjack/catchAdmin](https://gitee.com/jaguarjack/catchAdmin)
  
## 系列文章
如果是刚开始使用 thinkphp6, 以下文章可能会对你有些许帮助，文章基于 RC3 版本。整体架构是不变的。
- [Tp6 启动分析](https://www.kancloud.cn/akasishikelu/thinkphp6/1129385)
- [Tp6 Request 解析](https://www.kancloud.cn/akasishikelu/thinkphp6/1134496)
- [TP6 应用初始化](https://www.kancloud.cn/akasishikelu/thinkphp6/1130427)
- [Tp6 中间件分析](https://www.kancloud.cn/akasishikelu/thinkphp6/1136616)
- [Tp6 请求流程](https://www.kancloud.cn/akasishikelu/thinkphp6/1136608)

## 命名规范

遵循PSR-2命名规范和PSR-4自动加载规范，并且注意如下规范：

### 目录和文件

*   目录不强制规范，支持小写驼峰，建议使用单字母表达，不可出现`_`；
*   类库、函数文件统一以`.php`为后缀；
*   类的文件名均以命名空间定义，并且命名空间的路径和类库文件所在路径一致；
*   类名和类文件名保持一致，统一采用大驼峰法命名（首字母大写）；

### 函数和类、属性命名

*   类的命名采用大驼峰法，例如 `User`、`UserType`，默认不需要添加后缀，例如`UserController`应该直接命名为`User`；
*   函数的命名使用小驼峰法，例如 `getClientIp`；
*   方法的命名使用小驼峰法，例如 `getUserName`；
*   属性的命名使用小驼峰法，例如 `tableName`、`instance`；
*   以双下划线“__”打头的函数或方法作为魔法方法，例如 `__call` 和 `__autoload`；

### 常量和配置

*   常量以大写字母和下划线命名，例如 `APP_PATH`和 `THINK_PATH`；
*   配置参数以小写字母和下划线命名，例如 `url_route_on` 和`url_convert`；

### 数据表和字段

*   数据表和字段采用小写加下划线方式命名，并注意字段名不要以下划线开头，例如 `think_user` 表和 `user_name`字段，不建议使用驼峰和中文作为数据表字段命名。
