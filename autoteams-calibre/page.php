<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();
while ( have_posts() ) : the_post(); ?>
<div class="page-head"><div class="wrap"><h1><?php the_title(); ?></h1></div></div>
<div class="wrap entry"><div class="entry-content"><?php the_content(); ?></div></div>
<?php endwhile;
get_footer();
