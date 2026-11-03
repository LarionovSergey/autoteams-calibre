<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
</main>
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
