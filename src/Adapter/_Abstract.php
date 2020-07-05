<?php

namespace Topdb\Adapter;

class _Abstract
{
    public $database = null;

    /*
    +---------------------------------------
    + 基本
    +---------------------------------------
    */

    public function setVars($data = [])
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    /*
    +---------------------------------------
    + 覆盖
    +---------------------------------------
    */

    public function query()
    {
        return call_user_func_array([$this->database, 'query'], func_get_args());
    }
}
