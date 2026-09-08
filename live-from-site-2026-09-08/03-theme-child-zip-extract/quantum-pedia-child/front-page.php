<?php
/**
 * Front page template.
 *
 * ساختار دائمی صفحهٔ نخست سایت.
 * این فایل مستقل از برگهٔ سفارشی عمل می‌کند و با وجود آن،
 * وردپرس مستقیماً همین فایل را برای صفحهٔ اول به‌کار می‌گیرد.
 *
 * @package Quantum_Pedia_Child
 */

defined( 'ABSPATH' ) || exit;

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

$subcategories = get_terms(
	array(
		'taxonomy'   => 'quantum_category',
		'hide_empty' => true,
		'orderby'    => 'count',
		'order'      => 'DESC',
	)
);
$sub_total = 0;
if ( ! is_wp_error( $subcategories ) && ! empty( $subcategories ) ) {
	foreach ( $subcategories as $sub_term ) {
		if ( ! empty( $sub_term->parent ) ) {
			$sub_total++;
		}
	}
}

$cat_descriptions = array(
	'fundamentals'        => 'سنگ‌بنای مکانیک کوانتومی؛ از مفاهیم پایه تا ذرات بنیادی.',
	'technology'          => 'از لیزر و GPS تا رایانش و کاربردهای واقعی کوانتوم.',
	'history-experiments' => 'روایت تاریخی نظریه و آزمایش‌هایی که فهم ما را تغییر دادند.',
	'phenomena'           => 'درهم‌تنیدگی، تونل‌زنی و پدیده‌هایی که شهود کلاسیک را می‌شکنند.',
	'mathematics'         => 'زبان ریاضی کوانتوم؛ فضای هیلبرت، عملگرها و معادلات.',
	'interpretations'     => 'خوانش‌های فلسفی و تفسیری از معنای نظریهٔ کوانتوم.',
	'pseudoscience'       => 'مرزبندی علم دقیق با سوءاستفاده‌های بازاری و شبه‌علم.',
);

$cat_icons = array(
	'fundamentals'        => 'مبانی',
	'technology'          => 'فناوری',
	'history-experiments' => 'تاریخ',
	'phenomena'           => 'پدیده',
	'mathematics'         => 'ریاضی',
	'interpretations'     => 'تفسیر',
	'pseudoscience'       => 'نقد',
);

$quick_links = array(
	array(
		'title' => 'شروع از مبانی',
		'desc'  => 'اگر تازه واردی، از مفاهیم پایه و مقاله‌های مقدماتی شروع کن.',
		'url'   => home_url( '/topic/fundamentals/' ),
	),
	array(
		'title' => 'مرور دسته‌ها',
		'desc'  => 'اگر دنبال مسیر موضوعی هستی، از دسته‌بندی‌های اصلی وارد شو.',
		'url'   => '#qp-front-cats',
	),
	array(
		'title' => 'آشنایی با دانشمندان',
		'desc'  => 'نقش چهره‌های اصلی این علم را در شکل‌گیری نظریه ببین.',
		'url'   => home_url( '/scientists/' ),
	),
);

$latest_articles = new WP_Query(
	array(
		'post_type'              => 'quantum_article',
		'posts_per_page'         => 6,
		'post_status'            => 'publish',
		'orderby'                => 'date',
		'order'                  => 'DESC',
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
	)
);

