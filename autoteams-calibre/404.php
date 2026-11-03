<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header(); ?>
<div class="page-head"><div class="wrap"><h1>Страница не найдена</h1></div></div>
<div class="wrap entry">
	<p>Такой страницы нет или она переехала. Вернитесь на <a href="<?php echo esc_url( home_url( '/' ) ); ?>">главную</a> или посмотрите <a href="<?php echo esc_url( get_post_type_archive_link( 'work' ) ); ?>">наши работы</a>.</p>
</div>
<?php get_footer(); ?>
