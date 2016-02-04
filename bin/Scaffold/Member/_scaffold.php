<?php

/* 
 * Created by Hei
 */

copy(BIN_SCAFFOLD . 'Member/Member.php', CONTROLLER . 'Member.php');
$output = '===== Copy to ' . CONTROLLER . 'Member.php';
echo padding($output);

copy(BIN_SCAFFOLD . 'Member/MemberModel.php', MODEL . 'MemberModel.php');
$output = '===== Copy to ' . MODEL . 'MemberModel.php';
echo padding($output);

include BIN . 'mvc_generate_view.php';
copy(BIN_SCAFFOLD . 'Member/Create.html', $return . 'Create.html');
$output = '===== Copy to ' . $return . 'Create.html';
echo padding($output);
copy(BIN_SCAFFOLD . 'Member/Edit.html', $return . 'Edit.html');
$output = '===== Copy to ' . $return . 'Edit.html';
echo padding($output);
copy(BIN_SCAFFOLD . 'Member/Index.html', $return . 'Index.html');
$output = '===== Copy to ' . $return . 'Index.html';
echo padding($output);
copy(BIN_SCAFFOLD . 'Member/Login.html', $return . 'Login.html');
$output = '===== Copy to ' . $return . 'Login.html';
echo padding($output);
copy(BIN_SCAFFOLD . 'Member/Show.html', $return . 'Show.html');
$output = '===== Copy to ' . $return . 'Show.html';
echo padding($output);

include BIN . 'db_create.php';
copy(BIN_SCAFFOLD . 'Member/create_member.schema', $return['path']);
$output = '===== Replace ' . $return['path'];
echo padding($output);

$output = '===== Scaffold Member successfully generated';
echo padding($output);
$return = TRUE;
