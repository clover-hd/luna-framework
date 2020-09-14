<?php

namespace Luna\Framework\Http;

class RequestVars extends HttpVars
{
    public function __construct()
    {
        parent::__construct($_REQUEST);
    }
}