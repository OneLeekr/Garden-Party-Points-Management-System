# 游园会积分登记与核销系统 - 部署说明

## 项目结构

```
yyh/
├── backend/                 # PHP 后端 (部署至虚拟主机)
│   ├── bootstrap.php
│   ├── config/
│   │   ├── app.php          # 应用配置 (JWT/QR密钥)
│   │   └── database.php     # 数据库配置
│   ├── controllers/         # API 控制器
│   ├── core/                # 核心类 (JWT/DB/Security/QR)
│   ├── middleware/          # 认证与CSRF中间件
│   ├── public/              # Web 根目录
│   │   ├── index.php        # API 入口
│   │   ├── install.php      # 安装脚本 (安装后删除)
│   │   └── .htaccess
│   ├── services/            # 业务服务
│   ├── sql/install.sql      # 建表SQL
│   └── utils/               # Excel导入工具
├── components/              # 公共组件
├── config/index.js          # 前端API地址
├── pages/                   # 页面
│   ├── login/               # 登录
│   ├── super/               # 超级管理员
│   ├── admin/               # 普通管理员
│   ├── player/              # 玩家
│   └── common/              # 公共页面
├── store/                   # Pinia 状态
├── styles/theme.scss        # 设计系统
└── utils/                   # 请求与认证工具
```

---

## 一、后端部署 (ByetHost 虚拟主机)

### 1. 上传文件

**推荐方式：** 使用项目内已打包好的 `deploy/` 目录（或 `yyfss-backend-deploy.zip`）。

将 `deploy/` 目录 **里面的所有内容** 上传至虚拟主机 Web 根目录 `htdocs`（不是上传 deploy 文件夹本身）。

详细图文说明见：`deploy/上传说明.txt`

确保目录结构如下：

```
public_html/
├── bootstrap.php
├── config/
├── controllers/
├── core/
├── middleware/
├── services/
├── sql/
├── utils/
├── index.php      (来自 public/index.php)
├── install.php    (来自 public/install.php)
└── .htaccess      (来自 public/.htaccess)
```

### 2. 配置数据库

编辑 `config/database.php`，确认以下信息：

| 配置项 | 值 |
|--------|-----|
| Host | your-mysql-host.example.com |
| Database | your_database_user_newyyh |
| Username | your_database_user |
| Password | your_database_password |

### 3. 运行安装

浏览器访问：

```
http://your-domain.example.com/install.php
```

安装完成后：

- 自动创建所有数据表
- 初始化角色与默认分组
- 创建超级管理员账户

**默认账户：**

| 用户名 | 密码 |
|--------|------|
| admin | Admin@123456 |

首次登录强制修改密码。

**重要：安装成功后立即删除 `install.php`**

### 4. 修改安全密钥

编辑 `config/app.php`，修改以下配置：

```php
'jwt_secret' => '替换为随机长字符串',
'qr_secret' => '替换为随机长字符串',
```

### 5. 验证 API

访问健康检查接口：

```
http://your-domain.example.com/api/health
```

应返回：

```json
{"code":200,"message":"success","data":{"status":"ok","time":"..."}}
```

---

## 二、前端部署 (HBuilderX)

### 1. 安装依赖

```bash
cd yyh
npm install
```

### 2. 配置 API 地址

编辑 `config/index.js`：

```javascript
export default {
  baseURL: 'http://your-domain.example.com',
  timeout: 30000
}
```

### 3. HBuilderX 运行

1. 使用 HBuilderX 打开 `yyh` 项目
2. 运行 -> 运行到浏览器 -> Chrome (H5)
3. 或运行到微信开发者工具 (需配置小程序 appid)

### 4. H5 发布

HBuilderX -> 发行 -> 网站-H5手机版

### 5. APP 打包

HBuilderX -> 发行 -> 原生 App-云打包

需在 manifest.json 中已配置相机权限 (扫码功能)。

---

## 三、Excel 批量导入

### 导入格式

支持 CSV 和 XLSX 格式，第一行为表头：

| 姓名 | 学号 | 手机号 | 班级 | 分组 | 初始积分 |
|------|------|--------|------|------|----------|
| 张三 | 2024001 | 13800000001 | 一班 | 一年级 | 0 |

### 导入方式

超级管理员 -> 用户管理 -> 导入

---

## 四、常见问题

### API 404

- 确认 `.htaccess` 已上传
- 确认虚拟主机支持 mod_rewrite

### 跨域问题

后端已配置 CORS，如需限制来源，修改 `config/app.php` 中的 `cors_origins`。

### 数据库连接失败

- 检查 ByetHost 数据库远程访问权限
- 确认数据库名与用户名前缀一致

### 扫码不可用

- H5 需 HTTPS 环境 (微信内除外)
- APP 需授予相机权限

---

## 五、安全清单

- [ ] 删除 install.php
- [ ] 修改 jwt_secret 和 qr_secret
- [ ] 修改默认管理员密码
- [ ] 生产环境关闭 debug 模式
- [ ] 定期备份数据库
