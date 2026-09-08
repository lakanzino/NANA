<?php
/**
 * The header for Quantum Pedia theme.
 *
 * @package Quantum_Pedia
 * @since   1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
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

<a class="skip-link screen-reader-text" href="#primary">
	<?php esc_html_e( 'Skip to content', 'quantum-pedia' ); ?>
</a>

<header id="masthead" class="site-header">
	<div class="container site-header__inner">

		<div class="site-branding">
			<?php
			if ( has_custom_logo() ) {
				the_custom_logo();
			} else {
				?>
				<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<span class="brand__mark" aria-hidden="true">Q</span>
					<span class="brand__text">
						<span class="site-title"><?php bloginfo( 'name' ); ?></span>
						<?php
						$quantum_pedia_description = get_bloginfo( 'description', 'display' );
						if ( $quantum_pedia_description || is_customize_preview() ) :
							?>
							<span class="site-description"><?php echo $quantum_pedia_description; ?></span>
						<?php endif; ?>
					</span>
				</a>
				<?php
			}
			?>
		</div><!-- .site-branding -->

		<nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'Primary Menu', 'quantum-pedia' ); ?>">
			<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false">
				<span class="screen-reader-text"><?php esc_html_e( 'Menu', 'quantum-pedia' ); ?></span>
				<span class="menu-toggle__icon" aria-hidden="true"></span>
			</button>
			<?php
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'menu_id'        => 'primary-menu',
					'container'      => false,
					'fallback_cb'    => false,
				)
			);
			?>
		</nav><!-- #site-navigation -->

		<div class="site-header__actions">
			<button class="search-toggle" aria-expanded="false" aria-controls="header-search" aria-label="<?php esc_attr_e( 'Open search', 'quantum-pedia' ); ?>">
				<span aria-hidden="true">⌕</span>
			</button>
		</div>

	</div><!-- .container -->

	<div id="header-search" class="header-search" hidden>
		<div class="container">
			<?php get_search_form(); ?>
		</div>
	</div>
	

</header><!-- #masthead -->