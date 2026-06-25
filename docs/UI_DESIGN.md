# YYFSS 前端 UI 设计方案

## 设计原则

- 现代化、简洁、专业
- 禁止使用 Emoji，使用字母/图形符号代替
- 深浅色模式自动适配 (CSS `prefers-color-scheme`)
- 全站统一设计语言
- 移动端优先，响应式布局

---

## 色彩系统

### 浅色模式

| 变量 | 色值 | 用途 |
|------|------|------|
| --primary | #2563eb | 主色、按钮、链接 |
| --primary-dark | #1d4ed8 | 渐变终点 |
| --success | #059669 | 增加积分、成功 |
| --warning | #d97706 | 警告、签到 |
| --danger | #dc2626 | 扣除积分、删除 |
| --bg-page | #f0f4f8 | 页面背景 |
| --bg-card | #ffffff | 卡片背景 |
| --text-primary | #1e293b | 主文字 |
| --text-secondary | #64748b | 次要文字 |

### 深色模式

自动切换，背景 #0f172a，卡片 #1e293b，文字 #f1f5f9。

---

## 字体

```
-apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Microsoft YaHei', sans-serif
```

| 级别 | 大小 |
|------|------|
| 大标题 | 36-40rpx, font-weight: 600 |
| 标题 | 32-34rpx, font-weight: 600 |
| 正文 | 28-30rpx |
| 辅助 | 24-26rpx |
| 标签 | 22rpx |

---

## 组件规范

### 卡片 (.yy-card)

- 圆角: 24rpx
- 内边距: 32rpx
- 阴影: 0 4px 24px rgba(15,23,42,0.08)
- 边框: 1px solid var(--border)

### 按钮 (.yy-btn)

- 高度: 88rpx
- 圆角: 16rpx
- 主按钮: 蓝色渐变
- 轮廓按钮: 透明底 + 蓝色边框
- 危险按钮: 红色实心

### 输入框 (.yy-input)

- 高度: 88rpx
- 圆角: 16rpx
- 背景: var(--bg-page)

### 标签 (.yy-tag)

- 圆角: 8rpx
- 半透明背景 + 对应色文字

---

## 页面布局

### 登录页

- 全屏渐变背景 (#1e3a8a -> #2563eb -> #7c3aed)
- 居中毛玻璃卡片 (backdrop-filter: blur)
- Logo 圆形渐变 + 系统名称

### 超级管理员

- 左侧可折叠菜单 (移动端抽屉)
- 右侧内容区 + 顶栏
- 仪表盘: 统计卡片网格 + 排行榜列表

### 普通管理员

- 移动端卡片式导航
- 扫码页: 大按钮 + 玩家信息卡片 + 表单

### 玩家

- 底部 Tab 栏 (首页/积分/二维码/我的)
- 首页: 积分大数字展示
- 二维码: 居中 QR + 刷新/全屏/保存

---

## 交互规范

- 按钮点击: opacity 0.85
- 加载状态: uni loading / button loading
- 操作反馈: uni.showToast
- 危险操作: uni.showModal 确认

---

## 角色导航

| 角色 | 登录后跳转 |
|------|-----------|
| super_admin | /pages/super/dashboard |
| admin | /pages/admin/index |
| player | /pages/player/home (Tab) |

---

## 扩展建议

- 可在 `styles/theme.scss` 中调整主色
- 组件位于 `components/` 目录，可按需扩展
- Tab 图标位于 `static/tab/`，可替换为设计稿图标
