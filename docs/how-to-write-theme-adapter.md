# 如何编写主题适配器

本项目的主题适配器用于在不修改主题源码的情况下，让插件在“自定义评论表单 / AJAX 提交 / PJAX 导航”等场景下仍能稳定注入并校验腾讯云验证码。

## 1. 目录结构与加载方式

新增一个主题适配器时，推荐结构如下：

- `tencentcloud-captcha/theme-adapters/<theme-slug>/adapter.php`
- `tencentcloud-captcha/theme-adapters/<theme-slug>/front.js`

插件会在启动时自动加载所有 `theme-adapters/*/adapter.php`，无需额外注册。

判断当前主题是否命中，可用 `tencentcloud_captcha_theme_is($context, '<theme-slug>')`（同时匹配 `stylesheet` 与 `template`）。

## 2. 后端：adapter.php 应该做什么

适配器通常通过 3 个钩子完成工作：

### 2.1 `tencentcloud_captcha_should_load_assets`

作用：

- 决定是否在当前页面加载前端脚本（默认策略通常是 `is_singular() || is_paged()`）

主题可能存在：

- PJAX 导航导致“并非首次进入文章页时脚本缺失/未初始化”
- 主题把评论面板放到全局布局里（导致非单篇页面也可能需要脚本）

你可以在命中主题且开启特定场景（例如评论验证码）时，返回 `true`。

### 2.2 `tencentcloud_captcha_enqueue_adapters`

作用：

- 在 WordPress 正常 `wp_enqueue_scripts` 资源队列中，为主题追加适配脚本

建议做法：

- 使用 `wp_enqueue_script` 加载 `front.js`，并按需设置依赖与版本号

### 2.3 `tencentcloud_captcha_output_fallback`

作用：

- 某些主题/页面场景下，WordPress 的 enqueue 链路可能不满足需求（或者被主题优化/缓存影响）
- 该钩子允许在 `get_footer` 阶段直接输出兜底脚本（适合主题兼容性修复）

实践建议：

- 仅在必要时使用，并确保全局只输出一次（可用 `static $done = false` 防重）

## 3. 前端：front.js 的编写原则

### 3.1 只处理“主题特有”的 DOM/行为

适配器脚本应当只关注该主题的差异点，例如：

- 主题的评论表单选择器（如 `form.commentForm`）
- 主题的 PJAX 事件（如 `pjax:fetch / pjax:content / pjax:ready`）
- 主题的 AJAX 提交流程是否会绕开 WordPress 默认表单提交

不要把通用能力放进适配器（通用能力应放在 `tencent_cloud_captcha_user.js`）。

### 3.2 以“提交前拦截”为核心保证 ticket/randstr 一定存在

评论场景的服务端校验要求：

- 请求体必须包含 `codeVerifyTicket` 与 `codeVerifyRandstr`

因此适配器的核心逻辑是：

1. 确保表单内存在 `input[name="codeVerifyTicket"]` 与 `input[name="codeVerifyRandstr"]`（没有就创建隐藏字段）
2. 在表单 `submit` 阶段拦截（建议用捕获阶段），如果字段为空则阻止提交并弹出验证码
3. 验证通过后写入字段，再继续触发提交（例如 `form.requestSubmit()`）

### 3.3 SDK 加载要“容错”

不要假设 `TJCaptcha.js` 一定已经加载完：

- 绑定 `submit` 监听不应依赖 SDK 已就绪
- 真正执行 `new TencentCaptcha()` 之前，才去确保 SDK 存在

本仓库会把基础配置通过 `window.TencentCloudCaptchaConfig` 注入到页面，你可以读取：

- `commentAppId`
- `aidEncrypted`（如果配置了）
- `enableDarkMode`（如果需要）

### 3.4 PJAX 场景下的初始化策略

典型问题：

- PJAX 导航替换内容后，新的评论表单节点尚未存在或事件未绑定
- 页面无需完整刷新，但为了保证“插件脚本/SDK/主题脚本”一致性，有时刷新一次是最稳定的方案

推荐策略（按侵入性从低到高）：

- 监听 PJAX 事件，在内容替换后重新执行初始化（重新扫描并绑定表单）
- 若主题对评论面板是延迟渲染且难以可靠捕捉，可在进入“疑似文章页”时触发一次整页刷新，并使用 `sessionStorage` 防止同一 URL 重复刷新

## 4. 最小示例（骨架）

`tencentcloud-captcha/theme-adapters/<theme>/adapter.php`：

- 命中主题与场景时，放开 `should_load_assets`
- enqueue 或 fallback 输出 `front.js`

`tencentcloud-captcha/theme-adapters/<theme>/front.js`：

- 读取 `window.TencentCloudCaptchaConfig`
- 绑定主题的评论表单 `submit` 拦截
- 必要时监听 PJAX/History 事件处理导航后初始化

可以参考现有 Oyiso 适配实现：

- `tencentcloud-captcha/theme-adapters/oyiso/adapter.php`
- `tencentcloud-captcha/theme-adapters/oyiso/front.js`

