# Puock 主题适配说明

本适配器用于让腾讯云验证码插件在 Puock 主题下的评论场景稳定工作（Puock 评论走 AJAX 提交）。

## 适配文件

- `adapter.php`：主题命中与资源加载策略
- `front.js`：前端拦截评论提交，弹出验证码并补齐参数

## 适配背景

Puock 主题的评论表单 `#comment-form` 默认提交到：

- `wp-admin/admin-ajax.php?action=comment_ajax`

当插件开启“评论需要验证码”后，服务端会强制要求请求体包含：

- `codeVerifyTicket`
- `codeVerifyRandstr`

否则会返回响应头：

- `x-tencentcloud-captcha-scene: comment`
- `x-tencentcloud-captcha-error: required`

## 工作原理

`front.js` 会在 Puock 的评论表单上做两层拦截（捕获阶段）：

- 提交按钮 `click`：若未携带 token，则阻止提交并弹出验证码，通过后自动继续提交
- 表单 `submit`：若未携带 token，则阻止提交并弹出验证码，通过后继续提交

验证码通过后会把 `ticket/randstr` 写入隐藏字段，Puock 主题的 AJAX 提交会自动把隐藏字段序列化到请求体中。

此外，为避免 token 被复用导致后续评论失败，适配器会在 `comment_ajax` 的 AJAX 请求结束后清空 token。

## 常见问题

### 发表评论失败，且响应头包含 `x-tencentcloud-captcha-error: required`

说明请求体未带 `codeVerifyTicket/codeVerifyRandstr`，按顺序排查：

1. 页面是否已加载插件脚本 `tencent_cloud_captcha_user.js`
2. Puock 适配脚本 `theme-adapters/puock/front.js` 是否已加载
3. 评论表单 `#comment-form` 内是否存在：
   - `input[name="codeVerifyTicket"]`
   - `input[name="codeVerifyRandstr"]`
4. 点击发布评论后是否弹出验证码；通过后这两个字段是否有值

