<?php
/**
 * Autoteams Calibre: основные функции темы.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'AT_VER', '1.0.6' );

require get_template_directory() . '/inc/defaults.php';
require get_template_directory() . '/inc/cpt.php';
require get_template_directory() . '/inc/customizer.php';
require get_template_directory() . '/inc/lead.php';

add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'comment-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'custom-logo', array( 'height' => 88, 'width' => 320, 'flex-width' => true, 'flex-height' => true ) );
	add_image_size( 'at-card', 720, 405, true );
	register_nav_menus( array(
		'primary' => 'Главное меню',
		'footer'  => 'Меню в подвале',
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'autoteams', get_stylesheet_uri(), array(), AT_VER );
	wp_enqueue_script( 'autoteams', get_template_directory_uri() . '/assets/js/theme.js', array(), AT_VER, true );
} );

/** При первой активации на чистом WordPress: главная страница и страница новостей. */
add_action( 'after_switch_theme', function () {
	if ( get_option( 'at_setup_done' ) ) { return; }
	if ( 'posts' === get_option( 'show_on_front' ) ) {
		$home = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Главная' ) );
		$news = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Новости', 'post_name' => 'news' ) );
		if ( $home && ! is_wp_error( $home ) && $news && ! is_wp_error( $news ) ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $home );
			update_option( 'page_for_posts', $news );
		}
	}
	update_option( 'at_setup_done', 1 );
	flush_rewrite_rules();
} );

/* ---------- Помощники для шаблонов ---------- */

function at_opt( $key ) {
	$d = at_defaults();
	return get_theme_mod( 'at_' . $key, isset( $d[ $key ] ) ? $d[ $key ] : '' );
}

function at_tel() {
	return preg_replace( '/[^\d+]/', '', (string) at_opt( 'phone' ) );
}

function at_wa() {
	$n = preg_replace( '/\D/', '', (string) at_opt( 'whatsapp' ) );
	return $n ? 'https://wa.me/' . $n : '';
}

function at_price( $v ) {
	$n = preg_replace( '/\D/', '', (string) $v );
	return '' === $n ? '' : 'от ' . number_format( (int) $n, 0, ',', "\xC2\xA0" ) . "\xC2\xA0₽";
}

function at_news_url() {
	$id = (int) get_option( 'page_for_posts' );
	return $id ? get_permalink( $id ) : home_url( '/' );
}

/** Включены ли новости на сайте (настройка в «Контакты и тексты», по умолчанию выключено). */
function at_news_on() {
	return (bool) at_opt( 'show_news' );
}

/** Пока новости выключены, их страницы ведут на главную. */
add_action( 'template_redirect', function () {
	if ( at_news_on() || is_admin() || is_customize_preview() || is_preview() ) { return; }
	$is_news = ( is_home() && ! is_front_page() ) || is_singular( 'post' ) || is_category() || is_tag() || is_date() || is_author();
	if ( $is_news ) {
		wp_safe_redirect( home_url( '/' ), 302 );
		exit;
	}
} );

function at_menu_fallback() {
	$items = array(
		home_url( '/#services' ) => 'Услуги',
		get_post_type_archive_link( 'work' ) => 'Работы',
		at_news_url() => 'Новости',
		home_url( '/#contact' ) => 'Контакты',
	);
	if ( ! at_news_on() ) {
		unset( $items[ at_news_url() ] );
	}
	echo '<ul>';
	foreach ( $items as $url => $label ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $label ) );
	}
	echo '</ul>';
}

/**
 * Условная карта калибровки для главного экрана. Значения вычисляются по формуле,
 * это иллюстрация, а не данные конкретного автомобиля.
 */
