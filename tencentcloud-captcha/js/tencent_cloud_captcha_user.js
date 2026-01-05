/**
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
(function () {
	function ready(fn) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', fn);
		} else {
			fn();
		}
	}

	function hide(el) {
		if (!el) return;
		el.style.display = 'none';
	}

	function show(el) {
		if (!el) return;
		el.style.display = '';
	}

	function getEnableDarkMode(config, el) {
		if (config && typeof config.enableDarkMode !== 'undefined') {
			return config.enableDarkMode;
		}
		if (el) {
			var v = el.getAttribute('data-dark-mode');
			if (v != null) {
				var s = String(v).trim().toLowerCase();
				if (s === 'force') return 'force';
				if (s === 'true' || s === '1' || s === 'yes' || s === 'on') return true;
				if (s === 'false' || s === '0' || s === 'no' || s === 'off') return false;
			}
		}
		try {
			if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
				return true;
			}
		} catch (e) { }
		return undefined;
	}

	ready(function () {
	function dbg() { }
	function diagEnv() { }

	function findScope(el) {
		if (!el) return null;
		return el.closest('form') || el.closest('p') || el.parentElement;
	}

	function detectScene(buttonEl, scopeEl) {
		var form = scopeEl ? scopeEl.closest('form') : null;
		if (form && form.querySelector('textarea[name="comment"]')) {
			return 'comment';
		}

		var path = String(window.location && window.location.pathname ? window.location.pathname : '');
		if (path.indexOf('wp-login.php') !== -1) {
			try {
				var sp = new URLSearchParams(String(window.location.search || ''));
				var action = sp.get('action');
				if (action === 'register') return 'register';
				if (action === 'lostpassword') return 'lostpassword';
			} catch (e) { }
			return 'login';
		}

		return 'login';
	}

	function bindVerifyButton(buttonEl) {
		if (!buttonEl || buttonEl.dataset.tencentcloudCaptchaBound === '1') return;
		buttonEl.dataset.tencentcloudCaptchaBound = '1';

		var scope = findScope(buttonEl);
		if (!scope) return;

		var passEl = scope.querySelector('#codePassButton, [data-tencentcloud-captcha="passed"]');
		hide(passEl);

		buttonEl.addEventListener('click', function () {
			dbg('click verify button', { href: String(window.location && window.location.href ? window.location.href : '') });
			var appId = buttonEl.getAttribute('data-appid');
			if (!appId) return;

			var config = window.TencentCloudCaptchaConfig || {};
			var scene = detectScene(buttonEl, scope);
			var options = {};
			if (config && config.aidEncrypted && config.aidEncrypted[scene]) {
				options.aidEncrypted = String(config.aidEncrypted[scene]);
			}
			var enableDarkMode = getEnableDarkMode(config, buttonEl);
			if (typeof enableDarkMode !== 'undefined') {
				options.enableDarkMode = enableDarkMode;
			}

			var ticketEl = scope.querySelector('input[name="codeVerifyTicket"]') || scope.querySelector('#codeVerifyTicket');
			var randEl = scope.querySelector('input[name="codeVerifyRandstr"]') || scope.querySelector('#codeVerifyRandstr');

			if (ticketEl) ticketEl.value = '';
			if (randEl) randEl.value = '';

			function showCaptcha() {
				if (typeof TencentCaptcha === 'undefined') {
					dbg('TencentCaptcha undefined when showCaptcha()');
					return;
				}
				dbg('captcha.show()', { appId: String(appId) });
				diagEnv('before captcha.show');
				var captcha = Object.keys(options).length ? new TencentCaptcha(appId, function (res) {
					if (res && res.ret == 0) {
						if (ticketEl) ticketEl.value = res.ticket;
						if (randEl) randEl.value = res.randstr;
						hide(buttonEl);
						show(passEl);
					}
				}, options) : new TencentCaptcha(appId, function (res) {
					if (res && res.ret == 0) {
						if (ticketEl) ticketEl.value = res.ticket;
						if (randEl) randEl.value = res.randstr;
						hide(buttonEl);
						show(passEl);
					}
				});
				captcha.show();
				setTimeout(function () {
					diagEnv('after captcha.show');
				}, 0);
			}

			ensureCaptchaSdk(showCaptcha);
		});
	}

	function initLegacyButtons() {
		document.querySelectorAll('#codeVerifyButton').forEach(bindVerifyButton);
		document.querySelectorAll('[data-tencentcloud-captcha="verify"]').forEach(bindVerifyButton);
	}

	function bindWpLoginFormSubmit(formEl) {
		if (!formEl || formEl.dataset.tencentcloudCaptchaSubmitBound === '1') return;
		formEl.dataset.tencentcloudCaptchaSubmitBound = '1';

		formEl.addEventListener('submit', function (e) {
			if (formEl.dataset.tencentcloudCaptchaSubmitting === '1') return;

			var verifyBtn = formEl.querySelector('#codeVerifyButton, [data-tencentcloud-captcha="verify"]');
			if (!verifyBtn) return;

			var ticketEl = formEl.querySelector('input[name="codeVerifyTicket"]') || formEl.querySelector('#codeVerifyTicket');
			var randEl = formEl.querySelector('input[name="codeVerifyRandstr"]') || formEl.querySelector('#codeVerifyRandstr');
			var hasToken = !!(ticketEl && randEl && ticketEl.value && randEl.value);
			if (hasToken) return;

			e.preventDefault();
			try { e.stopImmediatePropagation(); } catch (err) { }

			var appId = verifyBtn.getAttribute('data-appid');
			if (!appId) return;

			var config = window.TencentCloudCaptchaConfig || {};
			var scene = detectScene(verifyBtn, formEl);
			var options = {};
			if (config && config.aidEncrypted && config.aidEncrypted[scene]) {
				options.aidEncrypted = String(config.aidEncrypted[scene]);
			}
			var enableDarkMode = getEnableDarkMode(config, verifyBtn);
			if (typeof enableDarkMode !== 'undefined') {
				options.enableDarkMode = enableDarkMode;
			}

			if (ticketEl) ticketEl.value = '';
			if (randEl) randEl.value = '';

			function showCaptcha() {
				if (typeof TencentCaptcha === 'undefined') {
					return;
				}
				var captcha = Object.keys(options).length ? new TencentCaptcha(appId, function (res) {
					if (res && res.ret == 0) {
						if (ticketEl) ticketEl.value = res.ticket;
						if (randEl) randEl.value = res.randstr;
						try { formEl.dataset.tencentcloudCaptchaSubmitting = '1'; } catch (err) { }
						try { formEl.submit(); } catch (err) { }
					}
				}, options) : new TencentCaptcha(appId, function (res) {
					if (res && res.ret == 0) {
						if (ticketEl) ticketEl.value = res.ticket;
						if (randEl) randEl.value = res.randstr;
						try { formEl.dataset.tencentcloudCaptchaSubmitting = '1'; } catch (err) { }
						try { formEl.submit(); } catch (err) { }
					}
				});
				captcha.show();
			}

			ensureCaptchaSdk(showCaptcha);
		}, true);
	}

	function initWpLoginForms() {
		var lf = document.getElementById('loginform');
		var rf = document.getElementById('registerform');
		var pf = document.getElementById('lostpasswordform');
		if (lf) bindWpLoginFormSubmit(lf);
		if (rf) bindWpLoginFormSubmit(rf);
		if (pf) bindWpLoginFormSubmit(pf);
	}

	var sdkLoading = false;
	var sdkQueue = [];

	function flushSdkQueue() {
		var q = sdkQueue.slice();
		sdkQueue.length = 0;
		q.forEach(function (fn) {
			try { fn(); } catch (e) { }
		});
	}

	function ensureCaptchaSdk(cb) {
		if (typeof cb === 'function') {
			sdkQueue.push(cb);
		}
		if (sdkLoading) return;
		if (typeof window.TencentCaptcha !== 'undefined') {
			flushSdkQueue();
			return;
		}

		var existingScript = null;
		try { existingScript = document.querySelector('script[src*="turing.captcha.qcloud.com/TJCaptcha.js"]'); } catch (e) { }
		if (existingScript) {
			sdkLoading = true;
			try {
				if (existingScript.dataset.tencentcloudCaptchaHooked !== '1') {
					existingScript.dataset.tencentcloudCaptchaHooked = '1';
					existingScript.addEventListener('load', function () { sdkLoading = false; flushSdkQueue(); });
					existingScript.addEventListener('error', function () { sdkLoading = false; flushSdkQueue(); });
				}
			} catch (e) { }
			var start = Date.now();
			(function waitCaptcha() {
				if (typeof window.TencentCaptcha !== 'undefined') {
					sdkLoading = false;
					flushSdkQueue();
					return;
				}
				if (Date.now() - start > 8000) {
					sdkLoading = false;
					flushSdkQueue();
					return;
				}
				setTimeout(waitCaptcha, 50);
			})();
			return;
		}

		sdkLoading = true;
		var script = document.createElement('script');
		script.src = 'https://turing.captcha.qcloud.com/TJCaptcha.js';
		script.async = true;
		script.onload = function () { sdkLoading = false; flushSdkQueue(); };
		script.onerror = function () { sdkLoading = false; flushSdkQueue(); };
		var parent = document.head || document.body || document.documentElement;
		if (parent) parent.appendChild(script);
	}

	function init() {
		initLegacyButtons();
		initWpLoginForms();
	}

	try { window.TencentCloudCaptchaRunInit = init; } catch (e) { }
	try { window.TencentCloudCaptchaEnsureSdk = ensureCaptchaSdk; } catch (e) { }

	init();
	});
})();
