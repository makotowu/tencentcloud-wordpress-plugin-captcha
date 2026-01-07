<?php

if (!function_exists('tencentcloud_captcha_theme_is')) {
	return;
}

if (!function_exists('tencentcloud_captcha_puock_theme_hit')) {
	function tencentcloud_captcha_puock_theme_hit($context)
	{
		if (tencentcloud_captcha_theme_is($context, 'puock') || tencentcloud_captcha_theme_is($context, 'wordpress-theme-puock')) {
			return true;
		}
		if (!is_array($context)) {
			return false;
		}
		$theme = isset($context['theme']) ? strval($context['theme']) : '';
		$template = isset($context['template']) ? strval($context['template']) : '';
		if ($theme !== '' && stripos($theme, 'puock') !== false) {
			return true;
		}
		if ($template !== '' && stripos($template, 'puock') !== false) {
			return true;
		}
		return false;
	}
}

if (!function_exists('tencentcloud_captcha_puock_any_scene_enabled')) {
	function tencentcloud_captcha_puock_any_scene_enabled()
	{
		$opts = get_option('tencent_wordpress_captcha_options');
		if (!is_array($opts)) {
			return false;
		}
		$keys = array('comment_need_code', 'login_need_code', 'register_need_code', 'lostpassword_need_code');
		foreach ($keys as $k) {
			if (isset($opts[$k]) && strval($opts[$k]) === '2') {
				return true;
			}
		}
		return false;
	}
}

add_filter('tencentcloud_captcha_should_load_assets', function ($shouldLoad, $context) {
	if (!tencentcloud_captcha_puock_theme_hit($context)) {
		return $shouldLoad;
	}
	if (isset($context['isAdmin']) && $context['isAdmin']) {
		return $shouldLoad;
	}
	if (isset($context['isLogin']) && $context['isLogin']) {
		return $shouldLoad;
	}
	if (!tencentcloud_captcha_puock_any_scene_enabled()) {
		return $shouldLoad;
	}
	return true;
}, 10, 2);

add_action('tencentcloud_captcha_enqueue_adapters', function ($assets, $context) {
	if (!tencentcloud_captcha_puock_theme_hit($context)) {
		return;
	}
	if (isset($context['isAdmin']) && $context['isAdmin']) {
		return;
	}
	if (isset($context['isLogin']) && $context['isLogin']) {
		return;
	}
	if (!tencentcloud_captcha_puock_any_scene_enabled()) {
		return;
	}
	if (!is_array($assets) || !isset($assets['front_handle'])) {
		return;
	}

	$adapterVer = isset($assets['assetVer']) ? strval($assets['assetVer']) : '';
	if (class_exists('TencentCloudCaptchaActions') && is_callable(array('TencentCloudCaptchaActions', 'assetVersion'))) {
		$adapterVer = TencentCloudCaptchaActions::assetVersion('theme-adapters/puock/front.js');
	}

	if (function_exists('wp_enqueue_script')) {
		wp_enqueue_script(
			'tencentcloud_captcha_theme_adapter_puock',
			TENCENT_WORDPRESS_CAPTCHA_URL . 'theme-adapters/puock/front.js',
			array($assets['front_handle']),
			$adapterVer,
			true
		);
	}
}, 10, 2);

