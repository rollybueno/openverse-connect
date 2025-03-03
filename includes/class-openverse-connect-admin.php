<?php
/**
 * Handles the admin functionality of the Openverse Connect plugin.
 *
 * @package Openverse_Connect
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Openverse Connect Admin Class.
 *
 * Handles all administrative functionality for the Openverse Connect plugin.
 * This includes settings pages, API key management, and admin-specific features.
 *
 * @since 1.0.0
 */
class Openverse_Connect_Admin {

	/**
	 * Constructor.
	 *
	 * Sets up the admin hooks and initializes the admin functionality.
	 * Registers menu items, settings, and admin notices.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_openverse_connect_oauth', array( $this, 'handle_oauth_redirect' ) );
		add_action( 'admin_post_openverse_register_app', array( $this, 'handle_app_registration' ) );
		add_action( 'admin_notices', array( $this, 'display_oauth_notices' ) );
	}

	/**
	 * Add menu item to WordPress admin.
	 *
	 * Creates the settings page menu item under Settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function add_admin_menu() {
		add_options_page(
			__( 'Openverse Connect Settings', 'openverse-connect' ),
			__( 'Openverse Connect', 'openverse-connect' ),
			'manage_options',
			'openverse-connect',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * Registers the settings fields and sections for the plugin.
	 * Includes client ID, client secret, and access token settings.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			'openverse_connect_settings',
			'openverse_connect_client_id',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'openverse_connect_settings',
			'openverse_connect_client_secret',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);

		register_setting(
			'openverse_connect_settings',
			'openverse_connect_access_token',
			array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'default'           => '',
			)
		);
	}

	/**
	 * Render the settings page.
	 *
	 * Displays the main settings page for the plugin.
	 * Shows connection status, registration form, and credentials.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function render_settings_page() {
		$client_id               = get_option( 'openverse_connect_client_id' );
		$client_secret           = get_option( 'openverse_connect_client_secret' );
		$access_token            = get_option( 'openverse_connect_access_token' );
		$is_connected            = ! empty( $access_token );
		$has_credentials         = ! empty( $client_id ) && ! empty( $client_secret );
		$show_manual_credentials = isset( $_GET['error'] ) && 'email_already_registered' === $_GET['error'];
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

			<?php if ( ! $is_connected ) : ?>
				<div class="notice notice-info">
					<p><?php esc_html_e( 'You need to connect to Openverse to use this plugin.', 'openverse-connect' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="openverse-connect-oauth">
				<h2><?php esc_html_e( 'Connection Status', 'openverse-connect' ); ?></h2>
				<?php if ( $is_connected ) : ?>
					<p class="openverse-connect-status connected">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Connected to Openverse', 'openverse-connect' ); ?>
					</p>
				<?php elseif ( $has_credentials ) : ?>
					<p class="openverse-connect-status not-connected">
						<span class="dashicons dashicons-warning"></span>
						<?php esc_html_e( 'Not connected to Openverse', 'openverse-connect' ); ?>
					</p>
				<?php else : ?>
					<p class="openverse-connect-status not-registered">
						<span class="dashicons dashicons-admin-plugins"></span>
						<?php esc_html_e( 'Register your WordPress site with Openverse', 'openverse-connect' ); ?>
					</p>
					<p class="description">
						<?php esc_html_e( 'This will create a new application registration with Openverse automatically. You can only have one application registration per email address.', 'openverse-connect' ); ?>
					</p>
					<form method="get" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="openverse_register_app">
						<?php wp_nonce_field( 'openverse_register_app' ); ?>

						<p class="description">
							<input type="email" 
								name="email" 
								id="openverse-connect-email" 
								value="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>" 
								placeholder="<?php esc_attr_e( 'Your email address', 'openverse-connect' ); ?>"
								required
								class="regular-text"
							>
						</p>

						<?php if ( $show_manual_credentials ) : ?>
							<div class="manual-credentials">
								<p class="description">
									<?php esc_html_e( 'An application is already registered with this email. If you already have credentials, you can enter them below:', 'openverse-connect' ); ?>
								</p>
								<p>
									<label for="openverse-connect-client-id"><?php esc_html_e( 'Client ID', 'openverse-connect' ); ?></label>
									<input type="text"
										name="client_id"
										id="openverse-connect-client-id"
										class="regular-text"
										placeholder="<?php esc_attr_e( 'Enter your existing Client ID', 'openverse-connect' ); ?>"
									>
								</p>
								<p>
									<label for="openverse-connect-client-secret"><?php esc_html_e( 'Client Secret', 'openverse-connect' ); ?></label>
									<input type="password"
										name="client_secret"
										id="openverse-connect-client-secret"
										class="regular-text"
										placeholder="<?php esc_attr_e( 'Enter your existing Client Secret', 'openverse-connect' ); ?>"
									>
								</p>
							</div>
						<?php endif; ?>

						<p>
							<button type="submit" class="button button-primary">
								<?php echo $show_manual_credentials ? esc_html__( 'Save Credentials', 'openverse-connect' ) : esc_html__( 'Register with Openverse', 'openverse-connect' ); ?>
							</button>
						</p>
					</form>
				<?php endif; ?>

				<?php if ( $has_credentials ) : ?>
					<hr>
					<h3><?php esc_html_e( 'Application Credentials', 'openverse-connect' ); ?></h3>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Client ID', 'openverse-connect' ); ?></th>
							<td><code><?php echo esc_html( $client_id ); ?></code></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Client Secret', 'openverse-connect' ); ?></th>
							<td>
								<code>••••••••</code>
								<button type="button" class="button-link" onclick="this.previousElementSibling.textContent = '<?php echo esc_js( $client_secret ); ?>'">
									<?php esc_html_e( 'Show', 'openverse-connect' ); ?>
								</button>
							</td>
						</tr>
					</table>
				<?php endif; ?>
			</div>
		</div>

		<style>
			.openverse-connect-oauth {
				margin-top: 20px;
				padding: 20px;
				background: #fff;
				border: 1px solid #ccd0d4;
				box-shadow: 0 1px 1px rgba(0,0,0,.04);
			}
			.openverse-connect-status {
				display: flex;
				align-items: center;
				gap: 8px;
				margin: 15px 0;
				font-size: 14px;
			}
			.openverse-connect-status .dashicons {
				font-size: 20px;
				width: 20px;
				height: 20px;
			}
			.openverse-connect-status.connected .dashicons {
				color: #46b450;
			}
			.openverse-connect-status.not-connected .dashicons {
				color: #dc3232;
			}
			.openverse-connect-status.not-registered .dashicons {
				color: #007cba;
			}
			.description {
				color: #646970;
				font-style: italic;
				margin: 5px 0 15px;
			}
			.manual-credentials {
				margin: 15px 0;
				padding: 15px;
				background: #f8f9fa;
				border: 1px solid #e5e7eb;
				border-radius: 4px;
			}
			.manual-credentials label {
				display: block;
				margin-bottom: 5px;
				font-weight: 600;
			}
			.manual-credentials input {
				margin-bottom: 15px;
			}
		</style>
		<?php
	}

	/**
	 * Handle application registration with Openverse.
	 *
	 * Processes the application registration form submission.
	 * Creates a new application registration or validates existing credentials.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function handle_app_registration() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'openverse-connect' ) );
		}

		check_admin_referer( 'openverse_register_app' );

		if ( ! isset( $_GET['email'] ) ) {
			wp_safe_redirect( admin_url( 'options-general.php?page=openverse-connect&error=missing_email' ) );
			exit;
		}

		$email = sanitize_email( wp_unslash( $_GET['email'] ) );

		if ( ! is_email( $email ) ) {
			wp_safe_redirect( admin_url( 'options-general.php?page=openverse-connect&error=invalid_email' ) );
			exit;
		}

		// Check if manual credentials were provided.
		if ( isset( $_GET['client_id'], $_GET['client_secret'] ) ) {
			$client_id     = sanitize_text_field( wp_unslash( $_GET['client_id'] ) );
			$client_secret = sanitize_text_field( wp_unslash( $_GET['client_secret'] ) );

			// Verify the credentials by attempting to get a token.
			update_option( 'openverse_connect_client_id', $client_id );
			update_option( 'openverse_connect_client_secret', $client_secret );

			$token = $this->get_client_credentials_token();
			if ( is_wp_error( $token ) ) {
				delete_option( 'openverse_connect_client_id' );
				delete_option( 'openverse_connect_client_secret' );
				wp_safe_redirect( admin_url( 'options-general.php?page=openverse-connect&error=invalid_credentials' ) );
				exit;
			}

			update_option( 'openverse_connect_access_token', $token );
			wp_safe_redirect( admin_url( 'options-general.php?page=openverse-connect&registered=1&connected=1' ) );
			exit;
		}

		$site_name = get_bloginfo( 'name' );
		$site_url  = get_site_url();

		$response = wp_remote_post(
			'https://api.openverse.engineering/v1/auth_tokens/register/',
			array(
				'body' => array(
					'name'         => sprintf( '%s Openverse Connect Plugin', $site_name ),
					'description'  => sprintf(
						'Openverse integration for WordPress site: %s (%s)',
						$site_name,
						$site_url
					),
					'email'        => $email,
					'redirect_uri' => admin_url( 'admin-post.php?action=openverse_connect_oauth' ),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_safe_redirect( admin_url( 'options-general.php?page=openverse-connect&error=registration_failed' ) );
			exit;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['client_id'] ) || empty( $body['client_secret'] ) ) {
			if ( isset( $body['name'][0] ) && 'o auth2 registration with this name already exists.' === $body['name'][0] ) {
				wp_safe_redirect( admin_url( 'options-general.php?page=openverse-connect&error=email_already_registered' ) );
				exit;
			}
			wp_safe_redirect( admin_url( 'options-general.php?page=openverse-connect&error=invalid_registration_response' ) );
			exit;
		}

		update_option( 'openverse_connect_client_id', $body['client_id'] );
		update_option( 'openverse_connect_client_secret', $body['client_secret'] );

		// After successful registration, get a client credentials token.
		$token = $this->get_client_credentials_token();
		if ( is_wp_error( $token ) ) {
			wp_safe_redirect( admin_url( 'options-general.php?page=openverse-connect&error=token_error' ) );
			exit;
		}

		update_option( 'openverse_connect_access_token', $token );
		wp_safe_redirect( admin_url( 'options-general.php?page=openverse-connect&registered=1&connected=1' ) );
		exit;
	}

	/**
	 * Get access token using client credentials grant.
	 *
	 * Retrieves or generates a new access token for API authentication.
	 * Uses client credentials flow to obtain the token.
	 *
	 * @since 1.0.0
	 * @return string|WP_Error Access token or error.
	 */
	public function get_client_credentials_token() {
		$get_transient = get_transient( 'openverse_connect_access_token' );

		if ( false !== $get_transient ) {
			return $get_transient;
		}

		$client_id     = get_option( 'openverse_connect_client_id' );
		$client_secret = get_option( 'openverse_connect_client_secret' );

		$response = wp_remote_post(
			'https://api.openverse.org/v1/auth_tokens/token/',
			array(
				'body' => array(
					'grant_type'    => 'client_credentials',
					'client_id'     => $client_id,
					'client_secret' => $client_secret,
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['access_token'] ) ) {
			return new WP_Error( 'invalid_token', __( 'Invalid response from Openverse', 'openverse-connect' ) );
		}

		/**
		 * Save the access token to the transient.
		 * Use the expires_in value to set the transient expiration.
		 */
		set_transient( 'openverse_connect_access_token', $body['access_token'], $body['expires_in'] );

		return $body['access_token'];
	}

	/**
	 * Get the OAuth URL for connecting to Openverse.
	 *
	 * Generates the OAuth authorization URL with proper parameters.
	 * Includes client ID, state, and redirect URI.
	 *
	 * @since 1.0.0
	 * @return string The OAuth URL.
	 */
	private function get_oauth_url() {
		$client_id    = get_option( 'openverse_connect_client_id' );
		$redirect_uri = admin_url( 'admin-post.php?action=openverse_connect_oauth' );
		$state        = wp_create_nonce( 'openverse_connect_oauth' );

		return add_query_arg(
			array(
				'client_id'     => $client_id,
				'response_type' => 'code',
				'state'         => $state,
				'redirect_uri'  => urlencode( $redirect_uri ),
			),
			'https://api.openverse.engineering/v1/auth/oauth/authorize'
		);
	}

	/**
	 * Display OAuth-related admin notices.
	 *
	 * Shows success and error messages related to OAuth operations.
	 * Handles various error cases and success states.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function display_oauth_notices() {
		$screen = get_current_screen();
		if ( 'settings_page_openverse-connect' !== $screen->id ) {
			return;
		}

		if ( isset( $_GET['registered'] ) ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Successfully registered with Openverse! You can now connect your site.', 'openverse-connect' ); ?></p>
			</div>
			<?php
		}

		if ( isset( $_GET['connected'] ) ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Successfully connected to Openverse!', 'openverse-connect' ); ?></p>
			</div>
			<?php
		}

		if ( isset( $_GET['disconnected'] ) ) {
			?>
			<div class="notice notice-info is-dismissible">
				<p><?php esc_html_e( 'Successfully disconnected from Openverse.', 'openverse-connect' ); ?></p>
			</div>
			<?php
		}

		if ( isset( $_GET['error'] ) ) {
			$error_message = '';
			switch ( $_GET['error'] ) {
				case 'missing_email':
					$error_message = __( 'Email address is required.', 'openverse-connect' );
					break;
				case 'invalid_email':
					$error_message = __( 'Please enter a valid email address.', 'openverse-connect' );
					break;
				case 'registration_failed':
					$error_message = __( 'Failed to register with Openverse. Please try again.', 'openverse-connect' );
					break;
				case 'invalid_registration_response':
					$error_message = __( 'Invalid response from Openverse registration.', 'openverse-connect' );
					break;
				case 'invalid_response':
					$error_message = __( 'Invalid response from Openverse.', 'openverse-connect' );
					break;
				case 'invalid_state':
					$error_message = __( 'Invalid state parameter.', 'openverse-connect' );
					break;
				case 'token_error':
					$error_message = __( 'Error obtaining access token.', 'openverse-connect' );
					break;
				case 'email_already_registered':
					$error_message = __( 'An application is already registered with this email. You can enter your existing credentials below.', 'openverse-connect' );
					break;
				case 'invalid_credentials':
					$error_message = __( 'The provided credentials are invalid. Please check and try again.', 'openverse-connect' );
					break;
			}
			?>
			<div class="notice notice-error is-dismissible">
				<p><?php echo esc_html( $error_message ); ?></p>
			</div>
			<?php
		}
	}
}
