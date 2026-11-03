<?php
/**
 * Все работы.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>
<div class="page-head">
	<div class="wrap">
		<div class="crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a> / Работы</div>
		<h1>Работы</h1>
	</div>
</div>
<div class="wrap entry">
	<?php
	$at_cats = get_terms( array( 'taxonomy' => 'work_cat', 'hide_empty' => true ) );
	$at_cur  = sanitize_key( get_query_var( 'work_cat' ) );
	$at_base = get_post_type_archive_link( 'work' );
	$at_make = sanitize_key( get_query_var( 'car_make' ) );
	if ( $at_cats && ! is_wp_error( $at_cats ) ) : ?>
		<nav class="filters filters--cat" aria-label="Категории работ">
			<ul>
				<li><a href="<?php echo esc_url( $at_base ); ?>"<?php echo $at_cur ? '' : ' aria-current="page"'; ?>>Все</a></li>
				<?php foreach ( $at_cats as $c ) : ?>
					<li><a href="<?php echo esc_url( add_query_arg( 'work_cat', $c->slug, $at_base ) ); ?>"<?php echo $at_cur === $c->slug ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $c->name ); ?> <span><?php echo (int) $c->count; ?></span></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
	<?php endif; ?>
	<?php
	$at_makes = at_makes_for_category( $at_cur );
	if ( $at_makes ) :
		$at_url = $at_cur ? add_query_arg( 'work_cat', $at_cur, $at_base ) : $at_base;
		?>
		<nav class="filters filters--make" aria-label="Марка автомобиля">
			<p class="filters-title">Марка автомобиля</p>
			<ul>
				<li><a href="<?php echo esc_url( $at_url ); ?>"<?php echo $at_make ? '' : ' aria-current="page"'; ?>>Все марки</a></li>
				<?php foreach ( $at_makes as $m ) : ?>
					<li><a href="<?php echo esc_url( add_query_arg( 'car_make', $m['term']->slug, $at_url ) ); ?>"<?php echo $at_make === $m['term']->slug ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $m['term']->name ); ?> <span><?php echo (int) $m['count']; ?></span></a></li>
				<?php endforeach; ?>
			</ul>
		</nav>
	<?php endif; ?>
	<?php if ( have_posts() ) : ?>
		<div class="works">
			<?php while ( have_posts() ) : the_post(); at_work_card( at_work_from_post( get_post() ) ); endwhile; ?>
		</div>
		<div class="pagination"><?php the_posts_pagination( array( 'prev_text' => 'Назад', 'next_text' => 'Дальше' ) ); ?></div>
	<?php else : ?>
		<p class="empty">Работ пока нет. Добавьте первую в админке: Работы → Добавить работу.</p>
	<?php endif; ?>
</div>
<?php get_footer(); ?>
