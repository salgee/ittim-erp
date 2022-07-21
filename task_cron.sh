#!/bin/bash
#先载入环境变量
source /etc/profile

cd /data/wwwroot/erp/erp/
/usr/local/php/bin/php think catch:schedule >> runtime/erp/2021.log 2>&1

