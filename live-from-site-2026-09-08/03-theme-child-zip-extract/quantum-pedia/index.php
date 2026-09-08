<?php
/**
 * The main template file.
 *
 * @package Quantum_Pedia
 * @since   1.0.0
 */

get_header();
?>

<main id="primary" class="site-main">
	<div class="container">

		<?php if ( have_posts() ) : ?>

			<?php while ( have_posts() ) : the_post(); ?>
				<?php get_template_part( 'template-parts/content', get_post_type() ); ?>
			<?php endwhile; ?>

			<?php
			the_posts_pagination(
				array(
					'prev_text' => esc_html__( 'Previous', 'quantum-pedia' ),
					'next_text' => esc_html__( 'Next', 'quantum-pedia' ),
				)
			);
			?>

		<?php else : ?>

			<?php get_template_part( 'template-parts/content', 'none' ); ?>

		<?php endif; ?>

	</div><!-- .container -->
</main><!-- #primary -->

<?php
get_sidebar();
get_footer();