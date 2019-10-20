<?php

namespace Topdb\Adpater;

class _Abstract
{
    public $database = null;

    public function query($sql)
    {
        return $this->database->query($sql);
    }
}
