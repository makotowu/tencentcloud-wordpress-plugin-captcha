(function () {
	var config = window.TencentCloudCaptchaConfig || {};
	if (config.theme !== 'oyiso' && config.template !== 'oyiso') return;
	if (String(config.commentNeedCode) !== '2') return;
	if (!config.commentAppId) return;

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

	var navHooked = false;

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

	function tryInit() {
		document.querySelectorAll('form.commentForm').forEach(function (form) {
			if (form.dataset.tencentcloudCaptchaInit === '1') return;
			form.dataset.tencentcloudCaptchaInit = '1';

			var ticketEl = ensureHiddenInput(form, 'codeVerifyTicket');
			var randEl = ensureHiddenInput(form, 'codeVerifyRandstr');
			var textarea = form.querySelector('textarea[name="comment"]');

			function resetToken() {
				if (ticketEl) ticketEl.value = '';
				if (randEl) randEl.value = '';
				form.dataset.tencentcloudCaptchaPassed = '0';
			}

			if (textarea) {
				textarea.addEventListener('input', resetToken);
			}

			form.addEventListener('submit', function (e) {
				if (ticketEl && randEl && ticketEl.value && randEl.value) return;
				if (form.dataset.tencentcloudCaptchaVerifying === '1') {
					e.preventDefault();
					e.stopImmediatePropagation();
					return;
				}

				e.preventDefault();
				e.stopImmediatePropagation();
				form.dataset.tencentcloudCaptchaVerifying = '1';

				function runCaptcha() {
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
							try {
								form.requestSubmit();
							} catch (err) {
								var submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
								if (submitBtn) submitBtn.click();
							}
						}
					}, options) : new TencentCaptcha(String(config.commentAppId), function (res) {
						form.dataset.tencentcloudCaptchaVerifying = '0';
						if (res && res.ret == 0) {
							if (ticketEl) ticketEl.value = res.ticket;
							if (randEl) randEl.value = res.randstr;
							form.dataset.tencentcloudCaptchaPassed = '1';
							try {
								form.requestSubmit();
							} catch (err) {
								var submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
								if (submitBtn) submitBtn.click();
							}
						}
					});
					captcha.show();
				}

				ensureCaptchaSdk(runCaptcha);
			}, true);
		});

		return true;
	}

	function hookNavReinit() {
		if (navHooked) return;
		navHooked = true;
		try {
			if (window.__TencentCloudCaptchaOyisoNavHooked === '1') return;
			window.__TencentCloudCaptchaOyisoNavHooked = '1';
		} catch (e) { }

		var pendingHref = '';

		function isLikelyPostPage() {
			var path = '';
			var search = '';
			try { path = String(window.location && window.location.pathname ? window.location.pathname : ''); } catch (e) { path = ''; }
			try { search = String(window.location && window.location.search ? window.location.search : ''); } catch (e) { search = ''; }

			if (/\.html$/i.test(path)) return true;
			if (/[?&]p=\d+/.test(search)) return true;
			if (/\/\d+\/?$/.test(path)) return true;
			return false;
		}

		function waitLoadBarAndReload() {
			var maxTry = 30;
			(function waitBar() {
				var settled = true;
				try {
					var bars = document.querySelectorAll('.load-bar');
					if (bars && bars.length) {
						for (var i = 0; i < bars.length; i++) {
							if (!bars[i].classList || !bars[i].classList.contains('ready')) {
								settled = false;
								break;
							}
						}
					}
				} catch (err) { settled = true; }

				if (settled || maxTry <= 0) {
					window.location.reload();
					return;
				}
				maxTry--;
				setTimeout(waitBar, 100);
			})();
		}

		function scheduleReloadCheck() {
			var href = String(window.location && window.location.href ? window.location.href : '');
			if (!href) return;
			if (pendingHref === href) return;
			pendingHref = href;

			var key = '__tencentcloud_captcha_oyiso_last_reload_href';
			var lastHref = '';
			try { lastHref = String(sessionStorage.getItem(key) || ''); } catch (err) { lastHref = ''; }
			if (lastHref === href) {
				pendingHref = '';
				return;
			}

			if (!isLikelyPostPage()) {
				pendingHref = '';
				return;
			}

			try { sessionStorage.setItem(key, href); } catch (err) { }
			waitLoadBarAndReload();
		}

		function onNav(e) {
			var type = '';
			try { type = String(e && e.type ? e.type : ''); } catch (err) { type = ''; }
			try {
				if (type === 'pjax:fetch' || type === 'pjax:content' || type === 'pjax:ready' || type === 'popstate' || type === 'hashchange') {
					scheduleReloadCheck();
					return;
				}
			} catch (err) { }
		}

		[
			'pjax:fetch',
			'pjax:ready',
			'pjax:content'
		].forEach(function (evt) {
			try { document.addEventListener(evt, onNav); } catch (e) { }
			try { window.addEventListener(evt, onNav); } catch (e) { }
		});

		try { window.addEventListener('popstate', onNav); } catch (e) { }
		try { window.addEventListener('hashchange', onNav); } catch (e) { }

		try {
			if (window.__TencentCloudCaptchaOyisoHistoryHooked !== '1') {
				window.__TencentCloudCaptchaOyisoHistoryHooked = '1';
				var pushState = history.pushState;
				var replaceState = history.replaceState;
				if (typeof pushState === 'function') {
					history.pushState = function () {
						var ret = pushState.apply(this, arguments);
						setTimeout(scheduleReloadCheck, 0);
						return ret;
					};
				}
				if (typeof replaceState === 'function') {
					history.replaceState = function () {
						var ret = replaceState.apply(this, arguments);
						setTimeout(scheduleReloadCheck, 0);
						return ret;
					};
				}
			}
		} catch (e) { }
	}

	tryInit();
	hookNavReinit();
})();
