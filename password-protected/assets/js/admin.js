jQuery( document ).ready(function( $ ) {

    toggle_recaptcha_version( $ );
    set_recaptcha_version_fields( $ );
    password_protected_set_menu_location();


    $( '.click-to-display-popup' ).on( 'click', function( e ) {
        let elementId = '#pro-popup';
        if ( $( elementId ).length ) {
            $( elementId ).remove();
        }

        let popupHtml = `<div id="pro-popup" style="">
            <div class="pp-container" style="">
                <a class="pp-close-button" style="">&times;</a>
                
                <div class="pp-body" style="">
                    <img style="" src="${passwordProtectedAdminObject.imageURL}cropped-popup-logo.png" alt="Password Protected logo" class="pp-pro-popup-logo">

                    <h2 class="pp-pro-popup-heading">
                        ${passwordProtectedAdminObject.heading}
                    </h2>

                    <p class="pp-description" style="">
                        ${passwordProtectedAdminObject.description}
                    </p>
                    <a class="pp-link" style="" href="#">${passwordProtectedAdminObject.buttonText}</a>
                </div>
                
                
            </div>
        </div>`;
        $( 'body' ).append( popupHtml );
    } );

    $( '.click-to-display-purchase-popup' ).on( 'click', function( e ) {
        let elementId = '#pro-popup';
        if ( $( elementId ).length ) {
            $( elementId ).remove();
        }
        let popupHtml = `<div id="pro-popup" style="">
            <div class="pp-container" style="">
                <a class="pp-close-button" style="">&times;</a>
                
                <div class="pp-body" style="">
                    <img style="" src="${passwordProtectedAdminObject.imageURL}cropped-popup-logo.png" alt="Password Protected logo" class="pp-pro-popup-logo">

                    <h2 class="pp-heading__title">
                        ${passwordProtectedAdminObject.heading}
                    </h2>

                    <p class="pp-description" style="">
                        ${passwordProtectedAdminObject.description}
                    </p>
                    <a class="pp-link" style="" href="#">${passwordProtectedAdminObject.buttonText}</a>
                </div>
                
                
            </div>
        </div>`;
        $( 'body' ).append( popupHtml );
    } );

    $( 'body' ).on( 'click', '.pp-close-button', function( e ) {
        $( '#pro-popup, #pro-upgrade-popup' ).remove();
    } );

    $( 'body' ).on( 'click', '.pp-link', function( e ) {
        e.preventDefault();
        window.location.href = $( '.pro-badge a' ).attr( 'href' );
    } );

    $( '.click-to-display-upgrade-popup' ).on( 'click', function( e ) {
        e.preventDefault();

        let element_id = '#pro-upgrade-popup';
        let pricing_url = $( this ).data( 'pricing-url' );

        if ( ! pricing_url ) {
            pricing_url = 'https://passwordprotectedwp.com/pricing/?utm_source=plugin&utm_medium=business_pop_up&utm_campaign=plugin';
        }

        if ( $( element_id ).length ) {
            $( element_id ).remove();
        }

        let popup_html = `<div id="pro-upgrade-popup">
            <div class="pp-container" style="">
                <a class="pp-close-button" style="">&times;</a>
                
                <div class="pp-body" style="">
                    <img style="" src="${passwordProtectedAdminObject.imageURL}cropped-popup-logo.png" alt="Password Protected logo" class="pp-pro-popup-logo">
                
                    <h2 class="pp-heading__title">
                        Unlock unmatched website protection with advanced security features
                    </h2>
                    
                    <div style="margin-bottom: 20px;display: block;padding: 0 30px;" class="pp-description clearfix">
                        <div style="text-align: left; width: 50%;" class="pp-fl-left">
                            <ul>
                                <li style="" class="pp-list-content">
                                    <svg style="margin-bottom: -4px;background: #8086ff;border-radius: 90.909px;display: inline-flex;padding: 2.727px;align-items:flex-start;gap: 9.091px;" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.7005 3.5771L5.48535 11.8229L1.84375 8.18135L2.72763 7.29746L5.42446 9.99429L11.7597 2.75397L12.7005 3.5771Z" fill="white" stroke="white" stroke-width="0.454545" stroke-miterlimit="10" stroke-linecap="square"/>
                                    </svg>
                                    Protect Categories/Taxonomies
                                </li>
                                <li style="" class="pp-list-content">
                                    <svg style="margin-bottom: -4px;background: #8086ff;border-radius: 90.909px;display: inline-flex;padding: 2.727px;align-items:flex-start;gap: 9.091px;" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.7005 3.5771L5.48535 11.8229L1.84375 8.18135L2.72763 7.29746L5.42446 9.99429L11.7597 2.75397L12.7005 3.5771Z" fill="white" stroke="white" stroke-width="0.454545" stroke-miterlimit="10" stroke-linecap="square"/>
                                    </svg>
                                    Manage Unlimited Passwords
                                </li>
                                <li style="" class="pp-list-content">
                                    <svg style="margin-bottom: -4px;background: #8086ff;border-radius: 90.909px;display: inline-flex;padding: 2.727px;align-items:flex-start;gap: 9.091px;" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.7005 3.5771L5.48535 11.8229L1.84375 8.18135L2.72763 7.29746L5.42446 9.99429L11.7597 2.75397L12.7005 3.5771Z" fill="white" stroke="white" stroke-width="0.454545" stroke-miterlimit="10" stroke-linecap="square"/>
                                    </svg>
                                    Password Access Request
                                </li>
                            </ul>
                        </div>
                        <div style="text-align: left; width: 50%;" class="pp-fl-right">
                            <ul>
                                <li style="" class="pp-list-content">
                                    <svg style="margin-bottom: -4px;background: #8086ff;border-radius: 90.909px;display: inline-flex;padding: 2.727px;align-items:flex-start;gap: 9.091px;" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.7005 3.5771L5.48535 11.8229L1.84375 8.18135L2.72763 7.29746L5.42446 9.99429L11.7597 2.75397L12.7005 3.5771Z" fill="white" stroke="white" stroke-width="0.454545" stroke-miterlimit="10" stroke-linecap="square"/>
                                    </svg>
                                    Lock Screen Customization
                                </li>
                                <li style="" class="pp-list-content">
                                    <svg style="margin-bottom: -4px;background: #8086ff;border-radius: 90.909px;display: inline-flex;padding: 2.727px;align-items:flex-start;gap: 9.091px;" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.7005 3.5771L5.48535 11.8229L1.84375 8.18135L2.72763 7.29746L5.42446 9.99429L11.7597 2.75397L12.7005 3.5771Z" fill="white" stroke="white" stroke-width="0.454545" stroke-miterlimit="10" stroke-linecap="square"/>
                                    </svg>
                                    Partial Content Protection
                                </li>
                                <li style="" class="pp-list-content">
                                    <svg style="margin-bottom: -4px;background: #8086ff;border-radius: 90.909px;display: inline-flex;padding: 2.727px;align-items:flex-start;gap: 9.091px;" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 15 15" fill="none">
                                        <path fill-rule="evenodd" clip-rule="evenodd" d="M12.7005 3.5771L5.48535 11.8229L1.84375 8.18135L2.72763 7.29746L5.42446 9.99429L11.7597 2.75397L12.7005 3.5771Z" fill="white" stroke="white" stroke-width="0.454545" stroke-miterlimit="10" stroke-linecap="square"/>
                                    </svg>
                                    Multisite Network Support
                                </li>
                            </ul>
                        </div>
                    </div>

                    <a class="pp-pro-link" style="" href="${pricing_url}">Get Business Plan Now ></a>
                </div>
                
                
            </div>
        </div>`;

        $( 'body' ).append( popup_html );
    } );

    $( '.pp-customizer-mockup.click-to-display-upgrade-popup' ).on( 'keydown', function( e ) {
        if ( e.key === 'Enter' || e.key === ' ' ) {
            e.preventDefault();
            $( this ).trigger( 'click' );
        }
    } );

    $( '.pp-pwd-toggle' ).on( 'click', function() {
        var targetId = $( this ).data( 'target' );
        var input = $( '#' + targetId );
        var icon = $( this ).find( '.dashicons' );

        if ( input.attr( 'type' ) === 'password' ) {
            input.attr( 'type', 'text' );
            icon.removeClass( 'dashicons-visibility' ).addClass( 'dashicons-hidden' );
            $( this ).attr( 'aria-label', 'Hide password' );
        } else {
            input.attr( 'type', 'password' );
            icon.removeClass( 'dashicons-hidden' ).addClass( 'dashicons-visibility' );
            $( this ).attr( 'aria-label', 'Show password' );
        }
    } );

    // captcha tab jquery for hide and show on radio
    $('div.hCaptchaTab').hide();
    $('div.reCaptchaTab').hide();
    $('div.turnStileTab').hide();
    $('div.noneTab').hide();

    function updateCaptchaRequiredFields() {
        var captchaKeyFields = '#pp_google_recaptcha_v2_site_key, #pp_google_recaptcha_v2_secret_key, #pp_google_recaptcha_v3_site_key, #pp_google_recaptcha_v3_secret_key, #pp_hcaptcha_site_key, #pp_hcaptcha_secret_key, #pp_cloudflare_site_key, #pp_cloudflare_secret_key';
        $(captchaKeyFields).prop('required', false);

        var selected = $('span.captcha-setting-field input[type="radio"]:checked').val();

        if (selected === 'recaptcha') {
            var selectedVersion = $("input[name='password_protected_recaptcha[version]']:checked").val();
            if (selectedVersion === 'google_recaptcha_v2') {
                $('#pp_google_recaptcha_v2_site_key, #pp_google_recaptcha_v2_secret_key').prop('required', true);
            } else {
                $('#pp_google_recaptcha_v3_site_key, #pp_google_recaptcha_v3_secret_key').prop('required', true);
            }
        }

        if (selected === 'hcaptcha') {
            $('#pp_hcaptcha_site_key, #pp_hcaptcha_secret_key').prop('required', true);
        }

        if (selected === 'turnstile') {
            $('#pp_cloudflare_site_key, #pp_cloudflare_secret_key').prop('required', true);
        }
    }

    function updateCaptchaTabs() {
    let selected = $('span.captcha-setting-field input[type="radio"]:checked').val();

    if (selected === 'hcaptcha') {
        $('input#pppro_enable_hcaptcha').prop('checked', true);
        $('input#pp_enable_recaptcha, input#pppro_enable_cloudflare').removeAttr('checked');
        $('div.hCaptchaTab').show();
        $('div.reCaptchaTab, div.turnStileTab, div.noneTab').hide();
    }

    if (selected === 'recaptcha') {
        $('input#pp_enable_recaptcha').prop('checked', true);
        $('input#pppro_enable_cloudflare, input#pppro_enable_hcaptcha').removeAttr('checked');
        $('div.reCaptchaTab').show();
        $('div.hCaptchaTab, div.turnStileTab, div.noneTab').hide();
    }

    if (selected === 'turnstile') {
        $('input#pppro_enable_cloudflare').prop('checked', true);
        $('input#pp_enable_recaptcha, input#pppro_enable_hcaptcha').removeAttr('checked');
        $('div.turnStileTab').show();
        $('div.hCaptchaTab, div.reCaptchaTab, div.noneTab').hide();
    }

    if (selected === 'none') {
        $('input#pp_enable_recaptcha, input#pppro_enable_cloudflare, input#pppro_enable_hcaptcha').removeAttr('checked');
        $('div.noneTab').show();
        $('div.hCaptchaTab, div.reCaptchaTab, div.turnStileTab').hide();
    }

    updateCaptchaRequiredFields();
}

updateCaptchaTabs();
$('span.captcha-setting-field input[type="radio"]').on('change', updateCaptchaTabs);
$("input[name='password_protected_recaptcha[version]']").on('change', updateCaptchaRequiredFields);

// Hide enable toggles in captchas, We are doing enable when tab click.
jQuery('.turnStileTab #pppro_enable_cloudflare').closest('tr').hide();
jQuery('.hCaptchaTab #pppro_enable_hcaptcha').closest('tr').hide();
jQuery('.reCaptchaTab #pp_enable_recaptcha').closest('tr').hide();


} );

