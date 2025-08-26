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
        // print_r(debug_backtrace(0));
		$this->settings = get_option( $this->options_name );
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
			__( ' ', 'password-protected-pro' ),
			'__return_null',
			$this->tab
		);

		add_settings_field(
			'password_protected_enable_allcaptchas',
			__( '', 'password-protected-pro' ),
			array( $this, 'all_captchas_enable' ),
			$this->tab,
			$this->options_group
		);
        
        add_settings_field(
			'password_protected_show_captcha_fields',
			__( ' ', 'password-protected-pro' ),
			array( $this, 'show_captchas_fields' ),
			$this->tab,
			$this->options_group
		);

		register_setting( $this->options_group, $this->options_name, array( 'type' => 'array' ) );

	}

	public function all_captchas_settings() {
		?>
        <div class="wrap allCaptchaTabsTable">
            <h1 class="enableCaptchaHeading"><?php _e( 'Enable Captcha', 'password-protected-pro' ); ?></h1>
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
        echo '
            <span class="captcha-setting-field">
                <span class="captcha-settings-field-radio-wrapper">
                    <input 
                    type="radio" 
                    id="captcha-setting-captcha-provider[1]" 
                    name="'.esc_attr( $this->options_name ).'" 
                    value="recaptcha"
                    '. ( $this->settings === 'recaptcha' ? 'checked="checked"' : '' ) .'>
                    <label for="captcha-setting-captcha-provider[1]" class="option-recaptcha-free">reCAPTCHA</label>
                </span>
                <span class="captcha-settings-field-radio-wrapper click-to-display-popup">
                    <input 
                    type="radio" 
                    id="captcha-setting-captcha-provider[2]" 
                    name="'.esc_attr( $this->options_name ).'" 
                    value="hcaptcha"
                    '. ( $this->settings === 'hcaptcha' ? 'checked="checked"' : '' ) .'>
                    <label for="captcha-setting-captcha-provider[2]" class="option-hcaptcha-free">hCaptcha</label>
                    <span class="pro-badge captchaProBadge">
                        <a href="google.com">PRO</a>
                    </span>
                </span>
                <span class="captcha-settings-field-radio-wrapper click-to-display-popup">
                    <input 
                    type="radio" 
                    id="captcha-setting-captcha-provider[3]" 
                    name="'.esc_attr( $this->options_name ).'" 
                    value="turnstile"
                    '. ( $this->settings === 'turnstile' ? 'checked="checked"' : '' ) .'>
                    <label for="captcha-setting-captcha-provider[3]" class="option-turnstile-free">Turnstile</label>
                    <span class="pro-badge captchaProBadge">
                        <a href="google.com">PRO</a>
                    </span>
                </span>
                <span class="captcha-settings-field-radio-wrapper">
                    <input 
                    type="radio" 
                    id="captcha-setting-captcha-provider[4]" 
                    name="'.esc_attr( $this->options_name ).'" 
                    value="none"
                    '. ( $this->settings === 'none' ? 'checked="checked"' : '' ) .'>
                    <label for="captcha-setting-captcha-provider[4]" class="option-none-free">None</label>
                </span>
            </span>
        ';
	}

    public function show_captchas_fields() {
        
        echo do_action( 'password_protected_googlerecaptcha' );
        echo '
            <div class="noneTab"></div>
        ';

	}

}

