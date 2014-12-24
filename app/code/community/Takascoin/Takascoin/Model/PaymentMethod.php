<?php

class Takascoin_Takascoin_Model_PaymentMethod extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'Takascoin';

    /**
     * Is this payment method a gateway (online auth/charge) ?
     */
    protected $_isGateway               = true;

    /**
     * Can authorize online?
     */
    protected $_canAuthorize            = true;

    /**
     * Can capture funds online?
     */
    protected $_canCapture              = false;

    /**
     * Can capture partial amounts online?
     */
    protected $_canCapturePartial       = false;

    /**
     * Can refund online?
     */
    protected $_canRefund               = false;

    /**
     * Can void transactions online?
     */
    protected $_canVoid                 = false;

    /**
     * Can use this payment method in administration panel?
     */
    protected $_canUseInternal          = true;

    /**
     * Can show this payment method as an option on checkout payment page?
     */
    protected $_canUseCheckout          = true;

    /**
     * Is this payment method suitable for multi-shipping checkout?
     */
    protected $_canUseForMultishipping  = true;

    /**
     * Can save credit card information for future processing?
     */
    protected $_canSaveCc = false;


    public function authorize(Varien_Object $payment, $amount)
    {

      $tk_helper = Mage::helper('Takascoin');
      $secret    = Mage::getStoreConfig('payment/Takascoin/secret');
      $cvUrl     = Mage::getUrl('takascoin_takascoin');
      $storeName = Mage::app()->getStore()->getName();
      $order     = $payment->getOrder();
      $orderId   = $order->getId();
      $items = $order->getAllVisibleItems();
      $itemNames = "Items: ";
      foreach($items as $i) {
        $itemNames .= $i->getProductId().", ";
      }

      //PROCESS SECRET
      if(!$secret) {
        // generate
        $secret = hash('sha256', $email.mt_rand());
        // save
        Mage::getModel('core/config')->saveConfig('payment/Takascoin/secret', $secret)->cleanCache();
        Mage::app()->getStore()->resetConfig();
      }
      // CHECK BITCOIN ADDRESS
      if(!$address) {
        throw new Exception("Before using the Takascoin plugin, you need to enter an bitcoin address in Magento Admin > Configuration > System > Payment Methods > Takascoin.");
      }

      // //$successUrl = Mage::getStoreConfig('payment/Takascoin/custom_success_url');
      // //$cancelUrl = Mage::getStoreConfig('payment/Takascoin/custom_cancel_url');
      $successUrl = $cvUrl. 'redirect/success/';
      $cancelUrl = $cvUrl. 'redirect/cancel/';

      $params = array(
          'secret'   => $secret,
          'orderID'  => $orderId,
          'item'     => substr($itemNames,0,15),
          'description' => 'Purchase',
          'callback' => $cvUrl. 'callback/notify/',
      );

      // // Generate the code
      try {
        $invoice = $tk_helper->createInvoice($amount, $params);
      } catch (Exception $e) {
        throw new Exception("Could not generate checkout page. Double check your Magento Configuration. Error message: " . $e->getMessage());
      }
      $redirectUrl = 'https://coinvoy.net/paymentPage/'.$invoice['id'].'?redirect='.$successUrl;

      // Step 2: Redirect customer to payment page
      $payment->setIsTransactionPending(true); // Set status to Payment Review while waiting for Takascoin postback
      Mage::getSingleton('customer/session')->setRedirectUrl($redirectUrl);

      return $this;
    }


    public function getOrderPlaceRedirectUrl()
    {
      return Mage::getSingleton('customer/session')->getRedirectUrl();
    }
}
?>
