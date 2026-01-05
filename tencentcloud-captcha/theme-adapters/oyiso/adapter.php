<?php

add_filter('tencentcloud_captcha_should_load_assets', function ($shouldLoad, $context) {
	if (!tencentcloud_captcha_theme_is($context, 'oyiso')) {
		return $shouldLoad;
	}
	if (!isset($context['commentNeedCode']) || strval($context['commentNeedCode']) !== '2') {
		return $shouldLoad;
	}
	if (isset($context['isAdmin']) && $context['isAdmin']) {
		return $shouldLoad;
	}
	if (isset($context['isLogin']) && $context['isLogin']) {
		return $shouldLoad;
	}
	return true;
}, 10, 2);

add_action('tencentcloud_captcha_enqueue_adapters', function ($assets, $context) {
	if (!tencentcloud_captcha_theme_is($context, 'oyiso')) {
		return;
	}
	if (!isset($context['commentNeedCode']) || strval($context['commentNeedCode']) !== '2') {
		return;
	}
	if (!is_array($assets) || !isset($assets['front_handle'])) {
		return;
	}
	if (isset($context['isAdmin']) && $context['isAdmin']) {
		return;
	}
	if (isset($context['isLogin']) && $context['isLogin']) {
		return;
	}
}, 10, 2);

add_action('tencentcloud_captcha_output_fallback', function ($assets, $context) {
	static $done = false;
	if ($done) {
		return;
	}
	if (!tencentcloud_captcha_theme_is($context, 'oyiso')) {
		return;
	}
	if (!isset($context['commentNeedCode']) || strval($context['commentNeedCode']) !== '2') {
		return;
	}
	if (isset($context['isAdmin']) && $context['isAdmin']) {
		return;
	}
	if (isset($context['isLogin']) && $context['isLogin']) {
		return;
	}
	if (!is_array($assets) || !isset($assets['assetVer'])) {
		return;
	}
	$config = isset($assets['config']) && is_array($assets['config']) ? $assets['config'] : array();
	$ver = strval($assets['assetVer']);
	$adapterVer = $ver;
	if (class_exists('TencentCloudCaptchaActions') && is_callable(array('TencentCloudCaptchaActions', 'assetVersion'))) {
		$adapterVer = TencentCloudCaptchaActions::assetVersion('theme-adapters/oyiso/front.js');
	}

	echo '<script>window.TencentCloudCaptchaConfig=' . wp_json_encode($config) . ';</script>';
	if (function_exists('wp_enqueue_script') && function_exists('wp_print_scripts')) {
		wp_enqueue_script('jquery');
		wp_print_scripts(array('jquery'));
	}
	echo '<script src="' . esc_url('https://turing.captcha.qcloud.com/TJCaptcha.js?ver=' . rawurlencode($ver)) . '"></script>';
	echo '<script src="' . esc_url(TENCENT_WORDPRESS_CAPTCHA_JS_DIR . 'tencent_cloud_captcha_user.js?ver=' . rawurlencode($ver)) . '"></script>';
	echo '<script src="' . esc_url(TENCENT_WORDPRESS_CAPTCHA_URL . 'theme-adapters/oyiso/front.js?ver=' . rawurlencode($adapterVer)) . '"></script>';
	$done = true;
}, 10, 2);
