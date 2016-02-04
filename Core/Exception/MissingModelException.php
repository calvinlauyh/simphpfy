<?php

/* 
 * Created by Hei
 */

/*
 * Exception descripying situation when a Model class file is not found
 */
class MissingModelException extends SimPHPfyException{
    protected $_template = '';
    
    public function __construct($message, $code = 404) {
        $this->_template = "Model `%s` was not found in `" . MODEL . '`.';
        parent::__construct($message, $code);
    }
}
