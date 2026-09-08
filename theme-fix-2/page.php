<?php
/**
 * قالب برگه — خانهٔ ثابت و بقیهٔ برگه‌ها.
 *
 * اگر front-page.php روی سرور بماند، همان این فایل را صدا می‌زند.
 * خانه: Settings → Reading → برگهٔ ثابت.
 *
 * @package Quantum_Pedia_Child
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( is_front_page() ) {
	$qp_home = get_stylesheet_directory() . '/inc/home-sections.php';
	if ( is_readable( $qp_home ) ) {
		require $qp_home;
	}
} else {
	?>
<main id="primary" class="site-main">
	<div class="container qp-page">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="post-<?php the_ID(); ?>" <?php post_class( 'qp-static-page' ); ?>>
				<header class="qp-page-header">
					<h1 class="qp-page-title"><?php the_title(); ?></h1>
				</header>
				<div class="entry-content">
					<?php the_content(); ?>
				</div>
			</article>
			<?php
		endwhile;
		?>
	</div>
</main>
	<?php
}

get_footer();
