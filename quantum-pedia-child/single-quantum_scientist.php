<?php
/**
 * Single template for quantum_scientist.
 *
 * @package Quantum_Pedia_Child
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="site-main">
	<div class="container qp-shell qp-shell--scientist">
		<?php while ( have_posts() ) : the_post(); ?>
			<?php
			$en_name       = trim( (string) get_post_meta( get_the_ID(), '_scientist_en_name', true ) );
			$taxonomies    = array( 'scientist_field', 'quantum_scientist_group' );
			$scientist_tax = array();

			foreach ( $taxonomies as $taxonomy ) {
				if ( ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}

				$terms = get_the_terms( get_the_ID(), $taxonomy );
				if ( is_wp_error( $terms ) || empty( $terms ) ) {
					continue;
				}

				$scientist_tax = array_merge( $scientist_tax, $terms );
			}

			$content = get_the_content();
			$content = preg_replace( '/^\s*\[qsci_card\]\s*/u', '', $content );
			$content = apply_filters( 'the_content', $content );
			?>

			<article id="post-<?php the_ID(); ?>" <?php post_class( 'qp-scientist' ); ?>>
				<header class="qp-scientist__header">
					<div class="qp-scientist__eyebrow">دانشمند کوانتوم</div>

					<?php if ( ! empty( $scientist_tax ) ) : ?>
						<div class="qp-scientist__chips" aria-label="زمینه‌ها و گروه‌ها">
							<?php foreach ( $scientist_tax as $term ) : ?>
								<a class="qp-scientist__chip" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
									<?php echo esc_html( $term->name ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<h1 class="qp-scientist__title"><?php the_title(); ?></h1>

					<?php if ( $en_name ) : ?>
						<p class="qp-scientist__latin"><?php echo esc_html( $en_name ); ?></p>
					<?php endif; ?>

					<div class="qp-scientist__meta">
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
							<?php echo esc_html( get_the_date( 'j F Y' ) ); ?>
						</time>
						<?php
						$archive_link = get_post_type_archive_link( 'quantum_scientist' );
						if ( $archive_link ) :
							?>
							<span class="qp-scientist__sep">•</span>
							<a href="<?php echo esc_url( $archive_link ); ?>">بازگشت به فهرست دانشمندان</a>
						<?php endif; ?>
					</div>

					<?php if ( has_excerpt() ) : ?>
						<p class="qp-scientist__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="qp-scientist__media">
						<?php the_post_thumbnail( 'large', array( 'class' => 'qp-scientist__image' ) ); ?>
					</figure>
				<?php endif; ?>

				<div class="qp-scientist__content entry-content">
					<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<?php if ( function_exists( 'qpedia_author_bar' ) ) : ?>
					<?php qpedia_author_bar(); ?>
				<?php endif; ?>
			</article>
		<?php endwhile; ?>
	</div>
</main>
<?php
get_footer();