function set_recaptcha_version_fields( $ ) {
    var selected_version = $("input[name='password_protected_recaptcha[version]']:checked").val();
    if( selected_version == 'google_recaptcha_v2' ) {
        hide_recaptcha_v3_fields( $ );
    } else {
        hide_recaptcha_v2_fields( $ );
    }
}

function toggle_recaptcha_version( $ ) {
    $("input[name='password_protected_recaptcha[version]']").on('change', function() {
        if( $(this).val() === 'google_recaptcha_v2' ) {
            // hide v3 fields
            hide_recaptcha_v3_fields( $ );
            
            // show v2 fields
            $("#pp_google_recaptcha_v2_site_key").parent('div').fadeIn();
            $("#pp_google_recaptcha_v2_secret_key").parent('div').fadeIn();
            $("input[name='password_protected_recaptcha[v2_theme]']").parent('label').parent('td').parent('tr').fadeIn();
            
        } else {
            // show v3 fields
            $("#pp_google_recaptcha_v3_site_key").parent('div').fadeIn();
            $("#pp_google_recaptcha_v3_secret_key").parent('div').fadeIn();
            $("#pp_google_recpatcha_v3_score").parent('td').parent('tr').fadeIn();
            $("#pp_google_recpatcha_v3_badge").parent('td').parent('tr').fadeIn();
            
            // hide v2 fields
            hide_recaptcha_v2_fields( $ );
        }
    });
}

