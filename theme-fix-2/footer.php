<?php
/**
 * Global custom footer for Quantum Pedia Child.
 *
 * @package Quantum_Pedia_Child
 */

defined( 'ABSPATH' ) || exit;
?>
<footer id="colophon" class="qp-global-footer">
	<div class="container qp-global-footer__inner">
		<div class="qp-global-footer__brand">
			<div class="qp-global-footer__brand-top">
				<img class="qp-global-footer__logo" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/qpedia-logo-white.png' ); ?>" alt="<?php esc_attr_e( 'لوگوی کوانتوم پدیا', 'quantum-pedia-child' ); ?>" width="64" height="64" loading="lazy" decoding="async" />
				<div class="qp-global-footer__titles">
					<div class="qp-global-footer__wordmark">QUANTUM<span class="qp-global-footer__wordmark-thin">PEDIA</span></div>
					<div class="qp-global-footer__title">کوانتوم پدیا فارسی</div>
				</div>
			</div>
			<p class="qp-global-footer__desc">منبعی مینیمال و دقیق برای مرور مفاهیم، فناوری‌ها و روایت‌های مهم دنیای کوانتوم.</p>
		</div>

		<div class="qp-global-footer__links">
			<a href="<?php echo esc_url( qpedia_child_find_page_url( array( 'about-us', 'about', 'درباره-ما' ) ) ); ?>">درباره ما</a>
			<a href="<?php echo esc_url( qpedia_child_find_page_url( array( 'contact-us', 'contact', 'تماس-با-ما' ) ) ); ?>">تماس با ما</a>
			<a href="<?php echo esc_url( qpedia_child_find_page_url( array( 'rules', 'terms', 'regulations', 'مقررات-ما' ) ) ); ?>">مقررات ما</a>
		</div>
	</div>
	<div class="container qp-global-footer__bottom">
		<p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> کوانتوم پدیا فارسی — همه حقوق محفوظ است.</p>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
