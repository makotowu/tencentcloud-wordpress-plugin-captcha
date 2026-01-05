# Sakurairo 主题适配说明

本适配器用于让腾讯云验证码插件在 Sakurairo 主题下的“评论（AJAX 提交）”场景获得更好的交互体验：点击“发表评论”时弹出验证码，通过后自动提交评论。

## 适配文件

- `adapter.php`：主题命中与资源加载；移除旧版“人机验证按钮”输出
- `front.js`：绑定评论提交按钮，弹出验证码并续提；注入样式修复滑块跟手问题

## 适配背景

腾讯云验证码插件默认通过 `comment_form_submit_button` 在评论表单里插入一个单独的“人机验证”按钮。  
在 Sakurairo 主题下，这种交互不够自然；同时 Sakurairo 的全局 CSS 存在：

- `* { transition: all 0.6s ease-in-out; }`

会影响验证码弹层/滑块的位移动画，导致“滑块不跟手”。

## 工作原理

### 1) 与“发表评论”按钮绑定

`front.js` 会在 `#commentform` 上做两层拦截（capture 阶段）：

- 拦截 `#submit` 的 `click`
- 拦截 `#commentform` 的 `submit`

当发现表单内没有 `codeVerifyTicket/codeVerifyRandstr` 或值为空时：

1. 阻止本次提交
2. 弹出腾讯云验证码
3. 验证通过后写入隐藏字段 `codeVerifyTicket/codeVerifyRandstr`
4. 继续触发原始的提交动作（从而走 Sakurairo 的 AJAX 评论提交流程）

### 2) 去除额外“人机验证按钮”

`adapter.php` 会在命中 Sakurairo 主题后，对 `comment_form_submit_button` 的输出做清理，移除旧版插入的：

- `#codeVerifyButton`
- `#codePassButton`

保留隐藏字段由适配脚本负责写入。

### 3) 修复验证码滑块不跟手

`front.js` 会注入一段只作用于验证码弹层的样式，强制禁用验证码 DOM 上的 `transition/animation`，避免被 Sakurairo 全局 `transition: all` 影响拖动手感。

## 适用范围与开关

- 仅在当前主题命中 `sakurairo`（兼容 `sakurairo-main` 等目录名）时生效
- 仅在插件配置中开启“评论需要验证码”（`commentNeedCode=2`）时生效

## 验证方式

1. 在插件后台开启“评论需要验证码”
2. 打开文章页，填写评论并点击“发表评论”
3. 预期：先弹出验证码，通过后自动提交评论；页面不再出现单独的“人机验证”按钮；滑块拖动跟手

