<?php 

require_once('../controller/db.php');
// require_once('../model/response.php');

try{
    $writeDB = DB::connectWriteDB();
    $readDB = DB::connectReadDB();
    echo("Connection Successful");
}
catch(PDOException $ex) {
    $response = new Response();
    $response->setHttpStatusCode(500);
    $response->setSuccess(false);
    $response->addMessage("Database Connection Error");
    $response->send();
    exit;
}