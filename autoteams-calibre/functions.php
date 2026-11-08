<?php
/**
 * Autoteams Calibre: основные функции темы.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'AT_VER', '1.0.57' );

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
	if ( ! is_admin() && $q->is_main_query() && ( $q->is_post_type_archive( 'work' ) || $q->is_tax( 'work_cat' ) || $q->is_tax( 'car_make' ) ) ) {
		$q->set( 'posts_per_page', 24 );
	}
} );

/* ---------- Марки автомобилей ---------- */

/** Марка работы определяется по началу названия («Toyota Hilux 2.7d, 2016» даёт Toyota). Возвращает array( название, slug ). */
function at_make_from_title( $title ) {
	$title = trim( wp_strip_all_tags( (string) $title ) );
	// Начало названия (без учёта регистра) => как показывать марку.
	$known = array(
		'Land Rover' => 'Land Rover', 'Range Rover' => 'Land Rover', 'Mercedes' => 'Mercedes-Benz',
		'Škoda' => 'Skoda', 'Skoda' => 'Skoda', 'VW ' => 'Volkswagen', 'Volkswagen' => 'Volkswagen',
		'Toyota' => 'Toyota', 'Lexus' => 'Lexus', 'Honda' => 'Honda', 'Nissan' => 'Nissan', 'Infiniti' => 'Infiniti',
		'Mazda' => 'Mazda', 'Mitsubishi' => 'Mitsubishi', 'Subaru' => 'Subaru', 'Suzuki' => 'Suzuki', 'Isuzu' => 'Isuzu',
		'Hyundai' => 'Hyundai', 'Genesis' => 'Genesis', 'Kia' => 'Kia', 'SsangYong' => 'SsangYong', 'Ssang Yong' => 'SsangYong',
		'Audi' => 'Audi', 'BMW' => 'BMW', 'Opel' => 'Opel', 'Renault' => 'Renault', 'Peugeot' => 'Peugeot', 'Citroen' => 'Citroen',
		'Ford' => 'Ford', 'Chevrolet' => 'Chevrolet', 'Jeep' => 'Jeep', 'Cadillac' => 'Cadillac', 'Dodge' => 'Dodge',
		'Volvo' => 'Volvo', 'Lada' => 'Lada', 'ВАЗ' => 'Lada', 'УАЗ' => 'УАЗ', 'ГАЗ' => 'ГАЗ', 'Haval' => 'Haval', 'Chery' => 'Chery',
		'Geely' => 'Geely', 'Great Wall' => 'Great Wall', 'Daewoo' => 'Daewoo', 'Chrysler' => 'Chrysler', 'Mini' => 'Mini',
	);
	foreach ( $known as $prefix => $label ) {
		if ( 0 === mb_stripos( $title, $prefix ) ) {
			return array( $label, at_make_slug( $label ) );
		}
	}
	if ( preg_match( '/^[\p{L}][\p{L}\p{N}-]*/u', $title, $m ) ) {
		return array( $m[0], at_make_slug( $m[0] ) );
	}
	return array( '', '' );
}

function at_make_slug( $label ) {
	$map = array( 'УАЗ' => 'uaz', 'ГАЗ' => 'gaz' );
	if ( isset( $map[ $label ] ) ) { return $map[ $label ]; }
	$slug = sanitize_title( remove_accents( $label ) );
	return preg_match( '/^[a-z0-9-]+$/', $slug ) ? $slug : 'make-' . substr( md5( $label ), 0, 6 );
}

/** Назначает работе марку по названию, если марка ещё не выбрана вручную. */
function at_assign_make( $post_id ) {
	if ( wp_get_object_terms( $post_id, 'car_make', array( 'fields' => 'ids' ) ) ) { return; }
	list( $label, $slug ) = at_make_from_title( get_the_title( $post_id ) );
	if ( '' === $label ) { return; }
	$term = get_term_by( 'slug', $slug, 'car_make' );
	if ( ! $term ) {
		$res = wp_insert_term( $label, 'car_make', array( 'slug' => $slug ) );
		if ( is_wp_error( $res ) ) { return; }
		$term_id = (int) $res['term_id'];
	} else {
		$term_id = (int) $term->term_id;
	}
	wp_set_object_terms( $post_id, array( $term_id ), 'car_make' );
}

/** Новая или изменённая работа получает марку автоматически. */
add_action( 'save_post_work', function ( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || 'auto-draft' === $post->post_status ) { return; }
	at_assign_make( $post_id );
}, 20, 2 );

/** Один раз после обновления темы: расставляет марки уже существующим работам. */
add_action( 'init', function () {
	if ( get_option( 'at_makes_synced' ) === AT_VER ) { return; }
	$ids = get_posts( array( 'post_type' => 'work', 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'fields' => 'ids', 'nopaging' => true ) );
	foreach ( $ids as $id ) { at_assign_make( $id ); }
	update_option( 'at_makes_synced', AT_VER, false );
}, 30 );

