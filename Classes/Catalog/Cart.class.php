<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 16.06.14
 * Time: 11:54
 */

namespace Cpeople\Classes\Catalog;

class Cart
{
    protected $itemClass = '\Cpeople\Classes\Catalog\CartItem';
    protected $items;
    protected $tainted = true;

    public function __construct()
    {
        \CModule::IncludeModule('sale');
    }

    public function clearCache()
    {
        unset($this->items);
    }

    public function getCount()
    {
        return count($this->getItems());
    }

    public function getItems()
    {
        if (!isset($this->items) || $this->tainted)
        {
            $className = $this->itemClass;

            $result = array();

            \CModule::IncludeModule('sale');

            $dbBasketItems = \CSaleBasket::GetList(
                array("NAME" => "ASC", "ID" => "ASC"),
                array("FUSER_ID" => \CSaleBasket::GetBasketUserID(), /*"LID" => SITE_ID,*/ "ORDER_ID" => "NULL"),
                false,
                false,
                array()
            );

            while ($item = $dbBasketItems->Fetch())
            {
                $this->items[] = new $className($item, $this);
            }

            $this->tainted = false;
        }

        return $this->items;
    }

    public function getTotal()
    {
        $retval = 0;

        foreach ((array) $this->getItems() as $item)
        {
            $retval += $item->getSum();
        }

        return $retval;
    }

    public function setTainted($tainted)
    {
        $this->tainted = (bool) $tainted;
    }

    public function removeById($id)
    {
        $items = $this->getItems();

        foreach ($items as $item)
        {
            if ($item->id == $id)
            {
                $item->remove();
                break;
            }
        }
    }
}