<?php
/**
 * Global custom header for Quantum Pedia Child.
 *
 * @package Quantum_Pedia_Child
 */

defined( 'ABSPATH' ) || exit;

$qpedia_header_categories = get_terms(
	array(
		'taxonomy'   => 'quantum_category',
		'hide_empty' => true,
		'parent'     => 0,
	)
);

$qpedia_short_labels = array(
	'fundamentals'        => 'مفاهیم',
	'technology'          => 'فناوری',
	'phenomena'           => 'پدیده',
	'history-experiments' => 'آزمایش',
	'interpretations'     => 'تفسیر',
	'pseudoscience'       => 'شبه علم',
);

$qpedia_order = array(
	'fundamentals'        => 10,
	'technology'          => 20,
	'phenomena'           => 30,
	'history-experiments' => 40,
	'interpretations'     => 50,
	'pseudoscience'       => 60,
);

if ( ! is_wp_error( $qpedia_header_categories ) && ! empty( $qpedia_header_categories ) ) {
	usort(
		$qpedia_header_categories,
		static function( $a, $b ) use ( $qpedia_order ) {
			$ao = isset( $qpedia_order[ $a->slug ] ) ? $qpedia_order[ $a->slug ] : 999;
			$bo = isset( $qpedia_order[ $b->slug ] ) ? $qpedia_order[ $b->slug ] : 999;
			if ( $ao === $bo ) {
				return strcmp( $a->name, $b->name );
			}
			return $ao <=> $bo;
		}
	);
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'quantum-pedia-child' ); ?></a>

<header id="masthead" class="qp-global-header">
	<div class="container qp-global-header__inner">
		<div class="qp-global-header__brand">
			<a class="qp-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
				<span class="qp-brand__logo-wrap">
					<img class="qp-brand__logo" src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/images/qpedia-logo-white.png' ); ?>" alt="<?php esc_attr_e( 'لوگوی کوانتوم پدیا', 'quantum-pedia-child' ); ?>" width="52" height="52" decoding="async" />
				</span>
				<span class="qp-brand__text">
					<span class="qp-brand__title">کوانتوم پدیا فارسی</span>
					<span class="qp-brand__desc">دانشنامه‌ای دقیق، کاربردی و خوش‌خوان برای یادگیری کوانتوم</span>
				</span>
			</a>
		</div>

		<nav class="qp-desktop-nav" aria-label="<?php esc_attr_e( 'Quantum categories', 'quantum-pedia-child' ); ?>">
			<?php if ( ! is_wp_error( $qpedia_header_categories ) && ! empty( $qpedia_header_categories ) ) : ?>
				<ul class="qp-desktop-nav__list">
					<?php foreach ( $qpedia_header_categories as $parent_term ) : ?>
						<?php
						$children = get_terms(
							array(
								'taxonomy'   => 'quantum_category',
								'hide_empty' => true,
								'parent'     => $parent_term->term_id,
								'orderby'    => 'count',
								'order'      => 'DESC',
							)
						);
						$has_children = ! is_wp_error( $children ) && ! empty( $children );
						$menu_label   = isset( $qpedia_short_labels[ $parent_term->slug ] ) ? $qpedia_short_labels[ $parent_term->slug ] : $parent_term->name;
						?>
						<li class="qp-desktop-nav__item<?php echo $has_children ? ' has-children' : ''; ?>">
							<div class="qp-desktop-nav__trigger-wrap">
								<a class="qp-desktop-nav__link" href="<?php echo esc_url( get_term_link( $parent_term ) ); ?>"><?php echo esc_html( $menu_label ); ?></a>
								<?php if ( $has_children ) : ?>
									<button class="qp-desktop-nav__toggle" type="button" aria-expanded="false" aria-label="<?php echo esc_attr( sprintf( 'نمایش زیردسته‌های %s', $parent_term->name ) ); ?>">
										<span aria-hidden="true">+</span>
									</button>
								<?php endif; ?>
							</div>
							<?php if ( $has_children ) : ?>
								<div class="qp-desktop-nav__panel">
									<div class="qp-desktop-nav__panel-title"><?php echo esc_html( $parent_term->name ); ?></div>
									<ul class="qp-desktop-nav__sublist">
										<?php foreach ( $children as $child_term ) : ?>
											<li><a href="<?php echo esc_url( get_term_link( $child_term ) ); ?>"><?php echo esc_html( $child_term->name ); ?></a></li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</nav>
	</div>
</header>
