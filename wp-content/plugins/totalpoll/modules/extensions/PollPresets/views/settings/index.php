<?php ! defined( 'ABSPATH' ) && exit(); ?><div class="totalpoll-tabs-container has-tabs totalpoll-settings">
    <div class="totalpoll-tabs">
		<?php
		/**
		 * Fires before settings tabs.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/before/admin/editor/settings/tabs', $this );
		?>

		<?php $firstTab = key( $settingsTabs ) ?>
		<?php foreach ( $settingsTabs as $tabId => $tab ): ?>
            <div track="{ event : '<?php echo esc_attr($tabId); ?>', target: 'poll-settings' }" class="totalpoll-tabs-item <?php echo $tabId == $firstTab ? 'active' : ''; ?>" tab-switch="editor>settings>general><?php echo esc_attr( $tabId ); ?>">
                <span class="dashicons dashicons-<?php echo esc_attr( $tab['icon'] ); ?>"></span>
				<?php echo esc_html( $tab['label'] ); ?>
            </div>
		<?php endforeach; ?>

		<?php
		/**
		 * Fires after settings tabs.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/after/admin/editor/settings/tabs', $this );
		?>
    </div>
    <div class="totalpoll-tabs-content">
		<?php foreach ( $settingsTabs as $tabId => $tab ): ?>
            <div class="totalpoll-tab-content <?php echo $tabId == $firstTab ? 'active' : ''; ?>" tab="editor>settings>general><?php echo esc_attr( $tabId ); ?>">
				<?php
				/**
				 * Fires before settings tab content.
				 *
				 * @since 4.0.0
				 */
				do_action( 'totalpoll/actions/before/admin/editor/settings/tabs/content', $tabId );

				$path = empty( $tab['file'] ) ? __DIR__ . "/{$tabId}/index.php" : $tab['file'];
				if ( file_exists( $path ) ):
					include_once $path;
                elseif ( ! empty( $tab['tabs'] ) ):
					include __DIR__ . '/subtab.php';
				endif;

				/**
				 * Fires after settings tab content.
				 *
				 * @since 4.0.0
				 */
				do_action( 'totalpoll/actions/after/admin/editor/settings/tabs/content', $tabId );
				?>
            </div>
		<?php endforeach; ?>
    </div>
</div>