function hide_recaptcha_v2_fields( $ ) {
    $("#pp_google_recaptcha_v2_site_key").parent('div').hide();
    $("#pp_google_recaptcha_v2_secret_key").parent('div').hide();
    $("input[name='password_protected_recaptcha[v2_theme]']").parent('label').parent('td').parent('tr').hide();
}

function hide_recaptcha_v3_fields( $ ) {
    $("#pp_google_recaptcha_v3_site_key").parent('div').hide();
    $("#pp_google_recaptcha_v3_secret_key").parent('div').hide();
    $("#pp_google_recpatcha_v3_score").parent('td').parent('tr').hide();
    $("#pp_google_recpatcha_v3_badge").parent('td').parent('tr').hide();
}

function password_protected_set_menu_location() {
    let current_url = window.location.href;
    let url = new URL( current_url );
    let searchParams = url.searchParams;
    let page, tab;
    page = searchParams.get( 'page' );
    tab = searchParams.get( 'tab' );
    
    if ( 'password-protected' === page ) {
        switch ( tab ) {
            case 'content-protection':
                password_protected_activate_deactivate_menus( 3 )
                break;
            case 'manage_passwords':
                password_protected_activate_deactivate_menus( 4 )
                break;
            case 'logs':
                password_protected_activate_deactivate_menus( 5 )
                break;
            case 'protected-screen':
                password_protected_activate_deactivate_menus( 6 )
                break;
            case 'request-password':
                password_protected_activate_deactivate_menus( 7 )
                break;
            case 'general':
            case null:
                password_protected_activate_deactivate_menus( 2 )
                break;
            default:
                password_protected_activate_deactivate_menus( 0 )
                break;
        }
    }
}

