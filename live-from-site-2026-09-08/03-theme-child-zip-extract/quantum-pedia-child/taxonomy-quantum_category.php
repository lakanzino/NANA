<?php
/**
 * Template for quantum_category archives.
 *
 * @package Quantum_Pedia_Child
 */

defined( 'ABSPATH' ) || exit;

get_header();

$term        = get_queried_object();
$title       = $term && ! is_wp_error( $term ) ? $term->name : single_term_title( '', false );
$description = get_the_archive_description();
?>
<main id="primary" class="site-main">
	<div class="container qp-archive qp-archive--articles">
		<header class="qp-archive__hero">
			<div class="qp-archive__eyebrow">دسته‌بندی کوانتوم</div>
			<h1 class="qp-archive__title"><?php echo esc_html( $title ); ?></h1>
			<?php if ( $description ) : ?>
				<div class="qp-archive__desc"><?php echo wp_kses_post( $description ); ?></div>
			<?php else : ?>
				<p class="qp-archive__desc">مقاله‌های این شاخه از دانشنامهٔ کوانتوم در این صفحه فهرست می‌شوند.</p>
			<?php endif; ?>
		</header>

		<?php if ( have_posts() ) : ?>
			<div class="qp-archive-list qp-archive-list--articles">
				<?php while ( have_posts() ) : the_post(); ?>
					<article id="post-<?php the_ID(); ?>" <?php post_class( 'qp-archive-card qp-archive-card--article' ); ?>>
						<h2 class="qp-archive-card__title">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h2>
						<?php if ( has_excerpt() ) : ?>
							<p class="qp-archive-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
						<?php endif; ?>
						<div class="qp-archive-card__meta">
							<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></time>
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
				<h2 class="qp-empty-state__title">هنوز مقاله‌ای در این دسته منتشر نشده است</h2>
				<p class="qp-empty-state__desc">عنوان و مسیر این دسته درست ثبت شده و می‌توانید بعداً مقاله‌های آن را اضافه کنید.</p>
			</div>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
