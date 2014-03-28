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
        return $this->data['URL'];
    }

    public function getPath()
    {

    }

    public function getItemID()
    {
        return $this->data['ITEM_ID'];
    }
} 