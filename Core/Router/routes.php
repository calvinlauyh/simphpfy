<?php

/* 
 * Created by Hei
 */
Router::listen('GET', DIRECTORY_PREFIX . '$controller:Controller', function($request){
    $url = SIMPHPFY_RELATIVE_PATH . $request->params['controller'] . '/Index';
    header("location: {$url}");
    die();
});
Router::listen('GET', DIRECTORY_PREFIX . '__WebDocument/$base64:*', function($request){
    $file = base64_decode(urldecode($request->params['base64']));
    if (!file_exists($file)) {
        throw new FileNotFoundException('File not found');
    }
    header('Content-Type: ' . Mimetype::fromPath($file));
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
});
Router::listen(array('GET', 'POST', 'PUT', 'DELETE'), DIRECTORY_PREFIX . ':Controller/:Action');
Router::listen(array('GET', 'POST', 'PUT', 'DELETE'), DIRECTORY_PREFIX . ':Controller/:Action/$id:ID');
