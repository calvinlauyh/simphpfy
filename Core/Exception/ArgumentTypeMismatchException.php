<?php

/* 
 * Created by Hei
 */

class ArgumentTypeMismatchException extends SimPHPfyException {
    protected $_template = "Argument type mismatch for `%s` when calling `%s()`, (`%s` expcected , `%s` found).";
}
