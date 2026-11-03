<?php
/**
 * Форма заявки. Параметр variant: quick (две строки) или full (с услугой и комментарием).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$variant = isset( $args['variant'] ) ? $args['variant'] : 'full';
$uid     = 'f' . wp_unique_id();

$services = array();
$posts    = get_posts( array( 'post_type' => 'service', 'numberposts' => 40, 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
if ( $posts ) {
	foreach ( $posts as $p ) { $services[] = $p->post_title; }
} else {
	foreach ( at_default_services() as $s ) { $services[] = $s[0]; }
}
?>
<form class="lead-form lead-form--<?php echo esc_attr( $variant ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="at_lead">
	<?php wp_nonce_field( 'at_lead', '_atn' ); ?>
	<div class="hp" aria-hidden="true">
		<label for="<?php echo esc_attr( $uid ); ?>-site">Не заполняйте это поле</label>
		<input type="text" id="<?php echo esc_attr( $uid ); ?>-site" name="website" tabindex="-1" autocomplete="off">
	</div>

	<?php if ( 'full' === $variant ) : ?>
		<div class="field">
			<label for="<?php echo esc_attr( $uid ); ?>-name">Имя</label>
			<input type="text" id="<?php echo esc_attr( $uid ); ?>-name" name="name" autocomplete="name">
		</div>
	<?php endif; ?>

	<div class="field">
		<label for="<?php echo esc_attr( $uid ); ?>-car">Марка, модель, год</label>
		<input type="text" id="<?php echo esc_attr( $uid ); ?>-car" name="car" placeholder="Например: Toyota RAV4, 2012" required>
	</div>
	<div class="field">
		<label for="<?php echo esc_attr( $uid ); ?>-phone">Телефон</label>
		<input type="tel" id="<?php echo esc_attr( $uid ); ?>-phone" name="phone" autocomplete="tel" placeholder="+7" required>
	</div>

	<?php if ( 'full' === $variant ) : ?>
		<div class="field">
			<label for="<?php echo esc_attr( $uid ); ?>-service">Что нужно</label>
			<select id="<?php echo esc_attr( $uid ); ?>-service" name="service">
				<option value="">Пока не знаю, подскажите</option>
				<?php foreach ( $services as $s ) : ?>
					<option value="<?php echo esc_attr( $s ); ?>"><?php echo esc_html( $s ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="field">
			<label for="<?php echo esc_attr( $uid ); ?>-comment">Комментарий</label>
			<textarea id="<?php echo esc_attr( $uid ); ?>-comment" name="comment" placeholder="Что хотите изменить, есть ли ошибки на приборной панели"></textarea>
		</div>
	<?php endif; ?>

	<button class="btn<?php echo 'full' === $variant ? ' btn--block' : ''; ?>" type="submit">Узнать стоимость</button>

	<?php if ( 'full' === $variant ) : ?>
		<p class="form-note">
			Отправляя заявку, вы соглашаетесь на обработку персональных данных<?php if ( at_opt( 'privacy' ) ) : ?>
				(<a href="<?php echo esc_url( at_opt( 'privacy' ) ); ?>">политика</a>)<?php endif; ?>.
		</p>
	<?php endif; ?>
</form>
