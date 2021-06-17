<?php
require_once('../Model/response.php');
require_once("db.php");
require_once("../Model/meaning_m.php");

if (array_key_exists('id', $_GET)) {
    $meaningId = $_GET['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {

            $query = $readDB->prepare("SELECT * FROM meaning where id = :meaningId");
            $query->bindParam(":meaningId", $meaningId, PDO::PARAM_INT);
            $query->execute();

            if ($query->rowCount() === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->addMessage("No Meaning Found");
                $response->setSuccess(false);
                $response->send();
                exit;
            }
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $meaning = new Meaning(
                    $row['id'],
                    $row['meaning'],
                    $row["word_id"]
                );
                $returnedMeaning = $meaning->returnMeaningAsArry();
            }
            $returnData = array();
            $returnData["Rows_returned"] = $query->rowCount();
            $returnData["meaning"] = $returnedMeaning;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->addMessage("meaning");
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
            $query = $writeDB->prepare('DELETE FROM meaning where id = :meaningId');
            $query->bindParam(":meaningId", $meaningId, PDO::PARAM_INT);
            $query->execute();

            if ($query->rowCount() === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->addMessage("Meaning not found");
                $response->setSuccess(false);
                $response->send();
                exit;
            }

            $response = new Response();
            $response->setHttpStatusCode(204);
            $response->addMessage("Meaning Deleted");
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
            if(!isset($jsonData->meaning)){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                !isset($jsonData->meaning) ? $response->addMessage("Meaning is required") : false;                
                $response->send();
                exit;
            }
            
            $jsonMeaning = $jsonData->meaning;

            $query = $readDB->prepare('SELECT * FROM meaning WHERE id = :meaning_id');
            $query->bindParam(":meaning_id", $meaningId, PDO::PARAM_INT);
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

            $meaning_array = array();

            
            $meaning = new Meaning(null, $jsonData->meaning, $f_wordId);
            
            $ex = $meaning->getMeaning();
            $word_id = $meaning->getWordId();

            $query = $writeDB->prepare('UPDATE meaning SET meaning = :meaning WHERE id = :meaning_id');
            $query->bindParam(":meaning", $ex, PDO::PARAM_STR);
            $query->bindParam(':meaning_id', $meaningId);
            $query->execute();

            if($query->rowCount() ===0){
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage("Error: Meaning update failed");
                $response->send();
                exit;
            }

            $query = $readDB->prepare('SELECT * FROM meaning WHERE id = :meaning_id');
            $query->bindParam(':meaning_id', $meaningId, PDO::PARAM_INT);
            $query->execute();
            
            if($query->rowCount()===0){
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage("Failed to get updated record");
                $response->send();
                exit;
            }

            $meaningArray = array();

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $meaning = new Meaning(
                    $row['id'],
                    $row['meaning'],
                    $row['word_id']
                );
                $meaningArray[] = $meaning->returnMeaningAsArry();
            }
            
            $returnData = array();
            $returnData["rows_returned"] = $query->rowCount();
            $returnData["data"] = $meaningArray;

            $response = new Response();
            $response->setSuccess(true);
            $response->setHttpStatusCode(200);
            $response->addMessage("Updated Successfully");
            $response->setData($returnData);
            $response->send();
            exit;


        } catch (MeaningException $ex) {
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
        $meaning = $jsonData->meaning;

        if (!isset($jsonData->word_id) || !isset($jsonData->meaning)) {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            !isset($jsonData->word_id) ? $response->addMessage("word_id is missing") : false;
            !isset($jsonData->meaning) ? $response->addMessage("meaning is missing") : false;
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

        $meaning = new Meaning(null, $meaning, $word_id);

        $ex = $meaning->getMeaning();
        $w_id = $meaning->getWordId();

        $query = $writeDB->prepare('INSERT INTO meaning(meaning, word_id) VALUES(:meaning, :word_id)');
        $query->bindParam(':meaning', $ex, PDO::PARAM_STR);
        $query->bindParam(':word_id', $w_id, PDO::PARAM_INT);
        $query->execute();

        if ($query->rowCount() === 0) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Error! Inserting meaning");
            $response->send();
            exit;
        }
        $lastInsertId = $writeDB->lastInsertId();

        $query = $readDB->prepare('SELECT * FROM meaning WHERE id = :lastInsertId');
        $query->bindParam(':lastInsertId', $lastInsertId, PDO::PARAM_INT);
        $query->execute();

        if($query->rowCount() === 0){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to fectch inserted meaning");
            $response->send();
            exit;
        }

        $newMeaningArray = array();
        while($row = $query->fetch(PDO::FETCH_ASSOC)){
            $meaning = new Meaning(
                $row['id'],
                $row['meaning'],
                $row['word_id']
            );
            $newMeaningArray[] = $meaning->returnMeaningAsArry();
        }
        $returnedData = array();
        $returnedData['rows_returned'] = $query->rowCount();
        $returnedData['data'] = $newMeaningArray;

        $response = new Response();
        $response->setHttpStatusCode(201);
        $response->setSuccess(true);
        $response->addMessage("Meaning successfully inserted");
        $response->setData($returnedData);
        $response->send();
        exit;
    }
    catch(MeaningException $ex){
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
