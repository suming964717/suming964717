-- ============================================
-- MeasureMaster 测量大师 数据库脚本
-- ============================================

CREATE DATABASE IF NOT EXISTS `measure_master` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `measure_master`;

-- 用户表
CREATE TABLE `users` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mobile` varchar(11) NOT NULL COMMENT '手机号',
  `nickname` varchar(50) DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像URL',
  `member_type` tinyint(1) DEFAULT 0 COMMENT '0=非会员 1=月 2=季 3=年 4=永久',
  `member_expire` datetime DEFAULT NULL COMMENT '会员到期时间',
  `today_usage` int(11) DEFAULT 0 COMMENT '今日使用次数',
  `usage_date` date DEFAULT NULL COMMENT '使用计数日期',
  `total_usage` int(11) DEFAULT 0 COMMENT '累计使用次数',
  `status` tinyint(1) DEFAULT 1 COMMENT '1=正常 0=禁用',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='用户表';

-- 短信验证码表
CREATE TABLE `sms_codes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `mobile` varchar(11) NOT NULL COMMENT '手机号',
  `code` varchar(6) NOT NULL COMMENT '验证码',
  `type` varchar(20) DEFAULT 'login' COMMENT '用途: login',
  `used` tinyint(1) DEFAULT 0 COMMENT '0=未用 1=已用',
  `expired_at` datetime NOT NULL COMMENT '过期时间',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mobile` (`mobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='短信验证码';

-- 会员套餐配置表
CREATE TABLE `member_plans` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint(1) NOT NULL COMMENT '1=月 2=季 3=年 4=永久',
  `name` varchar(50) NOT NULL COMMENT '套餐名称',
  `price` decimal(10,2) NOT NULL COMMENT '售价',
  `original_price` decimal(10,2) DEFAULT NULL COMMENT '划线原价',
  `days` int(11) NOT NULL COMMENT '有效天数(永久=36500)',
  `description` varchar(255) DEFAULT '' COMMENT '套餐描述',
  `features` text DEFAULT NULL COMMENT '功能列表JSON',
  `is_recommend` tinyint(1) DEFAULT 0 COMMENT '是否推荐标签',
  `sort` int(11) DEFAULT 0 COMMENT '排序(小的在前)',
  `status` tinyint(1) DEFAULT 1 COMMENT '1=上架 0=下架',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='会员套餐';

-- 订单表
CREATE TABLE `orders` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `order_no` varchar(32) NOT NULL COMMENT '订单号',
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `plan_id` int(11) unsigned NOT NULL COMMENT '套餐ID',
  `plan_name` varchar(50) DEFAULT '' COMMENT '套餐名称快照',
  `amount` decimal(10,2) NOT NULL COMMENT '支付金额',
  `pay_type` tinyint(1) DEFAULT 1 COMMENT '1=微信 2=支付宝',
  `pay_status` tinyint(1) DEFAULT 0 COMMENT '0=待支付 1=已支付 2=已退款',
  `trade_no` varchar(64) DEFAULT '' COMMENT '第三方交易号',
  `prepay_id` varchar(128) DEFAULT '' COMMENT '微信预支付ID',
  `paid_at` datetime DEFAULT NULL COMMENT '支付时间',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `order_no` (`order_no`),
  KEY `idx_user` (`user_id`),
  KEY `idx_status` (`pay_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='支付订单';

-- 测量历史记录表
CREATE TABLE `measure_history` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) unsigned NOT NULL COMMENT '用户ID',
  `type` varchar(20) NOT NULL COMMENT 'ruler/level/decibel',
  `value` varchar(50) NOT NULL COMMENT '主要测量值',
  `unit` varchar(10) DEFAULT '' COMMENT '单位',
  `extra` json DEFAULT NULL COMMENT '附加数据(JSON)',
  `note` varchar(255) DEFAULT '' COMMENT '备注',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_type` (`user_id`, `type`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='测量历史';

-- 系统配置表
CREATE TABLE `configs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(50) NOT NULL COMMENT '配置键',
  `value` text COMMENT '配置值',
  `group` varchar(30) DEFAULT 'basic' COMMENT '分组: basic/sms/payment',
  `description` varchar(255) DEFAULT '' COMMENT '说明',
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='系统配置';

-- 操作日志表
CREATE TABLE `admin_logs` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `admin` varchar(50) DEFAULT '' COMMENT '管理员',
  `action` varchar(100) DEFAULT '' COMMENT '操作',
  `content` text COMMENT '详情',
  `ip` varchar(50) DEFAULT '' COMMENT 'IP',
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='操作日志';

-- ============================================
-- 初始数据
-- ============================================

-- 会员套餐初始配置
INSERT INTO `member_plans` (`type`, `name`, `price`, `original_price`, `days`, `description`, `features`, `is_recommend`, `sort`) VALUES
(1, '月度会员', 12.00, 18.00, 30,  '畅享30天完整功能', '["无限次测量","测量历史记录","去除使用限制","数据导出"]', 0, 1),
(2, '季度会员', 30.00, 54.00, 90,  '超值三个月，立省¥24', '["无限次测量","测量历史记录","去除使用限制","数据导出","噪音预警"]', 1, 2),
(3, '年度会员', 88.00, 216.00, 365, '全年畅用，立省¥128',  '["无限次测量","测量历史记录","去除使用限制","数据导出","噪音预警","截图保存"]', 0, 3),
(4, '永久会员', 198.00, 999.00, 36500, '一次购买永久使用', '["无限次测量","测量历史记录","去除使用限制","数据导出","噪音预警","截图保存","优先客服"]', 0, 4);

-- 系统配置初始值
INSERT INTO `configs` (`key`, `value`, `group`, `description`) VALUES
-- 基础配置
('site_name',         'MeasureMaster', 'basic', '站点名称'),
('free_daily_limit',  '10',            'basic', '免费用户每日使用次数'),
-- 短信配置(阿里云)
('sms_provider',      'aliyun',        'sms', '短信服务商: aliyun/tencent'),
('aliyun_access_key', '',              'sms', '阿里云AccessKeyId'),
('aliyun_access_secret', '',           'sms', '阿里云AccessKeySecret'),
('aliyun_sms_sign',   '',              'sms', '短信签名(需在阿里云申请)'),
('aliyun_sms_template_login', '',      'sms', '登录验证码模板ID(如:SMS_123456)'),
-- 微信支付
('wechat_appid',      '',              'payment', '微信公众号/小程序AppID'),
('wechat_mch_id',     '',              'payment', '微信商户号'),
('wechat_api_key',    '',              'payment', '微信支付API密钥(v2)'),
('wechat_notify_url', '',              'payment', '微信支付回调地址(公网可访问)'),
-- 支付宝
('alipay_app_id',     '',              'payment', '支付宝AppID'),
('alipay_private_key','',              'payment', '支付宝应用私钥'),
('alipay_public_key', '',              'payment', '支付宝公钥'),
('alipay_notify_url', '',              'payment', '支付宝回调地址(公网可访问)'),
-- 后台管理
('admin_username',    'admin',         'basic', '后台管理员账号'),
('admin_password',    '21232f297a57a5a743894a0e4a801fc3', 'basic', '后台密码MD5(默认:admin)');
