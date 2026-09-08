<?php
/**
 * Archive template for quantum_scientist.
 *
 * @package Quantum_Pedia_Child
 */

defined( 'ABSPATH' ) || exit;

get_header();

$post_type_object = get_post_type_object( 'quantum_scientist' );
$title            = ( $post_type_object && ! empty( $post_type_object->labels->name ) )
	? $post_type_object->labels->name
	: 'دانشمندان کوانتوم';
?>
<main id="primary" class="site-main">
	<div class="container qp-archive qp-archive--scientists">
		<header class="qp-archive__hero">
			<div class="qp-archive__eyebrow">آرشیو دانشمندان</div>
			<h1 class="qp-archive__title"><?php echo esc_html( $title ); ?></h1>
			<p class="qp-archive__desc">زندگی، نقش علمی و سهم هر چهره در شکل‌گیری فیزیک کوانتومی.</p>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="qp-archive-grid qp-archive-grid--scientists">
				<?php while ( have_posts() ) : the_post(); ?>
					<?php
					$en_name    = trim( (string) get_post_meta( get_the_ID(), '_scientist_en_name', true ) );
					$taxonomies = array( 'scientist_field', 'quantum_scientist_group' );
					$terms_out  = array();

					foreach ( $taxonomies as $taxonomy ) {
						if ( ! taxonomy_exists( $taxonomy ) ) {
							continue;
						}

						$terms = get_the_terms( get_the_ID(), $taxonomy );
						if ( is_wp_error( $terms ) || empty( $terms ) ) {
							continue;
						}

						$terms_out = array_merge( $terms_out, $terms );
					}
					?>

					<article id="post-<?php the_ID(); ?>" <?php post_class( 'qp-scientist-card' ); ?>>
						<a class="qp-scientist-card__media" href="<?php the_permalink(); ?>" aria-label="<?php the_title(); ?>">
							<?php if ( has_post_thumbnail() ) : ?>
								<?php the_post_thumbnail( 'medium_large', array( 'class' => 'qp-scientist-card__image' ) ); ?>
							<?php else : ?>
								<span class="qp-scientist-card__placeholder">دانشمند</span>
							<?php endif; ?>
						</a>

						<div class="qp-scientist-card__body">
							<?php if ( ! empty( $terms_out ) ) : ?>
								<div class="qp-scientist-card__chips" aria-label="زمینه‌ها">
									<?php foreach ( array_slice( $terms_out, 0, 3 ) as $term ) : ?>
										<a class="qp-scientist-card__chip" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
											<?php echo esc_html( $term->name ); ?>
										</a>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<h2 class="qp-scientist-card__title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h2>

							<?php if ( $en_name ) : ?>
								<p class="qp-scientist-card__latin"><?php echo esc_html( $en_name ); ?></p>
							<?php endif; ?>

							<?php if ( has_excerpt() ) : ?>
								<p class="qp-scientist-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
							<?php endif; ?>

							<div class="qp-scientist-card__meta">
								<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
									<?php echo esc_html( get_the_date( 'j F Y' ) ); ?>
								</time>
								<a class="qp-scientist-card__more" href="<?php the_permalink(); ?>">مشاهدهٔ صفحه</a>
							</div>
						</div>
					</article>
				<?php endwhile; ?>
			</div>

			<?php
			the_posts_pagination(
				array(
					'prev_text' => '← قبلی',
					'next_text' => 'بعدی →',
				)
			);
			?>
		<?php else : ?>
			<div class="qp-empty-state">
				<h2 class="qp-empty-state__title">هنوز دانشمندی اینجا ثبت نشده است</h2>
				<p class="qp-empty-state__desc">بعد از افزودن مدخل‌های جدید، این بخش کامل می‌شود.</p>
			</div>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();