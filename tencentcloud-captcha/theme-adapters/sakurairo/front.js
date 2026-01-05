(function () {
	var config = window.TencentCloudCaptchaConfig || {};

	function themeMatches() {
		var theme = '';
		var template = '';
		try { theme = String(config.theme || '').toLowerCase(); } catch (e) { theme = ''; }
		try { template = String(config.template || '').toLowerCase(); } catch (e) { template = ''; }
		var targets = {
			'sakurairo': 1,
			'sakurairo-main': 1,
			'sakurairo_main': 1,
			'sakurairo-master': 1,
			'sakurairo-theme': 1
		};
		return !!(targets[theme] || targets[template]);
	}

	if (!themeMatches()) return;
	if (String(config.commentNeedCode) !== '2') return;
	if (!config.commentAppId) return;

	function ensureCaptchaSmoothStyle() {
		try {
			if (document.getElementById('tencentcloud-captcha-sakurairo-smooth-style')) return;
		} catch (e) { }

		var css = [
			'[id^="tcaptcha"],[id^="tcaptcha"] *{transition:none !important;-webkit-transition:none !important;animation:none !important;}',
			'#tcaptcha_iframe,#tcaptcha_iframe *{transition:none !important;-webkit-transition:none !important;animation:none !important;}',
			'#tcaptcha_overlay,#tcaptcha_overlay *{transition:none !important;-webkit-transition:none !important;animation:none !important;}'
		].join('');

		var style = document.createElement('style');
		style.id = 'tencentcloud-captcha-sakurairo-smooth-style';
		style.type = 'text/css';
		style.appendChild(document.createTextNode(css));
		var parent = document.head || document.body || document.documentElement;
		if (parent) parent.appendChild(style);
	}

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

	function hideLegacyButtons() {
		var verifyBtn = null;
		var passedBtn = null;
		try { verifyBtn = document.getElementById('codeVerifyButton'); } catch (e) { verifyBtn = null; }
		try { passedBtn = document.getElementById('codePassButton'); } catch (e) { passedBtn = null; }
		if (verifyBtn) verifyBtn.style.display = 'none';
		if (passedBtn) passedBtn.style.display = 'none';
	}

	function initCommentForm(form) {
		if (!form || form.dataset.tencentcloudCaptchaInit === '1') return;
		form.dataset.tencentcloudCaptchaInit = '1';

		var ticketEl = ensureHiddenInput(form, 'codeVerifyTicket');
		var randEl = ensureHiddenInput(form, 'codeVerifyRandstr');
		var submitBtn = form.querySelector('#submit, button[type="submit"], input[type="submit"]');
		var textarea = form.querySelector('textarea[name="comment"]');

		function hasToken() {
			return !!(ticketEl && randEl && ticketEl.value && randEl.value);
		}

		function resetToken() {
			if (ticketEl) ticketEl.value = '';
			if (randEl) randEl.value = '';
			form.dataset.tencentcloudCaptchaPassed = '0';
		}

		if (textarea) {
			textarea.addEventListener('input', resetToken);
		}

		function runCaptcha(continueFn) {
			if (form.dataset.tencentcloudCaptchaVerifying === '1') return;
			form.dataset.tencentcloudCaptchaVerifying = '1';

			function onReady() {
				if (typeof window.TencentCaptcha === 'undefined') {
					form.dataset.tencentcloudCaptchaVerifying = '0';
					return;
				}

				var options = {};
				if (config && config.aidEncrypted && config.aidEncrypted.comment) {
					options.aidEncrypted = String(config.aidEncrypted.comment);
				}
				var enableDarkMode = getEnableDarkMode();
				if (typeof enableDarkMode !== 'undefined') {
					options.enableDarkMode = enableDarkMode;
				}

				var captcha = Object.keys(options).length ? new TencentCaptcha(String(config.commentAppId), function (res) {
					form.dataset.tencentcloudCaptchaVerifying = '0';
					if (res && res.ret == 0) {
						if (ticketEl) ticketEl.value = res.ticket;
						if (randEl) randEl.value = res.randstr;
						form.dataset.tencentcloudCaptchaPassed = '1';
						try { continueFn(); } catch (e) { }
					}
				}, options) : new TencentCaptcha(String(config.commentAppId), function (res) {
					form.dataset.tencentcloudCaptchaVerifying = '0';
					if (res && res.ret == 0) {
						if (ticketEl) ticketEl.value = res.ticket;
						if (randEl) randEl.value = res.randstr;
						form.dataset.tencentcloudCaptchaPassed = '1';
						try { continueFn(); } catch (e) { }
					}
				});

				captcha.show();
			}

			ensureCaptchaSdk(onReady);
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

				runCaptcha(function () {
					form.dataset.tencentcloudCaptchaBypassClick = '1';
					try { submitBtn.click(); } catch (err) { }
				});
			}, true);
		}

		form.addEventListener('submit', function (e) {
			if (hasToken()) return;
			if (form.dataset.tencentcloudCaptchaVerifying === '1') {
				e.preventDefault();
				try { e.stopImmediatePropagation(); } catch (err) { }
				return;
			}
			e.preventDefault();
			try { e.stopImmediatePropagation(); } catch (err) { }

			runCaptcha(function () {
				try {
					form.requestSubmit(submitBtn || undefined);
				} catch (err) {
					var btn = submitBtn || form.querySelector('button[type="submit"], input[type="submit"]');
					if (btn) {
						form.dataset.tencentcloudCaptchaBypassClick = '1';
						try { btn.click(); } catch (e2) { }
						return;
					}
					try { form.submit(); } catch (e3) { }
				}
			});
		}, true);
	}

	function tryInit() {
		ensureCaptchaSmoothStyle();
		hideLegacyButtons();
		var form = null;
		try { form = document.getElementById('commentform'); } catch (e) { form = null; }
		if (form) initCommentForm(form);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', tryInit);
	} else {
		tryInit();
	}

	var tries = 20;
	(function poll() {
		if (tries <= 0) return;
		tries--;
		tryInit();
		setTimeout(poll, 500);
	})();
})();
