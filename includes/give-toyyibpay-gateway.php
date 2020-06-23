<?php

if (!defined('ABSPATH')) {
    exit;
}

class Give_ToyyibPay_Gateway
{
    private static $instance;

    const QUERY_VAR = 'toyyibpay_givewp_return';
    const LISTENER_PASSPHRASE = 'toyyibpay_givewp_listener_passphrase';

    private function __construct()
    {
        add_action('init', array($this, 'return_listener'));
        add_action('give_gateway_toyyibpay', array($this, 'process_payment'));
        add_action('give_toyyibpay_cc_form', array($this, 'give_toyyibpay_cc_form'));
        add_filter('give_enabled_payment_gateways', array($this, 'give_filter_toyyibpay_gateway'), 10, 2);
        add_filter('give_payment_confirm_toyyibpay', array($this, 'give_toyyibpay_success_page_content'));
    }

    public static function get_instance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function give_filter_toyyibpay_gateway($gateway_list, $form_id)
    {
        if ((false === strpos($_SERVER['REQUEST_URI'], '/wp-admin/post-new.php?post_type=give_forms'))
            && $form_id
            && !give_is_setting_enabled(give_get_meta($form_id, 'toyyibpay_customize_toyyibpay_donations', true, 'global'), array('enabled', 'global'))
        ) {
            unset($gateway_list['toyyibpay']);
        }
        return $gateway_list;
    }

    private function create_payment($purchase_data)
    {

        $form_id = intval($purchase_data['post_data']['give-form-id']);
        $price_id = isset($purchase_data['post_data']['give-price-id']) ? $purchase_data['post_data']['give-price-id'] : '';

        // Collect payment data.
        $insert_payment_data = array(
            'price' => $purchase_data['price'],
            'give_form_title' => $purchase_data['post_data']['give-form-title'],
            'give_form_id' => $form_id,
            'give_price_id' => $price_id,
            'date' => $purchase_data['date'],
            'user_email' => $purchase_data['user_email'],
			'user_phone' => $purchase_data['post_data']['give_phone'],
            'purchase_key' => $purchase_data['purchase_key'],
            'currency' => give_get_currency($form_id, $purchase_data),
            'user_info' => $purchase_data['user_info'],
            'status' => 'pending',
            'gateway' => 'toyyibpay',
        );

        /**
         * Filter the payment params.
         *
         * @since 3.0.2
         *
         * @param array $insert_payment_data
         */
        $insert_payment_data = apply_filters('give_create_payment', $insert_payment_data);

        // Record the pending payment.
        return give_insert_payment($insert_payment_data);
    }

    private function get_toyyibpay($purchase_data)
    {

        $form_id = intval($purchase_data['post_data']['give-form-id']);

        $custom_donation = give_get_meta($form_id, 'toyyibpay_customize_toyyibpay_donations', true, 'global');
        $status = give_is_setting_enabled($custom_donation, 'enabled');

        if ($status) {
            return array(
                'api_key' => give_get_meta($form_id, 'toyyibpay_api_key', true),
                'categoryCode' => give_get_meta($form_id, 'toyyibpay_category_code', true),
                'name' => give_get_meta($form_id, 'toyyibpay_name', true, "Bill Name"),
                'description' => give_get_meta($form_id, 'toyyibpay_description', true, "Bill Desc"),
                'payment_channel' => give_get_meta($form_id, 'toyyibpay_payment_channel', true)
            );
        }
        return array(
            'api_key' => give_get_option('toyyibpay_api_key'),
            'categoryCode' => give_get_option('toyyibpay_category_code'),
            'name' => give_get_option('toyyibpay_name', "Bill Name"),
            'description' => give_get_option('toyyibpay_description', "Bill Desc"),
            'payment_channel' => give_get_option('toyyibpay_payment_channel')
        );
    }

    public static function get_listener_url($payment_id)
    {
        $passphrase = get_option(self::LISTENER_PASSPHRASE, false);
        if (!$passphrase) {
            $passphrase = md5(site_url() . time());
            update_option(self::LISTENER_PASSPHRASE, $passphrase);
        }

        $arg = array(
            self::QUERY_VAR => $passphrase,
            'payment_id' => $payment_id,
        );
        return add_query_arg($arg, site_url('/'));
    }

