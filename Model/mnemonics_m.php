<?php
    class MnemonicsException extends Exception{}

    class Mnemonics{
        private $_id;
        private $_mnemonics;
        private $_wordId;

        public function __construct($id, $mnemonics, $wordId){
            $this->setId($id);
            $this->setMnemonics($mnemonics);
            $this->setWordId($wordId);
        }
        public function getId(){
            return $this->_id;
        }   
        public function getMnemonics(){
            return $this->_mnemonics;
        } 
        public function getWordId(){
            return $this->_wordId;
        }
        public function setId($id){
            $this->_id = $id;
        }
        public function setMnemonics($mnemonics){
            $this->_mnemonics = $mnemonics;
        }
        public function setWordId($wordId){
            $this->_wordId = $wordId;
        }
        public function returnMnemonicsAsArry(){
            $mnemonics = array();
            $mnemonics['id'] = $this->getId();
            $mnemonics['wordId'] = $this->getWordId();
            $mnemonics['mnemonics'] = $this->getMnemonics();
            return $mnemonics;
        }
    }
?>