<?php

#$result_values = require 'Andromeda.php';

function andromeda($undefined_functions, $chars, $tags = 'pre', $attributes = 'true')
{
/*
 * 输入
 */
if ($tags) {
    echo '<'. $tags .' contenteditable="'. $attributes .'">';
}

/* 参数 */
print_r([
'func_get_args' => func_get_args(),#[1]
'func_get_arg' => func_get_arg(0),
'func_num_args' => func_num_args(),
]);


/* 错误报告 */
echo "<b>Fatal error</b>: " . PHP_EOL;
echo "Uncaught Error: " . PHP_EOL;
echo "Call to " . PHP_EOL;


/* 看见看不见，覆盖值 */
$_undefinedFunction = func_get_args()[0];
$_undefinedFunction = func_get_arg(0);

/**/
var_export
(
[
 ' undefined function ' => $_undefinedFunction,
]
);


foreach ($_undefinedFunction as $key => $value) {
    eval("function $value(){};");
}


/*
 * 链接
 */
$func_get_arg = func_get_arg(1);
foreach ($func_get_arg as $arg) {
    print_r('http://192.168.100.4:60917/?q='. $arg . PHP_EOL);
}


/*
 * 输出
 */
if ($tags) {
    echo '</'. $tags .'>';
}
}

/**/
andromeda([
'mysql_connect',
'mysql_select_db',
'mysql_set_charset',
'mysql_query',
], array(), $_GET['pre'] ?? 'pre', $_GET['true'] ?? 'true', __FILE__, __LINE__);
