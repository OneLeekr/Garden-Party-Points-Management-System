# 游园会积分登记与核销系统 (YYFSS)

> YuYuan Fair Score System — 面向学校 / 社团 / 企业游园会的积分登记、核销与二维码身份认证系统。

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Vue 3](https://img.shields.io/badge/Vue-3-42b883)](https://vuejs.org/)
[![UniApp](https://img.shields.io/badge/UniApp-H5-2b9939)](https://uniapp.dcloud.net.cn/)
[![PHP](https://img.shields.io/badge/PHP-8+-777bb4)](https://www.php.net/)

---

## 功能概览

| 角色 | 主要能力 |
|------|----------|
| **超级管理员** | 用户 / 分组 / 活动项目管理、积分调整、数据统计、系统设置、批量导入用户 |
| **普通管理员** | 扫码登记积分、扫码核销积分、查看项目今日记录 |
| **玩家** | 查看积分与流水、展示个人二维码、个人中心 |

### 亮点特性

- 扫码登记 / 核销，支持连续扫码工作流
- 活动项目可配置：默认积分、是否锁定、是否允许重复游玩、核销原因预设
- 玩家二维码动态生成，可配置有效期
- 单设备登录、登录 IP / 设备记录
- H5 + PWA，可添加到手机主屏幕
- 前后端同域部署，适配 ByetHost 等免费虚拟主机

---

## 技术栈

| 层级 | 技术 |
|------|------|
| 前端 | UniApp、Vue 3、Pinia、Vite |
| 后端 | PHP 8+、RESTful API、JWT |
| 数据库 | MySQL 5.7+ |
| 工具 | HBuilderX（发行 H5）、html5-qrcode（扫码） |

---

## 项目结构

```
yyh/
├── backend/          # PHP 后端源码
├── deploy/           # 可直接上传虚拟主机的后端打包目录
├── pages/            # 前端页面（login / super / admin / player）
├── components/       # 公共组件
├── store/            # Pinia 状态
├── composables/      # 组合式逻辑（自动刷新、提交锁等）
├── config/           # 前端 API 配置
├── static/           # 静态资源、PWA manifest
├── docs/             # 详细技术文档
└── github/           # GitHub 发布相关说明（本目录）
```

更完整的目录说明见 [STRUCTURE.md](./STRUCTURE.md)。

---

## 快速开始

### 环境要求

- **前端开发**：Node.js 18+、HBuilderX 或 `@dcloudio/vite-plugin-uni`
- **后端运行**：PHP 8.0+（PDO、JSON、OpenSSL、ZipArchive 推荐）、MySQL
- **生产部署**：Apache（mod_rewrite）或 Nginx，支持 PHP

### 1. 克隆仓库

```bash
git clone https://github.com/<你的用户名>/yyh.git
cd yyh
npm install
```

### 2. 本地运行前端（H5）

使用 HBuilderX：**运行 → 运行到浏览器 → Chrome**

或使用 CLI（若已配置 UniApp 工程）：

```bash
npm run dev:h5
```

本地开发时 API 通过 `manifest.json` 中的 `h5.devServer.proxy` 代理到线上或本地 PHP。

### 3. 部署后端

1. 将 `deploy/` 目录**内的全部文件**上传到虚拟主机 `htdocs` 根目录  
2. 复制 `config/database.example.php` 为 `config/database.php` 并填写数据库信息  
3. 浏览器访问 `https://你的域名/install.php` 完成初始化  
4. **删除** `install.php`，修改 `config/app.php` 中的 `jwt_secret`、`qr_secret`  

详细步骤见 [docs/DEPLOY.md](../docs/DEPLOY.md) 与 [RELEASE.md](./RELEASE.md)。

### 4. 部署前端（H5）

1. HBuilderX：**发行 → 网站-H5手机版**  
2. 将 `unpackage/dist/build/web/` 内文件上传到与后端**同一域名**根目录（覆盖 `index.html`、`assets/`、`static/`）  
3. 确保根目录存在 `deploy/.htaccess`（API 路由 + SPA 回退）

---

## 默认账户（安装后）

| 用户名 | 密码 | 角色 |
|--------|------|------|
| `admin` | `Admin@123456` | 超级管理员 |

**首次登录后请立即修改密码，并在生产环境更换所有默认密钥。**

---

## 文档索引

| 文档 | 说明 |
|------|------|
| [RELEASE.md](./RELEASE.md) | GitHub 发布与版本管理流程 |
| [PUBLISH_CHECKLIST.md](./PUBLISH_CHECKLIST.md) | 上线 / 发版检查清单 |
| [STRUCTURE.md](./STRUCTURE.md) | 仓库目录说明 |
| [.gitignore 模板](./gitignore.template) | 提交 GitHub 前复制到项目根目录 |
| [docs/DEPLOY.md](docs/DEPLOY.md) | 完整部署说明 |
| [docs/API.md](docs/API.md) | API 接口文档 |
| [docs/H5_DEPLOY.md](docs/H5_DEPLOY.md) | H5 前端部署补充说明 |

---

## 安全提示

- 勿将 `config/database.php`、真实 JWT 密钥提交到公开仓库  
- 生产环境务必删除 `install.php`、`check.php` 等安装/诊断脚本  
- ByetHost 等免费主机有防机器人验证，**前端必须与 API 同域部署**

---

## 开源协议

本项目采用 [MIT License](./LICENSE)（见本目录 `LICENSE` 文件）。使用、修改、分发时请保留版权声明。

---

## 致谢

基于 UniApp + Vue 3 生态构建，适用于中小型线下活动积分管理场景。
