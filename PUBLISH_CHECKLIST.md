# 发布 / 上线检查清单

在推送到 GitHub 或更新生产环境前，请逐项确认。

---

## GitHub 仓库

- [ ] 根目录已添加 `.gitignore`（可从 `github/gitignore.template` 复制）
- [ ] 未提交真实数据库密码、`jwt_secret`、`qr_secret`
- [ ] 未提交 `node_modules/`、`unpackage/dist/`
- [ ] `github/README.md` 中仓库地址、用户名已按需修改
- [ ] 已添加 `github/LICENSE`（或根目录 LICENSE）
- [ ] 首次推送：`git push -u origin main`
- [ ] 发版：打 Tag + 创建 GitHub Release

---

## 后端（虚拟主机）

- [ ] 上传 `deploy/` 内全部文件到 `htdocs`
- [ ] `config/database.php` 已在服务器配置且**不在 Git 中**
- [ ] `config/app.php` 中 `jwt_secret`、`qr_secret` 已改为随机强密钥
- [ ] 已运行 `install.php` 初始化数据库
- [ ] 已**删除** `install.php`
- [ ] 根目录 `.htaccess` 已上传（API 路由 + Authorization 头）
- [ ] 访问 `/api/health` 返回 JSON `{ "code": 200, ... }`
- [ ] 时区：`config/app.php` 中 `timezone` 为 `Asia/Shanghai`

---

## 前端（H5）

- [ ] HBuilderX：**发行 → 网站-H5手机版**
- [ ] 上传 `unpackage/dist/build/web/` 到与 API **同一域名**根目录
- [ ] 覆盖 `index.html`、`assets/`、`static/`
- [ ] 浏览器打开 `/#/pages/login/login` 可正常登录
- [ ] PWA：删除旧桌面图标后重新「添加到主屏幕」

---

## 功能冒烟测试

- [ ] 超级管理员：用户管理、项目管理、积分管理、数据统计
- [ ] 普通管理员：扫码登记、扫码核销、项目记录
- [ ] 玩家：积分、二维码、个人中心
- [ ] 修改项目配置后，管理员扫码页约 8 秒内自动同步
- [ ] 用户 CSV 导入 / 模板下载中文正常
- [ ] 默认密码已修改或已强制用户改密（按需）

---

## 安全收尾

- [ ] 删除 `check.php`、`yyfss-migrate.php` 等临时脚本（若用过）
- [ ] 关闭目录列表、限制 `storage/` 访问（`.htaccess` 已配置）
- [ ] 生产环境 `config/app.php` 中 `debug` 为 `false`
