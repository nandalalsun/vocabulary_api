<?php
    print("Here it comes");
    require_once("../Model/response.php");
    try{
    $response = new Response();
    $response->setHttpStatusCode(400);
    $response->setSuccess(false);
    $response->addMessage("Ok");
    $response->send();
    exit;
}
catch (Exception $ex){
    print($ex->getMessage());
}

?>