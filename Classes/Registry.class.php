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
    protected static $instances;

    private function __construct() {}

    static function instance()
    {
        $className = get_called_class();

        if (!isset(self::$instances[$className]))
        {
            self::$instances[$className] = new $className;
        }

        return self::$instances[$className];
    }

    protected static function who()
    {
        return __CLASS__;
    }

    protected function get($key)
    {
        return isset($this->values[$key]) ? $this->values[$key] : null;
    }

    protected function set($key, $val)
    {
        $this->values[$key] = $val;
    }

    /**
     * @param $path
     * @return Cache\Manager
     */
    static function getFilesCacheManager($path)
    {
        $engine = new Cache\EngineFiles($path);
        $manager = Cache\Manager::instance();
        $manager->setEngine($engine);

        return $manager;
    }

    /**
     * @param $path
     * @return Cache\Manager
     */
    static function getAPCCacheManager($path)
    {
        $engine = new Cache\EngineAPC($path);
        $manager = Cache\Manager::instance();
        $manager->setEngine($engine);

        return $manager;
    }
}