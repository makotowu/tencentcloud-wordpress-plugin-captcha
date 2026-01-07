=== 腾讯云验证码 （CAPTCHA） ===

Contributors: Tencent, makotowu
Tags:tencent,tencentcloud,qcloud,春雨,腾讯云CAPTCHA,腾讯云验证码,腾讯云,验证码
Requires at least: 5.5
Tested up to: 5.8
Stable tag: 1.0.6
License:Apache 2.0
License URI:http://www.apache.org/licenses/LICENSE-2.0

== Description ==

<strong>腾讯云验证码 （CAPTCHA），基于腾讯云验证码在WordPress框架中实现登录、注册、评论、找回密码时验证码验证。</strong>

<strong>主要功能：</strong>

* 1、支持登录表单增加验证码；
* 2、支持注册表单增加验证码；
* 3、支持评论表单增加验证码；
* 4、支持忘记密码表单增加验证码；
* 5、支持场景自定义

原插件由腾讯云建设，现由社区维护。了解与该插件使用相关的更多信息，请访问[春雨文档中心](https://openapp.qq.com/docs/Wordpress/captcha.html)

请通过[咨询建议](https://txc.qq.com/)向我们提交宝贵意见。

== Installation ==

* 1、把tencentcloud-captcha文件夹上传到/wp-content/plugins/目录下<br />
* 2、在后台插件列表中激活腾讯云验证码插件<br />
* 3、在后台「腾讯云设置」→「验证码」中配置相关参数<br />

== Frequently Asked Questions ==

* 1.当发现插件出错时，开启调试获取错误信息。
== Screenshots ==

1. screenshot-1.png
2. screenshot-2.png
3. screenshot-3.png
4. screenshot-4.png
5. screenshot-5.png

== Changelog ==
= 1.0.6 =
* 1、新增 Argon / Sakurairo / Puock 主题评论验证码适配
* 2、后台配置界面样式升级为腾讯云风格

= 1.0.5 =
* 1、新增主题适配机制（Theme Adapters），用于处理自定义评论表单、AJAX 提交、PJAX 导航等场景
* 2、新增 Oyiso 主题适配：评论验证码支持 PJAX / History 导航与自定义评论表单提交流程
* 3、移除用户体验数据上报相关逻辑与后台开关

= 1.0.2 =
* 1、最高支持PHP8版本，并兼容WordPress5.8
* 2、新增本地调试日志功能
* 3、配置页面新增验证码测试功能

= 1.0.1 =
* 1、支持在windows环境下运行

= 1.0.0 =
* 1、支持登录表单增加验证码；
* 2、支持注册表单增加验证码；
* 3、支持评论表单增加验证码；
* 4、支持忘记密码表单增加验证码；
* 5、支持场景自定义
