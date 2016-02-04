<?php

/* 
 * Created by Hei
 */

class FileNotFoundException extends HttpException {

    function __construct($message, $code=404) {
        parent::__construct($message, $code);
    }
}
