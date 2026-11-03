<?php
/**
 * Главная страница.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

/* Услуги: из админки, а если их ещё нет, стартовый список. */
$services = array();
$svc_posts = get_posts( array( 'post_type' => 'service', 'numberposts' => 40, 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
if ( $svc_posts ) {
	foreach ( $svc_posts as $p ) {
		$services[] = array( $p->post_title, (int) get_post_meta( $p->ID, '_at_price', true ), has_excerpt( $p ) ? get_the_excerpt( $p ) : '' );
	}
} else {
	$services = at_default_services();
}

$works = array();
// До 25 последних работ уходят в карусель на главной.
$work_posts = get_posts( array( 'post_type' => 'work', 'numberposts' => 25 ) );
if ( $work_posts ) {
	foreach ( $work_posts as $p ) { $works[] = at_work_from_post( $p ); }
} else {
	$works = at_default_works();
}

$reviews = get_posts( array( 'post_type' => 'review', 'numberposts' => 3 ) );
$news    = at_news_on() ? get_posts( array( 'post_type' => 'post', 'numberposts' => 3 ) ) : array();
$map     = at_map_data();
$status  = isset( $_GET['lead'] ) ? sanitize_key( wp_unslash( $_GET['lead'] ) ) : '';

$strip = array();
foreach ( $services as $s ) {
	if ( $s[1] > 0 && count( $strip ) < 3 ) { $strip[] = $s; }
}
?>

<section class="hero">
	<div class="wrap">
		<div>
			<h1><?php echo esc_html( at_opt( 'hero_title' ) ); ?></h1>
			<p class="hero-lead"><?php echo esc_html( at_opt( 'hero_text' ) ); ?></p>
			<div class="hero-actions">
				<a class="btn" href="#contact">Узнать стоимость</a>
				<?php if ( at_wa() ) : ?><a class="btn btn--ghost" href="<?php echo esc_url( at_wa() ); ?>" rel="noopener">Написать в WhatsApp</a><?php endif; ?>
			</div>
		</div>

		<div class="flip" data-flip>
		<div class="flip-inner">
		<figure class="map flip-face flip-front" data-map data-min="<?php echo esc_attr( $map['min'] ); ?>" data-max="<?php echo esc_attr( $map['max'] ); ?>">
			<figcaption>
				<strong>Так выглядит работа с блоком управления</strong>
				<span>Пример карты угла опережения зажигания, значения условные</span>
			</figcaption>
			<div class="map-ctl" role="group" aria-label="Состояние карты">
				<button type="button" data-state="a" aria-pressed="false">Исходно</button>
				<button type="button" data-state="b" aria-pressed="true">После</button>
			</div>
			<div class="map-scroll">
				<table class="map-grid">
					<thead>
						<tr>
							<th scope="col">Нагрузка</th>
							<?php foreach ( $map['rpm'] as $r ) : ?><th scope="col"><?php echo esc_html( $r ); ?></th><?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $map['rows'] as $row ) : ?>
							<tr>
								<th scope="row"><?php echo esc_html( $row['load'] ); ?>%</th>
								<?php foreach ( $row['cells'] as $c ) : ?>
									<td data-a="<?php echo esc_attr( number_format( $c[0], 1, '.', '' ) ); ?>" data-b="<?php echo esc_attr( number_format( $c[1], 1, '.', '' ) ); ?>"><?php echo esc_html( number_format( $c[1], 1, '.', '' ) ); ?></td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<p class="map-axis">По горизонтали обороты двигателя, по вертикали нагрузка. Градусы опережения.</p>
		</figure>
		<figure class="map flip-face flip-back" data-dyno aria-hidden="true">
			<figcaption>
				<strong>Так меняется мощность после прошивки</strong>
				<span>Пример стендового замера турбированного бензинового мотора, значения условные</span>
			</figcaption>
			<div class="dyno-plot" data-plot></div>
			<ul class="dyno-legend" aria-label="Обозначения">
				<li><i class="lg lg--t0"></i>Момент исходно</li>
				<li><i class="lg lg--t1"></i>Момент после</li>
				<li><i class="lg lg--p0"></i>Мощность исходно</li>
				<li><i class="lg lg--p1"></i>Мощность после</li>
			</ul>
			<p class="dyno-result" data-result aria-live="polite"></p>
		</figure>
		</div>
		<div class="flip-dots" role="group" aria-label="Переключить блок">
			<button type="button" data-face="0" aria-pressed="true" aria-label="Таблица калибровки"></button>
			<button type="button" data-face="1" aria-pressed="false" aria-label="Стендовый замер мощности"></button>
		</div>
		</div>
	</div>
</section>

<?php if ( $strip ) : ?>
<div class="strip">
	<ul>
		<?php foreach ( $strip as $s ) : ?>
			<li><b><?php echo esc_html( at_price( $s[1] ) ); ?></b><span><?php echo esc_html( $s[0] ); ?></span></li>
		<?php endforeach; ?>
		<li><b>Бесплатно</b><span>ответим, что можно сделать с вашей машиной</span></li>
	</ul>
</div>
<?php endif; ?>

<?php
$makes_n = (int) wp_count_terms( array( 'taxonomy' => 'car_make', 'hide_empty' => true ) );
$stats   = array(
	array( 1700, '+', 'выполненных работ' ),
	array( max( 1, (int) wp_date( 'Y' ) - 2017 ), '', 'лет в деле' ),
);
if ( $makes_n > 0 ) { $stats[] = array( $makes_n, '', 'марок авто в наших работах' ); }
?>
<section class="stats" aria-label="Автотеамс в цифрах" data-stats>
	<ul>
		<?php foreach ( $stats as $st ) : ?>
			<li><b data-count="<?php echo (int) $st[0]; ?>" data-suffix="<?php echo esc_attr( $st[1] ); ?>"><?php echo (int) $st[0] . esc_html( $st[1] ); ?></b><span><?php echo esc_html( $st[2] ); ?></span></li>
		<?php endforeach; ?>
	</ul>
</section>

<section class="section section--tint" id="services">
	<div class="wrap">
		<div class="section-head">
			<h2>Услуги и цены</h2>
			<p>Цены «от». Точную стоимость назовём по вашему автомобилю.</p>
		</div>
		<ul class="prices">
			<?php foreach ( $services as $s ) : ?>
				<li>
					<h3><?php echo esc_html( $s[0] ); ?></h3>
					<?php if ( $s[1] > 0 ) : ?><span class="price"><?php echo esc_html( at_price( $s[1] ) ); ?></span><?php endif; ?>
					<?php if ( ! empty( $s[2] ) ) : ?><p><?php echo esc_html( $s[2] ); ?></p><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>

<section class="section" aria-labelledby="stages-title">
	<div class="wrap">
		<div class="section-head">
			<h2 id="stages-title">Какой уровень подойдёт вашему автомобилю</h2>
			<p>Зависит от того, что уже сделано с мотором.</p>
		</div>
		<div class="stages">
			<?php foreach ( at_default_stages() as $st ) : ?>
				<div class="stage">
					<h3><?php echo esc_html( $st[0] ); ?></h3>
					<div class="need"><?php echo esc_html( $st[1] ); ?></div>
					<p><?php echo esc_html( $st[2] ); ?></p>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="section section--tint" id="works">
	<div class="wrap">
		<div class="section-head">
			<h2>Последние работы</h2>
			<a class="more" href="<?php echo esc_url( get_post_type_archive_link( 'work' ) ); ?>">Все работы</a>
		</div>
		<div class="carousel" data-carousel data-interval="5000" role="region" aria-roledescription="карусель" aria-label="Последние работы">
			<div class="carousel-ctl">
				<button type="button" class="car-prev" aria-label="Назад">←</button>
				<button type="button" class="car-next" aria-label="Вперёд">→</button>
				<span class="car-timer" aria-hidden="true"><svg viewBox="0 0 28 28" width="28" height="28" focusable="false"><circle class="car-timer-bg" cx="14" cy="14" r="11"/><circle class="car-timer-fg" cx="14" cy="14" r="11"/></svg></span>
				<button type="button" class="car-play" aria-pressed="false">Пауза</button>
			</div>
			<div class="carousel-track" tabindex="0" aria-label="Список работ, листается стрелками клавиатуры">
				<?php foreach ( $works as $w ) { at_work_card( $w ); } ?>
			</div>
		</div>
	</div>
</section>

<section class="section" aria-labelledby="steps-title">
	<div class="wrap">
		<div class="section-head"><h2 id="steps-title">Как проходит работа</h2></div>
		<ol class="steps">
			<?php foreach ( at_default_steps() as $st ) : ?>
				<li><h3><?php echo esc_html( $st[0] ); ?></h3><p><?php echo esc_html( $st[1] ); ?></p></li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>

<section class="section section--tint" aria-labelledby="faq-title">
	<div class="wrap">
		<div class="section-head"><h2 id="faq-title">Частые вопросы</h2></div>
		<div class="faq">
			<?php foreach ( at_default_faq() as $f ) : ?>
				<details>
					<summary><?php echo esc_html( $f[0] ); ?></summary>
					<div class="answer"><p><?php echo esc_html( $f[1] ); ?></p></div>
				</details>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<?php if ( $reviews ) : ?>
<section class="section" aria-labelledby="rev-title">
	<div class="wrap">
		<div class="section-head"><h2 id="rev-title">Отзывы</h2></div>
		<div class="quotes">
			<?php foreach ( $reviews as $r ) : ?>
				<figure class="quote" style="margin:0">
					<blockquote><?php echo wp_kses_post( wpautop( $r->post_content ) ); ?></blockquote>
					<figcaption><cite><?php echo esc_html( $r->post_title ); ?></cite>
						<?php $car = get_post_meta( $r->ID, '_at_car', true ); if ( $car ) : ?><span class="car"><?php echo esc_html( $car ); ?></span><?php endif; ?>
					</figcaption>
				</figure>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php if ( $news ) : ?>
<section class="section section--tint" aria-labelledby="news-title">
	<div class="wrap">
		<div class="section-head">
			<h2 id="news-title">Новости</h2>
			<a class="more" href="<?php echo esc_url( at_news_url() ); ?>">Все новости</a>
		</div>
		<ul class="news-list">
			<?php foreach ( $news as $n ) : ?>
				<li>
					<time datetime="<?php echo esc_attr( get_the_date( 'c', $n ) ); ?>"><?php echo esc_html( get_the_date( 'j F Y', $n ) ); ?></time>
					<div>
						<h3><a href="<?php echo esc_url( get_permalink( $n ) ); ?>"><?php echo esc_html( get_the_title( $n ) ); ?></a></h3>
						<p><?php echo esc_html( wp_trim_words( wp_strip_all_tags( $n->post_content ), 26 ) ); ?></p>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php endif; ?>

<section class="section contact" id="contact">
	<div class="wrap">
		<div>
			<h2>Записаться</h2>
			<p>Оставьте заявку, и мы свяжемся с вами. Можно просто позвонить или написать в WhatsApp.</p>
			<ul class="contact-list">
				<li><span>Телефон</span><a href="tel:<?php echo esc_attr( at_tel() ); ?>"><?php echo esc_html( at_opt( 'phone' ) ); ?></a></li>
				<?php if ( at_wa() ) : ?><li><span>WhatsApp</span><a href="<?php echo esc_url( at_wa() ); ?>" rel="noopener">Написать</a></li><?php endif; ?>
				<?php if ( is_email( at_opt( 'email' ) ) ) : ?><li><span>Почта</span><a href="mailto:<?php echo esc_attr( at_opt( 'email' ) ); ?>"><?php echo esc_html( at_opt( 'email' ) ); ?></a></li><?php endif; ?>
				<li><span>Адрес</span><?php echo esc_html( at_opt( 'address' ) ); ?></li>
				<li><span>Часы работы</span><?php echo esc_html( at_opt( 'hours' ) ); ?></li>
			</ul>
			<?php if ( at_opt( 'map' ) ) : ?>
				<div class="map-embed"><iframe src="<?php echo esc_url( at_opt( 'map' ) ); ?>" title="Мы на карте" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe></div>
			<?php endif; ?>
		</div>
		<div>
			<?php if ( 'ok' === $status ) : ?>
				<div class="notice" role="status">Заявка отправлена. Мы свяжемся с вами.</div>
			<?php elseif ( 'err' === $status ) : ?>
				<div class="notice notice--err" role="alert">Заявка не отправилась. Проверьте телефон и попробуйте ещё раз или позвоните нам.</div>
			<?php endif; ?>
			<?php get_template_part( 'template-parts/lead-form', null, array( 'variant' => 'full' ) ); ?>
		</div>
	</div>
</section>

<?php get_footer(); ?>
