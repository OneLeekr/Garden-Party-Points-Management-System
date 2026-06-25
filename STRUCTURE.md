# 仓库目录结构说明

便于 GitHub 访客与协作者快速了解各目录用途。

```
yyh/
├── github/                 # GitHub 发布专用：README、发版流程、LICENSE、gitignore 模板
├── backend/                # PHP 后端源码（开发维护用）
│   ├── config/             # app.php、database.php（勿提交真实 database.php）
│   ├── controllers/        # API 控制器
│   ├── core/               # 数据库、JWT、QR、会话、系统设置等
│   ├── middleware/         # 认证、CSRF
│   ├── services/           # 积分等业务服务
│   ├── public/             # Web 入口 index.php、install.php
│   └── sql/install.sql     # 建表脚本
│
├── deploy/                 # 与 backend 同步的部署包，上传虚拟主机用
│   ├── .htaccess           # Apache：/api → index.php，其余 → index.html
│   └── 前端部署说明.txt
│
├── pages/                  # UniApp 页面
│   ├── login/              # 登录
│   ├── super/              # 超级管理员（仪表盘、用户、项目、统计…）
│   ├── admin/              # 普通管理员（扫码登记/核销、记录）
│   ├── player/             # 玩家端
│   └── common/             # 改密码等
│
├── components/             # 可复用 Vue 组件
├── composables/            # useAutoRefresh、useProjects、useSubmitLock…
├── store/                  # Pinia：user、settings、projects…
├── utils/                  # request、auth、qrScan、projectScore…
├── config/index.js         # API baseURL（同域自动识别）
├── static/                 # 图标、PWA manifest、sw.js、CSV 模板
├── styles/theme.scss       # 全局设计变量与按钮样式
├── docs/                   # DEPLOY、API、H5 部署等详细文档
├── manifest.json           # UniApp 工程配置
├── pages.json              # 页面路由与 tabBar
└── unpackage/              # 编译输出（.gitignore 忽略，勿提交）
```

## 前后端协作关系

```
浏览器 (H5 / PWA)
    │
    ├─ /index.html、/assets/*     → 前端 SPA
    └─ /api/*                     → deploy/index.php → controllers
                                          │
                                          └─ MySQL
```

前端 `config/index.js` 在线上自动使用 `window.location.origin`，因此 API 与页面必须同域。

## 关键配置文件

| 文件 | 作用 |
|------|------|
| `backend/config/app.php` | JWT/QR 密钥、时区、二维码过期时间 |
| `backend/config/database.php` | 数据库连接（仅服务器本地） |
| `config/index.js` | 前端 API 根地址 |
| `manifest.json` → `h5.devServer.proxy` | 本地开发 API 代理 |
