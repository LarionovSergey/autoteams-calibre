<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
</main>
<?php if ( at_map_src() ) : ?>
<section class="find-us" aria-labelledby="find-title">
	<div class="wrap find-head">
		<div>
			<h2 id="find-title">Как нас найти</h2>
		</div>
		<?php if ( at_route_url() ) : ?><a class="btn btn--ghost" href="<?php echo esc_url( at_route_url() ); ?>" target="_blank" rel="noopener">Построить маршрут</a><?php endif; ?>
	</div>
	<div class="find-map"><iframe src="<?php echo esc_url( at_map_src() ); ?>" title="Мы на карте" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe></div>
</section>
<?php endif; ?>
<footer class="site-footer">
	<div class="wrap">
		<div class="foot-grid">
			<div>
				<div class="foot-brand"><?php bloginfo( 'name' ); ?></div>
				<p><?php echo esc_html( at_opt( 'address' ) ); ?><br><?php echo esc_html( at_opt( 'hours' ) ); ?></p>
			</div>
			<div>
				<h2>Связаться</h2>
				<ul>
					<li><a href="tel:<?php echo esc_attr( at_tel() ); ?>"><?php echo esc_html( at_opt( 'phone' ) ); ?></a></li>
					<?php if ( at_wa() ) : ?><li><a href="<?php echo esc_url( at_wa() ); ?>" rel="noopener">WhatsApp</a></li><?php endif; ?>
					<?php if ( is_email( at_opt( 'email' ) ) ) : ?><li><a href="mailto:<?php echo esc_attr( at_opt( 'email' ) ); ?>"><?php echo esc_html( at_opt( 'email' ) ); ?></a></li><?php endif; ?>
				</ul>
			</div>
			<div>
				<h2>Мы в сети</h2>
				<ul>
					<?php if ( at_opt( 'vk' ) ) : ?><li><a href="<?php echo esc_url( at_opt( 'vk' ) ); ?>" rel="noopener">ВКонтакте</a></li><?php endif; ?>
					<?php if ( at_opt( 'facebook' ) ) : ?><li><a href="<?php echo esc_url( at_opt( 'facebook' ) ); ?>" rel="noopener">Facebook</a></li><?php endif; ?>
					<?php if ( at_opt( 'privacy' ) ) : ?><li><a href="<?php echo esc_url( at_opt( 'privacy' ) ); ?>">Политика конфиденциальности</a></li><?php endif; ?>
				</ul>
				<?php
				$wa  = preg_replace( '/\D/', '', (string) at_opt( 'whatsapp' ) );
				$soc = array(
					array( 'vk', 'ВКонтакте', at_opt( 'vk' ) ),
					array( 'fb', 'Facebook', at_opt( 'facebook' ) ),
					array( 'wa', 'WhatsApp', $wa ? 'https://api.whatsapp.com/send?phone=' . $wa : '' ),
					array( 'ig', 'Instagram', at_opt( 'instagram' ) ),
					array( 'ok', 'Одноклассники', at_opt( 'ok' ) ),
				);
				?>
				<ul class="soc">
					<?php foreach ( $soc as $s ) : if ( ! $s[2] ) { continue; } ?>
						<li><a href="<?php echo esc_url( $s[2] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $s[1] ); ?>" title="<?php echo esc_attr( $s[1] ); ?>"><?php echo at_soc_icon( $s[0] ); // phpcs:ignore WordPress.Security.EscapeOutput ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
		<div class="copy">© <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></div>
	</div>
</footer>
<button type="button" class="to-top" aria-label="Наверх" title="Наверх" tabindex="-1" aria-hidden="true">
	<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="square" stroke-linejoin="miter" aria-hidden="true" focusable="false"><path d="M12 20V5M5 12l7-7 7 7"/></svg>
</button>
<?php wp_footer(); ?>
</body>
</html>
