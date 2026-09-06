/*!
 * QPedia — شمارندهٔ انیمیشنی صفحهٔ نخست
 * محل: quantum-pedia-child/assets/js/qpedia-counters.js
 *
 * بدون هیچ کتابخانه‌ای. حدود ۱ کیلوبایت.
 * وقتی کاربر اسکرول کرد و باکس‌ها وارد صفحه شدند، اعداد از صفر
 * تا مقدار واقعی بالا می‌روند و آن‌جا می‌ایستند. فقط یک بار اجرا می‌شود.
 */
( function () {
	'use strict';

	var box = document.querySelector( '[data-qp-counters]' );
	if ( ! box ) {
		return;
	}

	var nums = box.querySelectorAll( '[data-qp-count]' );
	if ( ! nums.length ) {
		return;
	}

	// ارقام فارسی، چون بقیهٔ سایت فارسی است.
	var FA = [ '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' ];

	function toFa( n ) {
		return String( n ).replace( /\d/g, function ( d ) {
			return FA[ d ];
		} );
	}

	// اگر مرورگر قدیمی بود یا کاربر انیمیشن کمتر خواسته بود،
	// عدد نهایی همان‌جا می‌ماند و هیچ اتفاقی نمی‌افتد.
	var reduced = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	if ( reduced || ! ( 'IntersectionObserver' in window ) || ! window.requestAnimationFrame ) {
		return;
	}

	function run( el ) {
		var target = parseInt( el.getAttribute( 'data-qp-count' ), 10 );

		if ( isNaN( target ) || target <= 0 ) {
			return;
		}

		var duration = 1100; // میلی‌ثانیه
		var start = null;

		el.textContent = toFa( 0 );

		function step( now ) {
			if ( start === null ) {
				start = now;
			}

			var p = ( now - start ) / duration;

			if ( p > 1 ) {
				p = 1;
			}

			// نرم‌شدن انتها: سریع شروع می‌شود، آرام می‌ایستد.
			var eased = 1 - Math.pow( 1 - p, 3 );

			el.textContent = toFa( Math.round( target * eased ) );

			if ( p < 1 ) {
				window.requestAnimationFrame( step );
			} else {
				el.textContent = toFa( target );
			}
		}

		window.requestAnimationFrame( step );
	}

	var io = new IntersectionObserver(
		function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( ! entry.isIntersecting ) {
					return;
				}

				io.unobserve( entry.target ); // فقط یک بار

				Array.prototype.forEach.call( nums, function ( el, i ) {
					// کمی تأخیر پلکانی تا چهار عدد پشت سر هم روشن شوند.
					window.setTimeout( function () {
						run( el );
					}, i * 110 );
				} );
			} );
		},
		{ threshold: 0.35 }
	);

	io.observe( box );
} )();
