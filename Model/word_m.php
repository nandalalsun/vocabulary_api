<?php
    class WordException extends Exception{}
    class Word{
        private $_id;
        private $_word;
        private $_mnemonic;
        private $_meaning;
        private $_example;

        public function __construct($id, $word, $meaning = null, $example = null, $mnemonic = null){
            $this->setId($id);
            $this->setWord($word);
            $this->setMeaning($mnemonic);
            $this->setExample($example);
            $this->setMnemonic($mnemonic);
        }
        public function getId(){
            return $this->_id;
        }
        public function getWord(){
            return $this->_word;
        }
        public function getMeaning(){
            return $this->_meaning;
        }
        public function getExample(){
            return $this->_example;
        }
        public function getMnemonic(){
            return $this->_mnemonic;
        }
        public function setId($id){
            $this->_id = $id;
        }
        public function setWord($word){
            // if(strlen($word) < 1){
            //     throw new WordException("Length of the word is too short!!");
            // }
            $this->_word = $word;
        }
        public function setMnemonic($mnemonic){
            $this->_mnemonic = $mnemonic;
        }
        public function setMeaning($meaning){
            $this->_meaning = $meaning;
        }
        public function setExample($example){
            $this->_example = $example;
        }

        public function wordReturnAsArray(){
            $word = array();
            $word['id'] = $this->getId();
            $word['word'] = $this->getWord();

            return $word;
        }
        public function wordMeaningAsArray(){
            $wordAll = array();
            $wordAll['id'] = $this->getId();
            $wordAll['word'] = $this->getWord();
            $wordAll['meaning'] = $this->getMeaning();
            $wordAll['example'] = $this->getExample();
            $wordAll['mnemonic'] = $this->getMnemonic();

            return $wordAll;
        }
        
    }
?>