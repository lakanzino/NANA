<?php
/**
 * Single template for quantum_article.
 *
 * هدف این فایل: جایگزینی وابستگی رندر مقاله به افزونهٔ قدیمی.
 *
 * @package Quantum_Pedia_Child
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'qpedia_prepare_article_content' ) ) {
	/**
	 * محتوا را از shortcodes قدیمی تمیز می‌کند و برای TOC آماده می‌سازد.
	 *
	 * @param string $content محتوای خام.
	 * @return array{content:string,toc:string}
	 */
	function qpedia_prepare_article_content( $content ) {
		$content = (string) $content;

		// بقایای کوتاه‌کدهای قدیمی را فقط اگر مستقل آمده باشند حذف کن.
		$content = preg_replace( '/^\s*\[(?:qpt[^\]]*|qterm[^\]]*|related[^\]]*)\]\s*/u', '', $content );

		$matches = array();
		preg_match_all( '/<(h[2-3])([^>]*)>(.*?)<\/h[2-3]>/iu', $content, $matches, PREG_SET_ORDER );

		if ( count( $matches ) < 3 ) {
			return array(
				'content' => $content,
				'toc'     => '',
			);
		}

		$toc_items  = '';
		$counter    = 0;
		$in_sublist = false;
		$prev_level = 'h2';

		foreach ( $matches as $match ) {
			$counter++;
			$tag   = strtolower( $match[1] );
			$attrs = $match[2];
			$title = trim( wp_strip_all_tags( $match[3] ) );
			$id    = 'qp-heading-' . $counter;

			$new_heading = '<' . $match[1] . $attrs . ' id="' . esc_attr( $id ) . '">' . $match[3] . '</' . $match[1] . '>';
			$content     = str_replace( $match[0], $new_heading, $content );

			if ( 'h3' === $tag && 'h2' === $prev_level && ! $in_sublist ) {
				$toc_items .= '<ol class="qp-article-toc__sub">';
				$in_sublist = true;
			}

			if ( 'h2' === $tag && $in_sublist ) {
				$toc_items .= '</ol></li>';
				$in_sublist = false;
			}

			$toc_items .= '<li><a href="#' . esc_attr( $id ) . '">' . esc_html( $title ) . '</a>';
			if ( 'h3' === $tag ) {
				$toc_items .= '</li>';
			}

			$prev_level = $tag;
		}

		if ( $in_sublist ) {
			$toc_items .= '</ol></li>';
		}

		$toc = '<aside class="qp-article-toc" aria-label="فهرست مطالب">';
		$toc .= '<h2 class="qp-article-toc__title">فهرست مطالب</h2>';
		$toc .= '<ol class="qp-article-toc__list">' . $toc_items . '</ol>';
		$toc .= '</aside>';

		return array(
			'content' => $content,
			'toc'     => $toc,
		);
	}
}

get_header();
?>
<main id="primary" class="site-main">
	<div class="container qp-shell qp-shell--article">
		<?php while ( have_posts() ) : the_post(); ?>
			<?php
			$terms = get_the_terms( get_the_ID(), 'quantum_category' );

			$raw_content = apply_filters(
    'the_content',
    get_the_content()
);

$prepared = qpedia_prepare_article_content(
    $raw_content
);

$content = $prepared['content'];

			$toc         = $prepared['toc'];

			$related_posts = array();
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				$related_query = new WP_Query(
					array(
						'post_type'              => 'quantum_article',
						'post_status'            => 'publish',
						'posts_per_page'         => 3,
						'post__not_in'           => array( get_the_ID() ),
						'ignore_sticky_posts'    => true,
						'no_found_rows'          => true,
						'update_post_meta_cache' => false,
						'tax_query'              => array(
							array(
								'taxonomy' => 'quantum_category',
								'field'    => 'term_id',
								'terms'    => wp_list_pluck( $terms, 'term_id' ),
							),
						),
					)
				);

				if ( $related_query->have_posts() ) {
					$related_posts = $related_query->posts;
				}
				wp_reset_postdata();
			}
			?>

			<article id="post-<?php the_ID(); ?>" <?php post_class( 'qp-article' ); ?>>
				<header class="qp-article__header">
					<div class="qp-article__eyebrow">مقالهٔ کوانتوم</div>

					<?php if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) : ?>
						<div class="qp-article__chips" aria-label="دسته‌ها">
							<?php foreach ( $terms as $term ) : ?>
								<a class="qp-article__chip" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
									<?php echo esc_html( $term->name ); ?>
								</a>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<h1 class="qp-article__title"><?php the_title(); ?></h1>

					<div class="qp-article__meta">
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
							<?php echo esc_html( get_the_date( 'j F Y' ) ); ?>
						</time>
						<?php if ( get_the_modified_date( 'U' ) !== get_the_date( 'U' ) ) : ?>
							<span class="qp-article__sep">•</span>
							<span>به‌روزرسانی: <?php echo esc_html( get_the_modified_date( 'j F Y' ) ); ?></span>
						<?php endif; ?>
					</div>

					<?php if ( has_excerpt() ) : ?>
						<p class="qp-article__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="qp-article__media">
						<?php echo get_the_post_thumbnail( get_the_ID(), 'large', array( 'class' => 'qp-article__image' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</figure>
				<?php endif; ?>

				<?php if ( $toc ) : ?>
					<?php echo $toc; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php endif; ?>

				<div class="qp-article__content entry-content">
					<?php echo wp_kses_post( do_shortcode( $content ) ); ?>
				</div>
				<?php if ( function_exists( 'qpedia_author_bar' ) ) : ?>
					<?php qpedia_author_bar(); ?>
				<?php endif; ?>
			</article>

			<?php if ( ! empty( $related_posts ) ) : ?>
				<section class="qp-related" aria-label="مقاله‌های مرتبط">
					<div class="qp-related__head">
						<h2 class="qp-related__title">مقاله‌های مرتبط</h2>
					</div>
					<div class="qp-related__grid">
						<?php foreach ( $related_posts as $related_post ) : ?>
							<article class="qp-related-card">
								<h3 class="qp-related-card__title">
									<a href="<?php echo esc_url( get_permalink( $related_post->ID ) ); ?>">
										<?php echo esc_html( get_the_title( $related_post->ID ) ); ?>
									</a>
								</h3>
								<?php if ( has_excerpt( $related_post->ID ) ) : ?>
									<p class="qp-related-card__excerpt"><?php echo esc_html( get_the_excerpt( $related_post->ID ) ); ?></p>
								<?php endif; ?>
							</article>
						<?php endforeach; ?>
					</div>
				</section>
			<?php endif; ?>

			<?php
			the_post_navigation(
				array(
					'prev_text' => '<span class="nav-subtitle">← مقاله قبلی</span> <span class="nav-title">%title</span>',
					'next_text' => '<span class="nav-subtitle">مقاله بعدی →</span> <span class="nav-title">%title</span>',
				)
			);
			?>
		<?php endwhile; ?>
	</div>
</main>
<?php
get_footer();