<?php
/**
 * Fallback single template.
 *
 * این فایل تضمین می‌کند هر محتوای تکی، حتی بدون قالب اختصاصی،
 * با هدر/فوتر و چیدمان پایه نمایش داده شود.
 *
 * @package Quantum_Pedia_Child
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<main id="primary" class="site-main">
	<div class="container qp-shell qp-shell--single">
		<?php while ( have_posts() ) : the_post(); ?>
			<?php
			$post_type        = get_post_type();
			$post_type_object = get_post_type_object( $post_type );
			$post_type_label  = $post_type_object && ! empty( $post_type_object->labels->singular_name )
				? $post_type_object->labels->singular_name
				: '';
			?>

			<article id="post-<?php the_ID(); ?>" <?php post_class( 'qp-single qp-single--generic' ); ?>>
				<header class="qp-single__header">
					<?php if ( $post_type_label ) : ?>
						<div class="qp-single__eyebrow"><?php echo esc_html( $post_type_label ); ?></div>
					<?php endif; ?>

					<h1 class="qp-single__title"><?php the_title(); ?></h1>

					<div class="qp-single__meta">
						<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
							<?php echo esc_html( get_the_date( 'j F Y' ) ); ?>
						</time>
					</div>

					<?php if ( has_excerpt() ) : ?>
						<p class="qp-single__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
					<?php endif; ?>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="qp-single__media">
						<?php the_post_thumbnail( 'large', array( 'class' => 'qp-single__image' ) ); ?>
					</figure>
				<?php endif; ?>

				<div class="qp-single__content entry-content">
					<?php the_content(); ?>
				</div>
			</article>
		<?php endwhile; ?>
	</div>
</main>
<?php
get_footer();