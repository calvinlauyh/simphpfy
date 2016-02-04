<?php

/* 
 * Created by Hei
 */

function defaultController(){
    echo 'This is defaultController';
}

Router::listen('GET', '/simphpfy/', 'defaultController');

Router::listen('GET', '/simphpfy/redirect/:{[0-9a-zA-Z_]+\.jpg}', '/simphpfy/Member/Index');

Router::listen('GET', 'simphpfy/view/$id:ID', function($request){
    echo 'This is closure function<br />';
    echo '$id is ' . $request->params['id'];
});

Router::listen('GET', '/simphpfy/:Controller/:Action/$id:ID/*');

Router::listen('GET', '/simphpfy/:Controller/:Action/$id:ID/**');

Router::listen('GET', '/simphpfy/redirect/member', array(
    'controller' => 'Member', 
    'action' => 'Index'
));

Router::listen('GET', '/simphpfy/:Controller/:Action/$id:ID/**/test');

/*Router::listen(array('GET', 'POST'), '/:Controller/:Action/$id:{[0-9]+}/:{*}', array(
        'controller' => '', 
        'action' => ''
    ), function(){}
);*/

Router::dispatch();
