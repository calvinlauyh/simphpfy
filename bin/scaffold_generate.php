<?php

/* 
 * Created by Hei
 */
if (isset($argv[1])) {
    $scaffold = $argv[1];
} else {
    throw new CLIException('Missing scaffold name for generation');
}

include BIN_SCAFFOLD . $scaffold . DS . '_scaffold.php';