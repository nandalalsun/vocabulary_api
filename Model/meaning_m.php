<?php
    class MeaningException extends Exception{}
    class Meaning{
        private $_id;
        private $_meaning;
        private $_wordId;


        public function __construct($id, $meaning, $wordId){
            $this->setId($id);
            $this->setMeaning($meaning);
            $this->setWordId($wordId);
        }
        public function getId(){
            return $this->_id;
        }   
        public function getmeaning(){
            return $this->_meaning;
        } 
        public function getWordId(){
            return $this->_wordId;
        }
        public function setId($id){
            $this->_id = $id;
        }
        public function setMeaning($meaning){
            if(strlen($meaning) <= 5){
                throw new MeaningException("Meaning length is too short!!");
            }
            $this->_meaning = $meaning;
        }
        public function setWordId($wordId){
            $this->_wordId = $wordId;
        }
        public function returnmeaningAsArry(){
            $meaning = array();
            $meaning['id'] = $this->getId();
            $meaning['wordId'] = $this->getWordId();
            $meaning['meaning'] = $this->getmeaning();
            return $meaning;
        }
    }
?>