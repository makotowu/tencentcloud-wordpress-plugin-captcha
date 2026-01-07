<?php

if (!function_exists('tencentcloud_captcha_theme_is_sakurairo')) {
	function tencentcloud_captcha_theme_is_sakurairo($context)
	{
		if (!is_array($context)) {
			return false;
		}
		$theme = isset($context['theme']) ? strtolower(strval($context['theme'])) : '';
		$template = isset($context['template']) ? strtolower(strval($context['template'])) : '';
		$targets = array(
			'sakurairo',
			'sakurairo-theme',
		);
		return in_array($theme, $targets, true) || in_array($template, $targets, true);
	}
}

add_filter('tencentcloud_captcha_should_load_assets', function ($shouldLoad, $context) {
	if (!tencentcloud_captcha_theme_is_sakurairo($context)) {
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
	if (!tencentcloud_captcha_theme_is_sakurairo($context)) {
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
		$adapterVer = TencentCloudCaptchaActions::assetVersion('theme-adapters/sakurairo/front.js');
	}

	if (function_exists('wp_enqueue_script')) {
		wp_enqueue_script(
			'tencentcloud_captcha_theme_adapter_sakurairo',
			TENCENT_WORDPRESS_CAPTCHA_URL . 'theme-adapters/sakurairo/front.js',
			array($assets['front_handle']),
			$adapterVer,
			true
		);
	}
}, 10, 2);

add_filter('comment_form_submit_button', function ($submitButton) {
	if (!is_string($submitButton) || $submitButton === '') {
		return $submitButton;
	}
	if (!function_exists('wp_get_theme')) {
		return $submitButton;
	}
	$theme = wp_get_theme();
	if (!$theme) {
		return $submitButton;
	}
	$themeSlug = strtolower(strval($theme->get_stylesheet()));
	$templateSlug = strtolower(strval($theme->get_template()));
	$targets = array(
		'sakurairo',
		'sakurairo-theme',
	);
	if (!in_array($themeSlug, $targets, true) && !in_array($templateSlug, $targets, true)) {
		return $submitButton;
	}

	$submitButton = preg_replace('/<input[^>]*\bid=["\']codeVerifyButton["\'][^>]*>\s*/i', '', $submitButton);
	$submitButton = preg_replace('/<input[^>]*\bid=["\']codePassButton["\'][^>]*>\s*/i', '', $submitButton);

	return $submitButton;
}, 20, 1);

