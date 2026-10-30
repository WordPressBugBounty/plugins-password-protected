<?php
/**
 * Email Template for Activity report
 *
 * @package Password Protected
 */

defined( 'ABSPATH' ) || exit;

$password_protected_image_url = PASSWORD_PROTECTED_URL . 'assets/images/';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php bloginfo( 'name' ) ?></title>
    <style id="admin-email-notification--text-css" type="text/css">
        * {
            padding: 0;
            margin: 0;
        }
    </style>
</head>
<body>
<table style="width: 100%;height: 100vh;background: #000;">
    <tr>
        <td style="">
            <table style="width: 471px;height: 557px;margin: 0 auto;padding: 20px;background: #fff;">
                <tr>
                    <td style="vertical-align: top;width: 100%;">
                        <div style="text-align: center;">
                            <img src="<?php echo esc_url( PASSWORD_PROTECTED_URL ) ?>assets/images/cropped-logo.png" alt="">
                        </div>
                        <div style="width: 424px;height: 60px;line-height: 15px;">
                            <p style="font-size: 12px;font-weight: 400;font-family: Inter,serif;line-height: 25px">
								<?php
								echo wp_kses_post(
									sprintf(
										/* translators: %s: admin email address. */
										__( 'Hi %1$s,', 'password-protected' ),
										'<b>' . esc_html( get_option( 'admin_email' ) ) . '</b>'
									)
								);
								?>
                            </p>
                            <p style="font-size: 12px;font-weight: 400;font-family: Inter,serif;line-height: 25px">
								<?php
								echo wp_kses_post(
									sprintf(
										/* translators: %1$s: report start date, %2$s: report end date. */
										__( 'Here is a quick overview of who is accessing your site %1$s to %2$s', 'password-protected' ),
										'<b>' . esc_attr( gmdate( 'd-M', $time[0] ) ). '</b>',
										'<b>' . esc_attr( gmdate( 'd-M', $time[1] ) ) . '</b>',
									)
								);
								?>
                            </p>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: middle;width: 100%;">
                        <div style="width: 336px;margin: 0 auto;">

                            <div style="padding: 5px 0;margin:0 5px;width: 100px;height: 120px;float: left;border: 1px solid #BEB8E4;background: #ECE8F9;border-radius: 5px;">
                                <table style="width: 100%;height: 100%;">
                                    <tr>
                                        <td style="vertical-align: top">
                                            <div style="width: 38px;height: 38px;border-radius: 50px;background: #BEB8E4; margin: auto">
                                                <div style="line-height: 38px;text-align:center;">
                                                    <img src="<?php echo esc_url( $password_protected_image_url ); ?>total-attempts.png" />
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter,serif;font-weight: 400;font-size: 12px;line-height: 14px;text-align:center;color: #151D48; vertical-align: middle;">
                                            <p><?php esc_html_e( 'Total', 'password-protected' ); ?></p>
                                            <p><?php esc_html_e( 'Attempts', 'password-protected' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td >
                                            <h3 style="font-family: Inter,serif;font-weight: 700;font-size: 24px;line-height: 29px;color: #151D48;vertical-align: bottom;text-align:center;"><?php echo esc_html( $total ); ?></h3>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div style="padding: 5px 0;margin:0 5px;width: 100px;height: 120px;float: left;border: 1px solid #26E2AA;background: #EAFFF6;border-radius: 5px;">
                                <table style="width: 100%;height: 100%;">
                                    <tr>
                                        <td style="vertical-align: top;">
                                            <div style="width: 38px;height: 38px;border-radius: 50px;background: #26e2c0; margin: auto">
                                                <div style="line-height: 38px;text-align:center;">
                                                    <img src="<?php echo esc_url( $password_protected_image_url ) ?>success.png" alt="">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter,serif;font-weight: 400;font-size: 12px;line-height: 14px;text-align:center;color: #151D48; vertical-align: middle;">
                                            <p><?php esc_html_e( 'Successful', 'password-protected' ); ?></p>
                                            <p><?php esc_html_e( 'Attempts', 'password-protected' ); ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td >
                                            <h3 style="font-family: Inter,serif;font-weight: 700;font-size: 24px;line-height: 29px;color: #151D48;vertical-align: bottom;text-align:center;"><?php echo esc_html( $success ); ?></h3>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div style="padding: 5px 0;margin:0 5px;width: 100px;height: 120px;float: left;border: 1px solid #E4B8D2;background: #F9E8F0;border-radius: 5px;">
                                <table style="width: 100%;height: 100%;">
                                    <tr>
                                        <td style="vertical-align: top">
                                            <div style="width: 38px;height: 38px;border-radius: 50px;background: #e4b8d0; margin: auto">
                                                <div style="line-height: 38px;text-align:center;">
                                                    <img src="<?php echo esc_url( $password_protected_image_url ); ?>failed.png" alt="">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="font-family: Inter,serif;font-weight: 400;font-size: 12px;line-height: 14px;text-align:center;color: #151D48; vertical-align: middle;">
                                            <p><?php esc_html_e( 'Failed', 'password-protected' ) ?></p>
                                            <p><?php esc_html_e( 'Attempts', 'password-protected' ) ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td >
                                            <h3 style="font-family: Inter,serif;font-weight: 700;font-size: 24px;line-height: 29px;color: #151D48;vertical-align: bottom;text-align:center;"><?php echo esc_html( $failed ); ?></h3>
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <div style="clear:both;display:table;"></div>
                        </div>

						<?php
						do_action(
							'password_protected_activity_notification_content',
							array(
								'total'   => $total,
								'success' => $success,
								'failed'  => $failed,
								'time'    => $time,
							)
						);
						?>
                        <div style="text-align: center;margin-top: 16px;">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=password-protected&tab=logs&sub-tab=activity_logs' ) ); ?>" style="display: inline-block;padding: 8px 16px;background-color:#8076FF;font-weight: 500;font-size: 12px;font-family: Inter,serif;border-radius: 10px;color: #fff;text-decoration: none;">
								<?php esc_html_e( 'View activity logs', 'password-protected' ); ?>
                            </a>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: bottom;width: 100%;">
                        <p style="font-family: Inter,serif;font-size: 12px;line-height: 15px;font-weight: 500;color: #8076FF;text-align:center;">
                            <?php
                                echo wp_kses_post(
                                    sprintf(
                                        /* translators: %s: site name linked to the site URL. */
                                        __( 'This email was autogenerated and sent from %1$s.', 'password-protected' ),
                                        '<a href="' . esc_url( site_url() ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a>'
                                    )
                                );
                            ?>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>