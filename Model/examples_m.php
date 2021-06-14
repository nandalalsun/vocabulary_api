<?php
    class ExampleException extends Exception{}
    class Example{
        private $_id;
        private $_example;
        private $_wordId;


        public function __construct($id, $example, $wordId){
            $this->setId($id);
            $this->setExample($example);
            $this->setWordId($wordId);
        }
        public function getId(){
            return $this->_id;
        }   
        public function getExample(){
            return $this->_example;
        } 
        public function getWordId(){
            return $this->_wordId;
        }
        public function setId($id){
            $this->_id = $id;
        }
        public function setExample($example){
            if($example === null || strlen($example) < 1 || strlen($example) > 255){
                throw new ExampleException("Example is not valid");
            }
            $this->_example = $example;
        }
        public function setWordId($wordId){
            if($wordId == null || strlen($wordId) <= 0 || !is_numeric($wordId)){
                throw new ExampleException("Word Id is not valid");
            }
            $this->_wordId = $wordId;
        }
        public function returnExampleAsArry(){
            $example = array();
            $example['id'] = $this->getId();
            $example['wordId'] = $this->getWordId();
            $example['example'] = $this->getExample();
            return $example;
        }
    }
?>