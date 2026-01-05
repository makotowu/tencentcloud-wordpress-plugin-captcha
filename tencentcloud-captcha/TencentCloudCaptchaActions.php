<?php
/*
 * Copyright (C) 2020 Tencent Cloud.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
if (!is_file(TENCENT_WORDPRESS_CAPTCHA_DIR . 'vendor/autoload.php')) {
	CaptchaDebugLog::writeDebugLog('error', 'msg : ' . '缺少依赖文件，请先执行composer install', __FILE__, __LINE__);
	wp_die('缺少依赖文件，请先执行composer install', '缺少依赖文件', array('back_link' => true));
}
require_once 'vendor/autoload.php';
require_once TENCENT_WORDPRESS_PLUGINS_COMMON_DIR . 'TencentWordpressPluginsSettingActions.php';

use TencentCloud\Common\Credential;
use TencentCloud\Common\Profile\ClientProfile;
use TencentCloud\Common\Profile\HttpProfile;
use TencentCloud\Common\Exception\TencentCloudSDKException;
use TencentCloud\Captcha\V20190722\CaptchaClient;
use TencentCloud\Captcha\V20190722\Models\DescribeCaptchaResultRequest;


class TencentCloudCaptchaActions
{
	const TENCENT_WORDPRESS_CAPTCHA_OPTIONS = 'tencent_wordpress_captcha_options';
	const TENCENT_WORDPRESS_CAPTCHA_LOGIN_NEED_CODE = 'login_need_code';
	const TENCENT_WORDPRESS_CAPTCHA_DEBUG_NEED_CODE = 'debug_need_code';
	const TENCENT_WORDPRESS_CAPTCHA_CODE_FREE = 'code_free';
	const TENCENT_WORDPRESS_CAPTCHA_APP_ID = 'captcha_app_id';
	const TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_ID = 'captcha_register_app_id';
	const TENCENT_WORDPRESS_CAPTCHA_REGISTER_NEED_CODE = 'register_need_code';
	const TENCENT_WORDPRESS_CAPTCHA_COMMENT_NEED_CODE = 'comment_need_code';
	const TENCENT_WORDPRESS_CAPTCHA_COMMENT_APP_ID = 'captcha_comment_app_id';
	const TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_NEED_CODE = 'lostpassword_need_code';
	const TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_APP_ID = 'captcha_lostpassword_app_id';
	const TENCENT_WORDPRESS_CAPTCHA_SECRET_ID = 'secret_id';
	const TENCENT_WORDPRESS_CAPTCHA_SECRET_KEY = 'secret_key';
	const TENCENT_WORDPRESS_CAPTCHA_APP_KEY = 'captcha_app_key';
	const TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_KEY = 'captcha_register_app_key';
	const TENCENT_WORDPRESS_CAPTCHA_COMMENT_APP_KEY = 'captcha_comment_app_key';
	const TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_APP_KEY = 'captcha_lostpassword_app_key';
	const TENCENT_WORDPRESS_CAPTCHA_SECRET_CUSTOM = 'secret_custom';
	const TENCENT_WORDPRESS_CAPTCHA_AID_ENCRYPTED_ON = 'aid_encrypted_on';
	const TENCENT_WORDPRESS_CAPTCHA_AID_ENCRYPTED_EXPIRE = 'aid_encrypted_expire';
	const TENCENT_WORDPRESS_CAPTCHA_PLUGIN_TYPE = 'captcha';

	public static function tencentCaptchaSetingNotice()
	{
		if (
			isset($GLOBALS['_REQUEST']) && isset($GLOBALS['_REQUEST']['page'])
			&& $GLOBALS['_REQUEST']['page'] === 'tencent_wordpress_plugin_captcha'
		) {
			$captchaOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
			if (isset($captchaOptions['activation']) && $captchaOptions['activation'] === true) {
				$chosen = '腾讯云验证码插件启用生效中';
			} else {
				$chosen = '腾讯云验证码插件启用中';
			}
			echo '<div id="cos_message" class="updated notice is-dismissible" style="margin-bottom: 1%;margin-left:0%;">
                     <p>' . $chosen . '</p>
                 </div>';
		}
	}

	/**
	 * 登录表单增加验证码
	 */
	public function tencentCaptchaLoginForm()
	{

		$captchaOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
		$loginNeedCode = $captchaOptions[self::TENCENT_WORDPRESS_CAPTCHA_LOGIN_NEED_CODE];
		if ($loginNeedCode == '2') {
			$captchaAppId = '';
			$codeFree = $captchaOptions[self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE];
			if ($codeFree == '1') {
				$captchaAppId = $captchaOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID];
			} else {
				$captchaAppId = sanitize_text_field($captchaOptions[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_ID])
					?: $captchaOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID];
			}
			echo '<p>
            <label for="codeVerifyButton">我不是人机</label>
            <input type="button" name="codeVerifyButton" id="codeVerifyButton" data-appid="' . $captchaAppId . '" class="button" value="验证" style="width: 100%;margin-bottom: 16px;height:40px;">
             <input type="button" id="codePassButton" disabled="disabled" style="background-color: green;color: white;width: 100%;margin-bottom: 16px;height:40px" value="已通过验证"  >
            <input type="hidden" id="codeVerifyTicket" name="codeVerifyTicket" value="">
            <input type="hidden" id="codeVerifyRandstr" name="codeVerifyRandstr" value="">
            </p>';
		}
	}

	/**
	 * 注册表单增加验证码
	 */
	public function tencentCaptchaRegisterForm()
	{

		$CodeVerifyOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);

		$registerNeedCode = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_NEED_CODE];
		if ($registerNeedCode == '2') {
			$codAppId = '';
			$codeFree = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE];
			if ($codeFree == '1') {
				$codAppId = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID];
			} else {
				$codAppId = sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_ID]) ?: $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID];
			}
			echo '<p>
            <label for="codeVerifyButton">我不是人机</label>
            <input type="button" name="codeVerifyButton" id="codeVerifyButton" data-appid="' . $codAppId . '" class="button" value="验证" style="width: 100%;margin-bottom: 16px;height:40px;">
            <input type="button" id="codePassButton" disabled="disabled" style="background-color: green;color: white;width: 100%;margin-bottom: 16px;height:40px" value="已通过验证"  >
            <input type="hidden" id="codeVerifyTicket" name="codeVerifyTicket" value="">
            <input type="hidden" id="codeVerifyRandstr" name="codeVerifyRandstr" value="">
            </p>';
		}
	}


	/**
	 * 评论表单增加验证码
	 * @param $submitButton 评论按钮HTML
	 * @return string
	 */
	public function tencentCaptchaCommentForm($submitButton)
	{
		$CodeVerifyOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
		$commentNeedCode = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_NEED_CODE];
		if ($commentNeedCode == '2') {
			$codAppId = '';
			$codeFree = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE];
			if ($codeFree == '1') {
				$codAppId = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID];
			} else {
				$codAppId = sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_APP_ID]) ?: $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID];
			}
			$submitButton = '<p>
            <input type="button" name="codeVerifyCommentButton" id="codeVerifyButton" data-appid="' . $codAppId . '" class="button" value="人机验证" style="width: 100%;margin-bottom: 16px;height:40px;">
            <input type="button" id="codePassButton" disabled="disabled" style="background-color: green;color: white;width: 100%;margin-bottom: 16px;height:40px" value="已通过验证"  >
            <input type="hidden" id="codeVerifyTicket" name="codeVerifyTicket" value="">
            <input type="hidden" id="codeVerifyRandstr" name="codeVerifyRandstr" value=""></p>' . $submitButton;
			return $submitButton;
		} else {
			return $submitButton;
		}
	}

	/**
	 * 找回密码增加验证码字段
	 */
	public function tencentCaptchaLostpasswordForm()
	{
		$CodeVerifyOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
		$lostpasswordNeedCode = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_NEED_CODE];
		if ($lostpasswordNeedCode == '2') {
			$codAppId = '';
			$codeFree = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE];
			if ($codeFree == '1') {
				$codAppId = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID];
			} else {
				$codAppId = sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_APP_ID]) ?: $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID];
			}
			echo '<p>
            <label for="codeVerifyButton">我不是人机</label>
            <input type="button" name="codeVerifyButton" id="codeVerifyButton" data-appid="' . $codAppId . '" class="button" value="验证" style="width: 100%;margin-bottom: 16px;height:40px;">
            <input type="button" id="codePassButton" disabled="disabled" style="background-color: green;color: white;width: 100%;margin-bottom: 16px;height:40px" value="已通过验证"  >
            <input type="hidden" id="codeVerifyTicket" name="codeVerifyTicket" value="">
            <input type="hidden" id="codeVerifyRandstr" name="codeVerifyRandstr" value="">
            </p>';
		}
	}

	/**
	 * 插件菜单设置
	 */
	public function tencentCaptchaPluginSettingPage()
	{
		TencentWordpressPluginsSettingActions::AddTencentWordpressCommonSettingPage();
		$pagehook = add_submenu_page('TencentWordpressPluginsCommonSettingPage', '验证码', '验证码', 'manage_options', 'tencent_wordpress_plugin_captcha', array('TencentCloudCaptchaActions', 'tencentCaptchaSettingPage'));
		add_action('admin_print_styles-' . $pagehook, array(new TencentCloudCaptchaActions(), 'tencentCaptchaLoadCssForPage'));
	}

	/**
	 * 插件配置信息操作页面
	 */
	public static function tencentCaptchaSettingPage()
	{
		include TENCENT_WORDPRESS_CAPTCHA_DIR . 'tencentcloud-captcha-setting-page.php';
	}

	/**
	 * 添加设置按钮
	 * @param $links
	 * @param $file
	 * @return mixed
	 */
	public function tencentCaptchaPluginSettingPageLinkButton($links, $file)
	{
		if ($file == plugin_basename(TENCENT_WORDPRESS_CAPTCHA_DIR . 'tencentcloud-captcha.php')) {
			$links[] = '<a href="admin.php?page=tencent_wordpress_plugin_captcha">设置</a>';
		}

		return $links;
	}

	/**
	 * 在文章页面加载JS脚本
	 */
	public function tencentCaptchaLoadScriptForPage()
	{
		$assetVer = self::getAssetVersion();
		$CodeVerifyOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
		$commentNeedCode = is_array($CodeVerifyOptions) && isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_NEED_CODE])
			? $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_NEED_CODE]
			: '1';
		$context = self::buildFrontContext($commentNeedCode);
		$defaultShouldLoad = is_singular() || is_paged();
		$shouldLoad = apply_filters('tencentcloud_captcha_should_load_assets', $defaultShouldLoad, $context);

		if ($shouldLoad) {
			wp_register_script('TCaptcha', 'https://turing.captcha.qcloud.com/TJCaptcha.js', array(), $assetVer, true);
			wp_enqueue_script('TCaptcha');
			wp_register_script('codeVerify_front_user_script', TENCENT_WORDPRESS_CAPTCHA_JS_DIR . 'tencent_cloud_captcha_user.js', array('jquery', 'TCaptcha'), $assetVer, true);
			wp_enqueue_script('codeVerify_front_user_script');
			self::addFrontInlineConfig();
			$assets = array(
				'assetVer' => $assetVer,
				'captcha_handle' => 'TCaptcha',
				'front_handle' => 'codeVerify_front_user_script',
				'config' => self::buildFrontConfig(),
			);
			do_action('tencentcloud_captcha_enqueue_adapters', $assets, $context);
		}
	}

	public function tencentCaptchaLoadCssForPage()
	{
		$consoleVer = self::assetVersion('css/console-pack.css');
		$adminVer = self::assetVersion('css/back_admin_style.css');

		wp_enqueue_style('tcwp_console_pack', TENCENT_WORDPRESS_CAPTCHA_CSS_DIR . 'console-pack.css', array(), $consoleVer);
		wp_enqueue_style('tcwp_admin_style', TENCENT_WORDPRESS_CAPTCHA_CSS_DIR . 'back_admin_style.css', array('tcwp_console_pack'), $adminVer);
	}

	/**
	 * 加载js脚本
	 */
	public function tencentCaptchaLoadMyScriptEnqueue()
	{
		$assetVer = self::getAssetVersion();
		$adminVer = self::getAssetVersion('js/tencent_cloud_captcha_admin.js');

		wp_register_script('TCaptcha', 'https://turing.captcha.qcloud.com/TJCaptcha.js', array(), $assetVer, true);
		wp_enqueue_script('TCaptcha');
		wp_register_script('codeVerify_front_user_script', TENCENT_WORDPRESS_CAPTCHA_JS_DIR . 'tencent_cloud_captcha_user.js', array('jquery', 'TCaptcha'), $assetVer, true);
		wp_enqueue_script('codeVerify_front_user_script');
		wp_register_script('codeVerify_back_admin_script', TENCENT_WORDPRESS_CAPTCHA_JS_DIR . 'tencent_cloud_captcha_admin.js', array('jquery', 'TCaptcha'), $adminVer, true);
		wp_enqueue_script('codeVerify_back_admin_script');
		self::addFrontInlineConfig();

		$CodeVerifyOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
		$commentNeedCode = is_array($CodeVerifyOptions) && isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_NEED_CODE])
			? $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_NEED_CODE]
			: '1';
		$context = self::buildFrontContext($commentNeedCode);
		$assets = array(
			'assetVer' => $assetVer,
			'adminVer' => $adminVer,
			'captcha_handle' => 'TCaptcha',
			'front_handle' => 'codeVerify_front_user_script',
			'admin_handle' => 'codeVerify_back_admin_script',
			'config' => self::buildFrontConfig(),
		);
		do_action('tencentcloud_captcha_enqueue_adapters', $assets, $context);
	}

	private static function buildFrontContext($commentNeedCode)
	{
		$theme = wp_get_theme();
		$themeConfig = self::getThemeConfig($theme);
		return array(
			'theme' => $themeConfig['theme'],
			'template' => $themeConfig['template'],
			'commentNeedCode' => strval($commentNeedCode),
			'isAdmin' => is_admin(),
			'isLogin' => isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'wp-login.php',
		);
	}

	private static function getAssetVersion($relativePath = 'js/tencent_cloud_captcha_user.js')
	{
		$base = defined('TENCENT_WORDPRESS_CAPTCHA_VERSION') ? TENCENT_WORDPRESS_CAPTCHA_VERSION : '1.0.0';
		$full = TENCENT_WORDPRESS_CAPTCHA_DIR . str_replace('/', DIRECTORY_SEPARATOR, ltrim($relativePath, '/'));
		if (is_file($full)) {
			$mtime = @filemtime($full);
			if ($mtime) {
				return $base . '.' . strval($mtime);
			}
		}
		return $base;
	}

	public static function assetVersion($relativePath)
	{
		return self::getAssetVersion($relativePath);
	}

	private static function getThemeConfig($theme)
	{
		return array(
			'theme' => $theme ? $theme->get_stylesheet() : '',
			'template' => $theme ? $theme->get_template() : '',
		);
	}

	private static function getCaptchaAppId($scene)
	{
		$CodeVerifyOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
		if (!is_array($CodeVerifyOptions)) {
			return '';
		}
		$codeFree = isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE]) ? $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE] : '';
		$commonAppId = isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID]) ? $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID] : '';

		if ($codeFree == '1') {
			return $commonAppId;
		}

		if ($scene === 'comment') {
			return sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_APP_ID]) ?: $commonAppId;
		}
		if ($scene === 'lostpassword') {
			return sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_APP_ID]) ?: $commonAppId;
		}
		return sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_ID]) ?: $commonAppId;
	}

	private static function getCaptchaAppKey($scene)
	{
		$CodeVerifyOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
		if (!is_array($CodeVerifyOptions)) {
			return '';
		}
		$codeFree = isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE]) ? $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE] : '';
		$commonAppKey = isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_KEY]) ? $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_KEY] : '';

		if ($codeFree == '1') {
			return $commonAppKey;
		}

		if ($scene === 'comment') {
			return sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_APP_KEY]) ?: $commonAppKey;
		}
		if ($scene === 'lostpassword') {
			return sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_APP_KEY]) ?: $commonAppKey;
		}
		return sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_KEY]) ?: $commonAppKey;
	}

	private static function getAidEncryptedExpireSeconds()
	{
		$CodeVerifyOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
		if (!is_array($CodeVerifyOptions) || !isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_AID_ENCRYPTED_EXPIRE])) {
			return 86400;
		}
		$expire = intval($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_AID_ENCRYPTED_EXPIRE]);
		if ($expire < 1) {
			$expire = 1;
		}
		if ($expire > 86400) {
			$expire = 86400;
		}
		return $expire;
	}

	private static function getAidEncryptedOn()
	{
		$CodeVerifyOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
		if (!is_array($CodeVerifyOptions) || !isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_AID_ENCRYPTED_ON])) {
			return '1';
		}
		return sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_AID_ENCRYPTED_ON]);
	}

	private static function createAidEncrypted($captchaAppId, $appSecretKey, $expireSeconds)
	{
		if (empty($captchaAppId) || empty($appSecretKey)) {
			return '';
		}
		if (!function_exists('openssl_encrypt') || !function_exists('random_bytes')) {
			return '';
		}
		$expireSeconds = intval($expireSeconds);
		if ($expireSeconds < 1) {
			$expireSeconds = 1;
		}
		if ($expireSeconds > 86400) {
			$expireSeconds = 86400;
		}

		$key = (string)$appSecretKey;
		if (strlen($key) < 32) {
			$seed = $key;
			if ($seed === '') {
				return '';
			}
			while (strlen($key) < 32) {
				$key .= $seed;
			}
		}
		$key = substr($key, 0, 32);

		$timestamp = time();
		$plain = $captchaAppId . '&' . $timestamp . '&' . $expireSeconds;
		$iv = random_bytes(16);
		$cipher = openssl_encrypt($plain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
		if ($cipher === false) {
			return '';
		}
		return base64_encode($iv . $cipher);
	}

	public function tencentCaptchaGetAidEncrypted()
	{
		$scene = isset($_REQUEST['scene']) ? sanitize_text_field($_REQUEST['scene']) : '';
		$allowed = array('login', 'register', 'comment', 'lostpassword');
		if (!in_array($scene, $allowed, true)) {
			wp_send_json_error(array('msg' => 'invalid_scene'));
		}

		$on = self::getAidEncryptedOn();
		if ($on != '2') {
			wp_send_json_success(array('aidEncrypted' => ''));
		}

		$captchaAppId = self::getCaptchaAppId($scene);
		$appSecretKey = self::getCaptchaAppKey($scene);
		$expire = self::getAidEncryptedExpireSeconds();
		$aidEncrypted = self::createAidEncrypted($captchaAppId, $appSecretKey, $expire);
		wp_send_json_success(array('aidEncrypted' => $aidEncrypted));
	}

	private static function addFrontInlineConfig()
	{
		static $done = false;
		if ($done) {
			return;
		}
		if (!wp_script_is('codeVerify_front_user_script', 'enqueued') && !wp_script_is('codeVerify_front_user_script', 'registered')) {
			return;
		}
		$config = self::buildFrontConfig();
		wp_add_inline_script('codeVerify_front_user_script', 'window.TencentCloudCaptchaConfig = ' . wp_json_encode($config) . ';', 'before');
		$done = true;
	}

	public static function buildFrontConfig()
	{
		$CodeVerifyOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
		if (!is_array($CodeVerifyOptions)) {
			$CodeVerifyOptions = array();
		}

		$theme = wp_get_theme();
		$themeConfig = self::getThemeConfig($theme);
		$aidEncryptedOn = isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_AID_ENCRYPTED_ON]) ? $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_AID_ENCRYPTED_ON] : '1';
		$aidEncryptedByScene = array();
		if ($aidEncryptedOn == '2') {
			$expire = self::getAidEncryptedExpireSeconds();
			$aidEncryptedByScene = array(
				'login' => self::createAidEncrypted(self::getCaptchaAppId('login'), self::getCaptchaAppKey('login'), $expire),
				'register' => self::createAidEncrypted(self::getCaptchaAppId('register'), self::getCaptchaAppKey('register'), $expire),
				'comment' => self::createAidEncrypted(self::getCaptchaAppId('comment'), self::getCaptchaAppKey('comment'), $expire),
				'lostpassword' => self::createAidEncrypted(self::getCaptchaAppId('lostpassword'), self::getCaptchaAppKey('lostpassword'), $expire),
			);
		}

		return array(
			'theme' => $themeConfig['theme'],
			'template' => $themeConfig['template'],
			'ajaxUrl' => admin_url('admin-ajax.php'),
			'aidEncryptedOn' => $aidEncryptedOn,
			'aidEncryptedExpire' => isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_AID_ENCRYPTED_EXPIRE]) ? $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_AID_ENCRYPTED_EXPIRE] : '86400',
			'aidEncryptedAction' => 'tencentcloud_captcha_aid_encrypted',
			'aidEncrypted' => $aidEncryptedByScene,
			'codeFree' => isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE]) ? $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE] : '',
			'debugNeedCode' => isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_DEBUG_NEED_CODE]) ? $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_DEBUG_NEED_CODE] : '1',
			'loginNeedCode' => isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_LOGIN_NEED_CODE]) ? $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_LOGIN_NEED_CODE] : '1',
			'registerNeedCode' => isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_NEED_CODE]) ? $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_NEED_CODE] : '1',
			'commentNeedCode' => isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_NEED_CODE]) ? $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_NEED_CODE] : '1',
			'lostpasswordNeedCode' => isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_NEED_CODE]) ? $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_NEED_CODE] : '1',
			'loginAppId' => self::getCaptchaAppId('login'),
			'registerAppId' => self::getCaptchaAppId('register'),
			'commentAppId' => self::getCaptchaAppId('comment'),
			'lostpasswordAppId' => self::getCaptchaAppId('lostpassword'),
		);
	}


	/**
	 * @param $secretID 腾讯云密钥ID
	 * @param $secretKey 腾讯云密钥Key
	 * @param $codeAppId 验证码通用APPID
	 * @param $codeSecretKey 验证码通用APPKey
	 * @param $codeFree 是否自定义业务场景
	 * @param $registerAppId 注册场景应用APPID
	 * @param $registerAppKey 注册场景应用APPKey
	 * @param $commentAppId 评论场景应用APPID
	 * @param $commentAppKey 评论场景应用APPKey
	 * @param $secretCustom 自定义密钥
	 * @return bool|string
	 */
	public static function tencentCaptchaCheckMustParams(
		$secretID,
		$secretKey,
		$codeAppId,
		$codeSecretKey,
		$codeFree,
		$registerAppId,
		$registerAppKey,
		$commentAppId,
		$commentAppKey,
		$lostpasswordAppId,
		$lostpasswordAppKey,
		$secretCustom
	) {
		if ($secretCustom == '2') {
			if (empty($secretID)) {
				return 'Secret Id未填写.';
			}
			if (empty($secretKey)) {
				return 'Secret key未填写.';
			}
		}
		if (empty($codeAppId)) {
			return 'Captcha App Id未填写.';
		}
		if (empty($codeSecretKey)) {
			return 'Captcha App Secret Key未填写.';
		}
		if ($codeFree == '2') {
			if ((empty($registerAppId) && !empty($registerAppKey)) || (!empty($registerAppId) && empty($registerAppKey))) {
				return '注册场景应用APP ID和应用Secret Key需要同时填写.';
			}
			if ((empty($commentAppId) && !empty($commentAppKey)) || (!empty($commentAppId) && empty($commentAppKey))) {
				return '评论场景应用APP ID和应用Secret Key需要同时填写.';
			}

			if ((empty($lostpasswordAppId) && !empty($lostpasswordAppKey)) || (!empty($lostpasswordAppId) && empty($lostpasswordAppKey))) {
				return '找回密码场景应用APP ID和应用Secret Key需要同时填写.';
			}
		}
		return true;
	}

	/**
	 * 保存插件配置
	 */
	public function tencentCaptchaUpdateCaptchaSettings()
	{
		if (!current_user_can('manage_options')) {
			CaptchaDebugLog::writeDebugLog('error', 'msg : 当前用户无权限!', __FILE__, __LINE__);
			wp_send_json_error(array('msg' => '当前用户无权限.'));
		}
		$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_SECRET_ID] = sanitize_text_field($_POST['secret_id']);
		$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_SECRET_KEY] = sanitize_text_field($_POST['secret_key']);
		$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID] = sanitize_text_field($_POST['codeVerify_option_codeAppId']);
		$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_APP_KEY] = sanitize_text_field($_POST['codeVerify_option_codeSecretKey']);
		$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_NEED_CODE] = sanitize_text_field($_POST['registerNeedCode']);
		$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_NEED_CODE] = sanitize_text_field($_POST['commentNeedCode']);
		$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_LOGIN_NEED_CODE] = sanitize_text_field($_POST['loginNeedCode']);
		$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_DEBUG_NEED_CODE] = sanitize_text_field($_POST['debugNeedCode']);
		$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_NEED_CODE] = sanitize_text_field($_POST['lostpasswordNeedCode']);
		$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE] = sanitize_text_field($_POST['codeFree']);
		$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_SECRET_CUSTOM] = sanitize_text_field($_POST['secretCustom']);
		$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_AID_ENCRYPTED_ON] = isset($_POST['aidEncryptedOn']) ? sanitize_text_field($_POST['aidEncryptedOn']) : '1';
		$aidEncryptedExpire = isset($_POST['aidEncryptedExpire']) ? intval($_POST['aidEncryptedExpire']) : 86400;
		if ($aidEncryptedExpire < 1) {
			$aidEncryptedExpire = 1;
		}
		if ($aidEncryptedExpire > 86400) {
			$aidEncryptedExpire = 86400;
		}
		$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_AID_ENCRYPTED_EXPIRE] = strval($aidEncryptedExpire);
		$CodeVerifySettings['activation'] = true;
		if ($CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE] == 2) {
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_ID] = sanitize_text_field($_POST['registerCodeAppId']);
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_KEY] = sanitize_text_field($_POST['registerCodeKey']);
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_APP_ID] = sanitize_text_field($_POST['commentCodeAppId']);
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_APP_KEY] = sanitize_text_field($_POST['commentCodeKey']);
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_APP_ID] = sanitize_text_field($_POST['lostpasswordCodeAppId']);
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_APP_KEY] = sanitize_text_field($_POST['lostpasswordCodeKey']);
		} else {
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_ID] = '';
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_KEY] = '';
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_APP_ID] = '';
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_APP_KEY] = '';
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_APP_ID] = '';
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_APP_KEY] = '';
		}

		if ($CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_SECRET_CUSTOM] == '1') {
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_SECRET_ID] = '';
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_SECRET_KEY] = '';
		}


		$checkResult = self::tencentCaptchaCheckMustParams(
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_SECRET_ID],
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_SECRET_KEY],
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID],
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_APP_KEY],
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE],
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_ID],
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_KEY],
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_APP_ID],
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_APP_KEY],
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_APP_ID],
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_APP_KEY],
			$CodeVerifySettings[self::TENCENT_WORDPRESS_CAPTCHA_SECRET_CUSTOM]
		);
		if ($checkResult !== true) {
			CaptchaDebugLog::writeDebugLog('error', 'msg : ' . $checkResult, __FILE__, __LINE__);
			wp_send_json_error(array('msg' => $checkResult));
		}
		update_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS, $CodeVerifySettings, true);
		wp_send_json_success(array('msg' => '保存成功'));
	}

	/**
	 * 登录时验证
	 * @param $users 用户
	 * @return WP_Error 验证错误
	 */
	public function tencentCapthcaLoginCodeVerify($users)
	{
		if (!empty($_POST)) {
			$CodeVerifyOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
			$loginNeedCode = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_LOGIN_NEED_CODE];
			if ($loginNeedCode == '2') {
				$ticket = sanitize_text_field($_POST['codeVerifyTicket']);
				$randStr = sanitize_text_field($_POST['codeVerifyRandstr']);
				if (empty($ticket) || empty($randStr)) {
					CaptchaDebugLog::writeDebugLog('error', 'msg : ticket or randStr is null!', __FILE__, __LINE__);
					return new WP_Error(
						'invalid_CodeVerify',
						__('未通过人机验证.')
					);
				}
				$codeFree = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE];
				$codeAppId = '';
				$codeAppKey = '';
				if ($codeFree == '2') {
					$codeAppId = sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_ID]) ?: $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID];
					$codeAppKey = sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_KEY]) ?: $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_KEY];
				} else {
					$codeAppId = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID];
					$codeAppKey = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_KEY];
				}
				$verifyCode = self::verifyCodeReal($ticket, $randStr, $codeAppId, $codeAppKey);
				if (!$verifyCode || $verifyCode['CaptchaCode'] != 1) {
					$errormessage = '未通过人机验证.';
					if (isset($verifyCode['errorMessage']) && !empty($verifyCode['errorMessage'])) {
						$errormessage = $errormessage . $verifyCode['errorMessage'];
					}
					CaptchaDebugLog::writeDebugLog('error', 'msg : ' . $errormessage, __FILE__, __LINE__);
					return new WP_Error(
						'invalid_CodeVerify',
						__('未通过人机验证.')
					);;
				}
				return $users;
			} else {
				return $users;
			}
		}
		return $users;
	}


	/**
	 * 注册时验证码验证
	 * @param $login 用户名
	 * @param $email 用户邮箱
	 * @param $errors 异常
	 * @return mixed
	 */
	public function tencentCaptchaRegisterCodeVerify($login, $email, $errors)
	{
		if (!empty($_POST)) {
			$CodeVerifyOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
			$registerNeedCode = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_NEED_CODE];
			if ($registerNeedCode == '2') {
				$ticket = sanitize_text_field($_POST['codeVerifyTicket']);
				$randStr = sanitize_text_field($_POST['codeVerifyRandstr']);
				if (empty($ticket) || empty($randStr)) {
					CaptchaDebugLog::writeDebugLog('error', 'msg : ticket or randStr is null!', __FILE__, __LINE__);
					$errors->add('未通过人机验证.', __('未通过人机验证.', 'wpcaptchadomain'));
					return $errors;
				}

				$codeFree = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE];
				$codeAppId = '';
				$codeAppKey = '';
				if ($codeFree == '2') {
					$codeAppId = sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_ID]) ?: $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID];
					$codeAppKey = sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_KEY]) ?: $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_KEY];
				} else {
					$codeAppId = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID];
					$codeAppKey = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_KEY];
				}
				$verifyCode = self::verifyCodeReal($ticket, $randStr, $codeAppId, $codeAppKey);
				if (!$verifyCode || $verifyCode['CaptchaCode'] != 1) {
					$errormessage = '未通过人机验证.';
					if (isset($verifyCode['errorMessage']) && !empty($verifyCode['errorMessage'])) {
						$errormessage = $errormessage . $verifyCode['errorMessage'];
					}
					CaptchaDebugLog::writeDebugLog('error', 'msg : 未通过人机验证.' . $errormessage, __FILE__, __LINE__);
					$errors->add('未通过人机验证.', __($errormessage, 'wpcaptchadomain'));
					return $errors;
				}
			}
		}
	}

	/**
	 * 忘记密码时验证码验证
	 *
	 */
	public function tencentCaptchaLostpasswordCodeVerify()
	{
		if (!empty($_POST)) {
			$CodeVerifyOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
			$lostpasswordNeedCode = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_NEED_CODE];
			if ($lostpasswordNeedCode == '2') {
				$ticket = sanitize_text_field($_POST['codeVerifyTicket']);
				$randStr = sanitize_text_field($_POST['codeVerifyRandstr']);
				if (empty($ticket) || empty($randStr)) {
					CaptchaDebugLog::writeDebugLog('error', 'msg : ticket or randStr is null!', __FILE__, __LINE__);
					$error = new WP_Error(
						'invalid_CodeVerify',
						__('未通过人机验证.')
					);
					wp_die($error, '未通过人机验证.', array('back_link' => true));
				}
				$codeFree = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE];
				$codeAppId = '';
				$codeAppKey = '';
				if ($codeFree == '2') {
					$codeAppId = sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_APP_ID]) ?: $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID];
					$codeAppKey = sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_APP_KEY]) ?: $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_KEY];
				} else {
					$codeAppId = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID];
					$codeAppKey = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_KEY];
				}
				$verifyCode = self::verifyCodeReal($ticket, $randStr, $codeAppId, $codeAppKey);
				if (!$verifyCode || $verifyCode['CaptchaCode'] != 1) {
					$errormessage = '未通过人机验证.';
					if (isset($verifyCode['errorMessage']) && !empty($verifyCode['errorMessage'])) {
						$errormessage = $errormessage . $verifyCode['errorMessage'];
					}
					$error = new WP_Error(
						'invalid_CodeVerify',
						__('未通过人机验证.')
					);
					CaptchaDebugLog::writeDebugLog('error', 'msg : ' . $errormessage, __FILE__, __LINE__);
					wp_die($error, '未通过人机验证.', array('back_link' => true));
				}
			}
		}
	}

	/**
	 * 评论时验证码验证
	 * @param $comment 评论信息
	 * @return mixed
	 */
	public function tencentCaptchaCommentCodeVerify($comment)
	{
		$user = wp_get_current_user();
		// 管理员后台回复评论时无需验证
		$allowed_roles = array('editor', 'administrator', 'author');
		if (array_intersect($allowed_roles, $user->roles)) {
			return $comment;
		}
		$CodeVerifyOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
		if (!is_array($CodeVerifyOptions)) {
			return $comment;
		}
		$commentNeedCode = isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_NEED_CODE])
			? $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_NEED_CODE]
			: '1';
		if ($commentNeedCode == '2') {
			$ticket = isset($_POST['codeVerifyTicket']) ? sanitize_text_field(wp_unslash($_POST['codeVerifyTicket'])) : '';
			$randStr = isset($_POST['codeVerifyRandstr']) ? sanitize_text_field(wp_unslash($_POST['codeVerifyRandstr'])) : '';
			if (empty($ticket) || empty($randStr)) {
				if (defined('DOING_AJAX') && DOING_AJAX) {
					if (!headers_sent()) {
						header('X-TencentCloud-Captcha-Scene: comment');
						header('X-TencentCloud-Captcha-Error: required');
					}
					wp_die('error');
				}
				$error = new WP_Error(
					'need_authenticated_code',
					__('请先进行人机验证.')
				);
				CaptchaDebugLog::writeDebugLog('error', 'msg : ticket or randStr is null!', __FILE__, __LINE__);
				wp_die($error, '验证码不能为空', array('back_link' => true));
			}
			$codeFree = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE];
			$codeAppId = '';
			$codeAppKey = '';
			if ($codeFree == '2') {
				$codeAppId = sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_APP_ID]) ?: $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID];
				$codeAppKey = sanitize_text_field($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_APP_KEY]) ?: $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_KEY];
			} else {
				$codeAppId = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID];
				$codeAppKey = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_KEY];
			}
			$verifyCode = self::verifyCodeReal($ticket, $randStr, $codeAppId, $codeAppKey);
			if (!$verifyCode || !isset($verifyCode['CaptchaCode']) || intval($verifyCode['CaptchaCode']) !== 1) {
				if (defined('DOING_AJAX') && DOING_AJAX) {
					if (!headers_sent()) {
						header('X-TencentCloud-Captcha-Scene: comment');
						header('X-TencentCloud-Captcha-Error: verify_failed');
						if (is_array($verifyCode)) {
							if (isset($verifyCode['CaptchaCode'])) header('X-TencentCloud-Captcha-Code: ' . intval($verifyCode['CaptchaCode']));
							if (isset($verifyCode['CaptchaMsg'])) header('X-TencentCloud-Captcha-Msg: ' . rawurlencode((string)$verifyCode['CaptchaMsg']));
						}
					}
					wp_die('error');
				}
				$errormessage = '验证码验证失败.';
				if (isset($verifyCode['errorMessage']) && !empty($verifyCode['errorMessage'])) {
					$errormessage = $errormessage . $verifyCode['errorMessage'];
				}
				$error = new WP_Error(
					'authenticated_fail',
					__('验证码验证失败.')
				);
				CaptchaDebugLog::writeDebugLog('error', 'msg : ' . $errormessage, __FILE__, __LINE__);
				wp_die($error, '验证码验证失败,请重新验证', array('back_link' => true));
			}
			return $comment;
		} else {
			return $comment;
		}
	}

	/**
	 * 验证码服务端验证
	 * @param $secretID 腾讯云密钥ID
	 * @param $secretKey 腾讯云密钥Key
	 * @param $ticket 用户验证票据
	 * @param $randStr 用户验证时随机字符串
	 * @param $codeAppId 验证码应用ID
	 * @param $codeSecretKey 验证码应用蜜月
	 * @return array|mixed
	 */
	public static function verifyCodeReal($ticket, $randStr, $codeAppId, $codeSecretKey)
	{
		try {
			$secretID = self::getSecretID();
			$secretKey = self::getSecretKey();
			$remoteIp = preg_replace('/[^0-9a-fA-F:., ]/', '', $_SERVER['REMOTE_ADDR']);
			$cred = new Credential($secretID, $secretKey);
			$httpProfile = new HttpProfile();
			$httpProfile->setEndpoint("captcha.tencentcloudapi.com");
			$clientProfile = new ClientProfile();
			$clientProfile->setHttpProfile($httpProfile);
			$client = new CaptchaClient($cred, "", $clientProfile);
			$req = new DescribeCaptchaResultRequest();
			$params = array('CaptchaType' => 9, 'Ticket' => $ticket, 'Randstr' => $randStr, 'CaptchaAppId' => intval($codeAppId), 'AppSecretKey' => $codeSecretKey, 'UserIp' => $remoteIp);
			$req->fromJsonString(json_encode($params));
			$resp = $client->DescribeCaptchaResult($req);
			return json_decode($resp->toJsonString(), JSON_OBJECT_AS_ARRAY);
		} catch (TencentCloudSDKException $e) {
			CaptchaDebugLog::writeDebugLog('error', 'msg : ' . $e->getMessage(), __FILE__, __LINE__);
			return false;
		}
	}

	/**
	 * 获取SecrtId
	 * @return mixed
	 */
	private static function getSecretID()
	{
		$tecentCaptchaOptinos = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
		if (sanitize_text_field($tecentCaptchaOptinos[self::TENCENT_WORDPRESS_CAPTCHA_SECRET_CUSTOM]) == '2') {
			return $tecentCaptchaOptinos[self::TENCENT_WORDPRESS_CAPTCHA_SECRET_ID];
		} else {
			$commonOptinos = get_option('tencent_wordpress_common_options');
			if (isset($commonOptinos[self::TENCENT_WORDPRESS_CAPTCHA_SECRET_ID])) {
				return $commonOptinos[self::TENCENT_WORDPRESS_CAPTCHA_SECRET_ID];
			}
			CaptchaDebugLog::writeDebugLog('error', 'msg : SecretID is null', __FILE__, __LINE__);
			return '';
		}
	}

	/**
	 * 获取SecrtKey
	 * @return mixed
	 */
	private static function getSecretKey()
	{
		$tecentCaptchaOptinos = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
		if (sanitize_text_field($tecentCaptchaOptinos[self::TENCENT_WORDPRESS_CAPTCHA_SECRET_CUSTOM]) == '2') {
			return $tecentCaptchaOptinos[self::TENCENT_WORDPRESS_CAPTCHA_SECRET_KEY];
		} else {
			$commonOptinos = get_option('tencent_wordpress_common_options');
			if (isset($commonOptinos[self::TENCENT_WORDPRESS_CAPTCHA_SECRET_KEY])) {
				return $commonOptinos[self::TENCENT_WORDPRESS_CAPTCHA_SECRET_KEY];
			}
			CaptchaDebugLog::writeDebugLog('error', 'msg : SecretKey is null', __FILE__, __LINE__);
			return '';
		}
	}

	/**
	 * 开启插件
	 */
	public static function tencentCaptchaActivatePlugin()
	{
		$initOptions = array(
			'activation' => false,
			self::TENCENT_WORDPRESS_CAPTCHA_SECRET_ID => "",
			self::TENCENT_WORDPRESS_CAPTCHA_SECRET_KEY => "",
			self::TENCENT_WORDPRESS_CAPTCHA_APP_ID => '',
			self::TENCENT_WORDPRESS_CAPTCHA_APP_KEY => '',
			self::TENCENT_WORDPRESS_CAPTCHA_LOGIN_NEED_CODE => '',
			self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_NEED_CODE => '',
			self::TENCENT_WORDPRESS_CAPTCHA_DEBUG_NEED_CODE => '',
			self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_NEED_CODE => '',
			self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_NEED_CODE => '',
			self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_ID => '',
			self::TENCENT_WORDPRESS_CAPTCHA_REGISTER_APP_KEY => '',
			self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_APP_ID => '',
			self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_APP_KEY => '',
			self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_APP_ID => '',
			self::TENCENT_WORDPRESS_CAPTCHA_LOSTPASSWORD_APP_KEY => '',
			self::TENCENT_WORDPRESS_CAPTCHA_CODE_FREE => '',
			self::TENCENT_WORDPRESS_CAPTCHA_SECRET_CUSTOM => '',
			self::TENCENT_WORDPRESS_CAPTCHA_AID_ENCRYPTED_ON => '1',
			self::TENCENT_WORDPRESS_CAPTCHA_AID_ENCRYPTED_EXPIRE => '86400'
		);
		$captchaOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
		if (empty($captchaOptions)) {
			add_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS, $initOptions);
		} else {
			$captchaOptions = array_merge($initOptions, $captchaOptions);
			update_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS, $captchaOptions);
		}

		$plugin = array(
			'plugin_name' => TENCENT_WORDPRESS_CAPTCHA_SHOW_NAME,
			'nick_name' => '腾讯云验证码（CAPTCHA）插件',
			'plugin_dir' => 'tencentcloud-captcha/tencentcloud-captcha.php',
			'href' => 'admin.php?page=tencent_wordpress_plugin_captcha',
			'activation' => 'true',
			'status' => 'true',
			'download_url' => ''
		);
		TencentWordpressPluginsSettingActions::prepareTencentWordressPluginsDB($plugin);

		// 第一次开启插件则生成一个全站唯一的站点id，保存在公共的option中
		TencentWordpressPluginsSettingActions::setWordPressSiteID();
	}

	/**
	 * 禁止插件
	 */
	public static function tencentCaptchaDeactivePlugin()
	{
		$captchaOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
		if (!empty($captchaOptions) && isset($captchaOptions['activation'])) {
			$captchaOptions['activation'] = false;
			update_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS, $captchaOptions);
		}
		TencentWordpressPluginsSettingActions::disableTencentWordpressPlugin(TENCENT_WORDPRESS_CAPTCHA_SHOW_NAME);
	}

	/**
	 * 插件初始化
	 */
	public static function tencentCaptchaInit()
	{
		if (class_exists('TencentWordpressPluginsSettingActions')) {
			TencentWordpressPluginsSettingActions::init();
		}
		if (!is_admin()) {
			add_action('get_footer', array(__CLASS__, 'tencentCaptchaMaybeOutputThemeFallbackScripts'), 1, 1);
		}
	}

	public static function tencentCaptchaMaybeOutputThemeFallbackScripts($name = null)
	{
		if (is_admin() || wp_doing_ajax() || is_feed()) {
			return;
		}
		$CodeVerifyOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
		$commentNeedCode = is_array($CodeVerifyOptions) && isset($CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_NEED_CODE])
			? $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_COMMENT_NEED_CODE]
			: '1';
		$context = self::buildFrontContext($commentNeedCode);
		$assets = array(
			'assetVer' => self::getAssetVersion(),
			'captcha_handle' => 'TCaptcha',
			'front_handle' => 'codeVerify_front_user_script',
			'config' => self::buildFrontConfig(),
		);
		do_action('tencentcloud_captcha_output_fallback', $assets, $context);
	}

	/**
	 *  配置页面测试验证码功能，先保存配置再测试
	 * @throws Exception
	 */
	public static function tencentCaptchaCodeVerifyCheck()
	{
		$CodeVerifyOptions = get_option(self::TENCENT_WORDPRESS_CAPTCHA_OPTIONS);
		$codeAppId = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_ID];
		$codeAppKey = $CodeVerifyOptions[self::TENCENT_WORDPRESS_CAPTCHA_APP_KEY];
		$ticket = sanitize_text_field($_POST['codeVerifyTicketCheck']);
		$randStr = sanitize_text_field($_POST['codeVerifyRandstrCheck']);

		if (empty($ticket) || empty($randStr) || empty($codeAppId) || empty($codeAppKey)) {
			$errormessage = 'ticket(' . $ticket . ') or randStr(' . $randStr .
				') or AppId(' . $codeAppId . ') or AppKey(' . $codeAppKey . ') is null';

			CaptchaDebugLog::writeDebugLog('error', 'msg : ' . $errormessage, __FILE__, __LINE__);
			wp_send_json_error(array('msg' => $errormessage));
		}

		try {
			$verifyCode = self::verifyCodeReal($ticket, $randStr, $codeAppId, $codeAppKey);
			if (!$verifyCode || isset($verifyCode['CaptchaCode']) && $verifyCode['CaptchaCode'] != 1) {
				$errormessage = '验证码验证失败.';
				if (!empty($verifyCode['CaptchaMsg'])) {
					$errormessage = $errormessage . $verifyCode['CaptchaMsg'];
				}
				CaptchaDebugLog::writeDebugLog('error', 'msg : ' . $errormessage, __FILE__, __LINE__);
				wp_send_json_error(array('msg' => $errormessage));
			}
			wp_send_json_success(array('msg' => '验证成功'));
		} catch (\Exception $e) {
			CaptchaDebugLog::writeDebugLog('error', 'msg : ' . $e->getMessage(), __FILE__, __LINE__);
			wp_send_json_error(array('msg' => $e->getMessage()));
		}
	}

	public static function tencentCaptchaDeleteLogfile()
	{
		if (!file_exists(TENCENT_WORDPRESS_CAPTCHA_LOGS)) {
			wp_send_json_success();
		}

		self::removeDir(TENCENT_WORDPRESS_CAPTCHA_LOGS);
		wp_send_json_success();
	}

	/**
	 *  迭代删除目录及目录中的自文件
	 *
	 * @param string $dir
	 * @access public
	 * @return bool
	 * @throws Exception
	 */
	public static function removeDir($dir)
	{
		if (empty($dir)) {
			return true;
		}

		$dir = realpath($dir) . '/';
		if ($dir == '/') {
			CaptchaDebugLog::writeDebugLog('notice', 'msg : can not remove root dir', __FILE__, __LINE__);
			return false;
		}

		if (!is_writable($dir)) {
			CaptchaDebugLog::writeDebugLog('notice', 'msg : no delete permission ', __FILE__, __LINE__);
			return false;
		}

		if (!is_dir($dir)) {
			return true;
		}

		$entries = scandir($dir);
		foreach ($entries as $entry) {
			if ($entry == '.' or $entry == '..')
				continue;

			$fullEntry = $dir . $entry;
			if (is_file($fullEntry)) {
				@unlink($fullEntry);
			} else {
				return self::removeDir($fullEntry);
			}
		}
		if (!@rmdir($dir)) {
			CaptchaDebugLog::writeDebugLog('notice', 'msg : remove log dir failed', __FILE__, __LINE__);
			return false;
		}
		return true;
	}
}