function password_protected_activate_deactivate_menus( $index ) {
    jQuery( '#toplevel_page_password-protected > ul > li' ).removeClass( 'current' );
    jQuery( `#toplevel_page_password-protected > ul > li:nth-child( ${ $index } )` ).addClass( 'current' );
}

jQuery( function( $ ) {
	$( document ).on( 'click', '.pp-activity-log-password-toggle', function( e ) {
		e.preventDefault();

		var $btn = $( this );
		var $wrap = $btn.closest( '.pp-activity-log-password' );
		var $mask = $wrap.find( '.pp-activity-log-password-mask' );
		var $value = $wrap.find( '.pp-activity-log-password-value' );
		var expanded = $btn.attr( 'aria-expanded' ) === 'true';
		var $icon = $btn.find( '.dashicons' );
		var showAria = ( typeof passwordProtectedAdminObject !== 'undefined' && passwordProtectedAdminObject.showPassword ) ? passwordProtectedAdminObject.showPassword : 'Show password';
		var hideAria = ( typeof passwordProtectedAdminObject !== 'undefined' && passwordProtectedAdminObject.hidePassword ) ? passwordProtectedAdminObject.hidePassword : 'Hide password';

		if ( expanded ) {
			$value.prop( 'hidden', true );
			$mask.prop( 'hidden', false );
			$icon.removeClass( 'dashicons-hidden' ).addClass( 'dashicons-visibility' );
			$btn.attr( 'aria-expanded', 'false' ).attr( 'aria-label', showAria );
		} else {
			$value.prop( 'hidden', false );
			$mask.prop( 'hidden', true );
			$icon.removeClass( 'dashicons-visibility' ).addClass( 'dashicons-hidden' );
			$btn.attr( 'aria-expanded', 'true' ).attr( 'aria-label', hideAria );
		}
	} );
} );
