<?php
/**
 * Приём заявок с формы: проверка, сохранение в «Заявки» и письмо на почту.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'admin_post_nopriv_at_lead', 'at_handle_lead' );
add_action( 'admin_post_at_lead', 'at_handle_lead' );

function at_lead_redirect( $status ) {
	$back = wp_get_referer();
	if ( ! $back ) { $back = home_url( '/' ); }
	$back = remove_query_arg( 'lead', $back );
	$back = strtok( $back, '#' );
	wp_safe_redirect( add_query_arg( 'lead', $status, $back ) . '#contact' );
	exit;
}

function at_handle_lead() {
	$nonce = isset( $_POST['_atn'] ) ? sanitize_text_field( wp_unslash( $_POST['_atn'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'at_lead' ) ) {
		at_lead_redirect( 'err' );
	}
	// Ловушка для ботов: настоящий человек это поле не видит и не заполняет.
	if ( ! empty( $_POST['website'] ) ) {
		at_lead_redirect( 'ok' );
	}

	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$key = 'at_lead_' . md5( $ip );
	if ( get_transient( $key ) ) {
		at_lead_redirect( 'err' );
	}

	$name    = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';
	$phone   = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$car     = isset( $_POST['car'] ) ? sanitize_text_field( wp_unslash( $_POST['car'] ) ) : '';
	$service = isset( $_POST['service'] ) ? sanitize_text_field( wp_unslash( $_POST['service'] ) ) : '';
	$comment = isset( $_POST['comment'] ) ? sanitize_textarea_field( wp_unslash( $_POST['comment'] ) ) : '';

	if ( strlen( preg_replace( '/\D/', '', $phone ) ) < 10 ) {
		at_lead_redirect( 'err' );
	}
	set_transient( $key, 1, 30 );

	$lines = array(
		'Имя: ' . ( $name ? $name : '—' ),
		'Телефон: ' . $phone,
		'Автомобиль: ' . ( $car ? $car : '—' ),
		'Услуга: ' . ( $service ? $service : '—' ),
		'Комментарий: ' . ( $comment ? $comment : '—' ),
	);
	$body = implode( "\n", $lines );

	wp_insert_post( array(
		'post_type'    => 'lead',
		'post_status'  => 'private',
		'post_title'   => ( $name ? $name : 'Без имени' ) . ', ' . $phone,
		'post_content' => $body,
	) );

	$to = at_opt( 'email' );
	if ( is_email( $to ) ) {
		wp_mail( $to, 'Заявка с сайта: ' . ( $car ? $car : $phone ), $body );
	}
	at_lead_redirect( 'ok' );
}
