<?php
/**
 * Settings class for ExpiryFlow plugin.
 *
 * @package ExpiryFlow\Admin
 */

namespace ExpiryFlow\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ExpiryFlow\Utils\Helpers;

/**
 * Settings class
 */
class Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Set up plugin hooks.
	 */
	private function setup_hooks(): void {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Add settings sub-menu under Settings.
	 */
	public function add_settings_page(): void {
		add_options_page(
			__( 'ExpiryFlow Settings', 'expiryflow' ),
			__( 'ExpiryFlow', 'expiryflow' ),
			'manage_options',
			'expiryflow-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook The current admin page hook.
	 */
	public function enqueue_assets( $hook ): void {
		if ( 'settings_page_expiryflow-settings' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'expiryflow-admin-settings',
			EXPIRYFLOW_PLUGIN_URL . 'assets/admin-settings.css',
			array(),
			EXPIRYFLOW_VERSION
		);
	}

	/**
	 * Register settings, sections, and fields.
	 */
	public function register_settings(): void {
		register_setting(
			'expiryflow_settings_group',
			'expiryflow_grace_period',
			array(
				'type'              => 'integer',
				'sanitize_callback' => array( $this, 'sanitize_grace_period' ),
				'default'           => EXPIRYFLOW_GRACE_PERIOD_DAYS,
			)
		);

		add_settings_section(
			'expiryflow_auto_delete_section',
			__( 'Auto-deletion Settings', 'expiryflow' ),
			null,
			'expiryflow-settings'
		);

		add_settings_field(
			'expiryflow_grace_period',
			__( 'Grace Period (Days)', 'expiryflow' ),
			array( $this, 'render_grace_period_field' ),
			'expiryflow-settings',
			'expiryflow_auto_delete_section'
		);
	}

	/**
	 * Sanitize grace period input.
	 *
	 * @param mixed $input Input value.
	 * @return int
	 */
	public function sanitize_grace_period( $input ): int {
		$input = (int) $input;
		// Ensure minimum 0 days and maximum 365 days.
		if ( $input < 0 ) {
			return 0;
		}
		if ( $input > 365 ) {
			return 365;
		}
		return $input;
	}

	/**
	 * Render grace period input field.
	 */
	public function render_grace_period_field(): void {
		$value = Helpers::get_grace_period();
		?>
		<input type="number" name="expiryflow_grace_period" value="<?php echo (int) $value; ?>" class="small-text" min="0" max="365" />
		<p class="description">
			<?php esc_html_e( 'Number of days to wait after a user account expires before it is automatically deleted.', 'expiryflow' ); ?>
		</p>
		<?php
	}

	/**
	 * Render the settings page.
	 */
	public function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<div class="expiryflow-settings-wrapper">
				<div class="expiryflow-settings-form">
					<form action="options.php" method="post">
						<?php
						settings_fields( 'expiryflow_settings_group' );
						do_settings_sections( 'expiryflow-settings' );
						submit_button();
						?>
					</form>
				</div>

				<div class="expiryflow-settings-sidebar">
					<div class="expiryflow-sidebar-box" style="margin-bottom: 20px; background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04); padding: 20px; border-radius: 4px;">
						<h2 style="margin-top: 0; font-size: 14px; border-bottom: 1px solid #eee; padding-bottom: 10px;"><?php esc_html_e( 'Need a WordPress Developer?', 'expiryflow' ); ?></h2>
						<p><?php esc_html_e( 'I build custom plugins, themes, and high-performance WordPress sites for businesses that need more than off-the-shelf solutions.', 'expiryflow' ); ?></p>
						<p>
							<a href="https://gasatrya.com/?utm_source=plugin&amp;utm_medium=expiryflow-sidebar" target="_blank" rel="noopener noreferrer" class="button button-primary" style="width: 100%; text-align: center; box-sizing: border-box;">
								<?php esc_html_e( 'Hire Me', 'expiryflow' ); ?>
							</a>
						</p>
						<hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">
						<p style="margin-bottom: 0; display: flex; align-items: center; gap: 12px;">
							<span>
								<span class="dashicons dashicons-coffee" style="color: #C9A96E; vertical-align: middle;"></span>
								<a href="https://gasatrya.com/donate/?utm_source=plugin&amp;utm_medium=expiryflow-sidebar" target="_blank" style="text-decoration: none; vertical-align: middle;">
									<?php esc_html_e( 'Buy me a coffee', 'expiryflow' ); ?>
								</a>
							</span>
							<span style="color: #ccd0d4;">&middot;</span>
							<span>
								<span class="dashicons dashicons-star-filled" style="color: #ffb900; vertical-align: middle;"></span>
								<a href="https://wordpress.org/support/plugin/expiryflow/reviews/#new-post" target="_blank" style="text-decoration: none; vertical-align: middle;">
									<?php esc_html_e( 'Rate this plugin', 'expiryflow' ); ?>
								</a>
							</span>
						</p>
					</div>
				</div>
			</div>
		</div>
		<?php
	}
}
