<?php
/**
 * واژه‌نامهٔ خودکار — نسخهٔ اصلاح‌شده (۱۵ شهریور ۱۴۰۵)
 *
 * باگ نسخهٔ قبل:
 * preg_replace روی «کل رشتهٔ HTML» اجرا می‌شد و هیچ تمایزی میان متن و
 * نشانه‌گذاری قائل نبود. در نتیجه یک واژه می‌توانست داخل صفتِ
 * data-definition واژهٔ قبلی درج شود و تگ را بشکند، یا داخل href و
 * متن لینک بنشیند. خروجی خراب روی سایت دیده شد:
 *   <span class="quantum-glossary-term" data-definition="قانونی که ...
 * که به‌صورت متن خام چاپ می‌شد.
 *
 * اصلاح:
 * ۱. محتوا با DOMDocument پیمایش می‌شود و جایگزینی فقط روی گره‌های متنی
 *    انجام می‌گیرد؛ هیچ صفتی هرگز لمس نمی‌شود.
 * ۲. متن داخل a, h1..h6, span, code, pre, summary, blockquote و
 *    خود واژه‌نامه نادیده گرفته می‌شود.
 * ۳. بخش «منابع» تا انتهای مقاله از پردازش کنار گذاشته می‌شود.
 * ۴. هر واژه حداکثر یک بار در کل مقاله.
 */

defined( 'ABSPATH' ) || exit;

/** تگ‌هایی که متن داخلشان نباید واژه‌نامه بگیرد. */
function quantum_glossary_skip_tags(): array {
	return [
		'a', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
		'span', 'code', 'pre', 'script', 'style',
		'summary', 'blockquote', 'strong', 'em',
	];
}

/** آیا این گره داخل یکی از تگ‌های ممنوع است؟ */
function quantum_glossary_is_protected( DOMNode $node ): bool {
	$skip = quantum_glossary_skip_tags();
	for ( $p = $node->parentNode; $p instanceof DOMElement; $p = $p->parentNode ) {
		if ( in_array( strtolower( $p->tagName ), $skip, true ) ) {
			return true;
		}
	}
	return false;
}

add_filter(
	'the_content',
	static function ( $content ) {

		if ( is_admin() || ! is_singular( [ 'quantum_article', 'quantum_scientist' ] ) ) {
			return $content;
		}
		if ( ! is_string( $content ) || '' === trim( $content ) ) {
			return $content;
		}

		$terms = quantum_get_glossary_terms();
		if ( empty( $terms ) ) {
			return $content;
		}

		// بخش منابع به بعد اصلاً پردازش نمی‌شود.
		$tail = '';
		$cut  = mb_stripos( $content, '<h2>منابع' );
		if ( false !== $cut ) {
			$tail    = mb_substr( $content, $cut );
			$content = mb_substr( $content, 0, $cut );
		}

		if ( ! class_exists( 'DOMDocument' ) ) {
			return $content . $tail;   // بدون افزونهٔ dom، دست نمی‌زنیم.
		}

		$dom = new DOMDocument( '1.0', 'UTF-8' );

		$prev = libxml_use_internal_errors( true );
		$ok   = $dom->loadHTML(
			'<?xml encoding="UTF-8"?><div id="qg-root">' . $content . '</div>',
			LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $prev );

		if ( ! $ok ) {
			return $content . $tail;   // اگر پارس نشد، محتوای اصلی دست‌نخورده برمی‌گردد.
		}

		$xpath = new DOMXPath( $dom );
		$used  = [];

		foreach ( $terms as $item ) {

			$term = trim( (string) ( $item['term'] ?? '' ) );
			if ( '' === $term || isset( $used[ $term ] ) ) {
				continue;
			}

			$pattern = '/(?<![\p{L}\p{N}\x{200C}])' . preg_quote( $term, '/' ) . '(?![\p{L}\p{N}\x{200C}])/u';

			foreach ( iterator_to_array( $xpath->query( '//text()' ) ) as $text ) {

				if ( isset( $used[ $term ] ) ) {
					break;
				}
				if ( quantum_glossary_is_protected( $text ) ) {
					continue;
				}
				if ( ! preg_match( $pattern, $text->nodeValue, $m, PREG_OFFSET_CAPTURE ) ) {
					continue;
				}

				$offset = $m[0][1];                      // آفست بایتی
				$before = substr( $text->nodeValue, 0, $offset );
				$after  = substr( $text->nodeValue, $offset + strlen( $term ) );

				$span = $dom->createElement( 'span' );
				$span->setAttribute( 'class', 'quantum-glossary-term' );
				$span->setAttribute( 'data-definition', (string) ( $item['definition'] ?? '' ) );
				$span->appendChild( $dom->createTextNode( $term ) );

				$parent = $text->parentNode;
				$parent->insertBefore( $dom->createTextNode( $before ), $text );
				$parent->insertBefore( $span, $text );
				$parent->insertBefore( $dom->createTextNode( $after ), $text );
				$parent->removeChild( $text );

				$used[ $term ] = true;
			}
		}

		$root = $dom->getElementById( 'qg-root' );
		if ( ! $root ) {
			return $content . $tail;
		}

		$out = '';
		foreach ( $root->childNodes as $child ) {
			$out .= $dom->saveHTML( $child );
		}

		return $out . $tail;
	},
	20
);
