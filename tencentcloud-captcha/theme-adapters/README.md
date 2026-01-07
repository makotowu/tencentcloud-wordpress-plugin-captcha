# 主题适配（Theme Adapters）

本目录用于存放针对不同 WordPress 主题的“最小侵入”适配，用来解决主题自定义评论表单、AJAX 提交、PJAX 导航等带来的验证码集成问题。

## 1. 适配器机制概览

主题适配器位于：

- `<theme-slug>/adapter.php`
- `<theme-slug>/front.js`（可选，但通常需要）

插件会在启动时自动加载所有 `theme-adapters/*/adapter.php`，由适配器自行通过 `theme/template` 判断是否生效。

适配器通常做两件事：

- 控制前端资源在特定主题下是否需要加载（例如 PJAX 场景下，不仅 `is_singular()` 才需要）
- 针对主题的自定义 DOM/交互，确保评论提交前能正确完成验证码并把参数带到请求中

## 2. 已支持主题

### Argon

详见：`argon/README.md`

### Oyiso

详见：`oyiso/README.md`

### Puock

详见：`puock/README.md`

### Sakurairo

详见：`sakurairo/README.md`

## 3. 常见问题排查

- 发表评论失败且响应头含 `x-tencentcloud-captcha-error: required`：优先检查是否带上 `codeVerifyTicket/codeVerifyRandstr`
- PJAX 导航后不生效：优先检查主题适配器是否已命中并加载对应 `front.js`
