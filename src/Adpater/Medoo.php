<?php

namespace Topdb\Adpater;

use Medoo\Medoo as Db;

class Medoo extends _Abstract
{
    public function __construct($config)
    {
        $this->database = new Db($config);
    }

    public function query($sql)
    {
        $this->database->query($sql);
    }
}
