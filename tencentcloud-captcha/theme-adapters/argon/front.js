(function () {
	var config = window.TencentCloudCaptchaConfig || {};
	if (
		config.theme !== 'argon' &&
		config.template !== 'argon' &&
		config.theme !== 'argon-theme-master' &&
		config.template !== 'argon-theme-master'
	) {
		return;
	}
	if (String(config.commentNeedCode) !== '2') return;
	if (!config.commentAppId) return;

	var state = {
		ticket: '',
		randstr: '',
		verifying: false,
		queue: []
	};

	function resetToken() {
		state.ticket = '';
		state.randstr = '';
	}

	function getEnableDarkMode() {
		try {
			if (document && document.documentElement && document.documentElement.classList) {
				if (document.documentElement.classList.contains('darkmode')) return true;
			}
		} catch (e) { }
		try {
			if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
				return true;
			}
		} catch (e) { }
		return undefined;
	}

	function ensureCaptchaSdk(cb) {
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

	function showCancelToast() {
		try {
			if (window.iziToast && typeof window.iziToast.show === 'function') {
				window.iziToast.show({
					title: '请先进行人机验证',
					class: 'shadow-sm',
					position: 'topRight',
					backgroundColor: '#f5365c',
					titleColor: '#ffffff',
					messageColor: '#ffffff',
					iconColor: '#ffffff',
					progressBarColor: '#ffffff',
					icon: 'fa fa-close',
					timeout: 5000
				});
				return;
			}
		} catch (e) { }
	}

	function runCaptcha(done) {
		ensureCaptchaSdk(function () {
			if (typeof window.TencentCaptcha === 'undefined') {
				try { done(null); } catch (e) { }
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
				try { done(res); } catch (e) { }
			}, options) : new TencentCaptcha(String(config.commentAppId), function (res) {
				try { done(res); } catch (e) { }
			});
			captcha.show();
		});
	}

	function ensureToken(cb) {
		if (state.ticket && state.randstr) {
			try { cb(true); } catch (e) { }
			return;
		}
		state.queue.push(cb);
		if (state.verifying) return;
		state.verifying = true;
		runCaptcha(function (res) {
			state.verifying = false;
			if (res && res.ret == 0 && res.ticket && res.randstr) {
				state.ticket = res.ticket;
				state.randstr = res.randstr;
			} else {
				resetToken();
				showCancelToast();
			}
			var q = state.queue.slice();
			state.queue.length = 0;
			q.forEach(function (fn) {
				try { fn(!!(state.ticket && state.randstr)); } catch (e) { }
			});
		});
	}

	function getCaptchaErrorHeader(jqxhr) {
		try {
			if (!jqxhr || typeof jqxhr.getResponseHeader !== 'function') return '';
			var v = jqxhr.getResponseHeader('X-TencentCloud-Captcha-Error');
			if (!v) return '';
			return String(v).trim().toLowerCase();
		} catch (e) {
			return '';
		}
	}

	function isAdminAjaxUrl(url) {
		var u = '';
		try { u = String(url || ''); } catch (e) { u = ''; }
		if (!u) return false;
		try {
			if (config && config.ajaxUrl) {
				var au = String(config.ajaxUrl || '');
				if (au && u.indexOf(au) !== -1) return true;
				if (au && au.indexOf(u) !== -1) return true;
			}
		} catch (e) { }
		return u.indexOf('admin-ajax.php') !== -1;
	}

	function getActionFromBody(body) {
		try {
			if (!body) return '';
			if (typeof body === 'string') {
				try {
					var sp = new URLSearchParams(body);
					return sp.get('action') ? String(sp.get('action')) : '';
				} catch (e) {
					if (body.indexOf('action=ajax_post_comment') !== -1) return 'ajax_post_comment';
					return '';
				}
			}
			if (typeof URLSearchParams !== 'undefined' && body instanceof URLSearchParams) {
				return body.get('action') ? String(body.get('action')) : '';
			}
			if (typeof FormData !== 'undefined' && body instanceof FormData) {
				var a = body.get('action');
				return a ? String(a) : '';
			}
			if (typeof body === 'object' && body.action) {
				return String(body.action);
			}
		} catch (e) { }
		return '';
	}

	function bodyHasToken(body) {
		try {
			if (!body) return false;
			if (typeof body === 'string') {
				return body.indexOf('codeVerifyTicket=') !== -1 && body.indexOf('codeVerifyRandstr=') !== -1;
			}
			if (typeof URLSearchParams !== 'undefined' && body instanceof URLSearchParams) {
				return !!(body.get('codeVerifyTicket') && body.get('codeVerifyRandstr'));
			}
			if (typeof FormData !== 'undefined' && body instanceof FormData) {
				return !!(body.get('codeVerifyTicket') && body.get('codeVerifyRandstr'));
			}
			if (typeof body === 'object') {
				return !!(body.codeVerifyTicket && body.codeVerifyRandstr);
			}
		} catch (e) { }
		return false;
	}

	function applyTokenToBody(body) {
		if (!state.ticket || !state.randstr) return body;
		try {
			if (!body) return body;
			if (typeof body === 'string') {
				try {
					var sp = new URLSearchParams(body);
					if (!sp.get('codeVerifyTicket')) sp.set('codeVerifyTicket', state.ticket);
					if (!sp.get('codeVerifyRandstr')) sp.set('codeVerifyRandstr', state.randstr);
					return sp.toString();
				} catch (e) {
					return body;
				}
			}
			if (typeof URLSearchParams !== 'undefined' && body instanceof URLSearchParams) {
				if (!body.get('codeVerifyTicket')) body.set('codeVerifyTicket', state.ticket);
				if (!body.get('codeVerifyRandstr')) body.set('codeVerifyRandstr', state.randstr);
				return body;
			}
			if (typeof FormData !== 'undefined' && body instanceof FormData) {
				if (!body.get('codeVerifyTicket')) body.append('codeVerifyTicket', state.ticket);
				if (!body.get('codeVerifyRandstr')) body.append('codeVerifyRandstr', state.randstr);
				return body;
			}
			if (typeof body === 'object') {
				if (!body.codeVerifyTicket) body.codeVerifyTicket = state.ticket;
				if (!body.codeVerifyRandstr) body.codeVerifyRandstr = state.randstr;
				return body;
			}
		} catch (e) { }
		return body;
	}

	function hookXhr() {
		if (!window.XMLHttpRequest || !window.XMLHttpRequest.prototype) return false;
		var proto = window.XMLHttpRequest.prototype;
		if (proto.send && proto.send.__TencentCloudCaptchaArgonWrapped === '1') return true;
		if (typeof proto.open !== 'function' || typeof proto.send !== 'function') return false;

		var originalOpen = proto.open;
		var originalSend = proto.send;

		proto.open = function (method, url) {
			try {
				this.__tencentcloudCaptchaArgonReq = {
					method: String(method || '').toUpperCase(),
					url: String(url || '')
				};
			} catch (e) { }
			return originalOpen.apply(this, arguments);
		};

		proto.send = function (body) {
			try {
				var req = this.__tencentcloudCaptchaArgonReq || {};
				var url = req.url || '';
				var method = req.method || '';
				if (method === 'POST' && isAdminAjaxUrl(url) && getActionFromBody(body) === 'ajax_post_comment') {
					if (!bodyHasToken(body) && this.__tencentcloudCaptchaArgonWaiting !== '1') {
						this.__tencentcloudCaptchaArgonWaiting = '1';
						var xhr = this;
						ensureToken(function (ok) {
							try { xhr.__tencentcloudCaptchaArgonWaiting = '0'; } catch (e) { }
							if (!ok) {
								resetToken();
								try { xhr.abort(); } catch (e) { }
								return;
							}
							var newBody = applyTokenToBody(body);
							originalSend.call(xhr, newBody);
							resetToken();
						});
						return;
					}
				}
			} catch (e) { }
			return originalSend.apply(this, arguments);
		};

		try { proto.send.__TencentCloudCaptchaArgonWrapped = '1'; } catch (e) { }
		return true;
	}

	function hookFetch() {
		if (!window.fetch || window.fetch.__TencentCloudCaptchaArgonWrapped === '1') return false;
		if (typeof window.fetch !== 'function') return false;
		var originalFetch = window.fetch;

		function ensureTokenPromise() {
			return new Promise(function (resolve) {
				try { ensureToken(resolve); } catch (e) { resolve(false); }
			});
		}

		function wrappedFetch(input, init) {
			var url = '';
			try { url = typeof input === 'string' ? input : (input && input.url ? String(input.url) : ''); } catch (e) { url = ''; }
			var opts = init || {};
			var method = '';
			try { method = String(opts.method || (input && input.method ? input.method : '') || 'GET').toUpperCase(); } catch (e) { method = 'GET'; }
			var body = opts.body;

			if (method === 'POST' && isAdminAjaxUrl(url) && getActionFromBody(body) === 'ajax_post_comment' && !bodyHasToken(body)) {
				return ensureTokenPromise().then(function (ok) {
					if (!ok) {
						resetToken();
						throw new Error('tencentcloud_captcha_cancelled');
					}
					var newInit = Object.assign({}, opts);
					newInit.body = applyTokenToBody(body);
					resetToken();
					return originalFetch.call(this, input, newInit);
				}.bind(this));
			}
			return originalFetch.apply(this, arguments);
		}

		try { wrappedFetch.__TencentCloudCaptchaArgonWrapped = '1'; } catch (e) { }
		try { wrappedFetch.__TencentCloudCaptchaArgonOriginalFetch = originalFetch; } catch (e) { }
		window.fetch = wrappedFetch;
		try { window.fetch.__TencentCloudCaptchaArgonWrapped = '1'; } catch (e) { }
		return true;
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

	function hookAjax() {
		var $ = getAjaxHost();
		if (!$ || !$.ajax) return false;
		if (typeof $.Deferred !== 'function') return false;
		if ($.ajax && $.ajax.__TencentCloudCaptchaArgonWrapped === '1') return true;

		var originalAjax = $.ajax;
		if (typeof originalAjax !== 'function') return false;

		function wrappedAjax(urlOrOptions, optionsMaybe) {
			var isUrlStyle = typeof urlOrOptions === 'string';
			var ajaxOptions = isUrlStyle ? (optionsMaybe || {}) : (urlOrOptions || {});
			if (isUrlStyle) {
				ajaxOptions = Object.assign({ url: urlOrOptions }, ajaxOptions);
			}

			var data = ajaxOptions.data;
			var action = '';
			try {
				if (data && typeof data === 'object') {
					action = data.action ? String(data.action) : '';
				} else if (typeof data === 'string') {
					try {
						var sp = new URLSearchParams(data);
						action = sp.get('action') ? String(sp.get('action')) : '';
					} catch (e) { action = ''; }
				}
			} catch (e) { action = ''; }

			if (action !== 'ajax_post_comment') {
				return originalAjax.apply(this, arguments);
			}

			function hasToken(opts) {
				var d = opts ? opts.data : null;
				if (d && typeof d === 'object' && d.codeVerifyTicket && d.codeVerifyRandstr) return true;
				if (typeof d === 'string' && d.indexOf('codeVerifyTicket=') !== -1 && d.indexOf('codeVerifyRandstr=') !== -1) return true;
				return false;
			}

			function applyToken(opts) {
				if (!state.ticket || !state.randstr) return;
				if (!opts) return;
				var d = opts.data;
				if (d && typeof d === 'object') {
					if (!d.codeVerifyTicket) d.codeVerifyTicket = state.ticket;
					if (!d.codeVerifyRandstr) d.codeVerifyRandstr = state.randstr;
					return;
				}
				if (typeof d === 'string') {
					try {
						var sp2 = new URLSearchParams(d);
						if (!sp2.get('codeVerifyTicket')) sp2.set('codeVerifyTicket', state.ticket);
						if (!sp2.get('codeVerifyRandstr')) sp2.set('codeVerifyRandstr', state.randstr);
						opts.data = sp2.toString();
					} catch (e) { }
				}
			}

			if (hasToken(ajaxOptions)) {
				return originalAjax.call(this, ajaxOptions);
			}

			var originalError = ajaxOptions.error;
			ajaxOptions.error = function (jqxhr) {
				var capErr = getCaptchaErrorHeader(jqxhr);
				if (capErr === 'required' || capErr === 'verify_failed') {
					return;
				}
				if (typeof originalError === 'function') {
					return originalError.apply(this, arguments);
				}
			};

			var dfd = $.Deferred();
			ensureToken(function (ok) {
				if (!ok) {
					resetToken();
					var fakeRes = {
						status: 'failed',
						msg: '请先进行人机验证',
						isAdmin: false
					};
					try {
						if (typeof ajaxOptions.success === 'function') {
							ajaxOptions.success(fakeRes, 'success', null);
						}
					} catch (e) { }
					try {
						if (typeof ajaxOptions.complete === 'function') {
							ajaxOptions.complete(null, 'success');
						}
					} catch (e) { }
					dfd.resolve(fakeRes, 'success', null);
					return;
				}
				applyToken(ajaxOptions);
				var jqxhr = originalAjax.call(this, ajaxOptions);
				jqxhr.done(function () {
					resetToken();
					dfd.resolveWith(this, arguments);
				});
				jqxhr.fail(function (failedXhr) {
					var capErr = getCaptchaErrorHeader(failedXhr);
					if ((capErr === 'required' || capErr === 'verify_failed') && ajaxOptions.__tencentcloudCaptchaRetried !== '1') {
						ajaxOptions.__tencentcloudCaptchaRetried = '1';
						resetToken();
						ensureToken(function (retryOk) {
							if (!retryOk) {
								resetToken();
								var fakeRes2 = {
									status: 'failed',
									msg: '请先进行人机验证',
									isAdmin: false
								};
								try {
									if (typeof ajaxOptions.success === 'function') {
										ajaxOptions.success(fakeRes2, 'success', null);
									}
								} catch (e) { }
								try {
									if (typeof ajaxOptions.complete === 'function') {
										ajaxOptions.complete(null, 'success');
									}
								} catch (e) { }
								dfd.resolve(fakeRes2, 'success', null);
								return;
							}
							applyToken(ajaxOptions);
							var jqxhr2 = originalAjax.call(this, ajaxOptions);
							jqxhr2.done(function () {
								resetToken();
								dfd.resolveWith(this, arguments);
							});
							jqxhr2.fail(function (failedXhr2) {
								var capErr2 = getCaptchaErrorHeader(failedXhr2);
								if (capErr2 === 'required' || capErr2 === 'verify_failed') {
									resetToken();
									var fakeRes3 = {
										status: 'failed',
										msg: capErr2 === 'verify_failed' ? '验证码验证失败，请重新验证' : '请先进行人机验证',
										isAdmin: false
									};
									try {
										if (typeof ajaxOptions.success === 'function') {
											ajaxOptions.success(fakeRes3, 'success', null);
										}
									} catch (e) { }
									try {
										if (typeof ajaxOptions.complete === 'function') {
											ajaxOptions.complete(null, 'success');
										}
									} catch (e) { }
									dfd.resolve(fakeRes3, 'success', null);
									return;
								}
								resetToken();
								dfd.rejectWith(this, arguments);
							});
						}.bind(this));
						return;
					}
					if (capErr === 'required' || capErr === 'verify_failed') {
						resetToken();
						var fakeRes4 = {
							status: 'failed',
							msg: capErr === 'verify_failed' ? '验证码验证失败，请重新验证' : '请先进行人机验证',
							isAdmin: false
						};
						try {
							if (typeof ajaxOptions.success === 'function') {
								ajaxOptions.success(fakeRes4, 'success', null);
							}
						} catch (e) { }
						try {
							if (typeof ajaxOptions.complete === 'function') {
								ajaxOptions.complete(null, 'success');
							}
						} catch (e) { }
						dfd.resolve(fakeRes4, 'success', null);
						return;
					}
					resetToken();
					dfd.rejectWith(this, arguments);
				});
			}.bind(this));
			return dfd.promise();
		}

		try { wrappedAjax.__TencentCloudCaptchaArgonWrapped = '1'; } catch (e) { }
		try { wrappedAjax.__TencentCloudCaptchaArgonOriginalAjax = originalAjax; } catch (e) { }
		$.ajax = wrappedAjax;

		return true;
	}

	function install() {
		try {
			config = window.TencentCloudCaptchaConfig || config || {};
		} catch (e) { }

		try {
			if (window.__TencentCloudCaptchaArgonInputHooked !== '1') {
				window.__TencentCloudCaptchaArgonInputHooked = '1';
				document.addEventListener('input', function (e) {
					try {
						if (!e || !e.target) return;
						if (e.target.id === 'post_comment_content') {
							resetToken();
						}
					} catch (err) { }
				}, true);
			}
		} catch (e) { }

		try {
			var ajaxHooked = hookAjax();
			try { hookXhr(); } catch (e) { }
			try { hookFetch(); } catch (e) { }
			if (ajaxHooked) return;
		} catch (e) { }

		try {
			if (window.__TencentCloudCaptchaArgonWaitHooked === '1') return;
			window.__TencentCloudCaptchaArgonWaitHooked = '1';
		} catch (e) { }

		(function waitHook() {
			if (hookAjax()) return;
			try { hookXhr(); } catch (e) { }
			try { hookFetch(); } catch (e) { }
			setTimeout(waitHook, 50);
		})();
	}

	try {
		var prev = window.pjaxLoaded;
		window.pjaxLoaded = function () {
			try {
				if (typeof prev === 'function') {
					prev.apply(this, arguments);
				}
			} catch (e) { }
			try { install(); } catch (e) { }
		};
	} catch (e) { }

	install();
})();
