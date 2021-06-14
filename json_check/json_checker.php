<?php
    if ($_SERVER['CONTENT_TYPE'] !== 'application/json') {
        // set up response for unsuccessful request
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->addMessage("Content-Type header not set in JSON");
        $response->setSuccess(false);
        $response->send();
        exit;
    }
    
    // get PATCH request body as the PATCHed data will be JSON format
    $rawPatchData = file_get_contents('php://input'); 
    
    if (!$jsonData = json_decode($rawPatchData)) {
        // set up response for unsuccessful request
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->addMessage("Reuest body is not valid JSON");
        $response->setSuccess(false);
        $response->send();
        exit;
    }
?>