$featured_scientists = new WP_Query(
	array(
		'post_type'              => 'quantum_scientist',
		'posts_per_page'         => 6,
		'post_status'            => 'publish',
		'orderby'                => 'date',
		'order'                  => 'DESC',
		'ignore_sticky_posts'    => true,
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
	)
);
?>
<main id="primary" class="site-main">
	<div class="container qp-front">
		<section class="qp-front-hero">
			<div class="qp-front-hero__badge">دانشنامهٔ فارسی فیزیک کوانتوم</div>
			<h1 class="qp-front-hero__title">کوانتوم را از پایه، دقیق و روان یاد بگیرید</h1>
			<p class="qp-front-hero__desc">از مبانی نظری تا فناوری‌های واقعی — با مقاله‌های کوتاه، دسته‌بندی روشن و مسیر مطالعهٔ قابل‌فهم.</p>

			<div class="qp-front-hero__stats" aria-label="آمار دانشنامه">
				<div class="qp-front-stat">
					<span class="qp-front-stat__num"><?php echo esc_html( number_format_i18n( $article_total ) ); ?></span>
					<span class="qp-front-stat__label">مقاله</span>
				</div>
				<div class="qp-front-stat">
					<span class="qp-front-stat__num"><?php echo esc_html( number_format_i18n( is_wp_error( $parent_categories ) ? 0 : count( $parent_categories ) ) ); ?></span>
					<span class="qp-front-stat__label">دستهٔ اصلی</span>
				</div>
				<div class="qp-front-stat">
					<span class="qp-front-stat__num"><?php echo esc_html( number_format_i18n( $sub_total ) ); ?></span>
					<span class="qp-front-stat__label">زیردسته</span>
				</div>
				<div class="qp-front-stat">
					<span class="qp-front-stat__num"><?php echo esc_html( number_format_i18n( $scientist_total ) ); ?></span>
					<span class="qp-front-stat__label">دانشمند</span>
				</div>
			</div>

			<div class="qp-front-search">
				<?php get_search_form(); ?>
			</div>

			<div class="qp-front-hero__actions">
				<a class="qp-front-btn qp-front-btn--primary" href="<?php echo esc_url( home_url( '/topic/fundamentals/' ) ); ?>">شروع از مبانی</a>
				<a class="qp-front-btn qp-front-btn--ghost" href="#qp-front-cats">مرور دسته‌ها</a>
			</div>
		</section>

		<section class="qp-front-section qp-front-section--path">
			<div class="qp-front-section__head">
				<div>
					<div class="qp-front-section__eyebrow">مسیر سریع</div>
					<h2 class="qp-front-section__title">از کجا شروع کنم؟</h2>
				</div>
			</div>

			<div class="qp-front-paths">
				<?php foreach ( $quick_links as $item ) : ?>
					<a class="qp-front-path" href="<?php echo esc_url( $item['url'] ); ?>">
						<h3 class="qp-front-path__title"><?php echo esc_html( $item['title'] ); ?></h3>
						<p class="qp-front-path__desc"><?php echo esc_html( $item['desc'] ); ?></p>
						<span class="qp-front-path__more">ورود به بخش</span>
					</a>
				<?php endforeach; ?>
			</div>
		</section>

		<section id="qp-front-cats" class="qp-front-section qp-front-section--cats">
			<div class="qp-front-section__head">
				<div>
					<div class="qp-front-section__eyebrow">ساختار دانشنامه</div>
					<h2 class="qp-front-section__title">دسته‌بندی موضوعات</h2>
					<p class="qp-front-section__desc">هفت مسیر اصلی برای خواندن موضوعی مقاله‌ها.</p>
				</div>
			</div>

			<?php if ( ! is_wp_error( $parent_categories ) && ! empty( $parent_categories ) ) : ?>
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
						$slug      = isset( $category->slug ) ? $category->slug : '';
						$cat_desc  = isset( $cat_descriptions[ $slug ] ) ? $cat_descriptions[ $slug ] : '';
						$cat_icon  = isset( $cat_icons[ $slug ] ) ? $cat_icons[ $slug ] : 'موضوع';
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

		<section class="qp-front-section qp-front-section--articles">
			<div class="qp-front-section__head">
				<div>
					<div class="qp-front-section__eyebrow">تازه‌ترین‌ها</div>
					<h2 class="qp-front-section__title">آخرین مقاله‌ها</h2>
				</div>
				<a class="qp-front-section__link" href="#qp-front-cats">مرور دسته‌ها</a>
			</div>

			<?php if ( $latest_articles->have_posts() ) : ?>
				<div class="qp-front-articles">
					<?php while ( $latest_articles->have_posts() ) : $latest_articles->the_post(); ?>
						<?php
						$terms      = get_the_terms( get_the_ID(), 'quantum_category' );
						$term_label = '';
						if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
							$term_label = $terms[0]->name;
						}
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

		<section class="qp-front-section qp-front-section--scientists">
			<div class="qp-front-section__head">
				<div>
					<div class="qp-front-section__eyebrow">تالار دانشمندان</div>
					<h2 class="qp-front-section__title">چهره‌های مهم کوانتوم</h2>
				</div>
				<a class="qp-front-section__link" href="<?php echo esc_url( home_url( '/scientists/' ) ); ?>">همهٔ دانشمندان</a>
			</div>

			<?php if ( $featured_scientists->have_posts() ) : ?>
				<div class="qp-front-scientists">
					<?php while ( $featured_scientists->have_posts() ) : $featured_scientists->the_post(); ?>
						<?php
						$en_name = trim( (string) get_post_meta( get_the_ID(), '_scientist_en_name', true ) );
						$initial = '';
						if ( $en_name ) {
							$initial = strtoupper( function_exists( 'mb_substr' ) ? mb_substr( $en_name, 0, 1, 'UTF-8' ) : substr( $en_name, 0, 1 ) );
						} else {
							$initial = 'Q';
						}
						?>
						<a class="qp-front-scientist" href="<?php the_permalink(); ?>">
							<div class="qp-front-scientist__media">
								<?php if ( has_post_thumbnail() ) : ?>
									<?php echo get_the_post_thumbnail( get_the_ID(), 'medium_large', array( 'class' => 'qp-front-scientist__image' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
				</div>
				<?php wp_reset_postdata(); ?>
			<?php endif; ?>
		</section>
	</div>
</main>
<?php
get_footer();