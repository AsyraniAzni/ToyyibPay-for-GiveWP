<?php

/**
 * Class Give_ToyyibPay_Settings
 *
 * @since 1.0.3
 */
class Give_ToyyibPay_Settings
{

    /**
     * @access private
     * @var Give_ToyyibPay_Settings $instance
     */
    private static $instance;

    /**
     * @access private
     * @var string $section_id
     */
    private $section_id;

    /**
     * @access private
     *
     * @var string $section_label
     */
    private $section_label;

    /**
     * Give_ToyyibPay_Settings constructor.
     */
    private function __construct()
    {

    }

    /**
     * get class object.
     *
     * @return Give_ToyyibPay_Settings
     */
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

        $this->section_id = 'toyyibpay';
        $this->section_label = __('ToyyibPay', 'give-toyyibpay');

        if (is_admin()) {
            // Add settings.
            add_filter('give_get_settings_gateways', array($this, 'add_settings'), 99);
            add_filter('give_get_sections_gateways', array($this, 'add_sections'), 99);
        }
    }

    /**
     * Add setting section.
     *
     * @param array $sections Array of section.
     *
     * @return array
     */
    public function add_sections($sections)
    {
        $sections[$this->section_id] = $this->section_label;

        return $sections;
    }

    /**
     * Add plugin settings.
     *
     * @param array $settings Array of setting fields.
     *
     * @return array
     */
    public function add_settings($settings)
    {
        $current_section = give_get_current_setting_section();

        if ($current_section != 'toyyibpay') {
            return $settings;
        }

        $give_toyyibpay_settings = array(
            array(
                'name' => __('ToyyibPay Settings', 'give-toyyibpay'),
                'id' => 'give_title_gateway_toyyibpay',
                'type' => 'title',
            ),
            array(
                'name' => __('API Secret Key', 'give-toyyibpay'),
                'desc' => __('Enter your API Secret Key, found in your ToyyibPay Account Settings.', 'give-toyyibpay'),
                'id' => 'toyyibpay_api_key',
                'type' => 'api_key',
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
                'desc' => __('Enter bill\'s name .', 'give-toyyibpay'),
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
                'type' => 'select',
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
                'type' => 'radio_inline',
                'default' => 'disabled',
                'options' => array(
                    'enabled' => __('Enabled', 'give-toyyibpay'),
                    'disabled' => __('Disabled', 'give-toyyibpay'),
                ),
            ),
            array(
                'type' => 'sectionend',
                'id' => 'give_title_gateway_toyyibpay',
            ),
        );

        return array_merge($settings, $give_toyyibpay_settings);
    }
}

Give_ToyyibPay_Settings::get_instance()->setup_hooks();
