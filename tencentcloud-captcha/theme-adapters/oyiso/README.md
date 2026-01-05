# Oyiso 主题适配说明

本适配器用于让腾讯云验证码插件在 Oyiso 主题下的“评论（AJAX + PJAX）”场景稳定工作。

## 适配文件

- `adapter.php`：主题命中与资源加载策略
- `front.js`：前端评论表单拦截、验证码弹窗与 PJAX 导航处理

## 适配背景

Oyiso 主题的评论系统与默认 WordPress 评论流程存在差异：

- 评论表单为自定义结构：`form.commentForm`
- 评论提交走前端脚本 `fetch('/wp-admin/admin-ajax.php')`，并不会使用 WordPress 默认的 comment form 提交
- 站内导航使用 PJAX 替换内容，可能导致脚本初始化与 DOM 渲染时序错位

## 工作原理

### 1) 表单提交前强制完成验证码

服务端在“评论需要验证码”开启时，会要求请求体包含：

- `codeVerifyTicket`
- `codeVerifyRandstr`

否则会返回响应头：

- `x-tencentcloud-captcha-scene: comment`
- `x-tencentcloud-captcha-error: required`

`front.js` 会在 `form.commentForm` 的 `submit` 阶段拦截提交：

1. 确保表单内存在 `input[name="codeVerifyTicket"]` 与 `input[name="codeVerifyRandstr"]`（无则创建隐藏字段）
2. 若字段为空，阻止提交并弹出腾讯云验证码
3. 验证通过后写入 `ticket/randstr`，再触发继续提交（`requestSubmit` 或触发提交按钮）

### 2) PJAX 导航后触发一次整页刷新（仅一次）

从首页通过 PJAX 进入文章页时，可能出现：

- 评论相关脚本/SDK 尚未就绪
- 评论表单节点尚未渲染或事件未绑定

为保证一致性，`front.js` 会监听 `pjax:fetch / pjax:content / pjax:ready` 以及 History 相关事件，在进入“疑似文章页 URL”（例如 `/123.html`）时触发一次 `location.reload()`。

为避免死循环刷新，使用 `sessionStorage` 记录已刷新过的 URL，同一 URL 仅刷新一次。

## 常见问题

### 发表评论失败，且响应头包含 `x-tencentcloud-captcha-error: required`

说明请求体里没有带 `codeVerifyTicket/codeVerifyRandstr`，按顺序排查：

1. Network 里确认 `https://turing.captcha.qcloud.com/TJCaptcha.js` 加载成功
2. 评论表单是否包含：
   - `input[name="codeVerifyTicket"]`
   - `input[name="codeVerifyRandstr"]`
3. 点击发送后是否弹出验证码；通过后这两个字段是否有值

### 从首页进入文章页没有自动刷新

自动刷新只对“疑似文章页 URL”生效（例如 `/123.html`）。如果你的文章链接结构不同，需要在 `front.js` 的文章页判定逻辑里调整匹配规则。

