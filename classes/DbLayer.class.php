<?php


/**
 * Description of dblager
 *
 * @author Joseph Kodjo-Kuma Djomeda
 */

date_default_timezone_set('Africa/Accra');
class DbLayer {
    //put your code here
    
    private $db;
    private $username;
    private $password;
    private $host;
    private $database;
    
    function __construct($username, $password, $host, $database) {
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;
        $this->database = $database;
        
        $this->db = $this->getConnection($username, $password, $host, $database);
    }
    
    public function getConnection($username, $password, $host, $database){
        $dsn = "mysql:host=$host;dbname=$database";
        
        $db = new PDO($dsn, $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_BOTH);
        
        return $db;
        
    }
    
    
    public function setFixtures(){
        if($this->db ==null){
            $this->db = $this->getConnection($this->username, $this->password, $this->host, $this->database);
        }
        
        $this->db->exec("insert into category(id,name,description) values(1,'food','all sort of comestible item'),(2,'ninja tools','any sort of tool according to konoha classifications')");
        $this->db->exec("insert into product(id,category_id,product_id,name,price,in_stock,comment) values(1,1,'ra_0001','ramen',30,20,''),(2,2,'we_0001','shuriken',120,100,''),(3,2,'we_0002','kunai',62,95,'')");
    }
    
    
    public function tearDown(){
        
         if($this->db ==null){
            $this->db = $this->getConnection($this->username, $this->password, $this->host, $this->database);
        } 
        
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 0");
        $this->db->exec("truncate table product");
        $this->db->exec("truncate table category");
        $this->db->exec("truncate table order_product_map");
        $this->db->exec("truncate table `order`");
        $this->db->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    
    
    public function getAllItems(){
        
       if($this->db ==null){
            $this->db = $this->getConnection($this->username, $this->password, $this->host, $this->database);
       } 
        
       $stmt = $this->db->prepare("select c.name as category_name, p.name ,p.id, p.price,p.product_id ,p.comment from product p inner join category c on c.id = p.category_id where in_stock <> 0"); 
       $stmt->execute();
       $result = $stmt->fetchAll();
       return $result;
    }
    
    
    public function createOrder($orderId, $paymentToken, array $productIdList){
        
        if($this->db ==null){
            $this->db = $this->getConnection($this->username, $this->password, $this->host, $this->database);
        } 
        
        $this->db->beginTransaction();
        $stmt = $this->db->prepare("insert into `order`(order_id,payment_token) values (:id, :token)");
        $stmt->bindParam(":id", $orderId);
        $stmt->bindParam(":token", $paymentToken);
        $stmt->execute();
        
        $order_product_map_query = $this->orderProductMapQueryBuilder($orderId, $productIdList);
        
        $stmt2 = $this->db->prepare("insert into order_product_map values $order_product_map_query");
        $stmt2->execute();
        $this->db->commit();
        
    }
    
    
    
    public function getProductById($productId){
        
        if($this->db ==null){
            $this->db = $this->getConnection($this->username, $this->password, $this->host, $this->database);
       } 
       
       $stmt = $this->db->prepare("select * from product where id=:id");
       $stmt->bindParam(":id", $productId);
       $stmt->execute();
       $results = $stmt->fetchAll();
       return $results;
        
    }
    
    public function updateOrder($orderId, $paymentTransactionId, $paymentStatus){
        if($this->db ==null){
            $this->db = $this->getConnection($this->username, $this->password, $this->host, $this->database);
       }
       
       $stmt = $this->db->prepare("update `order` set payment_common_id=:transacId, order_status=:status, date_modified=:date where order_id=:orderId");
       $stmt->bindParam(":transacId", $paymentTransactionId);
       $stmt->bindParam(":status", $paymentStatus);
       $stmt->bindParam(":orderId", $orderId);
       $stmt->bindParam(":date", date('Y-m-d H:i:s'));
       $stmt->execute();
       
    }

    
    private function orderProductMapQueryBuilder($orderId, $productIdList){
        $queryPartString = "";
        foreach ($productIdList as $productId) {
            $queryPartString .= "('$orderId','$productId'),"; 
        }
        
        if(!empty($queryPartString)){
            $queryPartString = rtrim($queryPartString,",");
        }
        
        return $queryPartString;
    }
    
    
    public function countValidTransaction($paymentToken){
        
       if($this->db ==null){
            $this->db = $this->getConnection($this->username, $this->password, $this->host, $this->database);
       } 
       
       $stmt = $this->db->prepare("select order_id from `order` where payment_token= :token and order_status='PENDING'");
       $stmt->bindParam(":token", $paymentToken);
       $stmt->execute();
       $result = $stmt->fetch();
       return $result;
       
       
    }
    
}