function at_map_data() {
	$rpm  = array( 1500, 2000, 2500, 3000, 3500, 4000, 4500, 5000 );
	$load = array( 20, 35, 50, 65, 80, 95 );
	$rows = array();
	$min  = 99;
	$max  = 0;
	foreach ( $load as $i => $l ) {
		$row = array();
		foreach ( $rpm as $j => $r ) {
			$a = 10 + 2.3 * $i + 1.1 * $j;
			$bump = 3.6 * exp( -( pow( $i - 4, 2 ) / 5 + pow( $j - 4, 2 ) / 7 ) );
			$b = $a + $bump;
			$row[] = array( round( $a, 1 ), round( $b, 1 ) );
			$min = min( $min, $a, $b );
			$max = max( $max, $a, $b );
		}
		$rows[] = array( 'load' => $l, 'cells' => $row );
	}
	return array( 'rpm' => $rpm, 'rows' => $rows, 'min' => $min, 'max' => $max );
}

/** Карточка работы. $w: title, url, img, excerpt, tag, gain_hp, gain_nm */
function at_work_card( $w ) {
	?>
	<article class="work">
		<div class="work-img">
			<?php if ( ! empty( $w['img'] ) ) : ?>
				<img src="<?php echo esc_url( $w['img'] ); ?>" alt="<?php echo esc_attr( $w['title'] ); ?>" loading="lazy" width="720" height="405">
			<?php endif; ?>
		</div>
		<div class="work-body">
			<h3>
				<?php if ( ! empty( $w['url'] ) ) : ?>
					<a href="<?php echo esc_url( $w['url'] ); ?>"><?php echo esc_html( $w['title'] ); ?></a>
				<?php else : ?>
					<?php echo esc_html( $w['title'] ); ?>
				<?php endif; ?>
			</h3>
			<?php if ( ! empty( $w['excerpt'] ) ) : ?><p><?php echo esc_html( $w['excerpt'] ); ?></p><?php endif; ?>
			<div class="work-meta">
				<?php if ( ! empty( $w['gain_hp'] ) ) : ?><span class="gain">+<?php echo esc_html( $w['gain_hp'] ); ?> л.с.</span><?php endif; ?>
				<?php if ( ! empty( $w['gain_nm'] ) ) : ?><span class="gain">+<?php echo esc_html( $w['gain_nm'] ); ?> Н·м</span><?php endif; ?>
				<?php if ( ! empty( $w['gain_text'] ) ) : ?><span class="gain"><?php echo esc_html( $w['gain_text'] ); ?></span><?php endif; ?>
				<?php if ( ! empty( $w['tag'] ) ) : ?><span><?php echo esc_html( $w['tag'] ); ?></span><?php endif; ?>
			</div>
		</div>
	</article>
	<?php
}

/** Превращает запись «Работа» в массив для карточки. */
function at_work_from_post( $post ) {
	$hp_b = (int) get_post_meta( $post->ID, '_at_hp_before', true );
	$hp_a = (int) get_post_meta( $post->ID, '_at_hp_after', true );
	$nm_b = (int) get_post_meta( $post->ID, '_at_nm_before', true );
	$nm_a = (int) get_post_meta( $post->ID, '_at_nm_after', true );
	$tag  = get_post_meta( $post->ID, '_at_stage', true );
	if ( ! $tag ) { $tag = get_post_meta( $post->ID, '_at_ecu', true ); }
	if ( ! $tag ) {
		$terms = get_the_terms( $post, 'work_cat' );
		if ( $terms && ! is_wp_error( $terms ) ) { $tag = $terms[0]->name; }
	}
	return array(
		'title'   => get_the_title( $post ),
		'url'     => get_permalink( $post ),
		'img'     => get_the_post_thumbnail_url( $post, 'at-card' ),
		'excerpt' => has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 22 ),
		'tag'     => $tag,
		'gain_hp' => ( $hp_a > $hp_b && $hp_b > 0 ) ? $hp_a - $hp_b : 0,
		'gain_nm' => ( $nm_a > $nm_b && $nm_b > 0 ) ? $nm_a - $nm_b : 0,
		'gain_text' => (string) get_post_meta( $post->ID, '_at_gain_text', true ),
	);
}

/** Страница «Все работы» и фильтр по категории показывают до 24 работ сразу. */
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() && $q->is_main_query() && ( $q->is_post_type_archive( 'work' ) || $q->is_tax( 'work_cat' ) ) ) {
		$q->set( 'posts_per_page', 24 );
	}
} );
