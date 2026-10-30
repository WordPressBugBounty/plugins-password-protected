<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * @package     Password Protected
 * @subpackage  All-Captchas
 *
 * @since  2.7.8
 */

class Password_Protected_Free_allCaptchas {
    private static $instance = null;
    public $options_group = 'password-protected-all-captchas';
	public $options_name  = 'password_protected_allcaptchas';
	public $tab           = 'password-protected&tab=advanced-all-captchas';
	public $settings      = array();

	public function __construct() {
		$this->settings = get_option( $this->options_name, 'none' );
		add_action( 'admin_init', array( $this, 'register_all_captchas_settings' ), 6 );
		add_action( 'password_protected_subtab_all-captchas_content', array( $this, 'all_captchas_settings' ) );
	}
	
    public static function getInstance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

	public function register_all_captchas_settings() {

		add_settings_section(
			$this->options_group,
			'',
			'__return_null',
			$this->tab
		);

		add_settings_field(
			'password_protected_enable_allcaptchas',
			'',
			array( $this, 'all_captchas_enable' ),
			$this->tab,
			$this->options_group
		);
        
        add_settings_field(
			'password_protected_show_captcha_fields',
			'',
			array( $this, 'show_captchas_fields' ),
			$this->tab,
			$this->options_group
		);

		register_setting(
			$this->options_group,
			$this->options_name,
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_captcha_provider' ),
				'default'           => 'none',
			)
		);

	}

	/**
	 * Allowlist captcha provider slug.
	 *
	 * @param mixed $value Submitted provider.
	 * @return string
	 */
	public function sanitize_captcha_provider( $value ) {
		$allowed = array( 'recaptcha', 'none' );
		$value   = is_string( $value ) ? sanitize_key( $value ) : 'none';

		/**
		 * Filter allowed captcha provider slugs before saving.
		 *
		 * @param string[] $allowed Allowed provider slugs.
		 */
		$allowed = apply_filters( 'password_protected_allowed_captcha_providers', $allowed );

		if ( ! is_array( $allowed ) ) {
			$allowed = array( 'recaptcha', 'none' );
		}

		$allowed = array_map( 'sanitize_key', $allowed );

		if ( in_array( $value, $allowed, true ) ) {
			return $value;
		}

		return 'none';
	}

	public function all_captchas_settings() {
		?>
        <div class="wrap allCaptchaTabsTable">
            <h1 class="enableCaptchaHeading"><?php esc_html_e( 'Enable Captcha', 'password-protected' ); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields( $this->options_group );
                do_settings_sections( 'password-protected&tab=advanced-all-captchas' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

	public function all_captchas_enable() {
        
                $captcha_providers = array(
                    'recaptcha' => array(
                        'label' => 'reCAPTCHA',
                    ),
                    'none'      => array(
                        'label' => 'None',
                    ),
                );
                $captcha_providers = apply_filters( 'password_protected_captcha_providers', $captcha_providers );
                $i = 1;
                ?>
                <span class="captcha-setting-field">
                <?php
                foreach ( $captcha_providers as $value => $provider ) {
                        $input_id = 'captcha-setting-captcha-provider[' . $i . ']';
                        $label    = $provider['label'];

                        echo '<span class="captcha-settings-field-radio-wrapper">';
                        echo '<input type="radio" id="' . esc_attr( $input_id ) . '" name="' . esc_attr( $this->options_name ) . '" value="' . esc_attr( $value ) . '" ' . checked( $this->settings === $value, true, false ) . ' />';
                        echo '<label for="' . esc_attr( $input_id ) . '" class="option-' . esc_attr( strtolower( $value ) ) . '">' . esc_html( $label ) . '</label>';
                        echo '</span>';

                    $i++;
                }
                ?>
                </span>
                <?php
	}

    public function show_captchas_fields() {
        
        do_action( 'password_protected_all_captchas' );
        echo '
            <div class="noneTab"></div>
        ';

	}

}

