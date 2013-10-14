<?php

namespace Cpeople\Classes\Block;

class Object {

    protected $data;
    protected $thumbFunc = 'cp_get_thumb_url';
    protected $imagesSrc;
    protected $related;

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
                ' line ' . $trace[0]['line'], E_USER_NOTICE
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

    public function getSectionId()
    {
        return $this->data['IBLOCK_SECTION_ID'];
    }

    public function hasProp($propName)
    {
        return !empty($this->data[$propName]) && is_array($this->data[$propName]);
    }

    public function getProp($propName)
    {
        if (!$this->hasProp($propName))
        {
          throw new \Exception("Property $propName is not set for object with ID " . $this->id);
        }

        return $this->data[$propName];
    }

    public function propEmpty($propName)
    {
        return _empty($this->getPropValue($propName));
    }

    public function getPropValue($propName)
    {
        $prop = $this->getProp($propName);

        return $prop['VALUE'];
    }

    public function getPropEnumId($propName)
    {
        $prop = $this->getProp($propName);

        return $prop['VALUE_ENUM_ID'];
    }

    public function getPropRawValue($propName)
    {
        $prop = $this->getProp($propName);

        return $prop['~VALUE'];
    }

    public function getPropRawText($propName)
    {
        $value = $this->getPropRawValue($propName);

        if (is_array($value) && isset($value['TEXT']))
        {
            $value = $value['TEXT'];
        }

        return $value;
    }

    public function getPropText($propName)
    {
        $value = $this->getPropValue($propName);

        if (is_array($value) && isset($value['TEXT']))
        {
            $value = html_entity_decode($value['TEXT']);
        }

        return $value;
    }

    public function getPropXMLValue($propName)
    {
        $prop = $this->getProp($propName);
        return $prop['VALUE_XML_ID'];
    }

    protected function getImageId($key)
    {
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

    public function getFiles($key)
    {
        $prop = $this->getProp($key);

        $retval = false;

        if (!empty($prop['VALUE']))
        {
            for ($i = 0, $c = count($prop['VALUE']); $i < $c; $i++)
            {
                $file = File::fromId($prop['VALUE'][$i]);
                $file->setName($prop['DESCRIPTION'][$i]);

                $retval[] = $file;
            }
        }

        return $retval;
    }

    public function getImages($key)
    {
        $prop = $this->getProp($key);

        $retval = false;

        if (!empty($prop['VALUE']))
        {
            for ($i = 0, $c = count($prop['VALUE']); $i < $c; $i++)
            {
                $image = Image::fromId($prop['VALUE'][$i]);
                $image->setName($prop['DESCRIPTION'][$i]);

                $retval[] = $image;
            }
        }

        return $retval;
    }

    public function getImageUrl($key)
    {
        if (!$this->hasImage($key))
        {
            throw new \Exception(__CLASS__ . ' with ID ' . $this->id . ' does not have image ' . $key);
        }

        if (empty($this->imagesSrc[$key])) {
            if ($file = \CFile::GetByID($this->getImageId($key))->GetNext()) {
                $this->imagesSrc[$key] = \CFile::GetFileSRC($file);
            }
        }

        return $this->imagesSrc[$key];
    }

    public function getFile($key)
    {
        if (!$this->hasImage($key))
        {
            throw new \Exception(__CLASS__ . ' with ID ' . $this->id . ' does not have image ' . $key);
        }
        $retval = File::fromId($this->getImageId($key));
        return $retval;
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
        return $this->hasImage('PREVIEW_PICTURE');
    }

    public function getPreivewImageUrl()
    {
        return $this->getImageUrl('PREVIEW_PICTURE');
    }

    public function getPreviewImageThumb($options)
    {
        return $this->getImageThumb('PREVIEW_PICTURE', $options);
    }

    public function isDateActive()
    {
        return $this->getTimestamp('ACTIVE_FROM') < time() && $this->getTimestamp('ACTIVE_TO') > time();
    }

    public function getTimestamp($key)
    {
        return strtotime($this->data[$key]);
    }

    protected function getDate($key, $format)
    {
        return FormatDate($format, strtotime($this->data[$key]));
    }

    public function getActiveFromDate($format)
    {
        return $this->getDate('ACTIVE_FROM', $format);
    }

    public function getActiveToDate($format)
    {
        return $this->getDate('DATE_ACTIVE_TO', $format);
    }

    public function getUrl()
    {
        return $this->detail_page_url;
    }

    /**
     * @param $key
     * @return \Cpeople\Classes\Block\Getter
     */
    public function getRelatedGetter($key)
    {
        $prop = $this->getProp($key);

        $retval = false;

        if ((!empty_array($prop['VALUE'])) || (!empty($prop['VALUE']))) {
            $filter = array(
                'ID' => $prop['VALUE'],
                'IBLOCK_ID' => $prop['LINK_IBLOCK_ID']
            );

            $retval = Getter::instance()->setFilter($filter);
        }

        return $retval;
    }

    public function getRelated($key, $filter = array(), $order = array('SORT' => 'asc'))
    {
        $retval = false;

        if ($getter = $this->getRelatedGetter($key))
        {
            $getter->setOrder($order)->setHydrationMode($getter::HYDRATION_MODE_OBJECTS_ARRAY);

            if (!empty_array($filter))
            {
                $getter->addFilter($filter);
            }

            $retval = $getter->get();
        }

        return $retval;
    }

    // get price array
    public function getBasePriceArray()
    {
        if (!class_exists('\CPrice'))
        {
            \CModule::IncludeModule('catalog');
        }

        return \CPrice::GetBasePrice($this->id);
    }

    // get price in default currency
    public function getBasePriceRawValue()
    {
        if (!$price = $this->getBasePriceArray())
        {
            throw new \Exception(__CLASS__ . ' with ID ' . $this->id . ' does not have price');
        }

        return $price['PRICE'];
    }

    // get price in selected currency
    public function getBasePriceValue()
    {
        return $this->getBasePriceRawValue();
    }

    public function getBasePrice($currencyId = 'RUB')
    {
        \CModule::IncludeModule('catalog');

        return \FormatCurrency($this->getBasePriceRawValue(), $currencyId);
    }

    public function setBasePrice($price, $currencyId = 'RUB')
    {
        if (!class_exists('\CPrice'))
        {
            \CModule::IncludeModule('catalog');
        }

        return \CPrice::SetBasePrice($this->id, $price, $currencyId);
    }

    /**
     * @return \Cpeople\Classes\Section\Object
     */
    public function getParentSection()
    {
        return \Cpeople\Classes\Section\Getter::instance()->getById($this->iblock_section_id);
    }

    public function toArray()
    {
        return $this->data;
    }

    public function saveField($field, $value)
    {
        $this->update(array($field => $value));
    }

    public function saveProp($prop, $value)
    {
        $element = new \CIBlockElement;
        $element->SetPropertyValuesEx($this->id, false, array($prop => $value));
    }

    public function update($values)
    {
        $element = new \CIBlockElement;
        $element->Update($this->id, $values);
    }
}
