<?php
/**
 * Типы записей: Работы, Услуги, Отзывы, Заявки.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'init', function () {
	register_post_type( 'work', array(
		'labels' => array(
			'name' => 'Работы', 'singular_name' => 'Работа', 'add_new' => 'Добавить работу',
			'add_new_item' => 'Новая работа', 'edit_item' => 'Изменить работу', 'all_items' => 'Все работы',
			'menu_name' => 'Работы', 'not_found' => 'Работ пока нет',
		),
		'public' => true, 'has_archive' => 'works', 'rewrite' => array( 'slug' => 'works' ),
		'menu_icon' => 'dashicons-car', 'menu_position' => 5, 'show_in_rest' => true,
		'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields' ),
	) );

	register_taxonomy( 'work_cat', 'work', array(
		'labels' => array(
			'name' => 'Категории работ', 'singular_name' => 'Категория работ', 'menu_name' => 'Категории',
			'all_items' => 'Все категории', 'add_new_item' => 'Новая категория', 'edit_item' => 'Изменить категорию',
			'search_items' => 'Найти категорию', 'not_found' => 'Категорий пока нет',
		),
		'hierarchical' => true, 'public' => true, 'show_ui' => true, 'show_admin_column' => true,
		'show_in_rest' => true, 'rest_base' => 'work_cat', 'query_var' => 'work_cat',
		'rewrite' => array( 'slug' => 'work-category' ),
	) );

	register_taxonomy( 'car_make', 'work', array(
		'labels' => array(
			'name' => 'Марки автомобилей', 'singular_name' => 'Марка', 'menu_name' => 'Марки',
			'all_items' => 'Все марки', 'add_new_item' => 'Новая марка', 'edit_item' => 'Изменить марку',
			'search_items' => 'Найти марку', 'not_found' => 'Марок пока нет',
		),
		'hierarchical' => false, 'public' => true, 'show_ui' => true, 'show_admin_column' => true,
		'show_in_rest' => true, 'rest_base' => 'car_make', 'query_var' => 'car_make',
		'rewrite' => array( 'slug' => 'make' ),
	) );

	// Поля работы доступны и из админки WordPress.com / REST (нужно для импорта и редактирования извне)
	foreach ( array( '_at_ecu', '_at_stage', '_at_gain_text', '_at_hp_before', '_at_hp_after', '_at_nm_before', '_at_nm_after' ) as $mk ) {
		register_post_meta( 'work', $mk, array(
			'type' => 'string', 'single' => true, 'show_in_rest' => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback' => function () { return current_user_can( 'edit_posts' ); },
		) );
	}

	register_post_type( 'service', array(
		'labels' => array(
			'name' => 'Услуги', 'singular_name' => 'Услуга', 'add_new' => 'Добавить услугу',
			'add_new_item' => 'Новая услуга', 'edit_item' => 'Изменить услугу', 'all_items' => 'Все услуги',
			'menu_name' => 'Услуги', 'not_found' => 'Услуг пока нет',
		),
		'public' => false, 'show_ui' => true, 'menu_icon' => 'dashicons-list-view', 'menu_position' => 6,
		'supports' => array( 'title', 'excerpt', 'page-attributes' ),
	) );

	register_post_type( 'review', array(
		'labels' => array(
			'name' => 'Отзывы', 'singular_name' => 'Отзыв', 'add_new' => 'Добавить отзыв',
			'add_new_item' => 'Новый отзыв', 'edit_item' => 'Изменить отзыв', 'all_items' => 'Все отзывы',
			'menu_name' => 'Отзывы', 'not_found' => 'Отзывов пока нет',
		),
		'public' => false, 'show_ui' => true, 'menu_icon' => 'dashicons-format-quote', 'menu_position' => 7,
		'supports' => array( 'title', 'editor' ),
	) );

	register_post_type( 'lead', array(
		'labels' => array(
			'name' => 'Заявки', 'singular_name' => 'Заявка', 'menu_name' => 'Заявки',
			'all_items' => 'Все заявки', 'not_found' => 'Заявок пока нет',
		),
		'public' => false, 'show_ui' => true, 'menu_icon' => 'dashicons-email-alt', 'menu_position' => 8,
		'capability_type' => 'post', 'capabilities' => array( 'create_posts' => 'do_not_allow' ), 'map_meta_cap' => true,
		'supports' => array( 'title', 'editor' ),
	) );
} );

/* ---------- Поля ---------- */

function at_meta_fields() {
	return array(
		'work' => array(
			'_at_ecu'       => array( 'Блок управления (например, Simos 8.41)', 'text' ),
			'_at_stage'     => array( 'Метка на карточке (например, EGR, DPF)', 'text' ),
			'_at_gain_text' => array( 'Прирост текстом (например, +16–18%)', 'text' ),
			'_at_hp_before' => array( 'Мощность исходно, л.с.', 'number' ),
			'_at_hp_after'  => array( 'Мощность после, л.с.', 'number' ),
			'_at_nm_before' => array( 'Крутящий момент исходно, Н·м', 'number' ),
			'_at_nm_after'  => array( 'Крутящий момент после, Н·м', 'number' ),
		),
		'service' => array(
			'_at_price' => array( 'Цена «от», ₽ (только число)', 'number' ),
		),
		'review' => array(
			'_at_car' => array( 'Автомобиль клиента', 'text' ),
		),
	);
}

add_action( 'add_meta_boxes', function () {
	$titles = array( 'work' => 'Результат работы', 'service' => 'Цена', 'review' => 'Данные клиента' );
	foreach ( $titles as $type => $title ) {
		add_meta_box( 'at_meta_' . $type, $title, 'at_render_meta_box', $type, 'normal', 'high' );
	}
} );

function at_render_meta_box( $post ) {
	$fields = at_meta_fields();
	wp_nonce_field( 'at_save_meta', 'at_meta_nonce' );
	if ( 'work' === $post->post_type ) {
		echo '<p>Заполните цифры, и на странице работы появится таблица «Исходно / После / Разница». Если оставить пустыми, таблицы не будет.</p>';
	}
	foreach ( $fields[ $post->post_type ] as $key => $def ) {
		$val = get_post_meta( $post->ID, $key, true );
		printf(
			'<p><label for="%1$s"><strong>%2$s</strong></label><br><input type="%3$s" id="%1$s" name="%1$s" value="%4$s" class="widefat"%5$s></p>',
			esc_attr( $key ), esc_html( $def[0] ), esc_attr( $def[1] ), esc_attr( $val ),
			'number' === $def[1] ? ' min="0" step="1"' : ''
		);
	}
}

add_action( 'save_post', function ( $post_id ) {
	if ( ! isset( $_POST['at_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['at_meta_nonce'] ) ), 'at_save_meta' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
	if ( ! current_user_can( 'edit_post', $post_id ) ) { return; }
	$fields = at_meta_fields();
	$type   = get_post_type( $post_id );
	if ( empty( $fields[ $type ] ) ) { return; }
	foreach ( $fields[ $type ] as $key => $def ) {
		if ( ! isset( $_POST[ $key ] ) ) { continue; }
		$raw = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		if ( 'number' === $def[1] ) { $raw = '' === $raw ? '' : (string) absint( $raw ); }
		update_post_meta( $post_id, $key, $raw );
	}
} );

/** Колонка «Телефон» удобнее в списке заявок. */
add_filter( 'manage_lead_posts_columns', function ( $cols ) {
	return array( 'cb' => $cols['cb'], 'title' => 'Заявка', 'date' => 'Дата' );
} );
