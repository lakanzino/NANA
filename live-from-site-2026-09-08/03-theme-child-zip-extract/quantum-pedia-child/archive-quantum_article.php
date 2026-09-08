<?php
/**
 * Archive template for quantum_article.
 *
 * در وضعیت فعلی سایت، این فایل عملاً آرشیو /glossary/ را هم پوشش می‌دهد.
 *
 * @package Quantum_Pedia_Child
 */

defined( 'ABSPATH' ) || exit;

get_header();

$request_path = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
$request_path = is_string( $request_path ) ? trim( $request_path, '/' ) : '';
$is_glossary  = ( 0 === strpos( $request_path, 'glossary' ) );

$title = $is_glossary ? 'اصطلاحات کوانتوم' : 'مقاله‌های کوانتوم';
$desc  = $is_glossary
	? 'فهرستی از مدخل‌ها و مقاله‌های پایه برای شروع سریع در دانشنامهٔ کوانتوم.'
	: 'مرور همهٔ مقاله‌های منتشرشده در کوانتوم پدیا.';
?>
<main id="primary" class="site-main">
	<div class="container qp-archive qp-archive--articles">
		<header class="qp-archive__hero">
			<div class="qp-archive__eyebrow"><?php echo $is_glossary ? 'واژه‌نامه' : 'آرشیو مقاله‌ها'; ?></div>
			<h1 class="qp-archive__title"><?php echo esc_html( $title ); ?></h1>
			<p class="qp-archive__desc"><?php echo esc_html( $desc ); ?></p>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="qp-archive-list qp-archive-list--articles">
				<?php while ( have_posts() ) : the_post(); ?>
					<?php
					$terms = get_the_terms( get_the_ID(), 'quantum_category' );
					?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'qp-archive-card qp-archive-card--article' ); ?>>
						<?php if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) : ?>
							<div class="qp-archive-card__chips" aria-label="دسته‌ها">
								<?php foreach ( array_slice( $terms, 0, 3 ) as $term ) : ?>
									<a class="qp-archive-card__chip" href="<?php echo esc_url( get_term_link( $term ) ); ?>">
										<?php echo esc_html( $term->name ); ?>
									</a>
								<?php endforeach; ?>
							</div>
						<?php endif; ?>

						<h2 class="qp-archive-card__title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>

						<?php if ( has_excerpt() ) : ?>
							<p class="qp-archive-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
						<?php endif; ?>

						<div class="qp-archive-card__meta">
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
								<?php echo esc_html( get_the_date( 'j F Y' ) ); ?>
							</time>
							<a class="qp-archive-card__more" href="<?php the_permalink(); ?>">مطالعهٔ مقاله</a>
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
				<h2 class="qp-empty-state__title">هنوز مقاله‌ای اینجا ثبت نشده است</h2>
				<p class="qp-empty-state__desc">بعد از انتشار مدخل‌های جدید، این صفحه به‌روزرسانی می‌شود.</p>
			</div>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();