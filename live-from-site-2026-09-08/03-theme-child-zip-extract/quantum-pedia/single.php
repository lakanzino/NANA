<?php
/**
 * The template for displaying all single posts.
 *
 * @package Quantum_Pedia
 * @since   1.0.0
 */

get_header();
?>

<main id="primary" class="site-main">
	<div class="container">
		<?php
		while ( have_posts() ) :
			the_post();

			get_template_part( 'template-parts/content', 'single' );

			the_post_navigation(
				array(
					'prev_text' => esc_html__( 'Previous post', 'quantum-pedia' ),
					'next_text' => esc_html__( 'Next post', 'quantum-pedia' ),
				)
			);

			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
		endwhile;
		?>
	</div>
</main>

<?php
get_sidebar();
get_footer();