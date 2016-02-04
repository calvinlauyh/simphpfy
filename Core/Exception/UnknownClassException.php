<?php

/* 
 * Created by Hei
 */
class UnknownClassException extends SimPHPfyException{
    protected $_template = "Class `%s` was not in any package.";
    
    public function __construct($message, $code = 404) {
        parent::__construct($message, $code);
    }
}
