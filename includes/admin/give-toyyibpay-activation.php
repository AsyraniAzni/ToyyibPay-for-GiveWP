<?php

/**
 * Give ToyyibPay Gateway Activation
 *
 * @package     ToyyibPay for GiveWP
 * @copyright   Copyright (c) 2020, ToyyibPay
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0.3
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Plugins row action links
 *
 * @since 1.0.3
 *
 * @param array $actions An array of plugin action links.
 *
 * @return array An array of updated action links.
 */
function give_toyyibpay_plugin_action_links($actions)
{
    $new_actions = array(
        'settings' => sprintf(
            '<a href="%1$s">%2$s</a>', admin_url('edit.php?post_type=give_forms&page=give-settings&tab=gateways&section=toyyibpay'), esc_html__('Settings', 'give-toyyibpay')
        ),
    );
    return array_merge($new_actions, $actions);
}
add_filter('plugin_action_links_' . GIVE_TOYYIBPAY_BASENAME, 'give_toyyibpay_plugin_action_links');
