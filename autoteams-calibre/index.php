<?php
/**
 * Список новостей (и запасной шаблон для всего остального).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
?>
<div class="page-head">
	<div class="wrap">
		<h1><?php echo is_home() ? 'Новости' : esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h1>
	</div>
</div>
<div class="wrap entry">
	<?php if ( have_posts() ) : ?>
		<ul class="news-list">
			<?php while ( have_posts() ) : the_post(); ?>
				<li>
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></time>
					<div>
						<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
						<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( get_the_content() ), 30 ) ); ?></p>
					</div>
				</li>
			<?php endwhile; ?>
		</ul>
		<div class="pagination"><?php the_posts_pagination( array( 'prev_text' => 'Назад', 'next_text' => 'Дальше' ) ); ?></div>
	<?php else : ?>
		<p class="empty">Новостей пока нет.</p>
	<?php endif; ?>
</div>
<?php get_footer(); ?>
