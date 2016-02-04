<?php

/* 
 * Created by Hei
 */
if (isset($argv[1])) {
    $controller = $argv[1];
} else {
    throw new CLIException('Missing controller name for generation');
}

$viewPath = VIEW . $controller . DS;
if (file_exists($viewPath)) {
    throw new CLIException("Directory `{$viewPath}` already existed");
}
if (!mkdir($viewPath)) {
    throw new CLIException("Unable to create directory `{$viewPath}`");
}
$output = "===== View directory created: {$viewPath} ";
echo padding($output);

$return = $viewPath;
