<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post(); ?>
<div class="page-head">
	<div class="wrap">
		<div class="crumbs"><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Главная</a> / <a href="<?php echo esc_url( at_news_url() ); ?>">Новости</a></div>
		<h1><?php the_title(); ?></h1>
		<p class="crumbs" style="margin:.8rem 0 0"><time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y' ) ); ?></time></p>
	</div>
</div>
<div class="wrap entry">
	<?php if ( has_post_thumbnail() ) : ?><div style="max-width:70ch"><?php the_post_thumbnail( 'large' ); ?></div><?php endif; ?>
	<div class="entry-content"><?php the_content(); ?></div>
</div>
<?php endwhile;
get_footer();
