<?php
/**
 * Created by PhpStorm.
 * User: graymur
 * Date: 26.11.13
 * Time: 13:12
 */

namespace Cpeople\Classes\Cache;


class Manager
{
    static $instance;
    static $cachePath;
    static $enabled;
    private $lastCached;

    private function __construct()
    {
    }

    public function enabled()
    {
        return isset(self::$enabled) ? self::$enabled : (\COption::getOptionString('main', 'component_cache_on', 'Y') == 'Y');
    }

    public function setEnabled($value)
    {
        self::$enabled = (bool) $value;
    }

    static function instance()
    {
        if (!isset(self::$instance))
        {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function setCachePath($path)
    {
        if (!file_exists($path))
        {
            @mkdir($path, 0777, true);
        }

        $this->check(file_exists($path), 'Cache path does not exist');

        self::$cachePath = $path;
    }

    private function check($condition, $exceptionMessage)
    {
        if (!$condition)
        {
            throw new CacheException($exceptionMessage);
        }
    }

    private function getFileName($cacheId)
    {
        return self::$cachePath . DIRECTORY_SEPARATOR . md5($cacheId) . '.php';
    }

    public function valid($cacheId, $ttl = null)
    {
        $retval = true;

        try
        {
            if (!$this->enabled())
            {
                throw new \Exception('cache disabled');
            }

            $file = $this->getFileName($cacheId);

            if (!file_exists($file))
            {
                throw new \Exception('file does not exist');
            }

            if (!empty($ttl) && time() - filemtime($file) > $ttl)
            {
                throw new \Exception('too old');
            }
        }
        catch (\Exception $e)
        {
            $retval = false;
        }

        return $retval;
    }

    public function start()
    {
        ob_start();
    }

    public function end($cacheId, $flush = true)
    {
        $data = ob_get_clean();

        $this->save($cacheId, $data);

        if ($flush)
        {
            echo $data;
        }
    }

    public function serialize($cacheId, $data)
    {
        $this->save($cacheId, serialize($data));
    }

    public function save($cacheId, $data)
    {
        if (!$this->enabled())
        {
            return false;
        }

        $file = $this->getFileName($cacheId);
        file_put_contents($file, $data);
        $this->check(file_exists($file), 'Could not save cache file');
        chmod($file, 0666);
    }

    public function get($cacheId)
    {
        $this->check($this->valid($cacheId), "Cache with ID $cacheId does not exist");

        return file_get_contents($this->getFileName($cacheId));
    }

    public function unserialize($cacheId)
    {
        return unserialize($this->get($cacheId));
    }

    public function output($cacheId)
    {
        echo $this->get($cacheId);
    }

    public function clear()
    {
        $objects = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::$cachePath),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach($objects as $name => $object)
        {
            if ($object->isFile())
            {
                unlink($object->getRealPath());
            }
        }
    }
}

