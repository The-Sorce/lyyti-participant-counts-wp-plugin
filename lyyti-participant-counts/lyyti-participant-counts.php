<?php

/*
 * Plugin Name: Lyyti Participant Counts
 * Plugin URI: https://github.com/The-Sorce/lyyti-participant-counts-wp-plugin
 * Description: Exposes number of participants from Lyyti events in shortcodes using the Lyyti API.
 * Author: Tony Karlsson
 * Author URI: https://github.com/The-Sorce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Lyyti_Participant_Counts {

	private static $instance = null;

	private function __construct() {
		// Activation and deactivation hooks
		register_activation_hook(__FILE__, array($this, 'activation_hook_func'));
		register_deactivation_hook(__FILE__, array($this, 'deactivation_hook_func'));

		// Admin menu
		add_action('admin_menu', array($this, 'options_page'));

		// Init Settings
		add_action('admin_init', array($this, 'settings_init'));

		// Shortcode
		add_shortcode('lyyti-participant-count', array($this, 'lyyti_participant_count_shortcode'));
	}

	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function activation_hook_func() {
		// Set some default option values
		add_option('lyyti_api_public_key', '', '', 'no');
		add_option('lyyti_api_private_key', '', '', 'no');
		add_option('lyyti_default_eid', '', '', 'no');
		add_option('lyyti_default_status', 'reactedyes,show', '', 'no');
		add_option('lyyti_cache_lifetime', '600', '', 'no');
	}

	public function deactivation_hook_func() {
		// Remove all options used by the plugin
		delete_option('lyyti_api_public_key');
		delete_option('lyyti_api_private_key');
		delete_option('lyyti_default_eid');
		delete_option('lyyti_default_status');
		delete_option('lyyti_cache_lifetime');
	}

	public function options_page() {
		add_options_page(
			'Lyyti Participant Counts Options',
			'Lyyti Options',
			'manage_options',
			'lyyti',
			array($this, 'options_page_html')
		);
	}

	public function options_page_html() {
		// check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p>Here you can configure the Lyyti Participant Counts plugin settings and everything that happens under the hood. Batteries not included.</p>
			<form action="options.php" method="post">
				<?php
				settings_fields('lyyti');
				do_settings_sections('lyyti');
				submit_button('Save Settings');
				?>
			</form>
		</div>
		<?php
	}

	public function settings_init() {
		register_setting('lyyti', 'lyyti_api_public_key');
		register_setting('lyyti', 'lyyti_api_private_key');
		register_setting('lyyti', 'lyyti_default_eid');
		register_setting('lyyti', 'lyyti_default_status');
		register_setting('lyyti', 'lyyti_cache_lifetime');

		$that = $this; // Needed for the callbacks when adding the settings fields.

		add_settings_section(
			'lyyti_api_credentials_settings_section',
			'Lyyti API Credentials',
			function() {
				echo '<p>Here you need to provide the credentials to be used to access the Lyyti API.</p>';
			},
			'lyyti'
		);

		add_settings_field(
			'lyyti_api_public_key_settings_field',
			'Public key',
			function() use($that) { $that->settings_field_callback('lyyti_api_public_key'); },
			'lyyti',
			'lyyti_api_credentials_settings_section'
		);

		add_settings_field(
			'lyyti_api_private_key_settings_field',
			'Private key',
			function() use($that) { $that->settings_field_callback('lyyti_api_private_key'); },
			'lyyti',
			'lyyti_api_credentials_settings_section'
		);

		add_settings_section(
			'lyyti_events_settings_section',
			'Lyyti Events settings',
			function() {
				echo '<p>Here we define default values to use if not overridden in the shortcode.</p>';
			},
			'lyyti'
		);

		add_settings_field(
			'lyyti_default_eid_settings_field',
			'Default Event ID (eid)',
			function() use($that) {
				$that->settings_field_callback(
					'lyyti_default_eid',
					'Numeric event id, integer'
				);
			},
			'lyyti',
			'lyyti_events_settings_section'
		);

		add_settings_field(
			'lyyti_default_status_settings_field',
			'Default participant statuses',
			function() use($that) {
				$that->settings_field_callback(
					'lyyti_default_status',
					"Needs to be a comma-separated list without whitespace.\n Possible values: notreacted, reactedno, reactedyes, noshow, show"
				);
			},
			'lyyti',
			'lyyti_events_settings_section'
		);

		add_settings_section(
			'lyyti_cache_settings_section',
			'Cache settings',
			function() {
				echo '<p>In order to not spam the Lyyti API on every page load, we are able to cache the API responses.</p>';
			},
			'lyyti'
		);

		add_settings_field(
			'lyyti_default_eid_settings_field',
			'Cache lifetime',
			function() use($that) {
				$that->settings_field_callback(
					'lyyti_cache_lifetime',
					"The number of seconds to cache the API responses.\nIf not set, this defaults to 600 seconds."
				);
			},
			'lyyti',
			'lyyti_cache_settings_section'
		);

	}

	public function settings_field_callback($option_name, $description = '') {
		$setting = get_option($option_name);
		?>
		<input type="text" name="<?php echo $option_name; ?>" value="<?php echo isset( $setting ) ? esc_attr( $setting ) : ''; ?>">
		<?php
		if (!empty($description)) {
			?>
			<p class="description"><?php echo nl2br(esc_html($description)); ?></p>
			<?php
		}
	}

	public function lyyti_participant_count_shortcode($atts) {
		$a = shortcode_atts(array(
			'eid' => get_option('lyyti_default_eid'),
			'status' => get_option('lyyti_default_status'),
		), $atts);
		if (empty($a['eid'])) {
			// TODO: Error handling for cases where eid is undefined
			return 'ERROR_LYYTI_EID_UNDEFINED';
		}
		if (empty($a['status'])) {
			// TODO: Error handling for cases where status is undefined
			return 'ERROR_LYYTI_STATUS_UNDEFINED';
		}
		return $this->lyyti_participant_count($a['eid'], $a['status']);
	}

	public function lyyti_participant_count($eid, $status) {
		$transient_name = "lyyti_participant_count_{$eid}_{$status}";
		$transient = get_transient($transient_name);
		if ($transient !== false) {
			return $transient;
		}

		$public_key = get_option('lyyti_api_public_key');
		$private_key = get_option('lyyti_api_private_key');
		if (empty($public_key) || empty($private_key)) {
			// TODO: Error handling for missing Lyyti API credentials
			return 'ERROR_LYYTI_API_CREDENTIALS_MISSING';
		}

		$response = $this->lyyti_api_call($public_key, $private_key, "events/{$eid}/participants?status={$status}");
		if (empty($response['results_count'])) {
			// TODO: Error handling for unexpected Lyyti API response
			return 'ERROR_LYYTI_UNEXPECTED_API_RESPONSE';
		}

		$participant_count = $response['results_count'];

		$transient_expiration = get_option('lyyti_cache_lifetime', 600);
		set_transient($transient_name, $participant_count, $transient_expiration);

		return $participant_count;
	}

	// This function is stolen directly from the Lyyti API documentation
	private function lyyti_api_call($public_key, $private_key, $call_string, $http_method = 'GET', $payload = null)
	{
		// Generate the signature based on the API keys
		$timestamp = time();
		$signature = hash_hmac(
			'sha256',
			base64_encode($public_key.','.$timestamp.','.$call_string),
			$private_key
		);

		// Set the required HTTP headers
		$http_headers = array(
			'Accept: application/json; charset=utf-8',
			'Authorization: LYYTI-API-V2 public_key='.$public_key.', timestamp='.$timestamp.', signature='.$signature
		);

		// Initialize the cURL connection
		$curl = curl_init('https://api.lyyti.com/v2/'.$call_string);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		// Handle HTTP method and payload
		if ($http_method != 'GET' && isset($payload))
		{
			if ($http_method == 'PATCH')
			{
				$http_headers[] = 'Content-Type: application/merge-patch+json';
			}
			else
			{
				$http_headers[] = 'Content-Type: application/json; charset=utf-8';
			}
			if ($http_method == 'POST')
			{
				curl_setopt($curl, CURLOPT_POST, true);
			}
			else
			{
				curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $http_method);
			}
			curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
		}

		// Set the HTTP headers and execute the call
		curl_setopt($curl, CURLOPT_HTTPHEADER, $http_headers);
		$result_json = curl_exec($curl);

		// Check for errors
		if ($curl_errno = curl_errno($curl))
		{
			return array('curl_error' => array('errno' => $curl_errno, 'message' => curl_strerror($curl_errno)));
		}
		curl_close($curl);

		// Turn the resulting JSON into a PHP associative array and return it
		$result_array = json_decode($result_json, $assoc = true);
		return $result_array;
	}
}

$Lyyti_Participant_Counts = Lyyti_Participant_Counts::getInstance();
