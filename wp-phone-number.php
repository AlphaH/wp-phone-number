<?php
/*
Plugin Name:    WP Phone Number
Plugin URI:     https://github.com/Eruonen/wp-phone-number
Description:    This plugin allows you to insert formatted and validated phone number links into posts and pages with extreme ease.
Version:        1.0
Author:         Nathaniel Williams
Author URI:     http://coios.net/
Author Email:   nathaniel.williams@coios.net
License:        MIT
*/

use com\google\i18n\phonenumbers\PhoneNumberUtil;
use com\google\i18n\phonenumbers\PhoneNumberFormat;
use com\google\i18n\phonenumbers\PhoneNumberType;
use com\google\i18n\phonenumbers\NumberParseException;

require_once plugin_dir_path( __FILE__ ) . '/libphonenumber/PhoneNumberUtil.php';

class WP_Phone_Number {
	 
	/**
	 * Constructor
	 */
	function __construct() {
		// Load plugin text domain
		add_action( 'init', array( $this, 'plugin_textdomain' ) );
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
	    add_shortcode( 'phone', array( $this, 'shortcode' ) );

	}

	/**
	 * Loads the plugin text domain for translation
	 */
	public function plugin_textdomain() {
		$domain = 'wp-phone-number';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
        load_textdomain( $domain, WP_LANG_DIR.'/'.$domain.'/'.$domain.'-'.$locale.'.mo' );
        load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}

	public function add_settings_page() {
		add_options_page(
			'WP Phone Number',
			'WP Phone Number',
			'manage_options',
			'wp_phone_number',
			array( $this, 'create_settings_page' )
		);
		add_action( 'admin_init', array( $this, 'create_settings' ) );
	}

	public function create_settings() { // jesus...
		register_setting(
			'wp_phone_number_settings',
			'wp_phone_number_settings',
			array( $this, 'validate_settings' )
		);
		add_settings_section(
			'wp_phone_number_main',
			'Main Settings',
			array( $this, 'get_settings_section_text' ),
			'wp_phone_number'
		);
		add_settings_field(
			'wp_phone_number_region',
			'Default region from which the phone numbers will originate (can be overridden in shortcode)',
			array( $this, 'create_region_field' ),
			'wp_phone_number',
			'wp_phone_number_main'
		);
		add_settings_field(
			'wp_phone_number_format',
			'Phone number formatting standard',
			array( $this, 'create_format_type_field' ),
			'wp_phone_number',
			'wp_phone_number_main'
		);
		add_settings_field(
			'wp_phone_number_linkify',
			'Turn phone numbers into links by default.',
			array( $this, 'create_linkify_field' ),
			'wp_phone_number',
			'wp_phone_number_main'
		);
	}

	public function create_region_field() {
		require_once plugin_dir_path( __FILE__ ) . '/includes/countries.php';
		$wppn_countries = wp_phone_number_get_countries();
		$options = get_option( 'wp_phone_number_settings' );
		$options['wp_phone_number_region'] = isset( $options['wp_phone_number_region'] ) ? $options['wp_phone_number_region'] : 'US';
<<<<<<< HEAD
=======
		$options['wp_phone_number_region'] = isset( $options['wp_phone_number_region'] ) ? $options['wp_phone_number_region'] : '';
>>>>>>> 05d98a7bb93c88e1f839c5b13b093836d1dad8b7
		echo '<select id="wp_phone_number_region" name="wp_phone_number_settings[wp_phone_number_region]">';
		foreach( $wppn_countries as $country_code => $country ) {
			$selected = ( $options['wp_phone_number_region'] === $country_code ) ? 'selected' : '';
			printf('<option value="%s" %s>%s</option>', $country_code, $selected, $country);
		}
		echo '</select>';
	}

	public function create_linkify_field() {
		$options = get_option( 'wp_phone_number_settings' );
		$options['wp_phone_number_linkify'] = isset( $options['wp_phone_number_linkify'] ) ? $options['wp_phone_number_linkify'] : true;
		?>
		<input <?php echo ( $options['wp_phone_number_linkify'] ? 'checked' : '' ) ?> type="checkbox" name="wp_phone_number_settings[wp_phone_number_linkify]" value="true">
		<?php _e( '(check to enable)', 'wp-phone-number' );
	}