/** Марки, которые есть у работ выбранной категории (или у всех работ): список из array( term, count ). */
function at_makes_for_category( $cat_slug = '' ) {
	$args = array( 'post_type' => 'work', 'post_status' => 'publish', 'fields' => 'ids', 'nopaging' => true, 'no_found_rows' => true );
	if ( $cat_slug ) {
		$args['tax_query'] = array( array( 'taxonomy' => 'work_cat', 'field' => 'slug', 'terms' => $cat_slug ) );
	}
	$ids = get_posts( $args );
	if ( ! $ids ) { return array(); }
	$rel = wp_get_object_terms( $ids, 'car_make', array( 'fields' => 'all_with_object_id' ) );
	if ( is_wp_error( $rel ) ) { return array(); }
	$out = array();
	foreach ( $rel as $t ) {
		if ( ! isset( $out[ $t->term_id ] ) ) { $out[ $t->term_id ] = array( 'term' => $t, 'count' => 0 ); }
		$out[ $t->term_id ]['count']++;
	}
	uasort( $out, function ( $a, $b ) {
		return ( $b['count'] <=> $a['count'] ) ?: strcasecmp( $a['term']->name, $b['term']->name );
	} );
	return array_values( $out );
}

/* ---------- Карта перед подвалом ---------- */

/** Адрес карты для вставки: свой из «Контакты и тексты», иначе виджет Яндекс.Карт с меткой на точке сервиса. */
function at_map_src() {
	$custom = trim( (string) at_opt( 'map' ) );
	if ( '' !== $custom ) { return $custom; }
	$ll = preg_replace( '/[^0-9.,-]/', '', (string) at_opt( 'map_ll' ) );
	if ( ! preg_match( '/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/', $ll ) ) { return ''; }
	$org = preg_replace( '/\D/', '', (string) at_opt( 'map_org' ) );
	if ( $org ) { // виджет с карточкой организации (рейтинг, фото, отзывы)
		return 'https://yandex.ru/map-widget/v1/?ll=' . rawurlencode( $ll ) . '&mode=search&oid=' . $org . '&ol=biz&z=16';
	}
	return 'https://yandex.ru/map-widget/v1/?ll=' . rawurlencode( $ll ) . '&z=16&pt=' . rawurlencode( $ll . ',pm2ylm' );
}

/** Ссылки на отзывы на открытых площадках: [подпись, адрес]. */
function at_review_links() {
	$out = array();
	$tg  = trim( (string) at_opt( 'twogis' ) );
	if ( '' !== $tg ) { $out[] = array( 'Отзывы в 2ГИС', $tg ); }
	$org = preg_replace( '/\D/', '', (string) at_opt( 'map_org' ) );
	if ( $org ) { $out[] = array( 'Отзывы в Яндекс Картах', 'https://yandex.ru/maps/org/avtoteams/' . $org . '/reviews/' ); }
	return $out;
}

/** Ссылка «Построить маршрут» в Яндекс.Картах. */
function at_route_url() {
	$org = preg_replace( '/\D/', '', (string) at_opt( 'map_org' ) );
	if ( $org ) { return 'https://yandex.ru/maps/org/avtoteams/' . $org . '/'; }
	$ll = preg_replace( '/[^0-9.,-]/', '', (string) at_opt( 'map_ll' ) );
	$p  = explode( ',', $ll );
	if ( 2 !== count( $p ) ) { return ''; }
	return 'https://yandex.ru/maps/?rtext=~' . rawurlencode( $p[1] . ',' . $p[0] ) . '&rtt=auto';
}


/** Значки соцсетей для подвала (линейные, цвет берут из текста ссылки). */
function at_soc_icon( $key ) {
	$a = 'viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"';
	switch ( $key ) {
		case 'vk':
			return '<svg ' . $a . '><path d="M3 7.5c.4 5.5 3.5 8.7 8.5 8.7h1.1v-3.1c1.9.2 3.3 1.6 3.9 3.1H19c-.8-2.3-2.4-3.6-3.4-4.1 1-.6 2.4-2 2.8-4.6h-2.4c-.4 1.7-1.7 3.1-3.4 3.3V7.5H9.3v5.6C7.5 12.7 5.4 10.8 5.3 7.5z"/></svg>';
		case 'fb':
			return '<svg ' . $a . '><path d="M14.5 21v-8h2.6l.4-3h-3V8.2c0-.9.3-1.5 1.6-1.5h1.5V4.1c-.3 0-1.2-.1-2.2-.1-2.3 0-3.9 1.4-3.9 4v2H9v3h2.5v8z"/></svg>';
		case 'wa':
			return '<svg ' . $a . '><path d="M12 3.2a8.8 8.8 0 0 0-7.6 13.2L3.2 20.8l4.5-1.2A8.8 8.8 0 1 0 12 3.2z"/><path d="M9.2 8.2c-.3.4-.5 1-.2 1.8.6 1.6 2.6 3.7 4.5 4.3.8.3 1.5 0 1.9-.5l.3-.8-1.9-1-.8.8c-.9-.4-1.9-1.3-2.3-2.3l.7-.8-.9-1.9z"/></svg>';
		case 'ig':
			return '<svg ' . $a . '><rect x="3.5" y="3.5" width="17" height="17" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.2" cy="6.8" r=".6" fill="currentColor"/></svg>';
		case 'ok':
			return '<svg ' . $a . '><circle cx="12" cy="7.2" r="3.2"/><path d="M7.2 11.8c2.9 2 6.7 2 9.6 0M12 15v.2M8.8 20.2 12 16.4l3.2 3.8"/></svg>';
	}
	return '';
}

