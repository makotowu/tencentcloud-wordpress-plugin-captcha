# Argon 主题适配说明

本适配器用于让腾讯云验证码插件在 Argon 主题下的“评论（AJAX 提交）”场景稳定工作，并将验证码弹窗与评论提交过程绑定。

## 适配文件

- `adapter.php`：主题命中与资源加载
- `front.js`：前端拦截/补齐评论请求参数，弹出验证码并重试提交

## 适配背景

Argon 主题的评论提交通常不是浏览器原生表单直提，而是通过前端脚本向 `admin-ajax.php` 发送请求（常见 action 为 `ajax_post_comment`）。  
当插件启用“评论需要验证码”后，服务端会强制要求请求体包含：

- `codeVerifyTicket`
- `codeVerifyRandstr`

否则服务端会拒绝评论（并可能在响应头带上 `X-TencentCloud-Captcha-Error`）。

## 工作原理

### 1) 保证评论请求一定带上 ticket/randstr

`front.js` 在 Argon 主题下会对三类请求入口做拦截：

- `jQuery.ajax`
- `XMLHttpRequest`
- `fetch`

当检测到请求指向 `admin-ajax.php` 且 action 为 `ajax_post_comment`，并且请求体未包含 `codeVerifyTicket/codeVerifyRandstr` 时：

1. 阻止本次请求真正发出
2. 弹出腾讯云验证码
3. 验证通过后把 `ticket/randstr` 注入到本次请求体
4. 继续发送请求（并在完成后清理 token，避免复用）

### 2) 取消验证时的提示与重试逻辑

- 用户取消/验证失败：会尝试通过 `iziToast` 弹出“请先进行人机验证”（若页面未加载 `iziToast`，则静默处理）
- 若服务端返回 `X-TencentCloud-Captcha-Error: required/verify_failed`：会触发一次重试流程（重新弹出验证码后再提交）

### 3) 暗色模式

会优先检测 `document.documentElement` 是否包含 `darkmode` 类名；否则回退到系统 `prefers-color-scheme: dark`。

## 适用范围与开关

- 仅在当前主题为 `argon` / `argon-theme-master` 时生效
- 仅在插件配置中开启“评论需要验证码”（`commentNeedCode=2`）时生效

## 验证方式

1. 在插件后台开启“评论需要验证码”
2. 打开文章页，填写评论并点击提交
3. 预期：弹出验证码，通过后自动完成评论提交；网络请求中能看到 `codeVerifyTicket/codeVerifyRandstr`

