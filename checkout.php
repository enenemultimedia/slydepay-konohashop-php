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
    
    
    
    $productIds = $_POST["orderItems"];
    
    
    doWork($productIds);
   
    
    // global variables can be passed to the method or passed as parameter. It's a matter of school of thoughts. 
    //I will be using any when it feels natural
    function doWork(array $orderedProductIdList){
        global $db, $slydepayIntegrator;
        $arrayOfOrderItems = array();

        
        foreach ($orderedProductIdList as $productId) {
            $result = $db->getProductById($productId);
            $item = $result[0];
            $arrayOfOrderItems[] = $slydepayIntegrator->buildOrderItem($item["product_id"], $item["name"], $item["price"], 1, $item["price"]);
        }
        
        processSlydepayOrder($orderedProductIdList, $arrayOfOrderItems);
    }
    
    
    function processSlydepayOrder(array $productIds, array $arrayOfOrderItems){
      
        $orderId = GUID();
        global $settings, $slydepayIntegrator, $db;
        
        $grandSubTotal = grandSubTotalCalculator($arrayOfOrderItems);
        $flatShippingCost = $settings["shippingcost"];
        $tax = $settings["taxes"];
        $taxAmount = $grandSubTotal* $tax/100;
        $total = $grandSubTotal+$taxAmount+$flatShippingCost;
        
        //TODO: check for better way to parse the payment token
        $token = $slydepayIntegrator->ProcessPaymentOrder($orderId, $grandSubTotal, $flatShippingCost, $taxAmount, $total, "konohashop items", "", $arrayOfOrderItems);
        echo $token->ProcessPaymentOrderResult;
        $checkpart = explode(" ",$token->ProcessPaymentOrderResult);
        if(sizeof($checkpart) == 1){
           $db->createOrder($orderId, $token->ProcessPaymentOrderResult, $productIds);
           $redirectUrl = $settings["api.slydepay.redirecturl"].$token->ProcessPaymentOrderResult;
           echo $redirectUrl;
           header("Location: $redirectUrl");
        } else {
            echo "payment not successful";
        }
        
        
        
    }
    
    
    function grandSubTotalCalculator(array $arrayOfOrderItems){
        $subTotal =0;
        foreach ($arrayOfOrderItems as $item) {
            $subTotal += $item->SubTotal;
        }
        
        return $subTotal;
    }
    
    
    function GUID(){
        
        if (function_exists('com_create_guid') === true) {
                return trim(com_create_guid(), '{}');
        }
        return sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }

        
?>