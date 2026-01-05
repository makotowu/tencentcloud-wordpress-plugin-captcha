<?php

if (!function_exists('tencentcloud_captcha_theme_is')) {
	function tencentcloud_captcha_theme_is($context, $slug)
	{
		if (!is_array($context) || !is_string($slug) || $slug === '') {
			return false;
		}
		return (isset($context['theme']) && $context['theme'] === $slug) || (isset($context['template']) && $context['template'] === $slug);
	}
}

$pattern = TENCENT_WORDPRESS_CAPTCHA_DIR . 'theme-adapters' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . 'adapter.php';
$adapterFiles = glob($pattern);
if (is_array($adapterFiles)) {
	foreach ($adapterFiles as $adapterFile) {
		if (is_string($adapterFile) && $adapterFile !== '' && is_file($adapterFile)) {
			require_once $adapterFile;
		}
	}
}
