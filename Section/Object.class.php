<?php

namespace Cpeople\Classes\Section;

class Object
{
    private $data;
    private $thumbFunc = 'cp_get_thumb_url';

    public function __construct($data = array())
    {
        if (!is_array($data))
        {
            throw new \Exception('Argument should be an array to ' . __METHOD__);
        }

        $this->data = $data;
    }

    public function __get($name)
    {
        if (isset($this->data[strtoupper($name)]))
        {
            return $this->data[strtoupper($name)];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property in __get(): ' . $name .
                ' in file ' . $trace[0]['file'] .
                ' line ' . $trace[0]['line'],
            E_USER_NOTICE
        );
    }

    public function escape($name)
    {
        return $this->escaped($name);
    }

    public function escaped($name)
    {
        return htmlspecialchars($this->{$name});
    }

    private function getImageId($key)
    {
        $retval = false;

        $key = strtoupper($key);

        if ($this->hasProp($key))
        {
            $retval = $this->getPropValue($key);
        }
        else
        {
            $retval = $this->data[$key];
        }

        return $retval;
    }

    public function hasImage($key)
    {
        return (bool) $this->getImageId($key);
    }

    public function getImageUrl($key)
    {
        if (!$this->hasImage($key))
        {
            throw new \Exception(__CLASS__ . ' with ID ' . $this->id . ' does not have image ' . $key);
        }

        if (empty($this->imagesSrc[$key]))
        {
            if ($file = \CFile::GetByID($this->getImageId($key))->GetNext())
            {
                $this->imagesSrc[$key] = \CFile::GetFileSRC($file);
            }
        }

        return $this->imagesSrc[$key];
    }

    public function getImageThumb($key, $options)
    {
        return call_user_func($this->thumbFunc, $this->getImageUrl($key), $options);
    }

    public function getImageCaption($key)
    {
        $prop = $this->getProp($key);
        return $prop['DESCRIPTION'];
    }

    public function hasDetailImage()
    {
        return $this->hasImage('DETAIL_PICTURE');
    }

    public function getDetailImageUrl()
    {
        return $this->getImageUrl('DETAIL_PICTURE');
    }

    public function getDetailImageThumb($options)
    {
        return $this->getImageThumb('DETAIL_PICTURE', $options);
    }

    public function hasPreviewImage()
    {
        return $this->hasImage('PREVIEW_IMAGE_SRC');
    }

    public function getPreivewImageUrl()
    {
        return $this->getImageUrl('PREVIEW_IMAGE_SRC');
    }

    public function getPreviewImageThumb($options)
    {
        return $this->getImageThumb('PREVIEW_IMAGE_SRC', $options);
    }

    private function getDate($key, $format)
    {
        return russian_date($format, strtotime($this->data[$key]));
    }

    public function getUrl()
    {
        return $this->section_page_url;
    }

    public function getElementsCount()
    {
        return $this->data['ELEMENT_CNT'];
    }

    public function getDepth()
    {
        return isset($this->data['DEPTH_LEVEL']) ? $this->data['DEPTH_LEVEL'] : 0;
    }
}