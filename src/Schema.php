<?php

namespace Topdb;

class Schema extends Tbl
{
    const VERSION = 26.0206;
    const REVISION = 1;
    static $conf_file = null;

    function __construct($vars = null, $prop = null, $config = null, $merge = null)
    {
        // 不包含导入
        if (is_object($config)) {
            $conf = (array) $config;
        } else { // 导入配置
            $conf = include $this->config_file;
            $conf = $conf['model'];
            // 合并
            if (is_array($config)) {
                if (false !== $merge) {
                    $conf = array_merge($conf, $config);
                }
            }
        }

        $connect = $conf['Db']['connect'] ?? null;
        unset($conf['Db']['connect']);
        // print_r([$this->config_file, get_defined_vars()]);
        parent::__construct($vars, $prop, $conf, $connect);
    }
}
