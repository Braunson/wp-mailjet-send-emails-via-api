<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://geekybeaver.ca
 * @since             1.0.0
 * @package           Geekybeaver_Mailjet_Email_Api
 *
 * @wordpress-plugin
 * Plugin Name:       Mailjet Send Emails via API
 * Plugin URI:        https://geekybeaver.ca
 * Description:       Send WordPress and WooCommerce emails using the Mailjet API. Helpful when custom SMTP is not available on a host.
 * Version:           1.0.0
 * Author:            Braunson Yager
 * Author URI:        https://geekybeaver.ca/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       geekybeaver-mailjet-email-api
 */

if ( ! defined( 'ABSPATH' ) ) die();

require __DIR__ . '/vendor/autoload.php';

function mailjet_validate_options( $input ) {
    $input['mailjet_api_key_field'] = sanitize_text_field( $input['mailjet_api_key_field'] );
    $input['mailjet_private_key_field'] = sanitize_text_field( $input['mailjet_private_key_field'] );
    return $input;
}

function mailjet_api_key_settings_init()
{
    // register a new setting for "mailjet" page
    register_setting( 'mailjet', 'mailjet_options', 'mailjet_validate_options' );

    // register a new section in the "mailjet" page
    add_settings_section(
        'mailjet_api_key_section',
        __( 'MailJet API Key', 'geekybeaver-mailjet-email-api' ),
        'mailjet_section_text',
        'mailjet'
    );

    // register a new field in the "mailjet_api_key_section" section, inside the "mailjet" page
    add_settings_field(
        'mailjet_api_key_field',
        __( 'API Key', 'geekybeaver-mailjet-email-api' ),
        'mailjet_api_key_field_cb',
        'mailjet',
        'mailjet_api_key_section'
    );

    // register a new section in the "mailjet" page
    add_settings_section(
        'mailjet_private_key_section',
        __( 'MailJet Private Key', 'geekybeaver-mailjet-email-api' ),
        'mailjet_private_section_text',
        'mailjet'
    );

    // register a new field in the "mailjet_private_key_section" section, inside the "mailjet" page
    add_settings_field(
        'mailjet_private_key_field',
        __( 'Private Key', 'geekybeaver-mailjet-email-api' ),
        'mailjet_private_key_field_cb',
        'mailjet',
        'mailjet_private_key_section'
    );
}
add_action('admin_init', 'mailjet_api_key_settings_init');

function mailjet_section_text($args)
{
?>
    <p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'Enter your MailJet API Key.', 'geekybeaver-mailjet-email-api' ); ?></p>
<?php
}

function mailjet_private_section_text($args)
{
?>
    <p id="<?php echo esc_attr($args['id']); ?>"><?php esc_html_e('Enter your MailJet Private Key.', 'geekybeaver-mailjet-email-api'); ?></p>
<?php
}

function mailjet_api_key_field_cb($args)
{
    // get the value of the setting we've registered with register_setting()
    $options = get_option( 'mailjet_options' );
?>
    <input type="text" id="mailjet_api_key_field" name="mailjet_options[mailjet_api_key_field]" value="<?php echo esc_attr($options['mailjet_api_key_field']); ?>"/>
<?php
}

function mailjet_private_key_field_cb($args)
{
    // get the value of the setting we've registered with register_setting()
    $options = get_option( 'mailjet_options' );
?>
    <input type="text" id="mailjet_private_key_field" name="mailjet_options[mailjet_private_key_field]" value="<?php echo esc_attr($options['mailjet_private_key_field']); ?>"/>
<?php
}


function mailjet_options_page()
{
    // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
    add_menu_page(
        __( 'MailJet Options', 'geekybeaver-mailjet-email-api' ),
        __( 'MailJet Options', 'geekybeaver-mailjet-email-api' ),
        'manage_options',
        'mailjet',
        'mailjet_options_page_html',
        'dashicons-admin-generic'
    );
}
add_action('admin_menu', 'mailjet_options_page');

function mailjet_options_page_html()
{
    if (! current_user_can('manage_options')) return;

    if (isset($_GET['settings-updated'])) {
        add_settings_error('mailjet_messages', 'mailjet_message', __( 'Settings Saved', 'geekybeaver-mailjet-email-api' ), 'updated');
    }

    settings_errors('mailjet_messages');
    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            // output security fields for the registered setting "mailjet"
            settings_fields('mailjet');

            // output setting sections and their fields
            do_settings_sections('mailjet');

            // output save settings button
            submit_button(__('Save Settings', 'geekybeaver-mailjet-email-api'));
            ?>
        </form>
    </div>
    <?php
}


add_filter( 'wp_mail', 'override_wp_mail' );
function override_wp_mail($args)
{
    $options = get_option( 'mailjet_options' );
    $apiKey = isset($options['mailjet_api_key_field']) ? $options['mailjet_api_key_field'] : null;
    $privateKey = isset($options['mailjet_private_key_field']) ? $options['mailjet_private_key_field'] : null;

    if (is_null($apiKey) || is_null($privateKey)) {
        return "Error sending mail: Mailjet API/private key not set";
    }

    $mj = new \Mailjet\Client($apiKey, $privateKey,true,['version' => 'v3.1']);

    $em = getFromNameEmailFromHeaders($args['headers']);
    $fromName = $em['from_name'];
    $fromEmail = $em['from_email'];

    $body = [
        'Messages' => [
            [
                'From' => [
                    'Email' => $fromEmail,
                    'Name' => $fromName,
                ],
                'To' => [
                    [
                        'Email' => $args['to'],
                        'Name' => "Passenger"
                    ]
                ],
                'Subject' => $args['subject'],
                'TextPart' => strip_tags($args['message']),
                'HTMLPart' => $args['message'],
            ]
        ]
    ];

    $response = $mj->post(\MailJet\Resources::$Email, ['body' => $body]);

    // Read the response
    if (! $response->success()) {
        $error = $response->getReasonPhrase();
        return "Error sending mail: $error";
    }

    return true; // indicate that the mail has been sent successfully
}

function getFromNameEmailFromHeaders($headers)
{
    $from_name = '';
    $from_email = '';

    // Get the headers in an array
    if ( !is_array( $headers ) ) {
        $tempheaders = explode( "\n", str_replace( "\r\n", "\n", $headers ) );
    } else {
        $tempheaders = $headers;
    }

    // Parse the 'From' Header
    foreach ( (array) $tempheaders as $header ) {
        if ( strpos($header, 'From:') === 0 ) {
            if ( preg_match('/From: (.*) <(.*)>/i', $header, $matches) ) {
                $from_name = $matches[1];
                $from_email = $matches[2];
            } else if ( preg_match('/From: (.*)/i', $header, $matches) ) {
                $from_email = $matches[1];
                $from_name = '';
            }
        }
    }

    return [$from_name,$from_email];
}