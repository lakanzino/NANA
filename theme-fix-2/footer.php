<?php
/**
 * Global custom footer for Quantum Pedia Child.
 *
 * @package Quantum_Pedia_Child
 */

defined( 'ABSPATH' ) || exit;

$qp_footer_title = 'کوانتوم پدیا فارسی';
$qp_footer_desc  = 'منبعی مینیمال و دقیق برای مرور مفاهیم و فناوری‌های دنیای کوانتوم.';
$qp_footer_copy  = 'کوانتوم پدیا فارسی — همه حقوق محفوظ است.';
if ( function_exists( 'qpedia_front_get' ) ) {
	$qp_ft = (string) qpedia_front_get( 'header_title' );
	$qp_fd = (string) qpedia_front_get( 'footer_desc' );
	$qp_fc = (string) qpedia_front_get( 'footer_copy' );
	if ( '' !== $qp_ft ) {
		$qp_footer_title = $qp_ft;
	}
	if ( '' !== $qp_fd ) {
		$qp_footer_desc = $qp_fd;
	}
	if ( '' !== $qp_fc ) {
		$qp_footer_copy = $qp_fc;
	}
}

$qp_footer_desc_html = esc_html( $qp_footer_desc );
foreach ( array( 'مینیمال', 'مفاهیم', 'کوانتوم' ) as $qp_neon ) {
	$qp_footer_desc_html = preg_replace(
		'/' . preg_quote( $qp_neon, '/' ) . '/u',
		'<span class="qp-neon-word">' . $qp_neon . '</span>',
		$qp_footer_desc_html,
		1
	);
}
?>
<footer id="colophon" class="qp-global-footer">
	<div class="container qp-global-footer__inner">
		<div class="qp-global-footer__brand">
			<div class="qp-global-footer__brand-top">
				<div class="qp-global-footer__titles">
					<div class="qp-global-footer__wordmark"><span class="qp-global-footer__q">Q</span>PEDIA</div>
					<div class="qp-global-footer__title"><?php echo esc_html( $qp_footer_title ); ?></div>
				</div>
			</div>
			<p class="qp-global-footer__desc"><?php echo $qp_footer_desc_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
		</div>

		<div class="qp-global-footer__links">
			<a href="<?php echo esc_url( qpedia_child_find_page_url( array( 'about-us', 'about', 'درباره-ما' ) ) ); ?>">درباره ما</a>
			<a href="<?php echo esc_url( qpedia_child_find_page_url( array( 'contact-us', 'contact', 'تماس-با-ما' ) ) ); ?>">تماس با ما</a>
			<a href="<?php echo esc_url( qpedia_child_find_page_url( array( 'rules', 'terms', 'regulations', 'مقررات-ما' ) ) ); ?>">مقررات ما</a>
		</div>
	</div>
	<div class="container qp-global-footer__bottom">
		<p>© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( $qp_footer_copy ); ?></p>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
