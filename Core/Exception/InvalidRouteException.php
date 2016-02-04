<?php

/* 
 * Created by Hei
 */

class InvalidRouteException extends SimPHPfyException {
    protected $_template = "Invalid route rule: %s in `%s`.";
}
