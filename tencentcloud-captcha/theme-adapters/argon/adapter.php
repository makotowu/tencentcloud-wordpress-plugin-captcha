<?php

add_filter('tencentcloud_captcha_should_load_assets', function ($shouldLoad, $context) {
	if (!tencentcloud_captcha_theme_is($context, 'argon') && !tencentcloud_captcha_theme_is($context, 'argon-theme-master')) {
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
	if (!tencentcloud_captcha_theme_is($context, 'argon') && !tencentcloud_captcha_theme_is($context, 'argon-theme-master')) {
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

	$adapterVer = isset($assets['assetVer']) ? strval($assets['assetVer']) : '';
	if (class_exists('TencentCloudCaptchaActions') && is_callable(array('TencentCloudCaptchaActions', 'assetVersion'))) {
		$adapterVer = TencentCloudCaptchaActions::assetVersion('theme-adapters/argon/front.js');
	}

	if (function_exists('wp_enqueue_script')) {
		wp_enqueue_script(
			'tencentcloud_captcha_theme_adapter_argon',
			TENCENT_WORDPRESS_CAPTCHA_URL . 'theme-adapters/argon/front.js',
			array($assets['front_handle']),
			$adapterVer,
			true
		);
	}
}, 10, 2);