	public function create_format_type_field() {
		$options = get_option( 'wp_phone_number_settings' );
		if(isset($options['wp_phone_number_format']))
			$format = intval( $options['wp_phone_number_format'] );
		else
			$format = PhoneNumberFormat::INTERNATIONAL;
		$options = get_option( 'wp_phone_number_settings' );
		$region = isset( $options['wp_phone_number_region'] ) ? $options['wp_phone_number_region'] : 'US';
		?>
		<select id="wp_phone_number_format" name="wp_phone_number_settings[wp_phone_number_format]">
			<option <?php echo ( $format === PhoneNumberFormat::E164 ? 'selected' : '' ); ?> value="<?php echo PhoneNumberFormat::E164 ?>">E.164</option>
			<option <?php echo ( $format === PhoneNumberFormat::INTERNATIONAL ? 'selected' : '' ); ?> value="<?php echo PhoneNumberFormat::INTERNATIONAL ?>">International</option>
			<option <?php echo ( $format === PhoneNumberFormat::NATIONAL ? 'selected' : '' ); ?> value="<?php echo PhoneNumberFormat::NATIONAL ?>">National</option>
			<option <?php echo ( $format === PhoneNumberFormat::RFC3966 ? 'selected' : '' ); ?> value="<?php echo PhoneNumberFormat::RFC3966 ?>">RFC 3966</option>
		</select>
		<?php
		$this->generate_examples( $format, $region );
	}

	private function generate_examples( $format, $region ) {
		$phone_util = PhoneNumberUtil::getInstance();
		$input_fixed = $phone_util->format( $phone_util->getExampleNumberForType( $region, PhoneNumberType::FIXED_LINE ), PhoneNumberFormat::INTERNATIONAL );
		$input_mobile = $phone_util->format( $phone_util->getExampleNumberForType( $region, PhoneNumberType::MOBILE ), PhoneNumberFormat::INTERNATIONAL );
		?>
		<p style="" id="wp_phone_number_format_example">
			<span class="example" <?php echo ( $format !== PhoneNumberFormat::E164 ? 'style="display:none"' : '' ); ?> id="example-<?php echo PhoneNumberFormat::E164; ?>">
				<?php _e( 'Example fixed line:', 'wp-phone-number' ); ?> <?php echo $this->format_phone_number( $input_fixed, null, PhoneNumberFormat::E164, false ); ?><br>
				<?php _e( 'Example mobile:', 'wp-phone-number' ); ?> <?php echo $this->format_phone_number( $input_mobile, null, PhoneNumberFormat::E164, false ); ?>
			</span>
			<span class="example" <?php echo ( $format !== PhoneNumberFormat::INTERNATIONAL ? 'style="display:none"' : '' ); ?> id="example-<?php echo PhoneNumberFormat::INTERNATIONAL; ?>">
				<?php _e( 'Example fixed line:', 'wp-phone-number' ); ?> <?php echo $this->format_phone_number( $input_fixed, null, PhoneNumberFormat::INTERNATIONAL, false ); ?><br>
				<?php _e( 'Example mobile:', 'wp-phone-number' ); ?> <?php echo $this->format_phone_number( $input_mobile, null, PhoneNumberFormat::INTERNATIONAL, false ); ?>
			</span>
			<span class="example" <?php echo ( $format !== PhoneNumberFormat::NATIONAL ? 'style="display:none"' : '' ); ?> id="example-<?php echo PhoneNumberFormat::NATIONAL; ?>">
				<?php _e( 'Example fixed line:', 'wp-phone-number' ); ?> <?php echo $this->format_phone_number( $input_fixed, null, PhoneNumberFormat::NATIONAL, false ); ?><br>
				<?php _e( 'Example mobile:', 'wp-phone-number' ); ?> <?php echo $this->format_phone_number( $input_mobile, null, PhoneNumberFormat::NATIONAL, false ); ?>
			</span>
			<span class="example" <?php echo ( $format !== PhoneNumberFormat::RFC3966 ? 'style="display:none"' : '' ); ?> id="example-<?php echo PhoneNumberFormat::RFC3966; ?>">
				<?php _e( 'Example fixed line:', 'wp-phone-number' ); ?> <?php echo $this->format_phone_number( $input_fixed, null, PhoneNumberFormat::RFC3966, false ); ?><br>
				<?php _e( 'Example mobile:', 'wp-phone-number' ); ?> <?php echo $this->format_phone_number( $input_mobile, null, PhoneNumberFormat::RFC3966, false ); ?>
			</span>
		</p>
		<script>jQuery(document).ready(function($) {
			$('#wp_phone_number_format').change(function() {
				$('#wp_phone_number_format_example .example').hide();
				$('#wp_phone_number_format_example #example-' + $(this).val()).show();
			});
		});</script>
		<?php
	}

