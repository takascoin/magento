<?php


class Takascoin_Takascoin_Helper_Data extends Mage_Payment_Helper_Data
{
    const APIKEY  = 'payment/Takascoin/email';
    const SECRET  = 'payment/Takascoin/secret';

    function createInvoice($amount, $options = array()) {

        try{
            require_once(Mage::getBaseDir('lib') . '/Takascoin/Takascoin.php');
            $cv = new Takascoin();

            $apiKey = Mage::getStoreConfig(self::APIKEY);
            $secret  = Mage::getStoreConfig(self::SECRET);
            //$currency_code = Mage::app()->getStore()->getCurrentCurrencyCode();
            $currency_code = "TL";

            $response = $cv->payment($amount,$apiKey,$options);
        }catch(Exception $e) {
            $response = new stdClass();
            $response->error = $e->getMessage();
        }

        return $response;
    }

    function validateIPN($invoiceID, $hash, $orderID, $secret) {
        try {
            require_once(Mage::getBaseDir('lib') . '/Takascoin/Takascoin.php');
            $cv = new Takascoin();
            $secret  = Mage::getStoreConfig(self::SECRET);

            return $cv->validateNotification($hash, $orderID, $invoiceID, $secret);
        } catch(Exception $e) {
            return false;
        }
    }
}