/**
 * Категории по тексту работы. Если в описании упомянуты, например, чип-тюнинг и отключение EGR,
 * работа попадает сразу в обе категории. Только добавляет, ничего не снимает.
 */
function at_cats_for_text( $text ) {
	$text = wp_strip_all_tags( $text );
	$neg  = '/не трогали|не затрагивали|не отключали|не стали|уже (был|была|было|прошива|стоял)|оставили как был/iu';
	$keep = array();
	foreach ( preg_split( '/[.!?]+/u', $text ) as $s ) {
		if ( ! preg_match( $neg, $s ) ) { $keep[] = $s; }
	}
	$s     = implode( '. ', $keep );
	$rules = array(
		'chip-moshchnost'     => '/чип-?\s?тюнинг|stage\s?\d|(прибавк|добав|прирост)\w*\s+(мощност|момент)|мощност\w*\s+и\s+момент|\d+\s?л\.\s?с|\+\s?\d+\s?[–-]?\s?\d*\s?%/iu',
		'euro2-katalizator'   => '/евро-?\s?2|катализатор|лямбд|кислородн|p04[23]\d/iu',
		'egr-dpf-scr'         => '/\begr\b|егр|dpf|дпф|сажев|adblue|мочевин|\bscr\b|вихревы\w+ заслонк/iu',
		'srs'                 => '/srs|кр[аэ]ш|подушк|airbag/iu',
		'rusifikatsiya'       => '/русифи[кц]|на русском|русск\w+ язык/iu',
		'korobki'             => '/\bamt\b|пинк|ползущ|прошивк\w*\s+(акпп|коробк|робот)|переключени\w+\s+(передач\s+)?(быстрее|плавн)/iu',
		'dop-funkcii'         => '/istop|i-stop|дистанционн\w+ запуск|кодировк|европейск\w+ регион|качества топлива/iu',
		'immo'                => '/иммобилайзер|иммобилизатор|\bimmo\b/iu',
		'evap'                => '/evap|вентиляци\w+\s+(топливного\s+)?бак|адсорбер/iu',
	);
	$out = array();
	foreach ( $rules as $slug => $re ) {
		if ( preg_match( $re, $s ) ) { $out[] = $slug; }
	}
	return $out;
}

/** Категории «Отключение EVAP» и «Отключение иммобилайзера» создаются автоматически, если их ещё нет. */
function at_ensure_extra_terms() {
	$extra = array(
		'evap' => 'Отключение EVAP (вентиляция бака)',
		'immo' => 'Отключение иммобилайзера',
	);
	foreach ( $extra as $slug => $name ) {
		if ( ! get_term_by( 'slug', $slug, 'work_cat' ) ) {
			wp_insert_term( $name, 'work_cat', array( 'slug' => $slug ) );
		}
	}
}

function at_assign_cats( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post ) { return; }
	at_ensure_extra_terms();
	$slugs = at_cats_for_text( $post->post_title . '. ' . $post->post_content . ' ' . $post->post_excerpt );
	if ( ! $slugs ) { return; }
	$ids = array();
	foreach ( $slugs as $slug ) {
		$t = get_term_by( 'slug', $slug, 'work_cat' );
		if ( $t && ! is_wp_error( $t ) ) { $ids[] = (int) $t->term_id; }
	}
	if ( $ids ) { wp_set_object_terms( $post_id, $ids, 'work_cat', true ); }
}

add_action( 'save_post_work', function ( $post_id, $post ) {
	if ( wp_is_post_revision( $post_id ) || 'auto-draft' === $post->post_status ) { return; }
	at_assign_cats( $post_id );
}, 25, 2 );

/** Один раз после обновления темы: дополняет категории у уже существующих работ. */
add_action( 'init', function () {
	if ( get_option( 'at_cats_synced' ) === AT_VER ) { return; }
	$ids = get_posts( array( 'post_type' => 'work', 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'fields' => 'ids', 'nopaging' => true ) );
	foreach ( $ids as $id ) {
		at_assign_cats( $id );
		$p = get_post( $id );
		$s = at_cats_for_text( $p->post_title . '. ' . $p->post_content . ' ' . $p->post_excerpt );
		if ( ( in_array( 'evap', $s, true ) || in_array( 'immo', $s, true ) ) && ! in_array( 'dop-funkcii', $s, true ) ) {
			wp_remove_object_terms( $id, 'dop-funkcii', 'work_cat' );
		}
	}
	update_option( 'at_cats_synced', AT_VER, false );
}, 35 );
