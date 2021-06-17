<?php
    class WordException extends Exception{}
    class Word{
        private $_id;
        private $_word;

        public function __construct($id, $word){
            $this->setId($id);
            $this->setWord($word);
        }
        public function getId(){
            return $this->_id;
        }
        public function getWord(){
            return $this->_word;
        }

        public function setId($id){
            $this->_id = $id;
        }
        public function setWord($word){
            if(strlen($word) <= 1){
                throw new WordException("Length of the word is too short!!");
            }
            $this->_word = $word;
        }

        public function wordReturnAsArray(){
            $word = array();
            $word['id'] = $this->getId();
            $word['word'] = $this->getWord();

            return $word;
        }
        
    }
?>