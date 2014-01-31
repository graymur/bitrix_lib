<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 10.12.13
 * Time: 12:02
 */

namespace Cpeople\Classes\Cache;

class EngineFiles implements Engine
{
    private $path;

    public function __construct($path)
    {
        $this->path = $path;

        if (!file_exists($this->path))
        {
            @mkdir($this->path, 0777, true);
        }
    }

    private function getFileName($cacheId)
    {
        return $this->path . DIRECTORY_SEPARATOR . md5($cacheId) . '.txt';
    }

    public function valid($cacheId, $ttl = null)
    {
        $retval = true;

        try
        {
            $file = $this->getFileName($cacheId);

            if (!file_exists($file))
            {
                throw new CacheException('file does not exist');
            }

//            if (empty($ttl))
//            {
//                throw new CacheException('Cache TTL = 0');
//            }

            if ($ttl == null && time() - filemtime($file) > $ttl)
            {
                throw new CacheException('too old');
            }
        }
        catch (CacheException $e)
        {
            $retval = false;
        }

        return $retval;
    }

    public function save($cacheId, $data)
    {
        $file = $this->getFileName($cacheId);
        file_put_contents($file, $data);
//        $this->check(file_exists($file), 'Could not save cache file');
        chmod($file, 0666);
    }

    public function get($cacheId)
    {
        return file_get_contents($this->getFileName($cacheId));
    }

    public function clear()
    {
        $objects = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path),
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

    public function clearByTag($tag)
    {

    }
}