    public function process_payment($purchase_data)
    {
        // Validate nonce.
        give_validate_nonce($purchase_data['gateway_nonce'], 'give-gateway');

        $payment_id = $this->create_payment($purchase_data);

        // Check payment.
        if (empty($payment_id)) {
            // Record the error.
            give_record_gateway_error(__('Payment Error', 'give-toyyibpay'), sprintf( /* translators: %s: payment data */
                __('Payment creation failed before sending donor to ToyyibPay. Payment data: %s', 'give-toyyibpay'), json_encode($purchase_data)), $payment_id);
            // Problems? Send back.
            give_send_back_to_checkout();
        }

        $toyyibpay_key = $this->get_toyyibpay($purchase_data);

        $name = $purchase_data['user_info']['first_name'] . ' ' . $purchase_data['user_info']['last_name'];

        $parameter = array(
            'userSecretKey' => trim($toyyibpay_key['api_key']),
            'categoryCode' => trim($toyyibpay_key['categoryCode']),
            'billEmail' => $purchase_data['user_email'],
			'billPhone' => $purchase_data['post_data']['give_phone'],
            'billTo' => empty($name) ? $purchase_data['user_email'] : trim($name),
            'billAmount' => strval($purchase_data['price'] * 100),
            'billCallbackUrl' => self::get_listener_url($payment_id),
			'billName' => substr(trim($toyyibpay_key['name']), 0, 120),
            'billDescription' => substr(trim($toyyibpay_key['description']), 0, 120),
            'billPaymentChannel' => (int)$toyyibpay_key['payment_channel'],
			'billPriceSetting' => 1,
			'billPayorInfo' => 0,
            'billMultiPayment' => 0,
        );

        $optional = array(
            'billExternalReferenceNo' => $payment_id,
            'billReturnUrl' => $parameter['billCallbackUrl'],
        );

        $optional = apply_filters('give_toyyibpay_bill_optional_param', $purchase_data['post_data'], $optional);

        $connect = new ToyyibPayGiveWPConnect($toyyibpay_key['api_key']);
        $connect->setStaging(give_is_test_mode());
        $toyyibpay = new ToyyibPayGiveAPI($connect);

        list($rheader, $rbody) = $toyyibpay->toArray($toyyibpay->createBill($parameter, $optional));

        if ($rheader !== 200) {
            // Record the error.
            give_record_gateway_error(__('Payment Error', 'give-toyyibpay'), sprintf( /* translators: %s: payment data */
                __('Bill creation failed. Error message: %s', 'give-toyyibpay'), json_encode($rbody)), $payment_id);
            // Problems? Send back.
            give_send_back_to_checkout('?payment-mode=' . $purchase_data['post_data']['give-gateway']);
        }

        give_update_meta($payment_id, 'toyyibpay_id', $rbody[0]['BillCode']);

        wp_redirect($connect->url. $rbody[0]['BillCode']);
        exit;
    }

    public function give_toyyibpay_cc_form($form_id)
    {
        $post_toyyibpay_customize_option = give_get_meta($form_id, 'toyyibpay_customize_toyyibpay_donations', true, 'global');

        // Enable Default fields (billing info)
        $post_toyyibpay_cc_fields = give_get_meta($form_id, 'toyyibpay_collect_billing', true);
        $global_toyyibpay_cc_fields = give_get_option('toyyibpay_collect_billing');

        // Output Address fields if global option is on and user hasn't elected to customize this form's offline donation options
        if (
            (give_is_setting_enabled($post_toyyibpay_customize_option, 'global') && give_is_setting_enabled($global_toyyibpay_cc_fields))
            || (give_is_setting_enabled($post_toyyibpay_customize_option, 'enabled') && give_is_setting_enabled($post_toyyibpay_cc_fields))
        ) {
            give_default_cc_address_fields($form_id);
            return true;
        }

        return false;
    }

    private function publish_payment($payment_id, $data)
    {
        if ('publish' !== get_post_status($payment_id)) {
            give_update_payment_status($payment_id, 'publish');
            if ($data['type'] === 'redirect') {
                give_insert_payment_note($payment_id, "Payment ID: {$payment_id}. Bill Code: {$data['billcode']}.");
            } else {
                give_insert_payment_note($payment_id, "Payment ID: {$payment_id}. Bill Code: {$data['billcode']}. Reference No.: {$data['refno']}. Status.: {$data['status']}. Reason.: {$data['reason']}");
            }
        }
    }

    public function return_listener()
    {
        if (!isset($_GET[self::QUERY_VAR])) {
            return;
        }

        $passphrase = get_option(self::LISTENER_PASSPHRASE, false);
        if (!$passphrase) {
            return;
        }

        if ($_GET[self::QUERY_VAR] != $passphrase) {
            return;
        }

        if (!isset($_GET['payment_id'])) {
            status_header(403);
            exit;
        }

        $payment_id = preg_replace('/\D/', '', $_GET['payment_id']);
        $form_id = give_get_payment_form_id($payment_id);

        $custom_donation = give_get_meta($form_id, 'toyyibpay_customize_toyyibpay_donations', true, 'global');
        $status = give_is_setting_enabled($custom_donation, 'enabled');

        $data = ToyyibPayGiveWPConnect::paymentCallback();
		
        if ($data['billcode'] !== give_get_meta($payment_id, 'toyyibpay_id', true)) {
            status_header(404);
            exit('No ToyyibPay Bill Code found');
        }

        if ($data['paid'] && give_get_payment_status($payment_id)) {
            $this->publish_payment($payment_id, $data);
        }

        if ($data['type'] === 'redirect') {
            if ($data['paid']) {
                //give_send_to_success_page();
                $return = add_query_arg(array(
                    'payment-confirmation' => 'toyyibpay',
                    'payment-id' => $payment_id,
                ), get_permalink(give_get_option('success_page')));
            } else {
                $return = give_get_failed_transaction_uri('?payment-id=' . $payment_id);
            }

            wp_redirect($return);
        }
        exit;
    }

    public function give_toyyibpay_success_page_content($content)
    {
        if ( ! isset( $_GET['payment-id'] ) && ! give_get_purchase_session() ) {
          return $content;
        }

        $payment_id = isset( $_GET['payment-id'] ) ? absint( $_GET['payment-id'] ) : false;

        if ( ! $payment_id ) {
            $session    = give_get_purchase_session();
            $payment_id = give_get_donation_id_by_key( $session['purchase_key'] );
        }

        $payment = get_post( $payment_id );
        if ( $payment && 'pending' === $payment->post_status ) {

            // Payment is still pending so show processing indicator to fix the race condition.
            ob_start();

            give_get_template_part( 'payment', 'processing' );

            $content = ob_get_clean();

        }

        return $content;
    }
}
Give_ToyyibPay_Gateway::get_instance();
