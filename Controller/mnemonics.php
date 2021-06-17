<?php
require_once('../Model/response.php');
require_once("db.php");
require_once("../Model/mnemonics_m.php");

if (array_key_exists('id', $_GET)) {
    $mnemonicId = $_GET['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {

            $query = $readDB->prepare("SELECT * FROM mnemonics where id = :mnemonicId");
            $query->bindParam(":mnemonicId", $mnemonicId, PDO::PARAM_INT);
            $query->execute();

            if ($query->rowCount() === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->addMessage("No Mnemonics Found");
                $response->setSuccess(false);
                $response->send();
                exit;
            }
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $mnemonics = new Mnemonics(
                    $row['id'],
                    $row['mnemonic'],
                    $row["word_id"]
                );
                $returnedmnemonics = $mnemonics->returnMnemonicsAsArry();
            }
            $returnData = array();
            $returnData["Rows_returned"] = $query->rowCount();
            $returnData["mnemonics"] = $returnedmnemonics;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->addMessage("mnemonics");
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
            $query = $writeDB->prepare('DELETE FROM mnemonics where id = :mnemonicId');
            $query->bindParam(":mnemonicId", $mnemonicId, PDO::PARAM_INT);
            $query->execute();

            if ($query->rowCount() === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->addMessage("Mnemonics not found");
                $response->setSuccess(false);
                $response->send();
                exit;
            }

            $response = new Response();
            $response->setHttpStatusCode(204);
            $response->addMessage("Mnemonics Deleted");
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
            if(!isset($jsonData->mnemonic)){
                $response = new Response();
                $response->setHttpStatusCode(400);
                $response->setSuccess(false);
                !isset($jsonData->mnemonic) ? $response->addMessage("Mnemonics is required") : false;                
                $response->send();
                exit;
            }
            
            $jsonMnemonic = $jsonData->mnemonic;

            $query = $readDB->prepare('SELECT * FROM mnemonics WHERE id = :mnemonic_id');
            $query->bindParam(":mnemonic_id", $mnemonicId, PDO::PARAM_INT);
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

            $mnemonic_array = array();

            
            $mnemonic = new Mnemonics(null, $jsonData->mnemonic, $f_wordId);
            
            $ex = $mnemonic->getMnemonics();
            $word_id = $mnemonic->getWordId();

            $query = $writeDB->prepare('UPDATE mnemonics SET mnemonic = :mnemonic WHERE id = :mnemonic_id');
            $query->bindParam(":mnemonic", $ex, PDO::PARAM_STR);
            $query->bindParam(':mnemonic_id', $mnemonicId);
            $query->execute();

            if($query->rowCount() ===0){
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage("Error: Mnemonics update failed");
                $response->send();
                exit;
            }

            $query = $readDB->prepare('SELECT * FROM mnemonics WHERE id = :mnemonic_id');
            $query->bindParam(':mnemonic_id', $mnemonicId, PDO::PARAM_INT);
            $query->execute();
            
            if($query->rowCount()===0){
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage("Failed to get updated record");
                $response->send();
                exit;
            }

            $mnemonicArray = array();

            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $mnemonic = new Mnemonics(
                    $row['id'],
                    $row['mnemonic'],
                    $row['word_id']
                );
                $mnemonicArray[] = $mnemonic->returnMnemonicsAsArry();
            }
            
            $returnData = array();
            $returnData["rows_returned"] = $query->rowCount();
            $returnData["data"] = $mnemonicArray;

            $response = new Response();
            $response->setSuccess(true);
            $response->setHttpStatusCode(200);
            $response->addMessage("Updated Successfully");
            $response->setData($returnData);
            $response->send();
            exit;


        } catch (MnemonicsException $ex) {
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
        $mnemonic = $jsonData->mnemonic;

        if (!isset($jsonData->word_id) || !isset($jsonData->mnemonic)) {
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->setSuccess(false);
            !isset($jsonData->word_id) ? $response->addMessage("word_id is missing") : false;
            !isset($jsonData->mnemonic) ? $response->addMessage("mnemonic is missing") : false;
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

        $mnemonic = new Mnemonics(null, $mnemonic, $word_id);

        $ex = $mnemonic->getMnemonics();
        $w_id = $mnemonic->getWordId();

        $query = $writeDB->prepare('INSERT INTO mnemonics(mnemonic, word_id) VALUES(:mnemonic, :word_id)');
        $query->bindParam(':mnemonic', $ex, PDO::PARAM_STR);
        $query->bindParam(':word_id', $w_id, PDO::PARAM_INT);
        $query->execute();

        if ($query->rowCount() === 0) {
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Error! Inserting mnemonic");
            $response->send();
            exit;
        }
        $lastInsertId = $writeDB->lastInsertId();

        $query = $readDB->prepare('SELECT * FROM mnemonics WHERE id = :lastInsertId');
        $query->bindParam(':lastInsertId', $lastInsertId, PDO::PARAM_INT);
        $query->execute();

        if($query->rowCount() === 0){
            $response = new Response();
            $response->setHttpStatusCode(500);
            $response->setSuccess(false);
            $response->addMessage("Failed to fectch inserted mnemonic");
            $response->send();
            exit;
        }

        $newMnemonicArray = array();
        while($row = $query->fetch(PDO::FETCH_ASSOC)){
            $mnemonic = new Mnemonics(
                $row['id'],
                $row['mnemonic'],
                $row['word_id']
            );
            $newMnemonicArray[] = $mnemonic->returnMnemonicsAsArry();
        }
        $returnedData = array();
        $returnedData['rows_returned'] = $query->rowCount();
        $returnedData['data'] = $newMnemonicArray;

        $response = new Response();
        $response->setHttpStatusCode(201);
        $response->setSuccess(true);
        $response->addMessage("Mnemonics successfully inserted");
        $response->setData($returnedData);
        $response->send();
        exit;
    }
    catch(MnemonicsException $ex){
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
