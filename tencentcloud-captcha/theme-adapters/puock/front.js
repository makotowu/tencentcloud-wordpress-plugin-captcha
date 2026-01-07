(function () {
	var config = window.TencentCloudCaptchaConfig || {};

	function themeMatches() {
		var theme = '';
		var template = '';
		try { theme = String(config.theme || '').toLowerCase(); } catch (e) { theme = ''; }
		try { template = String(config.template || '').toLowerCase(); } catch (e) { template = ''; }
		if (theme === 'puock' || template === 'puock') return true;
		if (theme === 'wordpress-theme-puock' || template === 'wordpress-theme-puock') return true;
		if (theme && theme.indexOf('puock') !== -1) return true;
		if (template && template.indexOf('puock') !== -1) return true;
		return false;
	}

	if (!themeMatches()) return;

	var needComment = String(config.commentNeedCode) === '2' && !!config.commentAppId;
	var needLogin = String(config.loginNeedCode) === '2' && !!config.loginAppId;
	if (!needComment && !needLogin) return;

	function ensureHiddenInput(form, name) {
		if (!form) return null;
		var existing = form.querySelector('input[name="' + name + '"]');
		if (existing) return existing;
		var input = document.createElement('input');
		input.type = 'hidden';
		input.name = name;
		form.appendChild(input);
		return input;
	}

	function getEnableDarkMode() {
		if (config && typeof config.enableDarkMode !== 'undefined') {
			return config.enableDarkMode;
		}
		try {
			if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
				return true;
			}
		} catch (e) { }
		return undefined;
	}

	function ensureCaptchaSdk(cb) {
		if (typeof cb !== 'function') return;
		try {
			var fn = typeof window.TencentCloudCaptchaEnsureSdk === 'function' ? window.TencentCloudCaptchaEnsureSdk : null;
			if (fn) {
				fn(cb);
				return;
			}
		} catch (e) { }

		if (typeof window.TencentCaptcha !== 'undefined') {
			try { cb(); } catch (e) { }
			return;
		}

		var existingScript = null;
		try { existingScript = document.querySelector('script[src*="turing.captcha.qcloud.com/TJCaptcha.js"]'); } catch (e) { }
		if (existingScript) {
			var start = Date.now();
			(function waitCaptcha() {
				if (typeof window.TencentCaptcha !== 'undefined') {
					try { cb(); } catch (e) { }
					return;
				}
				if (Date.now() - start > 8000) {
					try { cb(); } catch (e) { }
					return;
				}
				setTimeout(waitCaptcha, 50);
			})();
			return;
		}

		var script = document.createElement('script');
		script.src = 'https://turing.captcha.qcloud.com/TJCaptcha.js';
		script.async = true;
		script.onload = function () { try { cb(); } catch (e) { } };
		script.onerror = function () { try { cb(); } catch (e) { } };
		var parent = document.head || document.body || document.documentElement;
		if (parent) parent.appendChild(script);
	}

	function initSceneForm(form, scene, appId) {
		if (!form) return;
		var initAttr = 'data-tencentcloud-captcha-init-' + scene;
		try {
			if (form.getAttribute(initAttr) === '1') return;
			form.setAttribute(initAttr, '1');
		} catch (e) { }

		var ticketEl = ensureHiddenInput(form, 'codeVerifyTicket');
		var randEl = ensureHiddenInput(form, 'codeVerifyRandstr');
		var submitBtn = null;
		try { submitBtn = form.querySelector('button[type="submit"], input[type="submit"]'); } catch (e) { submitBtn = null; }

		function hasToken() {
			return !!(ticketEl && randEl && ticketEl.value && randEl.value);
		}

		function resetToken() {
			if (ticketEl) ticketEl.value = '';
			if (randEl) randEl.value = '';
			form.dataset.tencentcloudCaptchaPassed = '0';
		}

		if (submitBtn) {
			submitBtn.addEventListener('click', function (e) {
				if (form.dataset.tencentcloudCaptchaBypassClick === '1') {
					form.dataset.tencentcloudCaptchaBypassClick = '0';
					return;
				}
				if (hasToken()) return;
				if (form.dataset.tencentcloudCaptchaVerifying === '1') {
					e.preventDefault();
					try { e.stopImmediatePropagation(); } catch (err) { }
					return;
				}
				e.preventDefault();
				try { e.stopImmediatePropagation(); } catch (err) { }
				form.dataset.tencentcloudCaptchaVerifying = '1';

				function runCaptchaForClick() {
					if (typeof window.TencentCaptcha === 'undefined') {
						form.dataset.tencentcloudCaptchaVerifying = '0';
						return;
					}

					var options = {};
					if (config && config.aidEncrypted && config.aidEncrypted[scene]) {
						options.aidEncrypted = String(config.aidEncrypted[scene]);
					}
					var enableDarkMode = getEnableDarkMode();
					if (typeof enableDarkMode !== 'undefined') {
						options.enableDarkMode = enableDarkMode;
					}

					var captcha = Object.keys(options).length ? new TencentCaptcha(String(appId), function (res) {
						form.dataset.tencentcloudCaptchaVerifying = '0';
						if (res && res.ret == 0) {
							if (ticketEl) ticketEl.value = res.ticket;
							if (randEl) randEl.value = res.randstr;
							form.dataset.tencentcloudCaptchaPassed = '1';
							form.dataset.tencentcloudCaptchaBypassClick = '1';
							try { submitBtn.click(); } catch (e2) { }
						}
					}, options) : new TencentCaptcha(String(appId), function (res) {
						form.dataset.tencentcloudCaptchaVerifying = '0';
						if (res && res.ret == 0) {
							if (ticketEl) ticketEl.value = res.ticket;
							if (randEl) randEl.value = res.randstr;
							form.dataset.tencentcloudCaptchaPassed = '1';
							form.dataset.tencentcloudCaptchaBypassClick = '1';
							try { submitBtn.click(); } catch (e2) { }
						}
					});
					captcha.show();
				}

				ensureCaptchaSdk(runCaptchaForClick);
			}, true);
		}

		try {
			form.addEventListener('input', function (e) {
				var t = e && e.target ? e.target : null;
				if (!t) return;
				var name = '';
				try { name = String(t.getAttribute && t.getAttribute('name') ? t.getAttribute('name') : ''); } catch (err) { name = ''; }
				if (name === 'codeVerifyTicket' || name === 'codeVerifyRandstr') return;
				resetToken();
			}, true);
		} catch (e) { }

		form.addEventListener('submit', function (e) {
			if (form.dataset.tencentcloudCaptchaBypassSubmit === '1') {
				form.dataset.tencentcloudCaptchaBypassSubmit = '0';
				return;
			}
			if (hasToken()) return;
			if (form.dataset.tencentcloudCaptchaVerifying === '1') {
				e.preventDefault();
				try { e.stopImmediatePropagation(); } catch (err) { }
				return;
			}

			e.preventDefault();
			try { e.stopImmediatePropagation(); } catch (err) { }
			form.dataset.tencentcloudCaptchaVerifying = '1';

			function runCaptcha() {
				if (typeof window.TencentCaptcha === 'undefined') {
					form.dataset.tencentcloudCaptchaVerifying = '0';
					return;
				}

				var options = {};
				if (config && config.aidEncrypted && config.aidEncrypted[scene]) {
					options.aidEncrypted = String(config.aidEncrypted[scene]);
				}
				var enableDarkMode = getEnableDarkMode();
				if (typeof enableDarkMode !== 'undefined') {
					options.enableDarkMode = enableDarkMode;
				}

				var captcha = Object.keys(options).length ? new TencentCaptcha(String(appId), function (res) {
					form.dataset.tencentcloudCaptchaVerifying = '0';
					if (res && res.ret == 0) {
						if (ticketEl) ticketEl.value = res.ticket;
						if (randEl) randEl.value = res.randstr;
						form.dataset.tencentcloudCaptchaPassed = '1';
						try {
							form.dataset.tencentcloudCaptchaBypassSubmit = '1';
							form.requestSubmit(submitBtn || undefined);
						} catch (err) {
							if (submitBtn) {
								try { submitBtn.click(); } catch (e2) { }
							} else {
								try { form.submit(); } catch (e2) { }
							}
						}
					}
				}, options) : new TencentCaptcha(String(appId), function (res) {
					form.dataset.tencentcloudCaptchaVerifying = '0';
					if (res && res.ret == 0) {
						if (ticketEl) ticketEl.value = res.ticket;
						if (randEl) randEl.value = res.randstr;
						form.dataset.tencentcloudCaptchaPassed = '1';
						try {
							form.dataset.tencentcloudCaptchaBypassSubmit = '1';
							form.requestSubmit(submitBtn || undefined);
						} catch (err) {
							if (submitBtn) {
								try { submitBtn.click(); } catch (e2) { }
							} else {
								try { form.submit(); } catch (e2) { }
							}
						}
					}
				});
				captcha.show();
			}

			ensureCaptchaSdk(runCaptcha);
		}, true);
	}

	function tryInit() {
		if (needComment) {
			var commentForm = null;
			try { commentForm = document.getElementById('comment-form'); } catch (e) { commentForm = null; }
			if (commentForm) initSceneForm(commentForm, 'comment', config.commentAppId);
		}

		if (needLogin) {
			var loginForm = null;
			try { loginForm = document.getElementById('front-login-form'); } catch (e) { loginForm = null; }
			if (loginForm) initSceneForm(loginForm, 'login', config.loginAppId);
		}
	}

	var hooked = false;
	function hookNavReinit() {
		if (hooked) return;
		hooked = true;

		try {
			if (window.InstantClick && typeof window.InstantClick.on === 'function') {
				window.InstantClick.on('change', function () {
					setTimeout(tryInit, 0);
				});
			}
		} catch (e) { }

		try { window.addEventListener('popstate', function () { setTimeout(tryInit, 0); }); } catch (e) { }
		try { window.addEventListener('hashchange', function () { setTimeout(tryInit, 0); }); } catch (e) { }
	}

	function getAjaxHost() {
		try {
			if (window.jQuery && window.jQuery.ajax) return window.jQuery;
		} catch (e) { }
		try {
			if (window.$ && window.$.ajax) return window.$;
		} catch (e) { }
		try {
			if (typeof jQuery !== 'undefined' && jQuery.ajax) return jQuery;
		} catch (e) { }
		return null;
	}

	function resetCommentToken() {
		var form = null;
		try { form = document.getElementById('comment-form'); } catch (e) { form = null; }
		if (!form) return;
		try {
			var ticketEl = form.querySelector('input[name="codeVerifyTicket"]');
			var randEl = form.querySelector('input[name="codeVerifyRandstr"]');
			if (ticketEl) ticketEl.value = '';
			if (randEl) randEl.value = '';
		} catch (e) { }
		try { form.dataset.tencentcloudCaptchaPassed = '0'; } catch (e) { }
		try { form.dataset.tencentcloudCaptchaVerifying = '0'; } catch (e) { }
	}

	function hookAjaxReset() {
		var $ = getAjaxHost();
		if (!$ || !$.ajax) return false;
		if ($.ajax && $.ajax.__TencentCloudCaptchaPuockWrapped === '1') return true;
		var originalAjax = $.ajax;
		if (typeof originalAjax !== 'function') return false;

		function wrappedAjax(urlOrOptions, optionsMaybe) {
			var isUrlStyle = typeof urlOrOptions === 'string';
			var ajaxOptions = isUrlStyle ? (optionsMaybe || {}) : (urlOrOptions || {});
			if (isUrlStyle) {
				ajaxOptions = Object.assign({ url: urlOrOptions }, ajaxOptions);
			}

			var url = '';
			try { url = String(ajaxOptions.url || ''); } catch (e) { url = ''; }
			var isCommentAjax = false;
			try {
				if (url && url.indexOf('admin-ajax.php') !== -1 && url.indexOf('action=comment_ajax') !== -1) {
					isCommentAjax = true;
				}
			} catch (e) { isCommentAjax = false; }
			if (!isCommentAjax) {
				try {
					var data = ajaxOptions.data;
					if (data && typeof data === 'object' && String(data.action || '') === 'comment_ajax') {
						isCommentAjax = true;
					} else if (typeof data === 'string' && data.indexOf('action=comment_ajax') !== -1) {
						isCommentAjax = true;
					}
				} catch (e) { }
			}

			var jqxhr = originalAjax.apply(this, arguments);
			if (needComment && isCommentAjax && jqxhr && typeof jqxhr.always === 'function') {
				try {
					jqxhr.always(function () {
						resetCommentToken();
					});
				} catch (e) { }
			}
			return jqxhr;
		}

		try { wrappedAjax.__TencentCloudCaptchaPuockWrapped = '1'; } catch (e) { }
		try { wrappedAjax.__TencentCloudCaptchaPuockOriginalAjax = originalAjax; } catch (e) { }
		$.ajax = wrappedAjax;
		return true;
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			tryInit();
			hookNavReinit();
			hookAjaxReset();
		});
	} else {
		tryInit();
		hookNavReinit();
		hookAjaxReset();
	}

	var tries = 20;
	(function poll() {
		if (tries <= 0) return;
		tries--;
		tryInit();
		setTimeout(poll, 500);
	})();
})();
