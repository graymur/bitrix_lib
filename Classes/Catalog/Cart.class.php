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
    protected $itemsRaw;
    protected $items;
    protected $tainted = true;
    protected $location;

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

    public function getItemsRaw()
    {
        $this->getItems();
        return $this->itemsRaw;
    }

    public function setLocation($location)
    {
        $this->location = $location;
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
                $this->itemsRaw[] = $item;
                $item = new $className($item, $this);
                $this->items[] = $item;
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

    public function getWeight()
    {
        return 1000;
    }

    public function getDeliveryOptions($location)
    {
        $filter = array(
            'COMPABILITY' => array(
                'WEIGHT' => $this->getWeight(),
                'PRICE' => $this->getTotal(),
                'LOCATION_FROM' => \COption::GetOptionString('sale', 'location', false, SITE_ID),
                'LOCATION_TO' => $location,
                'ITEMS' => $this->getItemsRaw()
            )
        );

//        dv($this->getItemsRaw());

        $delivery = array();

        $res = \CSaleDeliveryHandler::GetList(array('SORT' => 'ASC'), $filter);

        while ($deliveryItem = $res->Fetch())
        {
            if (!is_array($deliveryItem) || !is_array($deliveryItem['PROFILES'])) continue;

            foreach ($deliveryItem['PROFILES'] as $key => $profile)
            {
                $deliveryProfile = array(
                    'ID' => $deliveryItem['SID'] . ':' . $key,
                    'SID' => $deliveryItem['SID'],
                    'PROFILE' => $key,
                    'NAME' => $deliveryItem['NAME'],
                    'TITLE' => $profile['TITLE'],
                    'DESCRIPTION' => $deliveryItem['DESCRIPTION'],
                    'DESCRIPTION_INNER' => $deliveryItem['DESCRIPTION_INNER'],
                    'BASE_CURRENCY' => $deliveryItem['BASE_CURRENCY'],
                    'HANDLER' => $deliveryItem['HANDLER'],
                    'DELIVERY' => $deliveryItem
                );

//                dvv($deliveryProfile['TITLE']);

                $delivery[] = $deliveryProfile;
            }
        }

        $res = \CSaleDelivery::GetList(
            array('SORT'=>'ASC', 'NAME'=>'ASC'),
            array(
                'LID' => SITE_ID,
                '+<=WEIGHT_FROM' => $this->getWeight(),
                '+>=WEIGHT_TO' => $this->getWeight(),
                'ACTIVE' => 'Y',
                'LOCATION' => $location,
            )
        );

        while ($deliveryItem = $res->Fetch())
        {
            $deliveryDescription = \CSaleDelivery::GetByID($deliveryItem['ID']);
            $deliveryItem['DESCRIPTION'] = htmlspecialcharsbx($deliveryDescription['DESCRIPTION']);
            $delivery[] = $deliveryItem;
        }

        return $delivery;
    }
}