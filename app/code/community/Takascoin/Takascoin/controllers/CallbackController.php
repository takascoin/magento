<?php

class Takascoin_Takascoin_CallbackController extends Mage_Core_Controller_Front_Action
{

    public function notifyAction() {

      $secret    = Mage::getStoreConfig('payment/Takascoin/secret');
      $tk_helper = Mage::helper('Takascoin');

      $params = json_decode(file_get_contents('php://input'), true);

      $hash      = $params['hash'];
      $status    = $params['status'];
      $orderID   = $params['orderID'];
      $invoiceID = $params['invoiceID'];

      if(!$tk_helper->validateIPN($invoiceID, $hash, $orderID, $secret)) {
        Mage::log("Takascoin: incorrect callback with incorrect Takascoin order ID $cbOrderId.");
        header("HTTP/1.1 500 Internal Server Error");
        return;
      }

      $order = Mage::getModel('sales/order')->load($orderID);
      if(!$order) {
        Mage::log("Takascoin: incorrect callback with incorrect order ID $orderId.");
        header("HTTP/1.1 500 Internal Server Error");
        return;
      }

      // The callback is legitimate. Update the order's status in the database.
      $payment = $order->getPayment();
      $payment->setTransactionId($invoiceID)
        ->setPreparedMessage("Bitcoin payment through Takascoin ID $invoiceID.")
        ->setShouldCloseParentTransaction(true)
        ->setIsTransactionClosed(0);

      if("completed" == $orderInfo->status) {
        $payment->registerCaptureNotification($orderInfo->total_native->cents / 100);
      } else {
        $cancelReason = $postBody->cancellation_reason;
        $order->registerCancellation("Takascoin order $cbOrderId cancelled: $cancelReason");
      }

      Mage::dispatchEvent('Takascoin_callback_received', array('status' => $orderInfo->status, 'order_id' => $orderId));
      $order->save();
    }

}
