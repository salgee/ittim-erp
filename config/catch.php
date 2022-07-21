<?php

return [
    /**
     * set domain if you need
     *
     */
    'domain' => '',

    /**
     * 权限配置
     *
     */
    'permissions' => [
        /**
         * get 请求不验证
         */
        'is_allow_get' => true,

        /**
         * 超级管理员 ID
         *
         */
        'super_admin_id' => 1,

        /**
         * 方法认证标记
         *
         * 尽量使用唯以字符
         *
         */
        'method_auth_mark' => '@CatchAuth',
        // 采购员
        'buyer_staff_role' => 3,
        // 客户（公司）
        'company_role' => 4,
        // 运营岗位
        'operation_job' => 1,
    ],
    /**
     *  auth 认证
     *
     */
    'auth' => [
        // 默认
        'default' => [
            'guard' => 'admin',
        ],
        // 门面设置
        'guards' => [
            // admin 认证
            'admin' => [
                'driver' => 'jwt',
                'provider' => 'admin_users',
            ],
            // 开发者认证
            'developer' => [
                'driver' => 'jwt',
                'provider' => 'developer',
            ],
        ],
        // 服务提供
        'providers' => [
            // 后台用户认证服务
            'admin_users' => [
                'driver' => 'orm',
                'model' =>  \catchAdmin\permissions\model\Users::class,
            ],
            // 开发这认证服务
            'developer' => [
                'driver' => 'orm',
                'model' => \catchAdmin\system\model\Developers::class
            ]
        ],
    ],

    /**
     * 自定义验证规则
     *
     */
    'validates' => [
        \catcher\validates\Sometimes::class,
        \catcher\validates\SensitiveWord::class,
    ],
    /**
     * 上传设置
     *
     */
    'upload' => [
        'image' => 'fileSize:' . 1024 * 1024 * 5 . '|fileExt:jpg,png,gif,jpeg',
        'file' => 'fileSize:' . 1024 * 1024 * 20 . '|fileExt:txt,pdf,xlsx,xls,html,mp4,mp3,amr,zip'
    ],
    //账号密码错误次数锁定
    'login_number_config' => [
        'cms' => [
            'switch' => true,  //开关
            'number' => 3,       //次数
            'time' => 300        //锁定时间
        ],
        'admin' => [
            'switch' => true,  //开关
            'number' => 30,       //次数
            'time' => 300        //锁定时间
        ]
    ],
    // 系统费用配置
    'system_service_fee' => [
        'system_label_price' => 10, //
        'system_pallet_price' => 10,
        'system_outbound_price' => 10
    ],
    // UPS账号配置
    'ups' => [
        'account' => '',
        'access_key' => '',
        'user_id' => '',
        'password' => ''
    ],

    'ups_wayfair' => [
        'account' => '',
        'access_key' => '',
        'user_id' => '',
        'password' => '@',
        'pay_account' => '',
    ],

    'ups_overstockr' => [
        'account' => '',
        'access_key' => '',
        'user_id' => '',
        'password' => '',
        'pay_account' => '',
    ],
    // USPS账号配置
    'usps' => [
    ]
];
