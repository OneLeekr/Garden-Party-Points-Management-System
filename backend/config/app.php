<?php
/**
 * YYFSS Application Configuration
 */

return [
    'app_name' => 'YuYuan Fair Score System',
    'app_name_cn' => '游园会积分登记与核销系统',
    'debug' => false,
    'timezone' => 'Asia/Shanghai',
    'jwt_secret' => 'CHANGE_ME_RANDOM_JWT_SECRET_MIN_32_CHARS',
    'jwt_expire' => 86400 * 7, // 7 days
    'jwt_refresh_expire' => 86400 * 30,
    'qr_secret' => 'CHANGE_ME_RANDOM_QR_SECRET_MIN_32_CHARS',
    'qr_expire_minutes' => 5,
    'csrf_expire' => 3600,
    'cors_origins' => ['*'],
    'upload_max_size' => 5 * 1024 * 1024,
    'allowed_upload_types' => ['csv', 'xlsx', 'xls'],
];
