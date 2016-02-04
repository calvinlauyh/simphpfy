<?php

/* 
 * Created by Hei
 */

class ClassCollisionException extends SimPHPfyException {
    protected $_template = "class `%s` has beed already defined in namespace `%s`.";
}

