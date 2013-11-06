<?php
/**
 * User: graymur
 * Date: 06.11.13
 * Time: 16:45
 */

namespace Cpeople\Classes;

class Registry
{
    protected $values = array();
    protected static $instance;

    private function __construct() {}

    static function instance()
    {
        if (!isset(self::$instance))
        {
            self::$instance = new self();
        }

        return self::$instance;
    }

    protected function get($key)
    {
        return isset($this->values[$key]) ? $this->values[$key] : null;
    }

    protected function set($key, $val)
    {
        $this->values[$key] = $val;
    }
}