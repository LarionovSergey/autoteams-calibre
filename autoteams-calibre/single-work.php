<?php
/**
 * Страница одной работы: фото, таблица «Исходно / После / Разница», описание.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

while ( have_posts() ) : the_post();
	$id   = get_the_ID();
	$ecu  = get_post_meta( $id, '_at_ecu', true );
	$rows = array();
	$hp_b = (int) get_post_meta( $id, '_at_hp_before', true );
	$hp_a = (int) get_post_meta( $id, '_at_hp_after', true );
	$nm_b = (int) get_post_meta( $id, '_at_nm_before', true );
	$nm_a = (int) get_post_meta( $id, '_at_nm_after', true );
	if ( $hp_b > 0 && $hp_a > 0 ) { $rows[] = array( 'Мощность', 'л.с.', $hp_b, $hp_a ); }
	if ( $nm_b > 0 && $nm_a > 0 ) { $rows[] = array( 'Крутящий момент', 'Н·м', $nm_b, $nm_a ); }
	?>
	<div class="page-head">
		<div class="wrap">
			<div class="crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a> / <a href="<?php echo esc_url( get_post_type_archive_link( 'work' ) ); ?>">Работы</a></div>
			<h1><?php the_title(); ?></h1>
			<?php if ( $ecu ) : ?><p class="crumbs" style="margin:.8rem 0 0">Блок управления: <?php echo esc_html( $ecu ); ?></p><?php endif; ?>
		</div>
	</div>

	<div class="wrap work-single">
		<div>
			<?php if ( has_post_thumbnail() ) : ?><div class="hero-img"><?php the_post_thumbnail( 'large' ); ?></div><?php endif; ?>
			<div class="entry-content"><?php the_content(); ?></div>
		</div>
		<aside>
			<?php if ( $rows ) : ?>
				<table class="spec">
					<caption>Результат</caption>
					<thead><tr><th scope="col"></th><th scope="col">Исходно</th><th scope="col">После</th><th scope="col">Разница</th></tr></thead>
					<tbody>
						<?php foreach ( $rows as $r ) : ?>
							<tr>
								<td><?php echo esc_html( $r[0] ); ?>, <?php echo esc_html( $r[1] ); ?></td>
								<td><?php echo esc_html( $r[2] ); ?></td>
								<td><?php echo esc_html( $r[3] ); ?></td>
								<td class="diff"><?php echo esc_html( ( $r[3] - $r[2] >= 0 ? '+' : '' ) . ( $r[3] - $r[2] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="spec-note">Цифры относятся к этому автомобилю. На другом результат может отличаться.</p>
			<?php endif; ?>
			<p style="margin-top:1.4rem"><a class="btn btn--block" href="<?php echo esc_url( home_url( '/#contact' ) ); ?>">Узнать стоимость для моей машины</a></p>
		</aside>
	</div>

	<div class="wrap pager">
		<span><?php previous_post_link( '%link', '← Предыдущая' ); ?></span>
		<span><?php next_post_link( '%link', 'Следующая →' ); ?></span>
	</div>
<?php endwhile;
get_footer();
