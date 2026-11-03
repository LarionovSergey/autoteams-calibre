<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#main">Перейти к содержимому</a>

<header class="site-header">
	<div class="wrap">
		<?php if ( has_custom_logo() ) : ?>
			<?php the_custom_logo(); ?>
		<?php else : ?>
			<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<span class="brand-name"><?php bloginfo( 'name' ); ?></span>
				<?php $at_sub = get_bloginfo( 'description' ); ?>
				<span class="brand-sub"><?php echo esc_html( $at_sub ? $at_sub : at_opt( 'brand_sub' ) ); ?></span>
			</a>
		<?php endif; ?>

		<button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">Меню</button>
		<nav class="nav" id="site-nav" aria-label="Главное меню">
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'fallback_cb'    => 'at_menu_fallback',
			) );
			?>
		</nav>
		<a class="header-phone" href="tel:<?php echo esc_attr( at_tel() ); ?>"><?php echo esc_html( at_opt( 'phone' ) ); ?></a>
	</div>
</header>
<main id="main">
