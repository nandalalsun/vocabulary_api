<?php
require_once('../Model/response.php');
require_once("db.php");
require_once("../Model/examples_m.php");

if (array_key_exists('id', $_GET)) {
    $exampleId = $_GET['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {

            $query = $readDB->prepare("SELECT * FROM examples where id = :exampleId");
            $query->bindParam(":exampleId", $exampleId, PDO::PARAM_INT);
            $query->execute();

            if ($query->rowCount() === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->addMessage("No Example Found");
                $response->setSuccess(false);
                $response->send();
                exit;
            }
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $examples = new Example(
                    $row['id'],
                    $row['example'],
                    $row["word_id"]
                );
                $returnedExamples = $examples->returnExampleAsArry();
            }
            $returnData = array();
            $returnData["Rows_returned"] = $query->rowCount();
            $returnData["Examples"] = $returnedExamples;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->addMessage("Examples");
            $response->setSuccess(true);
            $response->setData($returnData);
            $response->toCache(true);
            $response->send();
            exit;
        } catch (PDOException $ex) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->addMessage("Data cannot fetch");
            $response->setSuccess(false);
            $response->send();
            exit;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        try {
            $query = $writeDB->prepare('DELETE FROM examples where id = :exampleId');
            $query->bindParam(":exampleId", $exampleId, PDO::PARAM_INT);
            $query->execute();

            if ($query->rowCount() === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->addMessage("Example not found");
                $response->setSuccess(false);
                $response->send();
                exit;
            }

            $response = new Response();
            $response->setHttpStatusCode(204);
            $response->addMessage("Example Deleted");
            $response->setSuccess(true);
            $response->send();
            exit;
            
        } catch (Exception $ex) {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->addMessage($ex->getMessage());
            $response->setSuccess(false);
            $response->send();
            exit;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        
        try {
            require_once('../json_check/json_checker.php');
            if(!isset($jsonData->example)){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                !isset($jsonData->example) ? $response->addMessage("Example is required") : false;                
                $response->send();
                exit;
            }
            
            $jsonExample = $jsonData->example;

            $query = $readDB->prepare('SELECT * FROM examples WHERE id = :example_id');
            $query->bindParam(":example_id", $exampleId, PDO::PARAM_INT);
            $query->execute();

            if($query->rowCount() === 0){
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("No word found");
                $response->send();
                exit;
            }

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $f_wordId = $row['word_id'];
            }

            $example_array = array();

            
            $example = new Example(null, $jsonData->example, $f_wordId);
            
            $ex = $example->getExample();
            $word_id = $example->getWordId();

            $query = $writeDB->prepare('UPDATE examples SET example = :example WHERE id = :example_id');
            $query->bindParam(":example", $ex, PDO::PARAM_STR);
            $query->bindParam(':example_id', $exampleId);
            $query->execute();

            if($query->rowCount() ===0){
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage("Error: Example update failed");
                $response->send();
                exit;
            }

            $query = $readDB->prepare('SELECT * FROM examples WHERE id = :example_id');
            $query->bindParam(':example_id', $exampleId, PDO::PARAM_INT);
            $query->execute();
            
            if($query->rowCount()===0){
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage("Failed to get updated record");
                $response->send();
                exit;
            }

            $exampleArray = array();

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $example = new Example(
                    $row['id'],
                    $row['example'],
                    $row['word_id']
                );
                $exampleArray[] = $example->returnExampleAsArry();
            }
            
            $returnData = array();
            $returnData["rows_returned"] = $query->rowCount();
            $returnData["data"] = $exampleArray;

            $response = new Response();
            $response->setSuccess(true);
            $response->setHttpStatusCode(200);
            $response->addMessage("Updated Successfully");
            $response->setData($returnData);
            $response->send();
            exit;


        } catch (ExampleException $ex) {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->addMessage($ex->getMessage());
            $response->setSuccess(false);
            $response->send();
            exit;
        } catch (PDOException $ex) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->addMessage($ex->getMessage());
            $response->setSuccess(false);
            $response->send();
            exit;
        }
    } else {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->addMessage("Reuest method not allowed");
        $response->setSuccess(false);
        $response->send();
        exit;
    }
} elseif (empty($_GET)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        try{
        require_once("../json_check/json_checker.php");

        $word_id = $jsonData->word_id;
        $example = $jsonData->example;

        if (!isset($jsonData->word_id) || !isset($jsonData->example)) {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            !isset($jsonData->word_id) ? $response->addMessage("word_id is missing") : false;
            !isset($jsonData->example) ? $response->addMessage("example is missing") : false;
            $response->send();
            exit;
        }

        $query = $readDB->prepare('SELECT id from words WHERE id = :wordId');
        $query->bindParam(":wordId", $word_id, PDO::PARAM_INT);
        $query->execute();

        if ($query->rowCount() === 0) {
            $response = new Response();
            $response->setHttpStatusCode(404);
            $response->addMessage("Given wordId is not match for any word");
            $response->setSuccess(false);
            $response->send();
            exit;
        }

        $example = new Example(null, $example, $word_id);

        $ex = $example->getExample();
        $w_id = $example->getWordId();

        $query = $writeDB->prepare('INSERT INTO examples(example, word_id) VALUES(:example, :word_id)');
        $query->bindParam(':example', $ex, PDO::PARAM_STR);
        $query->bindParam(':word_id', $w_id, PDO::PARAM_INT);
        $query->execute();

        if ($query->rowCount() === 0) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Error! Inserting example");
            $response->send();
            exit;
        }
        $lastInsertId = $writeDB->lastInsertId();

        $query = $readDB->prepare('SELECT * FROM examples WHERE id = :lastInsertId');
        $query->bindParam(':lastInsertId', $lastInsertId, PDO::PARAM_INT);
        $query->execute();

        if($query->rowCount() === 0){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to fectch inserted example");
            $response->send();
            exit;
        }

        $newExampleArray = array();
        while($row = $query->fetch(PDO::FETCH_ASSOC)){
            $example = new Example(
                $row['id'],
                $row['example'],
                $row['word_id']
            );
            $newExampleArray[] = $example->returnExampleAsArry();
        }
        $returnedData = array();
        $returnedData['rows_returned'] = $query->rowCount();
        $returnedData['data'] = $newExampleArray;

        $response = new Response();
        $response->setHttpStatusCode(201);
        $response->setSuccess(true);
        $response->addMessage("Example successfully inserted");
        $response->setData($returnedData);
        $response->send();
        exit;
    }
    catch(ExampleException $ex){
        $response = new Response();
        $response->setHttpStatusCode(403);
        $response->addMessage($ex->getMessage());
        $response->setSuccess(false);
        $response->send();
        exit;
    }
    catch(PDOException $ex){
        $err = $ex->errorInfo[1];
        if ($err === 1062) {
            $message = "Duplicate entry";
        } else {
            $message = ($ex->getMessage());
        }
        $response = new Response();
        $response->setHttpStatusCode(500);
        $response->addMessage($message);
        $response->setSuccess(false);
        $response->send();
        exit;
    }

    } else {
        $response = new Response();
        $response->setHttpStatusCode(400);
        $response->addMessage("Request method not allowed");
        $response->setSuccess(false);
        $response->send();
        exit;
    }
} else {
    $response = new Response();
    $response->setHttpStatusCode(400);
    $response->addMessage("Request method not allowed");
    $response->setSuccess(false);
    $response->send();
    exit;
}
