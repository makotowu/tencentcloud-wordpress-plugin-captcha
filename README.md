# 腾讯云验证码（CAPTCHA）WordPress 插件

官方已放弃对该插件的维护，目前该项目由社区维护。

如果觉得不错，希望可以点亮 star 支持一下我~

## 目录

- [更新日志](#更新日志)
- [项目简介](#项目简介)
- [功能特性](#功能特性)
- [安装指南](#安装指南)
- [使用说明](#使用说明)
- [配置项说明](#配置项说明)
- [主题适配与二次开发](#主题适配与二次开发)
- [常见问题](#常见问题)
- [原 GitHub 版本记录](#原-github-版本记录)

## 更新日志

### v1.0.5 (2026.01.05)

- Oyiso 主题评论验证码适配：支持 PJAX / History 导航场景与自定义评论表单提交
- 文档结构调整：主题适配说明拆分到 `tencentcloud-captcha/theme-adapters/*`，并补充适配器开发指引
- 移除用户体验数据上报相关逻辑与后台开关

## 项目简介

tencentcloud-captcha 插件是腾讯云研发并面向 WordPress 站长提供的官方插件，用于在网站的注册、评论、登录、找回密码等模块启用验证码校验，防止机器人注册、垃圾评论及垃圾邮件。

| 标题 | 名称 |
| --- | --- |
| 中文名称 | 腾讯云验证码（CAPTCHA）插件 |
| 英文名称 | tencentcloud-captcha |
| 最新版本 | v1.0.5 (2026.01.05) |
| 适用平台 | [WordPress](https://wordpress.org/) |
| 适用产品 | [腾讯云验证码](https://cloud.tencent.com/document/product/1110/36334) |

## 功能特性

- 支持在注册表单中增加验证码
- 支持在评论表单中增加验证码
- 支持用户自定义业务场景
- 支持登录表单增加验证码
- 支持找回密码表单增加验证码
- 支持配置页面验证码测试功能
- 支持本地调试日志功能

## 安装指南

### 方式一：通过 CNB 拉取并安装

1. 克隆仓库：

   ```bash
   git clone https://cnb.cool/makotowu/tencentcloud-wordpress-plugin-captcha.git
   ```

2. 将 `tencentcloud-captcha` 目录复制到你的 WordPress 插件目录：

   - 目标路径：`/wp-content/plugins/`

### 方式二：通过 WordPress 后台安装

1. 将 `tencentcloud-captcha` 目录打包为 zip（zip 根目录必须是 `tencentcloud-captcha/`）。
2. 登录 WordPress 后台，进入「插件」→「安装插件」→「上传插件」。
3. 上传 zip 并安装后，进入插件列表启用。

## 使用说明

### 配置页面与场景效果

- 后台路径：进入「腾讯云设置」→「验证码」。
- 插件配置页面（配置密钥与启用场景）：

  ![](./images/captcha1.png)

- 登录页面开启验证码效果：

  ![](./images/captcha2.png)

- 忘记密码页面开启验证码效果：

  ![](./images/captcha3.png)

- 注册页面开启验证码效果：

  ![](./images/captcha4.png)

- 评论页面开启验证码效果：

  ![](./images/captcha5.png)

- 配置页面测试验证码功能，并提供本地调试日志功能：

  ![](./images/captcha6.png)

## 配置项说明

- **自定义密钥**：插件提供统一密钥管理。在多个腾讯云插件共存时，可以共享 SecretId 和 SecretKey，也支持各插件自定义密钥。
- **SecretId / SecretKey**：腾讯云 API 密钥，用于调用服务端校验接口。获取入口：<https://console.cloud.tencent.com/cam/capi>。
- **CaptchaAppId / CaptchaAppSecretKey**：腾讯云验证码控制台中应用的 AppId 与密钥，需要配对使用。产品文档：<https://cloud.tencent.com/document/product/1110/36334>。
- **验证码启用场景**：配置腾讯云验证码在 WordPress 站点中登录、评论、注册、找回密码等场景中开启。
- **自定义业务场景**：开启后可为“登录/注册、评论、找回密码”等不同场景分别配置 AppId/AppSecretKey（留空则回退使用通用 AppId/AppSecretKey）。
- **aidEncrypted**：启用后会携带加密的 `aidEncrypted` 参数（需在控制台开启强制校验），可配置密文过期时间（秒）。

## 主题适配与二次开发

- 已支持主题的适配说明与排查：见 [theme-adapters/README.md](./tencentcloud-captcha/theme-adapters/README.md)
- Oyiso 主题适配说明：见 [oyiso/README.md](./tencentcloud-captcha/theme-adapters/oyiso/README.md)
- 如何编写新的主题适配器：见 [theme-adapters/how-to-write-theme-adapter.md](./tencentcloud-captcha/theme-adapters/how-to-write-theme-adapter.md)

## 常见问题

### 发表评论失败，响应头包含 `x-tencentcloud-captcha-error: required`

含义：评论场景已开启验证码校验，但请求体未携带 `codeVerifyTicket` 或 `codeVerifyRandstr`。

建议优先参考主题适配排查文档：

- [theme-adapters/README.md](./tencentcloud-captcha/theme-adapters/README.md)
- [oyiso/README.md](./tencentcloud-captcha/theme-adapters/oyiso/README.md)

## 原 GitHub 版本记录

### 2021.8.20 tencentcloud-wordpress-plugin-captcha v1.0.2

- 支持 PHP 8 并兼容 WordPress 5.8
- 支持配置页面进行验证码测试
- 新增本地调试日志功能

### 2020.12.11 tencentcloud-wordpress-plugin-captcha v1.0.1

- 支持在 Windows 环境下运行

### 2020.6.23 tencentcloud-wordpress-plugin-captcha v1.0.0

- 支持在注册表单中增加验证码
- 支持在评论表单中增加验证码
- 支持用户自定义业务场景
- 支持登录表单增加验证码
- 支持找回密码表单增加验证码

