<?php
/**
 * The template for displaying search results pages.
 *
 * @package Quantum_Pedia
 * @since   1.0.0
 */

get_header();
?>

<main id="primary" class="site-main">
	<div class="container">
		<?php if ( have_posts() ) : ?>
			<header class="page-header">
				<h1 class="page-title">
					<?php
					printf(
						/* translators: %s: search query. */
						esc_html__( 'Search Results for: %s', 'quantum-pedia' ),
						'<span>' . get_search_query() . '</span>'
					);
					?>
				</h1>
			</header>

			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content', 'search' );
			endwhile;

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
	</div>
</main>

<?php
get_sidebar();
get_footer();