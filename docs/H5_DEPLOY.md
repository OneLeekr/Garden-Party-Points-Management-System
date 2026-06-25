# H5 前端部署到虚拟主机

## 为什么 localhost 登录失败？

ByetHost 免费主机有 **JavaScript 防机器人验证**。
从 `localhost:5173` 跨域请求 API 会返回 HTML 验证页，不是 JSON，所以显示「请求失败」。

**解决办法：把 H5 前端也部署到同一域名。**

---

## 部署步骤

### 1. HBuilderX 发行 H5

1. 菜单 **发行 → 网站-H5手机版**
2. 等待编译完成
3. 输出目录：`unpackage/dist/build/h5/`

### 2. 上传到虚拟主机

将 `h5` 文件夹内 **所有文件** 上传到：

```
htdocs/app/
```

上传后结构：

```
htdocs/
├── index.php          (API，已有)
├── install.php
├── api/ ...           (路由)
├── app/               (前端 H5)
│   ├── index.html
│   ├── assets/
│   └── .htaccess      (从 deploy/app/.htaccess 上传)
```

### 3. 访问地址（正式后台入口）

```
http://your-domain.example.com/app/
```

或登录页：

```
http://your-domain.example.com/app/#/pages/login/login
```

首次打开会经过主机验证，之后即可正常登录。

### 4. 登录账号

- 用户名：`admin`
- 密码：`Admin@123456`

---

## 本地开发说明

本地 `localhost:5173` 无法稳定连接 ByetHost 免费 API。
请使用线上地址 `http://your-domain.example.com/app/` 进行测试。
