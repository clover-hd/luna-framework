<?php

namespace Luna\Framework\Http;

class GetVars extends HttpVars
{
    public function __construct()
    {
        parent::__construct($_GET);
    }
}