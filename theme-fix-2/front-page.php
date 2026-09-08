<?php
/**
 * Front page template — نسخهٔ ۴.
 * متن‌ها و نمایش بخش‌ها از پیشخوان: منوی «صفحهٔ نخست».
 *
 * ترتیب بخش‌ها:
 *   ۱. قهرمان
 *   ۲. جست‌وجو
 *   ۳. شمارنده‌ها
 *   ۴. مقاله‌های پیشنهادی — اسلایدر مربعی
 *   ۵. تازه‌ترین نوشته‌ها
 *   ۶. دسته‌بندی موضوعات
 *   ۷. دانشمندان — ردیف اسلایدری
 *
 * @package Quantum_Pedia_Child
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'qpedia_front_get' ) ) {
	$qp_fs = get_stylesheet_directory() . '/inc/front-settings.php';
	if ( is_readable( $qp_fs ) ) {
		require_once $qp_fs;
	}
}

$F = function_exists( 'qpedia_front_get' ) ? qpedia_front_get() : array();

get_header();

$article_counts   = wp_count_posts( 'quantum_article' );
$scientist_counts = wp_count_posts( 'quantum_scientist' );
$article_total    = isset( $article_counts->publish ) ? (int) $article_counts->publish : 0;
$scientist_total  = isset( $scientist_counts->publish ) ? (int) $scientist_counts->publish : 0;

$parent_categories = get_terms(
	array(
		'taxonomy'   => 'quantum_category',
		'hide_empty' => true,
		'parent'     => 0,
		'orderby'    => 'count',
		'order'      => 'DESC',
	)
);
if ( is_wp_error( $parent_categories ) ) {
	$parent_categories = array();
}

$all_terms = get_terms(
	array(
		'taxonomy'   => 'quantum_category',
		'hide_empty' => true,
	)
);
$sub_total = 0;
if ( ! is_wp_error( $all_terms ) ) {
	foreach ( $all_terms as $sub_term ) {
		if ( ! empty( $sub_term->parent ) ) {
			$sub_total++;
		}
	}
}

$cat_descriptions = isset( $F['cat_descriptions'] ) && is_array( $F['cat_descriptions'] ) ? $F['cat_descriptions'] : array();
$cat_icons        = isset( $F['cat_icons'] ) && is_array( $F['cat_icons'] ) ? $F['cat_icons'] : array();

$show_hero       = ! empty( $F['show_hero'] );
$show_search     = ! empty( $F['show_search'] );
$show_stats      = ! empty( $F['show_stats'] );
$show_featured   = ! empty( $F['show_featured'] );
$show_latest     = ! empty( $F['show_latest'] );
$show_cats       = ! empty( $F['show_cats'] );
$show_scientists = ! empty( $F['show_scientists'] );

if ( empty( $F ) ) {
	$show_hero = $show_search = $show_stats = $show_featured = $show_latest = $show_cats = $show_scientists = true;
}

$qp_featured_posts = array();
if ( $show_featured ) {
	$qp_featured_slugs = array();
	if ( ! empty( $F['featured'] ) && is_array( $F['featured'] ) ) {
		foreach ( $F['featured'] as $qp_item ) {
			if ( ! empty( $qp_item['slug'] ) ) {
				$qp_featured_slugs[] = $qp_item;
			}
		}
	}

	$qp_slug_list = wp_list_pluck( $qp_featured_slugs, 'slug' );

	if ( ! empty( $qp_slug_list ) ) {
		$qp_featured_query = get_posts(
			array(
				'post_type'              => 'quantum_article',
				'post_status'            => 'publish',
				'post_name__in'          => $qp_slug_list,
				'posts_per_page'         => count( $qp_slug_list ),
				'orderby'                => 'post_name__in',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		if ( ! empty( $qp_featured_query ) ) {
			$qp_by_slug = array();
			foreach ( $qp_featured_query as $qp_p ) {
				$qp_by_slug[ $qp_p->post_name ] = $qp_p;
			}
			foreach ( $qp_featured_slugs as $qp_item ) {
				if ( isset( $qp_by_slug[ $qp_item['slug'] ] ) ) {
					$qp_item['post']     = $qp_by_slug[ $qp_item['slug'] ];
					$qp_featured_posts[] = $qp_item;
				}
			}
		}
	}

	if ( empty( $qp_featured_posts ) ) {
		$qp_fallback = get_posts(
			array(
				'post_type'              => 'quantum_article',
				'posts_per_page'         => 6,
				'post_status'            => 'publish',
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
			)
		);
		foreach ( $qp_fallback as $qp_p ) {
			$qp_featured_posts[] = array(
				'post' => $qp_p,
				'hook' => wp_trim_words( (string) $qp_p->post_excerpt, 18, '…' ),
				'tag'  => 'پیشنهاد',
			);
		}
	}
}

$latest_count = isset( $F['latest_count'] ) ? absint( $F['latest_count'] ) : 6;
if ( $latest_count < 1 ) {
	$latest_count = 6;
}

$sci_count = isset( $F['sci_count'] ) ? absint( $F['sci_count'] ) : 10;
if ( $sci_count < 1 ) {
	$sci_count = 10;
}

$latest_articles = null;
if ( $show_latest ) {
	$latest_articles = new WP_Query(
		array(
			'post_type'              => 'quantum_article',
			'posts_per_page'         => $latest_count,
			'post_status'            => 'publish',
			'orderby'                => 'date',
			'order'                  => 'DESC',
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
		)
	);
}

$featured_scientists = null;
if ( $show_scientists ) {
	$featured_scientists = new WP_Query(
		array(
			'post_type'           => 'quantum_scientist',
			'posts_per_page'      => $sci_count,
			'post_status'         => 'publish',
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		)
	);
}

$qp_sci_index = 0;

$qp_stats = array(
	array(
		'num'   => $article_total,
		'label' => 'مقاله',
	),
	array(
		'num'   => count( $parent_categories ),
		'label' => 'دستهٔ اصلی',
	),
	array(
		'num'   => $sub_total,
		'label' => 'زیردسته',
	),
	array(
		'num'   => $scientist_total,
		'label' => 'دانشمند',
	),
);

$qp_href = function ( $url ) {
	if ( function_exists( 'qpedia_front_href' ) ) {
		return qpedia_front_href( $url );
	}
	$url = trim( (string) $url );
	if ( '' === $url ) {
		return home_url( '/' );
	}
	if ( '#' === $url[0] ) {
		return $url;
	}
	if ( '/' === $url[0] ) {
		return home_url( $url );
	}
	return $url;
};

$counter_ms = isset( $F['counter_ms'] ) ? absint( $F['counter_ms'] ) : 1100;
if ( $counter_ms < 200 ) {
	$counter_ms = 1100;
}
?>
<main id="primary" class="site-main">
	<div class="container qp-front">

		<?php if ( $show_hero ) : ?>
		<section class="qp-front-hero">
			<?php if ( ! empty( $F['hero_badge'] ) ) : ?>
				<div class="qp-front-hero__badge"><?php echo esc_html( $F['hero_badge'] ); ?></div>
			<?php endif; ?>
			<h1 class="qp-front-hero__title"><?php echo esc_html( ! empty( $F['hero_title'] ) ? $F['hero_title'] : 'شگفتی‌های دنیای کوانتوم را ساده، دقیق و بی‌اغراق کشف کنید' ); ?></h1>
			<?php if ( ! empty( $F['hero_desc'] ) ) : ?>
				<p class="qp-front-hero__desc"><?php echo esc_html( $F['hero_desc'] ); ?></p>
			<?php endif; ?>

			<div class="qp-front-hero__actions">
				<?php if ( ! empty( $F['hero_btn1_text'] ) ) : ?>
					<a class="qp-front-btn qp-front-btn--primary" href="<?php echo esc_url( $qp_href( isset( $F['hero_btn1_url'] ) ? $F['hero_btn1_url'] : '/topic/fundamentals/' ) ); ?>"><?php echo esc_html( $F['hero_btn1_text'] ); ?></a>
				<?php endif; ?>
				<?php if ( ! empty( $F['hero_btn2_text'] ) ) : ?>
					<a class="qp-front-btn qp-front-btn--ghost" href="<?php echo esc_url( $qp_href( isset( $F['hero_btn2_url'] ) ? $F['hero_btn2_url'] : '#qp-front-cats' ) ); ?>"><?php echo esc_html( $F['hero_btn2_text'] ); ?></a>
				<?php endif; ?>
			</div>
		</section>
		<?php endif; ?>

		<?php if ( $show_search ) : ?>
		<section class="qp-front-section qp-front-section--search" aria-label="جست‌وجو">
			<div class="qp-front-searchbox">
				<?php if ( ! empty( $F['search_label'] ) ) : ?>
					<div class="qp-front-searchbox__label"><?php echo esc_html( $F['search_label'] ); ?></div>
				<?php endif; ?>
				<div class="qp-front-search">
					<?php get_search_form(); ?>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<?php if ( $show_stats ) : ?>
		<section class="qp-front-section qp-front-section--stats" aria-label="آمار دانشنامه">
			<div class="qp-front-hero__stats" data-qp-counters data-qp-duration="<?php echo esc_attr( (string) $counter_ms ); ?>">
				<?php foreach ( $qp_stats as $qp_stat ) : ?>
					<div class="qp-front-stat">
						<span
							class="qp-front-stat__num"
							data-qp-count="<?php echo esc_attr( (string) $qp_stat['num'] ); ?>"
						><?php echo esc_html( number_format_i18n( $qp_stat['num'] ) ); ?></span>
						<span class="qp-front-stat__label"><?php echo esc_html( $qp_stat['label'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
		<?php endif; ?>

		<?php if ( $show_featured && ! empty( $qp_featured_posts ) ) : ?>
		<section class="qp-front-section qp-front-section--picks">
			<div class="qp-front-picksrail">
				<div class="qp-front-picks">
				<?php foreach ( $qp_featured_posts as $qp_i => $qp_item ) : ?>
					<?php $qp_p = $qp_item['post']; ?>
					<a class="qp-front-pick<?php echo ( 0 === $qp_i ) ? ' qp-front-pick--first' : ''; ?>" href="<?php echo esc_url( get_permalink( $qp_p ) ); ?>">
						<span class="qp-front-pick__tag"><?php echo esc_html( $qp_item['tag'] ); ?></span>
						<h3 class="qp-front-pick__title"><?php echo esc_html( get_the_title( $qp_p ) ); ?></h3>
						<p class="qp-front-pick__hook"><?php echo esc_html( $qp_item['hook'] ); ?></p>
						<span class="qp-front-pick__more">بخوانید</span>
					</a>
				<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php endif; ?>

		<?php if ( $show_latest ) : ?>
		<section class="qp-front-section qp-front-section--articles">
			<div class="qp-front-section__head">
				<div>
					<?php if ( ! empty( $F['latest_eyebrow'] ) ) : ?>
						<div class="qp-front-section__eyebrow"><?php echo esc_html( $F['latest_eyebrow'] ); ?></div>
					<?php endif; ?>
					<h2 class="qp-front-section__title"><?php echo esc_html( ! empty( $F['latest_title'] ) ? $F['latest_title'] : 'آخرین مقاله‌ها' ); ?></h2>
				</div>
				<?php if ( ! empty( $F['latest_link'] ) ) : ?>
					<a class="qp-front-section__link" href="<?php echo esc_url( $qp_href( isset( $F['latest_url'] ) ? $F['latest_url'] : '/topic/fundamentals/' ) ); ?>"><?php echo esc_html( $F['latest_link'] ); ?></a>
				<?php endif; ?>
			</div>

			<?php if ( $latest_articles && $latest_articles->have_posts() ) : ?>
				<div class="qp-front-articles">
					<?php
					while ( $latest_articles->have_posts() ) :
						$latest_articles->the_post();
						?>
						<?php
						$terms      = get_the_terms( get_the_ID(), 'quantum_category' );
						$term_label = ( ! is_wp_error( $terms ) && ! empty( $terms ) ) ? $terms[0]->name : '';
						?>
						<a class="qp-front-article" href="<?php the_permalink(); ?>">
							<div class="qp-front-article__meta">
								<?php if ( $term_label ) : ?>
									<span class="qp-front-article__term"><?php echo esc_html( $term_label ); ?></span>
								<?php endif; ?>
								<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></time>
							</div>
							<h3 class="qp-front-article__title"><?php the_title(); ?></h3>
							<p class="qp-front-article__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
						</a>
					<?php endwhile; ?>
				</div>
				<?php wp_reset_postdata(); ?>
			<?php endif; ?>
		</section>
		<?php endif; ?>

		<?php if ( $show_cats ) : ?>
		<section id="qp-front-cats" class="qp-front-section qp-front-section--cats">
			<div class="qp-front-section__head">
				<div>
					<?php if ( ! empty( $F['cats_eyebrow'] ) ) : ?>
						<div class="qp-front-section__eyebrow"><?php echo esc_html( $F['cats_eyebrow'] ); ?></div>
					<?php endif; ?>
					<h2 class="qp-front-section__title"><?php echo esc_html( ! empty( $F['cats_title'] ) ? $F['cats_title'] : 'دسته‌بندی موضوعات' ); ?></h2>
					<?php if ( ! empty( $F['cats_desc'] ) ) : ?>
						<p class="qp-front-section__desc"><?php echo esc_html( $F['cats_desc'] ); ?></p>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( ! empty( $parent_categories ) ) : ?>
				<div class="qp-front-cats">
					<?php foreach ( $parent_categories as $category ) : ?>
						<?php
						$children = get_terms(
							array(
								'taxonomy'   => 'quantum_category',
								'hide_empty' => true,
								'parent'     => $category->term_id,
							)
						);
						$slug     = isset( $category->slug ) ? $category->slug : '';
						$cat_desc = isset( $cat_descriptions[ $slug ] ) ? $cat_descriptions[ $slug ] : '';
						$cat_icon = isset( $cat_icons[ $slug ] ) ? $cat_icons[ $slug ] : 'موضوع';
						?>
						<a class="qp-front-cat" href="<?php echo esc_url( get_term_link( $category ) ); ?>">
							<div class="qp-front-cat__top">
								<span class="qp-front-cat__icon"><?php echo esc_html( $cat_icon ); ?></span>
								<span class="qp-front-cat__count"><?php echo esc_html( number_format_i18n( (int) $category->count ) ); ?> مقاله</span>
							</div>
							<h3 class="qp-front-cat__title"><?php echo esc_html( $category->name ); ?></h3>
							<?php if ( $cat_desc ) : ?>
								<p class="qp-front-cat__desc"><?php echo esc_html( $cat_desc ); ?></p>
							<?php endif; ?>

							<?php if ( ! is_wp_error( $children ) && ! empty( $children ) ) : ?>
								<div class="qp-front-cat__subs">
									<?php foreach ( $children as $child ) : ?>
										<span class="qp-front-cat__sub"><?php echo esc_html( $child->name ); ?></span>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php endif; ?>

		<?php if ( $show_scientists ) : ?>
		<section class="qp-front-section qp-front-section--scientists">
			<div class="qp-front-section__head">
				<div>
					<?php if ( ! empty( $F['sci_eyebrow'] ) ) : ?>
						<div class="qp-front-section__eyebrow"><?php echo esc_html( $F['sci_eyebrow'] ); ?></div>
					<?php endif; ?>
					<h2 class="qp-front-section__title"><?php echo esc_html( ! empty( $F['sci_title'] ) ? $F['sci_title'] : 'چهره‌های مهم کوانتوم' ); ?></h2>
					<?php if ( ! empty( $F['sci_desc'] ) ) : ?>
						<p class="qp-front-section__desc qp-front-swipe-hint"><?php echo esc_html( $F['sci_desc'] ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $F['sci_link'] ) ) : ?>
					<a class="qp-front-section__link" href="<?php echo esc_url( $qp_href( isset( $F['sci_url'] ) ? $F['sci_url'] : '/scientists/' ) ); ?>"><?php echo esc_html( $F['sci_link'] ); ?></a>
				<?php endif; ?>
			</div>

			<?php if ( $featured_scientists && $featured_scientists->have_posts() ) : ?>
				<div class="qp-front-scirail" role="region" aria-label="دانشمندان برجسته" tabindex="0">
					<div class="qp-front-scirail__track">
						<?php
						while ( $featured_scientists->have_posts() ) :
							$featured_scientists->the_post();
							?>
							<?php
							$en_name = trim( (string) get_post_meta( get_the_ID(), '_scientist_en_name', true ) );
							$initial = 'Q';
							if ( $en_name ) {
								$initial = strtoupper( mb_substr( $en_name, 0, 1, 'UTF-8' ) );
							}
							$qp_sci_index++;
							?>
							<a class="qp-front-scientist" href="<?php the_permalink(); ?>">
								<div class="qp-front-scientist__media">
									<?php if ( has_post_thumbnail() ) : ?>
										<?php
										echo get_the_post_thumbnail( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											get_the_ID(),
											'medium',
											array(
												'class'    => 'qp-front-scientist__image',
												'loading'  => ( $qp_sci_index <= 3 ) ? 'eager' : 'lazy',
												'decoding' => 'async',
											)
										);
										?>
									<?php else : ?>
										<span class="qp-front-scientist__placeholder"><?php echo esc_html( $initial ); ?></span>
									<?php endif; ?>
								</div>
								<div class="qp-front-scientist__body">
									<h3 class="qp-front-scientist__name"><?php the_title(); ?></h3>
									<?php if ( $en_name ) : ?>
										<p class="qp-front-scientist__latin"><?php echo esc_html( $en_name ); ?></p>
									<?php endif; ?>
								</div>
							</a>
						<?php endwhile; ?>

						<a class="qp-front-scientist qp-front-scientist--all" href="<?php echo esc_url( $qp_href( isset( $F['sci_url'] ) ? $F['sci_url'] : '/scientists/' ) ); ?>">
							<span class="qp-front-scientist--all__inner">
								<span class="qp-front-scientist--all__num"><?php echo esc_html( number_format_i18n( $scientist_total ) ); ?></span>
								<span class="qp-front-scientist--all__text">دیدن همهٔ دانشمندان</span>
							</span>
						</a>
					</div>
				</div>
				<?php wp_reset_postdata(); ?>
			<?php endif; ?>
		</section>
		<?php endif; ?>

	</div>
</main>
<?php
get_footer();
