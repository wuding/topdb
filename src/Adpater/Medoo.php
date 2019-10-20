<?php

namespace Topdb\Adpater;

use Medoo\Medoo as Db;

class Medoo extends _Abstract
{
    public function __construct($config, $options)
    {
        $config = $this->setConfig($config, $options);
        $this->database = new Db($config);
    }

    public function setConfig($config, $options)
    {
        if (isset($options['db_name']) && $options['db_name']) {
            $config['database_name'] = $options['db_name'];
        }
        return $config;
    }
}
