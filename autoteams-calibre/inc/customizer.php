<?php
/**
 * Настройки сайта: Внешний вид → Настроить → «Контакты и тексты».
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function at_sanitize_bool( $v ) {
	return (bool) $v;
}

add_action( 'customize_register', function ( $wp ) {
	$wp->add_section( 'at_site', array( 'title' => 'Контакты и тексты', 'priority' => 30 ) );

	$fields = array(
		'phone'      => array( 'Телефон', 'text', 'sanitize_text_field' ),
		'whatsapp'   => array( 'WhatsApp (номер цифрами, например 79233930123)', 'text', 'sanitize_text_field' ),
		'email'      => array( 'Почта для заявок', 'text', 'sanitize_email' ),
		'address'    => array( 'Адрес', 'text', 'sanitize_text_field' ),
		'hours'      => array( 'Часы работы', 'text', 'sanitize_text_field' ),
		'vk'         => array( 'Ссылка на ВКонтакте', 'url', 'esc_url_raw' ),
		'twogis'     => array( 'Ссылка на отзывы в 2ГИС (кнопка появится, когда адрес указан)', 'url', 'esc_url_raw' ),
		'instagram'  => array( 'Ссылка на Instagram', 'url', 'esc_url_raw' ),
		'ok'         => array( 'Ссылка на Одноклассники', 'url', 'esc_url_raw' ),
		'max'        => array( 'Ссылка на MAX (кнопка «Написать в MAX» на главной)', 'url', 'esc_url_raw' ),
		'max_label'  => array( 'Текст кнопки на главной (например: Написать в MAX)', 'text', 'sanitize_text_field' ),
		'facebook'   => array( 'Ссылка на Facebook', 'url', 'esc_url_raw' ),
		'map'        => array( 'Ссылка для вставки карты (iframe src из Яндекс.Карт или Google Maps)', 'url', 'esc_url_raw' ),
		'privacy'    => array( 'Ссылка на политику обработки персональных данных', 'url', 'esc_url_raw' ),
		'hero_title' => array( 'Главный заголовок', 'text', 'sanitize_text_field' ),
		'hero_text'  => array( 'Текст под заголовком', 'textarea', 'sanitize_textarea_field' ),
		'brand_sub'  => array( 'Подпись под названием в шапке (если у сайта не задана подпись)', 'text', 'sanitize_text_field' ),
		'show_news'  => array( 'Показывать новости на сайте', 'checkbox', 'at_sanitize_bool' ),
	);
	$defaults = at_defaults();
	foreach ( $fields as $key => $f ) {
		$wp->add_setting( 'at_' . $key, array(
			'default' => isset( $defaults[ $key ] ) ? $defaults[ $key ] : '',
			'sanitize_callback' => $f[2],
		) );
		$wp->add_control( 'at_' . $key, array( 'label' => $f[0], 'section' => 'at_site', 'type' => $f[1] ) );
	}
	// Своя картинка на кнопке: показывается вместо значка чата, после текста (например, «Написать в» + логотип)
	$wp->add_setting( 'at_max_img', array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
	$wp->add_control( new WP_Customize_Image_Control( $wp, 'at_max_img', array(
		'label'       => 'Картинка на кнопке MAX (необязательно)',
		'description' => 'Загрузите свою картинку (лучше PNG или SVG с прозрачным фоном). Она встанет после текста кнопки вместо значка чата. Тогда в поле «Текст кнопки» впишите, например, «Написать в».',
		'section'     => 'at_site',
	) ) );
} );
