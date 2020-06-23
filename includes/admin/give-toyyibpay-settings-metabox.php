<?php

class Give_ToyyibPay_Settings_Metabox
{
    private static $instance;

    private function __construct()
    {

    }

    public static function get_instance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Setup hooks.
     */
    public function setup_hooks()
    {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_js'));
            add_filter('give_forms_toyyibpay_metabox_fields', array($this, 'give_toyyibpay_add_settings'));
            add_filter('give_metabox_form_data_settings', array($this, 'add_toyyibpay_setting_tab'), 0, 1);
        }
    }

    public function add_toyyibpay_setting_tab($settings)
    {
        if (give_is_gateway_active('toyyibpay')) {
            $settings['toyyibpay_options'] = apply_filters('give_forms_toyyibpay_options', array(
                'id' => 'toyyibpay_options',
                'title' => __('ToyyibPay', 'give'),
                'icon-html' => '<span class="give-icon give-icon-purse"></span>',
                'fields' => apply_filters('give_forms_toyyibpay_metabox_fields', array()),
            ));
        }

        return $settings;
    }

    public function give_toyyibpay_add_settings($settings)
    {

        // Bailout: Do not show offline gateways setting in to metabox if its disabled globally.
        if (in_array('toyyibpay', (array) give_get_option('gateways'))) {
            return $settings;
        }

        $is_gateway_active = give_is_gateway_active('toyyibpay');

        //this gateway isn't active
        if (!$is_gateway_active) {
            //return settings and bounce
            return $settings;
        }

        //Fields
        $check_settings = array(

            array(
                'name' => __('ToyyibPay', 'give-toyyibpay'),
                'desc' => __('Do you want to customize the donation instructions for this form?', 'give-toyyibpay'),
                'id' => 'toyyibpay_customize_toyyibpay_donations',
                'type' => 'radio_inline',
                'default' => 'global',
                'options' => apply_filters('give_forms_content_options_select', array(
                    'global' => __('Global Option', 'give-toyyibpay'),
                    'enabled' => __('Customize', 'give-toyyibpay'),
                    'disabled' => __('Disable', 'give-toyyibpay'),
                )
                ),
            ),
            array(
                'name' => __('API Secret Key', 'give-toyyibpay'),
                'desc' => __('Enter your API Secret Key, found in your ToyyibPay Account Settings.', 'give-toyyibpay'),
                'id' => 'toyyibpay_api_key',
                'type' => 'text',
                'row_classes' => 'give-toyyibpay-key',
            ),
            array(
                'name' => __('Category Code', 'give-toyyibpay'),
                'desc' => __('Enter your Billing Category Code.', 'give-toyyibpay'),
                'id' => 'toyyibpay_category_code',
                'type' => 'text',
                'row_classes' => 'give-toyyibpay-key',
            ),
            array(
                'name' => __('Bill Name', 'give-toyyibpay'),
                'desc' => __('Enter bill\s name.', 'give-toyyibpay'),
                'id' => 'toyyibpay_name',
                'type' => 'text',
                'row_classes' => 'give-toyyibpay-key',
            ),
            array(
                'name' => __('Bill Description', 'give-toyyibpay'),
                'desc' => __('Enter description to be included in the bill.', 'give-toyyibpay'),
                'id' => 'toyyibpay_description',
                'type' => 'text',
                'row_classes' => 'give-toyyibpay-key',
            ),
            array(
                'name' => __('Payment Channel', 'give-toyyibpay'),
                'desc' => __('Choose payment channel for bill.', 'give-toyyibpay'),
                'id' => 'toyyibpay_payment_channel',
                'type' => 'radio_inline',
				'options' => array(
                    0 => __('FPX only', 'give-toyyibpay'),
                    1 => __('Card only', 'give-toyyibpay'),
					2 => __('Both FPX & Card', 'give-toyyibpay'),
                ),
                'row_classes' => 'give-toyyibpay-key',
            ),
            array(
                'name' => __('Billing Fields', 'give-toyyibpay'),
                'desc' => __('This option will enable the billing details section for ToyyibPay which requires the donor\'s address to complete the donation. These fields are not required by ToyyibPay to process the transaction, but you may have the need to collect the data.', 'give-toyyibpay'),
                'id' => 'toyyibpay_collect_billing',
                'row_classes' => 'give-subfield give-hidden',
                'type' => 'radio_inline',
                'default' => 'disabled',
                'options' => array(
                    'enabled' => __('Enabled', 'give-toyyibpay'),
                    'disabled' => __('Disabled', 'give-toyyibpay'),
                ),
            ),
        );

        return array_merge($settings, $check_settings);
    }

    public function enqueue_js($hook)
    {
        if ('post.php' === $hook || $hook === 'post-new.php') {
            wp_enqueue_script('give_toyyibpay_each_form', GIVE_TOYYIBPAY_PLUGIN_URL . '/includes/js/meta-box.js');
        }
    }

}
Give_ToyyibPay_Settings_Metabox::get_instance()->setup_hooks();
