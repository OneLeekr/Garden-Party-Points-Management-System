# YYFSS API 接口文档

Base URL: `http://your-domain.example.com`

## 通用说明

### 请求格式

- Content-Type: `application/json`
- 认证: `Authorization: Bearer {token}`
- CSRF (写操作): 请求体或 Header 携带 `csrf_token` / `X-CSRF-Token`

### 响应格式

```json
{
  "code": 200,
  "message": "success",
  "data": {}
}
```

### 错误码

| code | 说明 |
|------|------|
| 200 | 成功 |
| 201 | 创建成功 |
| 400 | 请求错误 |
| 401 | 未登录 |
| 403 | 权限不足 |
| 404 | 接口不存在 |
| 500 | 服务器错误 |

---

## 认证模块

### POST /api/auth/login

登录

**请求体：**

```json
{
  "username": "admin",
  "password": "Admin@123456",
  "remember": true
}
```

**响应 data：**

```json
{
  "token": "jwt...",
  "csrf_token": "...",
  "expires_in": 604800,
  "user": {
    "id": 1,
    "username": "admin",
    "role_slug": "super_admin",
    "must_change_password": 1
  }
}
```

### GET /api/auth/me

获取当前用户信息 (需登录)

### POST /api/auth/change-password

修改密码

```json
{
  "old_password": "",
  "new_password": "",
  "csrf_token": ""
}
```

### POST /api/auth/reset-password

重置用户密码 (超级管理员)

```json
{
  "user_id": 2,
  "new_password": "Admin@123456",
  "csrf_token": ""
}
```

### GET /api/auth/csrf-token

刷新 CSRF Token

### POST /api/auth/logout

退出登录

---

## 仪表盘

### GET /api/dashboard/stats

超级管理员统计数据

**响应 data：**

```json
{
  "total_players": 100,
  "total_admins": 5,
  "today_checkin": 50,
  "today_gain": 500,
  "today_consume": 200,
  "project_rank": [],
  "player_rank": []
}
```

### GET /api/dashboard/admin-home

普通管理员首页数据

---

## 用户管理 (超级管理员)

### GET /api/users

用户列表

**Query:** `page`, `page_size`, `keyword`, `group_id`, `role_id`, `status`

### POST /api/users

创建用户

```json
{
  "username": "player001",
  "real_name": "张三",
  "student_id": "2024001",
  "phone": "13800000001",
  "class_name": "一班",
  "group_id": 1,
  "role_id": 3,
  "password": "Player@123456",
  "csrf_token": ""
}
```

### PUT /api/users

更新用户

### DELETE /api/users

删除用户

### POST /api/users/batch-delete

批量删除

### POST /api/users/ban

封禁/解封

```json
{
  "user_id": 2,
  "status": 0,
  "csrf_token": ""
}
```

### POST /api/users/import

Excel/CSV 批量导入 (multipart/form-data, field: `file`)

### GET /api/users/export

导出 CSV

### PUT /api/users/profile

玩家修改昵称

### GET /api/users/login-logs

登录日志

### GET /api/users/operation-logs

操作日志

---

## 分组管理 (超级管理员)

### GET /api/groups

### POST /api/groups

```json
{
  "name": "一年级",
  "description": "",
  "csrf_token": ""
}
```

### PUT /api/groups

### DELETE /api/groups

---

## 项目管理

### GET /api/projects

获取项目列表 (按角色过滤)

### POST /api/projects

```json
{
  "name": "投壶",
  "description": "传统投壶游戏",
  "location": "A区",
  "manager_name": "负责人A",
  "status": 1,
  "admin_ids": [2, 3],
  "csrf_token": ""
}
```

### PUT /api/projects

### DELETE /api/projects

### GET /api/projects/admins

获取可指派的管理员列表

---

## 积分模块

### GET /api/scores/records

积分流水

**Query:** `page`, `page_size`, `type`, `project_id`, `user_id`, `today`

### POST /api/scores/gain

登记积分 (管理员)

```json
{
  "qr_token": "扫码获取",
  "user_id": 0,
  "project_id": 1,
  "score": 10,
  "reason": "投壶活动",
  "csrf_token": ""
}
```

### POST /api/scores/consume

核销积分

```json
{
  "qr_token": "",
  "project_id": 1,
  "score": 50,
  "reason": "礼品兑换",
  "csrf_token": ""
}
```

### POST /api/scores/adjust

手动调整 (超级管理员)

```json
{
  "user_id": 2,
  "score": 10,
  "direction": "add",
  "reason": "开幕式奖励",
  "csrf_token": ""
}
```

`direction`: `add` | `subtract`

### POST /api/scores/batch-adjust

批量调整

### GET /api/scores/players

玩家积分列表

### GET /api/scores/trend

玩家近7日积分趋势

### GET /api/scores/project-stats

项目今日统计

---

## 二维码模块

### GET /api/qr/generate

玩家生成身份二维码 Token (需玩家角色)

**响应：**

```json
{
  "token": "base64.signature",
  "expires_at": "2024-01-01 12:05:00",
  "expire_minutes": 5
}
```

### POST /api/qr/verify

管理员验证二维码

```json
{
  "token": "扫码内容"
}
```

---

## 系统设置 (超级管理员)

### GET /api/settings

### PUT /api/settings

```json
{
  "settings": {
    "site_name": "游园会积分登记与核销系统",
    "site_name_en": "YuYuan Fair Score System",
    "qr_expire_minutes": "5"
  },
  "csrf_token": ""
}
```

---

## 健康检查

### GET /api/health

无需认证
