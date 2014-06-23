<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 14.03.14
 * Time: 13:47
 */

namespace Cpeople\Classes\Search;


class Result
{
    protected $data;
    protected $element = null;

    public function __construct($data = array())
    {
        $this->data = $data;
    }

    public function getTitle()
    {
        return $this->data['TITLE'];
    }

    public function getText($limit = 500)
    {
        return trim(mb_substr($this->data['BODY'], 0, $limit));
    }

    public function getUrl()
    {
        if ($this->data['MODULE_ID'] === 'iblock')
        {

        }

        return $this->data['MODULE_ID'] === 'iblock'
            ? ($this->getElement() ? $this->getElement()->getUrl() : false)
            : $this->data['URL'];
    }

    public function getPath()
    {

    }

    public function getItemID()
    {
        return $this->data['ITEM_ID'];
    }

    public function getChangeDate($format)
    {
        return FormatDate($format, strtotime($this->data['DATE_CHANGE']));
    }

    public function getBodyHighlighted()
    {
        return $this->data['BODY'];
    }

    /**
     * @return \Cpeople\Classes\Block\Object|\Cpeople\Classes\Section\Object
     */
    public function getElement()
    {
        if($this->element === null)
        {
            $itemId = $this->getItemID();
            $isSection = !is_numeric($itemId);

            if($isSection)
            {
                $itemId = substr($itemId, 1);
                $instance = \Cpeople\Classes\Section\Getter::instance();
            }
            else
            {
                $instance = \Cpeople\Classes\Block\Getter::instance();
            }

            $this->element = $instance->getById($itemId);
        }

        return $this->element;
    }
} 
