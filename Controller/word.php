<?php
require_once('../Model/response.php');
require_once("db.php");
require_once("../Model/word_m.php");

if (array_key_exists('id', $_GET)) {
    $id = $_GET['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        try {
            $query = $readDB->prepare("SELECT * FROM words where id = :id");
            $query->bindParam(":id", $id, PDO::PARAM_INT);
            $query->execute();

            if ($query->rowCount() === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->addMessage("No Word Found");
                $response->setSuccess(false);
                $response->send();
                exit;
            }
            $wordGroup = array();
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                $word = new Word($row['id'], $row['word']);
                $wordGroup[] = $word->wordReturnAsArray();
            }
            $query = $readDB->prepare('SELECT meaning from meaning WHERE word_id = :id');
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            $query->execute();
            
            $meaningArray = array();
            $i = 1;
            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $meaning = array();
                $meaning[$i] = $row['meaning'];
                $meaningArray[] = $meaning;
                $i = $i + 1;
            }

            $query = $readDB->prepare('SELECT example FROM EXAMPLES WHERE WORD_ID = :id');
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            $query->execute();

            $exampleArray = array();
            $i = 1;
            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $example = array();
                $example[$i] = $row['example'];
                $exampleArray[] = $example;
                $i = $i + 1;
            }

            $query = $readDB->prepare('SELECT mnemonic FROM MNEMONICS WHERE WORD_ID = :id');
            $query->bindParam(':id', $id, PDO::PARAM_INT);
            $query->execute();

            $mnemonicArray = array();
            $i = 1;
            while($row = $query->fetch(PDO::FETCH_ASSOC)){
                $mnemonic = array();
                $mnemonic[$i] = $row['mnemonic'];
                $mnemonicArray[] = $mnemonic;
                $i = $i +1;
            }

            $returnData = array();
            $returnData["Rows_returned"] = $query->rowCount();
            $returnData["Word"] = $wordGroup;
            $returnData["meaning"] = $meaningArray;
            $returnData["example"] = $exampleArray;
            $returnData["mnemonic"] = $mnemonicArray;

            $response = new Response();
            $response->setHttpStatusCode(200);
            $response->addMessage("Word");
            $response->setSuccess(true);
            $response->setData($returnData);
            $response->toCache(true);
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
        catch(WordException $ex){
            $response = new Response();
            $response->setHttpStatusCode(400);
            $response->addMessage($ex->getMessage());
            $response->setSuccess(false);
            $response->send();
            exit;
        }
    } 
    elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        try {
            $writeDB->beginTransaction();
        
            $query = $writeDB->prepare('DELETE 
            M, E, N
            FROM
                WORDS AS W
                    JOIN
                MEANING AS M ON W.ID = M.WORD_ID
                    JOIN
                EXAMPLES E ON E.WORD_ID = W.ID
                    JOIN
                MNEMONICS N ON W.ID = N.WORD_ID
            WHERE
                W.ID = :wordId;');
            $query->bindParam(':wordId', $id, PDO::PARAM_INT);
            $query->execute();

            if($query->rowCount() === 0){
                if ($writeDB->inTransaction()) {
                    $writeDB->rollBack();
                }
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->addMessage("Cannot delete word Records");
                $response->setSuccess(false);
                $response->send();
                exit;
            }

            $query = $writeDB->prepare('DELETE FROM words where id = :wordId');
            $query->bindParam(":wordId", $id, PDO::PARAM_INT);
            $query->execute();

            if ($query->rowCount() === 0) {
                if ($writeDB->inTransaction()) {
                    $writeDB->rollBack();
                }
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->addMessage("Word not found");
                $response->setSuccess(false);
                $response->send();
                exit;
            }
            $writeDB->commit();
            $response = new Response();
            $response->setHttpStatusCode(204);
            $response->addMessage("Word Deleted");
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
            if (!isset($jsonData->meaning)) {
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

            if ($query->rowCount() === 0) {
                $response = new Response();
                $response->setHttpStatusCode(404);
                $response->setSuccess(false);
                $response->addMessage("No word found");
                $response->send();
                exit;
            }

            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
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

            if ($query->rowCount() === 0) {
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

            if ($query->rowCount() === 0) {
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage("Failed to get updated record");
                $response->send();
                exit;
            }

            $meaningArray = array();

            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
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
        try {
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

            if ($query->rowCount() === 0) {
                $response = new Response();
                $response->setHttpStatusCode(500);
                $response->setSuccess(false);
                $response->addMessage("Failed to fectch inserted meaning");
                $response->send();
                exit;
            }

            $newMeaningArray = array();
            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
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
        } catch (MeaningException $ex) {
            $response = new Response();
            $response->setHttpStatusCode(403);
            $response->addMessage($ex->getMessage());
            $response->setSuccess(false);
            $response->send();
            exit;
        } catch (PDOException $ex) {
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
