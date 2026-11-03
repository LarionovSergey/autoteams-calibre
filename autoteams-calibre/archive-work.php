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
