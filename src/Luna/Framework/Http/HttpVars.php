<?php

namespace Luna\Framework\Http;

use Iterator;

/**
 * HTTP変数クラス
 * 
 * @method array getRawVars()
 * @method mixed current()
 * @method mixed key()
 * @method mixed next()
 * @method void rewind()
 * @method void void()
 * @method int count()
 */
class HttpVars implements Iterator
{
    /**
     * 変数のキー
     *
     * @var array
     */
    protected $keys;
    /**
     * 変数の連想配列
     *
     * @var array
     */
    protected $vars;

    public function __construct(array &$vars)
    {
        $this->keys = \array_keys($vars);
        $this->vars = &$vars;
        $this->position = -1;
    }

    public function getRawVars()
    {
        return $this->vars;
    }
    
    public function __get($name)
    {
        if (\array_key_exists($name, $this->vars)) {
            return $this->vars[$name];
        } else {
            return null;
        }
    }


    public function current()
    {
        return $this->vars[$this->keys[$this->position]];
    }

    public function key()
    {
        return $this->keys[$this->position];
    }

    public function next()
    {
        $this->position++;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function valid()
    {
        return isset($this->keys[$this->position]);
    }

    public function count()
    {
        return count($this->vars);
    }

    public function close()
    {
    }
}
