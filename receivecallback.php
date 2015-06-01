<?php 

    include_once './classes/DbLayer.class.php';
    include_once './classes/Integrator.class.php';
        
    $settings= parse_ini_file("config/local.ini");
    $db = new DbLayer($settings["username"], $settings["password"], $settings["host"], $settings["database"]);
    $slydepayIntegrator = new SlydepayConnector($settings["api.slydepay.namespace"],
                                              $settings["api.slydepay.wsdl"], 
                                              $settings["api.slydepay.version"], 
                                              $settings["api.slydepay.merchantEmail"], 
                                              $settings["api.slydepay.merchantKey"], 
                                              $settings["api.slydepay.serviceType"], 
                                              $settings["api.slydepay.integrationmode"]);
    
    
    
    $statusCode = filter_input(INPUT_GET, "status", FILTER_SANITIZE_STRING);
    $transactionId = filter_input(INPUT_GET, "transac_id", FILTER_SANITIZE_STRING);
    $orderId = filter_input(INPUT_GET, "cust_ref", FILTER_SANITIZE_STRING);
    $paymentToken = filter_input(INPUT_GET, "pay_token", FILTER_SANITIZE_STRING);
    
    
    if(null == $statusCode || null == $orderId || null == $paymentToken){
        die("Not good, details are missing or someone is messing with you");
    }
    
    $paymentStatus = parseTransactionStatusCode($statusCode);
    
    if(null == $transactionId || strlen($transactionId) == 0) {
        $db->updateOrder($orderId, "", "FAILED");
        die("Empty or Null Transaction Id");
    }
    
    
    if(!checkValidity($paymentToken, $orderId)){
        die("There is no transaction corresponding to the received payment token. Please contact slydepay support");
        
    }
    
  
    $OrderResult = $slydepayIntegrator->VerifyMobilePayment($orderId);


    
    if($OrderResult->verifyMobilePaymentResult->success){
        
        $db->updateOrder($orderId, $transactionId, $paymentStatus);
        //do another process like initiate shipping and email and sms notification
        $slydepayIntegrator->ConfirmTransaction($paymentToken, $transactionId);
        echo "Yatta!! Your order is on the way";
        
    }else{
        
        echo "Something seems to be wrong with your order, Kindly start afresh";
        $slydepayIntegrator->CancelTransaction($paymentToken, $transactionId);
    }
    
    
    
    function checkValidity($paymentToken, $orderId) {
        global $db;
        $savedOrderIdString = $db->countValidTransaction($paymentToken);
                
		if (null== $savedOrderIdString["order_id"] || strlen($savedOrderIdString["order_id"]) == 0) {
			return false;
		}
		if ($orderId != $savedOrderIdString["order_id"]) {
			return false;
		}
		return true;
    }
    
    
    function parseTransactionStatusCode($statusCode) {
		$status = "";
        switch ($statusCode){
            case "0":
                $status = "success";
                break;
            case "-2":
                $status = "cancelled";
                break;
            case "-1":
                $status = "error";
                break;
            default:
            	$status = "unknown";
           
        }
        return $status;
	}
    
    
    
    
?>
