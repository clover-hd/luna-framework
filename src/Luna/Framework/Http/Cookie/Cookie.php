<?php

namespace Luna\Framework\Http\Cookie;

class Cookie
{

    public function __get(string $name)
    {
        return $this->get($name);
    }

    public function __put(string $name, $value)
    {
        $this->put($name, $value);
    }

    public function get(string $name, string $default = null)
    {
        if (isset($_COOKIE[$name])) {
            return $_COOKIE[$name];
        } else {
            return $default;
        }
    }
    
    public function put(string $name, string $value)
    {
        $_COOKIE[$name] = $value;
    }

    public function getCookieVars()
    {
        return $_COOKIE;
    }
}
