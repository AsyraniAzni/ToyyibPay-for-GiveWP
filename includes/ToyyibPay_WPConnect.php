<?php

class ToyyibPayGiveWPConnect
{
    private $api_key;
    private $categoryCode;

    private $process; //cURL or GuzzleHttp
    public $is_staging;
    public $detect_mode;
    public $url;
    public $webhook_rank;

    public $header;

    const TIMEOUT = 10; //10 Seconds
    const PRODUCTION_URL = 'https://toyyibpay.com/';
    const STAGING_URL = 'https://dev.toyyibpay.com/';

    public function __construct($api_key)
    {
        $this->api_key = $api_key;

        $this->header = array(
            'Authorization' => 'Basic ' . base64_encode($this->api_key . ':'),
        );
    }

    public function setStaging($is_staging = false)
    {
        $this->is_staging = $is_staging;
        if ($is_staging) {
            $this->url = self::STAGING_URL;
        } else {
            $this->url = self::PRODUCTION_URL;
        }
    }

    public function detectMode()
    {
        $this->url = self::PRODUCTION_URL;
        $this->detect_mode = true;
        return $this;
    }

    public static function paymentCallback()
    {
        $signing = '';

        if (isset($_GET['status_id']) && isset($_GET['billcode']) && isset($_GET['order_id'])) {
            $data = array(
                'status_id' => $_GET['status_id'],
                'billcode' => $_GET['billcode'],
                'order_id' => $_GET['order_id'],
            );
            $data['paid'] = $data['status_id'] == 1 ? true : false;
            $type = 'redirect';
        } elseif (isset($_POST['refno'])) {
            $data = array(
                'refno' => isset($_POST['refno']) ? $_POST['refno'] : '',
                'status' => isset($_POST['status']) ? $_POST['status'] : '',
                'reason' => isset($_POST['reason']) ? $_POST['reason'] : '',
                'billcode' => isset($_POST['billcode']) ? $_POST['billcode'] : '',
                'order_id' => isset($_POST['order_id']) ? $_POST['order_id'] : '',
                'amount' => isset($_POST['amount']) ? $_POST['amount'] : '',
            );
			
			$data['paid'] = $data['status'] == 1 ? true : false;
            $type = 'callback';
        } else {
            return false;
        }
        
        $data['type'] = $type;
        return $data;
    }
    
    public function createBill($parameter, $optional = array())
    {
        $url = $this->url . 'index.php/api/createBill';

        //if (sizeof($parameter) !== sizeof($optional) && !empty($optional)){
        //    throw new \Exception('Optional parameter size is not match with Required parameter');
        //}

        $data = array_merge($parameter, $optional);

        $wp_remote_data['sslverify'] = false;
        $wp_remote_data['headers'] = $this->header;
        $wp_remote_data['body'] = http_build_query($data);
        $wp_remote_data['method'] = 'POST';

        $response = \wp_remote_post($url, $wp_remote_data);
        $header = $response['response']['code'];
        $body = \wp_remote_retrieve_body($response);

        return array($header, $body);
    }

    public function closeConnection()
    {
    }

    public function toArray($json)
    {
        return array($json[0], \json_decode($json[1], true));
    }
}
