<?php

class ToyyibPayGiveAPI
{
    private $connect;

    public function __construct($connect)
    {
        $this->connect = $connect;
    }

    public function setConnect($connect)
    {
        $this->connect = $connect;
    }

    /**
     * This method is to change the URL to staging if failed to authenticate with production.
     * It will recall the method that has executed previously to perform it in staging.
     */
    private function detectMode($method_name, $response, $parameter = '', $optional = '', $extra = '')
    {
        if ($response[0] === 401 && $this->connect->detect_mode) {
            $this->connect->detect_mode = false;
            $this->connect->setStaging(true);
            if (!empty($extra)) {
                return $this->{$method_name}($parameter, $optional, $extra);
            } elseif (!empty($optional)) {
                return $this->{$method_name}($parameter, $optional);
            } elseif (!empty($parameter)) {
                return $this->{$method_name}($parameter);
            } else {
                return $this->{$method_name}();
            }
        }
        return false;
    }

    public function createBill($parameter, $optional = array(), $sendCopy = '')
    {
        /* Email or Mobile must be set */
        if (empty($parameter['billEmail']) && empty($parameter['billPhone'])) {
            throw new \Exception("Email or Mobile must be set!");
        }

        /* Validate Mobile Number first */
        if (!empty($parameter['billPhone'])) {
            /* Strip all unwanted character */
            $parameter['billPhone'] = preg_replace('/[^0-9]/', '', $parameter['billPhone']);

            /* Add '6' if applicable */
            $parameter['billPhone'] = $parameter['billPhone'][0] === '0' ? '6' . $parameter['billPhone'] : $parameter['billPhone'];

            /* If the number doesn't have valid formatting, reject it */
            /* The ONLY valid format '<1 Number>' + <10 Numbers> or '<1 Number>' + <11 Numbers> */
            /* Example: '60141234567' or '601412345678' */
            if (!preg_match('/^[0-9]{11,12}$/', $parameter['billPhone'], $m)) {
                $parameter['billPhone'] = '';
            }
        }

        /* Create Bills */
        $bill = $this->connect->createBill($parameter, $optional);
        if ($bill[0] === 200) {
            return $bill;
        }

        /* Determine if the API Key is belong to Staging */
        if ($detect_mode = $this->detectMode(__FUNCTION__, $bill, $parameter, $optional, $sendCopy)) {
            return $detect_mode;
        }

        /* Create Bills */
        return $this->connect->createBill($parameter, $optional);
    }

    public function toArray($json)
    {
        return $this->connect->toArray($json);
    }
}
