# GitHub 发布流程

本文说明如何将 YYFSS 项目发布到 GitHub，以及如何打版本 Tag、创建 Release。

---

## 一、首次发布到 GitHub

### 1. 准备仓库

```bash
cd /path/to/yyh

# 复制忽略规则到项目根目录（若尚未有 .gitignore）
cp github/gitignore.template .gitignore

# 初始化 Git（若尚未初始化）
git init
git branch -M main
```

### 2. 检查敏感信息

发布前**不要**提交以下内容：

| 文件 / 目录 | 原因 |
|-------------|------|
| `config/database.php`（含真实密码） | 数据库凭证 |
| `backend/config/database.php` | 同上 |
| `deploy/config/database.php` | 同上 |
| `unpackage/` | 编译产物，体积大 |
| `node_modules/` | 依赖目录 |
| 真实域名下的测试账号密码文档 | 安全 |

可使用 `git status` 与 `git diff` 确认。

### 3. 首次提交

```bash
git add .
git commit -m "Initial commit: YYFSS 游园会积分系统"
```

### 4. 关联远程并推送

在 GitHub 网页端新建空仓库（**不要**勾选「Add README」若本地已有）。

```bash
git remote add origin https://github.com/<用户名>/<仓库名>.git
git push -u origin main
```

---

## 二、日常更新发布

### 1. 提交变更

```bash
git add .
git commit -m "fix: 描述本次修改内容"
git push origin main
```

建议 commit 前缀：`feat:` / `fix:` / `docs:` / `refactor:`

### 2. 打版本 Tag

语义化版本示例：`v1.0.0`、`v1.1.0`、`v1.0.1`

```bash
# 创建附注标签
git tag -a v1.0.0 -m "v1.0.0 首次公开版本"
git push origin v1.0.0
```

### 3. 在 GitHub 创建 Release

1. 打开仓库 → **Releases** → **Draft a new release**
2. **Choose a tag**：选择刚推送的 `v1.0.0`
3. **Release title**：如 `v1.0.0 - 游园会积分系统首个稳定版`
4. **Describe this release**：填写更新说明（见下方模板）
5. 可选：上传 `deploy/` 打包 zip 作为附件，方便用户直接部署后端
6. 点击 **Publish release**

---

## Release 说明模板

```markdown
## 新增
- 功能 A
- 功能 B

## 修复
- 修复 XXX 问题

## 部署说明
1. 后端：上传 `deploy/` 目录内容到 htdocs，覆盖除 `config/database.php` 外的文件
2. 前端：HBuilderX 发行 H5，上传 `unpackage/dist/build/web/` 到同域根目录
3. 若数据库结构有变，访问一次 `yyfss-migrate.php` 后删除该文件

## 默认账户
- admin / Admin@123456（请登录后立即修改）
```

---

## 三、推荐分支策略（可选）

| 分支 | 用途 |
|------|------|
| `main` | 稳定可发布版本 |
| `dev` | 日常开发，合并到 main 前测试 |

```bash
git checkout -b dev
# 开发完成后
git checkout main
git merge dev
git push origin main
```

小型项目可只在 `main` 上开发。

---

## 四、GitHub Pages（可选）

本项目 H5 为 SPA，若需 GitHub Pages 演示：

- Pages **无法**运行 PHP 后端，仅适合静态预览或配合外部 API
- 生产环境仍推荐虚拟主机同域部署

若仅展示前端 UI，可将 `unpackage/dist/build/web/` 内容推送到 `gh-pages` 分支，并配置 Pages 源为该分支。

---

## 五、常见问题

**Q: push 被拒绝，含大文件？**  
A: 确认 `.gitignore` 已忽略 `node_modules/`、`unpackage/`，并用 `git rm -r --cached unpackage` 从历史中移除（若误提交）。

**Q: 公开仓库如何保护数据库配置？**  
A: 仅提交 `database.example.php`，部署时在服务器上复制为 `database.php`。

**Q: deploy 与 backend 都要提交吗？**  
A: 建议都提交。`deploy/` 面向部署，`backend/` 为源码；发 Release 时可额外打包 zip 附件。