	public function get_settings_section_text() {
		echo '<p>' . __( 'Please enter the desired settings for this plugin.', 'wp-phone-number' ) . '</p>';
	}

	public function create_settings_page() {
		?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2><?php _e( 'WP Phone Number settings', 'wp-phone-number' ); ?></h2>
			<form action="options.php" method="post">
			<?php settings_fields( 'wp_phone_number_settings' ); ?>
			<?php do_settings_sections( 'wp_phone_number' ); ?>
			<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public function validate_settings( $input ) {
		$region = $input['wp_phone_number_region'];
		$region = strtoupper( substr( $region, 0, 2 ) );
		if( !preg_match('/[A-Z]{2}/', $region) )
			$region = '';
		$input['wp_phone_number_region'] = $region;

		$format = intval($input['wp_phone_number_format']);
		if( $format > 3 || $format < 0)
			$format = PhoneNumberFormat::INTERNATIONAL;
		$input['wp_phone_number_format'] = $format;
		
		$linkify = $input['wp_phone_number_linkify'];
		$linkify = ($linkify == 'true');
		$input['wp_phone_number_linkify'] = $linkify;
		return $input;
	}
	
	/**
	 * Shortcode handling
	 */
	public function shortcode( $attributes, $content = null ) {
		if( !is_null($content) && $content !== '' ) {
			$input = $content;
		} else if(isset($attributes[0])) {
			$input = $attributes[0];
		} else if(isset($attributes['number'])) {
			$input = $attributes['number'];
		} else {
			$input = ''; // this will throw an exception later, that will be caught just fine.
		}
		$options = get_option( 'wp_phone_number_settings' );
		$region = isset( $attributes['region'] ) ? strtoupper( $attributes['region'] ) : $options['wp_phone_number_region'];
		if( isset( $attributes['format'] ) ) {
			// Interpret the format attribute. If we can't, use the settings.
			switch ( strtoupper( $attributes['format'] ) ) {
				case 'E164':
				case 'E.164':
					$format = PhoneNumberFormat::E164;
					break;
				case 'INT':
				case 'INTERNATIONAL':
					$format = PhoneNumberFormat::INTERNATIONAL;
					break;
				case 'DOMESTIC':
				case 'NATIONAL':
					$format = PhoneNumberFormat::NATIONAL;
					break;
				case 'RFC 3966':
				case 'RFC-3966':
				case 'RFC3966':
					$format = PhoneNumberFormat::RFC3966;
					break;
				default:
					$format = $options['wp_phone_number_format'];
					break;
			}
		} else {
			$format = $options['wp_phone_number_format'];
		}
		if ( isset( $attributes['linkify'] ) ) {
			switch ( strtoupper( $attributes['linkify'] ) ) {
				case 'YES':
				case 'TRUE':
				case '1':
					$linkify = true;
					break;
				case 'NO':
				case 'FALSE':
				case '0':
					$linkify = false;
					break;
				default:
					$linkify = $options['wp_phone_number_linkify'];
					break;
			}
		} else {
			$linkify = $options['wp_phone_number_linkify'];
		}
		return $this->format_phone_number( $input, $region, $format, $linkify );
	}

	/**
	 * Parse and format phone numbers
	 */
	public function format_phone_number( $input, $region = null, $formatting = PhoneNumberFormat::INTERNATIONAL, $linkify = true ) {
		$phone_util = PhoneNumberUtil::getInstance();
		try {
			$phone_nr_proto = $phone_util->parseAndKeepRawInput($input, $region);
		} catch (NumberParseException $e) {
			$this->log_error( $e, $input, $region );
			return $input;
		}
		if( ! $phone_util->isValidNumber( $phone_nr_proto ) ) {
			$this->log_error( __( 'Given phone number failed to validate.', 'wp-phone-number' ), $input, $region );
			return $input;
		}
		if( $linkify )
			return $this->linkify_phone_number( $phone_util, $phone_nr_proto, $formatting );
		else
			return $phone_util->format( $phone_nr_proto, $formatting );
	}

	private function linkify_phone_number( $phone_util, $phone_nr_proto, $formatting ) {
		return sprintf(
			'<a class="phone_link" title="%1$s" href="%2$s">%1$s</a>',
			$phone_util->format( $phone_nr_proto, $formatting ),
			$phone_util->format( $phone_nr_proto, PhoneNumberFormat::E164 )
		);
	}

	/**
	 * Handles error logging
	 */
	private function log_error($error, $input = '', $region = null) {
		// TODO:	log the error somewhere
		if( is_object( $error ) && get_class( $error ) === 'com\google\i18n\phonenumbers\NumberParseException' ) {
			switch ( $error->getErrorType() ) {
				case NumberParseException::INVALID_COUNTRY_CODE:
					$error_message = __( 'Country calling code supplied was not recognized.', 'wp-phone-number' );
					break;
				case NumberParseException::NOT_A_NUMBER:
					$error_message = __( 'The input supplied did not seem to be a phone number.', 'wp-phone-number' );
					break;
				case NumberParseException::TOO_SHORT_AFTER_IDD:
					$error_message = __( 'The number had an IDD, but after this was not long enough to be a viable phone number.', 'wp-phone-number' );
					break;
				case NumberParseException::TOO_SHORT_NSN:
					$error_message = __( 'The input supplied is too short to be a phone number.', 'wp-phone-number' );
					break;
				case NumberParseException::TOO_LONG:
					$error_message = __( 'The input supplied is too long to be a phone number.', 'wp-phone-number' );
					break;
				default:
					$error_message = sprintf( __( 'Unrecognized error: %s', 'wp-phone-number' ), $error);
					break;
			}
		} else {
			$error_message = (string) $error;
		}
		$region = is_null($region) ? 'null' : $region;
		return sprintf( '%s [%s %s]', $error_message, $input, $region );
	}

  
}

$wp_phone_number = new WP_Phone_Number();

// template tag for developers
function wp_phone_number_parse( $input, $region = null, $format = null, $linkify = null ) {
	$wp_phone_number = new WP_Phone_Number();
	$options = get_option( 'wp_phone_number_settings' );
	$region = !is_null( $region ) ? strtoupper( $region ) : $options['wp_phone_number_region'];
	if( !is_null( $format ) ) {
		// Interpret the format attribute. If we can't, use the settings.
		switch ( strtoupper( $format ) ) {
			case 'E164':
			case 'E.164':
				$format = PhoneNumberFormat::E164;
				break;
			case 'INT':
			case 'INTERNATIONAL':
				$format = PhoneNumberFormat::INTERNATIONAL;
				break;
			case 'DOMESTIC':
			case 'NATIONAL':
				$format = PhoneNumberFormat::NATIONAL;
				break;
			case 'RFC 3966':
			case 'RFC-3966':
			case 'RFC3966':
				$format = PhoneNumberFormat::RFC3966;
				break;
			default:
				$format = $options['wp_phone_number_format'];
				break;
		}
	} else {
		$format = $options['wp_phone_number_format'];
	}
	if ( !is_null( $linkify ) || !is_bool( $linkify ) )
		$linkify = $options['wp_phone_number_linkify'];

	return $wp_phone_number->format_phone_number( $input, $region, $format, $linkify );
}
