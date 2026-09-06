<?php
/**
 * Front page template — نسخهٔ ۳.
 *
 * ترتیب بخش‌ها:
 *   ۱) قهرمان (بدون آمار)
 *   ۲) مقاله‌های پیشنهادی
 *   ۳) تازه‌ترین نوشته‌ها  ← آمد بالا، بدون تصویر شاخص
 *   ۴) جست‌وجو
 *   ۵) شمارنده‌های انیمیشنی
 *   ۶) دسته‌بندی موضوعات
 *   ۷) دانشمندان (ردیف اسلایدری)
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

/*
 * ── مقاله‌های پیشنهادی ─────────────────────────────────────────
 * برای تغییر، فقط اسلاگ و متن قلاب را عوض کن.
 * کارت اول عرض دو ستون می‌گیرد، پس جذاب‌ترین را اول بگذار.
 */
$qp_featured_slugs = array(
	array(
		'slug' => 'schrodinger-cat',
		'hook' => 'گربه‌ای که نه زنده بود نه مرده — و چرا این ماجرا اصلاً دربارهٔ گربه نیست.',
		'tag'  => 'معروف‌ترین',
	),
	array(
		'slug' => 'double-slit-experiment',
		'hook' => 'آزمایشی که فاینمن آن را «تنها راز واقعی کوانتوم» می‌دانست.',
		'tag'  => 'کلاسیک',
	),
	array(
		'slug' => 'quantum-entanglement-explained',
		'hook' => 'اینشتین اسمش را گذاشت «کنش شبح‌وار از راه دور» و تا آخر عمر قبولش نکرد.',
		'tag'  => 'پرسش‌برانگیز',
	),
	array(
		'slug' => 'q-day',
		'hook' => 'روزی که رمزنگاری اینترنت می‌شکند. چقدر فاصله داریم؟',
		'tag'  => 'کاربردی',
	),
	array(
		'slug' => 'nobel-physics-2025',
		'hook' => 'نوبل امسال به سه نفری رسید که کوانتوم را از اتم بیرون کشیدند.',
		'tag'  => 'تازه',
	),
	array(
		'slug' => 'law-of-attraction-quantum',
		'hook' => 'رایج‌ترین سوءاستفاده از کوانتوم؛ دقیقاً کجای استدلال می‌لنگد؟',
		'tag'  => 'نقد',
	),
);

/*
 * یک کوئری برای همهٔ پیشنهادی‌ها به‌جای شش فراخوانی جدا.
 */
$qp_featured_posts = array();
$qp_slug_list      = wp_list_pluck( $qp_featured_slugs, 'slug' );

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

/* اگر هیچ‌کدام پیدا نشد، به آخرین مقاله‌ها برگرد تا بخش خالی نماند. */
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
		'post_type'           => 'quantum_scientist',
		'posts_per_page'      => 10,
		'post_status'         => 'publish',
		'orderby'             => 'date',
		'order'               => 'DESC',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);

/* سه تصویر اول eager، بقیه lazy — برای سرعت بارگذاری اول. */
$qp_sci_index = 0;

/* شمارنده‌ها: مقدار واقعی در data-target می‌رود تا جاوااسکریپت بشمارد. */
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
?>
<main id="primary" class="site-main">
	<div class="container qp-front">

		<section class="qp-front-hero">
			<div class="qp-front-hero__badge">دانشنامهٔ فارسی فیزیک کوانتوم</div>
			<h1 class="qp-front-hero__title">شگفتی‌های دنیای کوانتوم را ساده، دقیق و بی‌اغراق کشف کنید</h1>
			<p class="qp-front-hero__desc">هر مقاله با منبع علمی معتبر نوشته شده، به زبان ساده — بدون فرمول‌های ترسناک و بدون ادعاهای بی‌پایه.</p>

			<div class="qp-front-hero__actions">
				<a class="qp-front-btn qp-front-btn--primary" href="<?php echo esc_url( home_url( '/topic/fundamentals/' ) ); ?>">شروع از مبانی</a>
				<a class="qp-front-btn qp-front-btn--ghost" href="#qp-front-cats">مرور دسته‌ها</a>
			</div>
		</section>

		<section class="qp-front-section qp-front-section--search" aria-label="جست‌وجو">
			<div class="qp-front-searchbox">
				<div class="qp-front-searchbox__label">دنبال موضوع خاصی هستید؟</div>
				<div class="qp-front-search">
					<?php get_search_form(); ?>
				</div>
			</div>
		</section>

		<section class="qp-front-section qp-front-section--stats" aria-label="آمار دانشنامه">
			<div class="qp-front-hero__stats" data-qp-counters>
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

		<?php if ( ! empty( $qp_featured_posts ) ) : ?>
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

		<section class="qp-front-section qp-front-section--articles">
			<div class="qp-front-section__head">
				<div>
					<div class="qp-front-section__eyebrow">تازه‌ترین‌ها</div>
					<h2 class="qp-front-section__title">آخرین مقاله‌ها</h2>
				</div>
				<a class="qp-front-section__link" href="<?php echo esc_url( home_url( '/topic/fundamentals/' ) ); ?>">همهٔ مقاله‌ها</a>
			</div>

			<?php if ( $latest_articles->have_posts() ) : ?>
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

		<section id="qp-front-cats" class="qp-front-section qp-front-section--cats">
			<div class="qp-front-section__head">
				<div>
					<div class="qp-front-section__eyebrow">ساختار دانشنامه</div>
					<h2 class="qp-front-section__title">دسته‌بندی موضوعات</h2>
					<p class="qp-front-section__desc">مسیرهای اصلی برای خواندن موضوعی مقاله‌ها.</p>
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

		<section class="qp-front-section qp-front-section--scientists">
			<div class="qp-front-section__head">
				<div>
					<div class="qp-front-section__eyebrow">تالار دانشمندان</div>
					<h2 class="qp-front-section__title">چهره‌های مهم کوانتوم</h2>
					<p class="qp-front-section__desc qp-front-swipe-hint">برای دیدن بقیه، ردیف را بکشید.</p>
				</div>
				<a class="qp-front-section__link" href="<?php echo esc_url( home_url( '/scientists/' ) ); ?>">همهٔ دانشمندان</a>
			</div>

			<?php if ( $featured_scientists->have_posts() ) : ?>
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

						<a class="qp-front-scientist qp-front-scientist--all" href="<?php echo esc_url( home_url( '/scientists/' ) ); ?>">
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

	</div>
</main>
<?php
get_footer();
