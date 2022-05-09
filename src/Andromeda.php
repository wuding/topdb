<?php

#$result_values = require 'Andromeda.php';

function andromeda($undefined_functions, $chars, $tags = 'pre', $attributes = 'true', $options = array())
{
extract($options['func']['extract']['var_array'] ?? array());

/*
 * 输入
 */
if (is_string($tags)) {
    echo '<'. $tags .' contenteditable="'. $attributes .'">';
}

/* 参数 */
if (is_int($arg)) {
print_r([
'func_get_args' => func_get_args(),
'func_get_arg' => func_get_arg($arg),
'func_num_args' => func_num_args(),
]);
}



/* 错误报告 */
if ($err) {
echo "<b>Fatal error</b>: " . PHP_EOL;
echo "Uncaught Error: " . PHP_EOL;
echo "Call to " . PHP_EOL;
}



/* 看见看不见，覆盖值 */
if (is_array($var)) {

/**/
if ( true === $options['func']['']['var_export'] ) {
    var_export( [' undefined function ' => $var ] );
}


$stack_trace = array();
foreach ($var as $key => $value) {
    $var = isset($stack_trace[$value]);
    if (!$var) {
        $stack_trace[$value] = 1;
        if (!function_exists($value)) {
            eval("function $value(){};");
        }
    }
}
}

/*
 * 链接
 */
if (is_int($lnk)) {
$func_get_arg = func_get_arg($lnk);
foreach ($func_get_arg as $arg) {
    print_r('http://192.168.100.4:60917/?q='. $arg . PHP_EOL);
}
}

/*
 * 输出
 */
if (is_string($tags)) {
    echo '</'. $tags .'>';
}
}

/*
andromeda(

[
'mysql_connect',
'mysql_select_db',
'mysql_set_charset',
'mysql_query',
],

array('羁', '绊', '过', '客'),

$_GET['pre'] ?? null,

$_GET['true'] ?? 'true',

array(
    __FILE__,
    __LINE__,
    'func' => array(
        'extract' => array(
            'var_array' => array(
                'arg' => null,
                'err' => 0,
                'var' => 0,
                'lnk' => null,//1
            ),
        ),
    ),
),

['羁', '绊'],

);
*/
