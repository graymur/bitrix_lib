<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 19.06.14
 * Time: 16:28
 */

namespace Cpeople\Classes\Catalog;

class Order implements \ArrayAccess
{
    protected $id;
    protected $container;
    protected $props;
    protected $propsRaw;

    protected $statusTitles = array(
        'N' => 'Принят, ожидается оплата',
        'P' => 'Оплачен, формируется к отправке',
        'F' => 'Выполнен'
    );

    public function __construct($id)
    {
        if ($id)
        {
            $this->id = $id;
            $this->container = \CSaleOrder::GetByID($id);
        }
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset))
        {
            $this->container[] = $value;
        }
        else
        {
            $this->container[$offset] = $value;
        }
    }
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    public function __get($key)
    {
        $key = strtoupper($key);

        if (!isset($this->container[$key]))
        {
            throw new Exception('Object ' . get_class($this) . ' does not have property ' . $key);
        }

        return $this->container[$key];
    }

    public function getRaw()
    {
        return $this->container;
    }

    public function getProperties()
    {
        if (!isset($this->props))
        {
            $this->props = $this->propsRaw = array();

            $res = \CSaleOrderPropsValue::GetList(array('SORT' => 'ASC'), array('ORDER_ID' => $this->id));

            while ($row = $res->Fetch())
            {
                $this->props[$row['CODE']] = $row['VALUE'];
                $this->propsRaw[] = $row;
            }
        }

        return $this->props;
    }

    public function getLocationId()
    {
        return $this->getProperties()['LOCATION'];
    }
    
    public function isPayed()
    {
        return $this['PAYED'] == 'Y';
    }

    public function setPayed($value)
    {
        $value = (bool) $value ? 'Y' : 'N';

        if ($value == 'Y')
        {
            \CSaleOrder::PayOrder($this['ID'], 'Y');
        }
        else
        {
            $data = array(
                'PAYED' => (bool) $value ? 'Y' : 'N',
                'DATE_PAYED' => Date(\CDatabase::DateFormatToPHP(\CLang::GetDateFormat('FULL', LANG))),
                'USER_ID' => $this['USER_ID'],
            );

            return \CSaleOrder::Update($this['ID'], $data);
        }
    }

    public function getStatusString()
    {
        return $this->statusTitles[$this['STATUS_ID']];
    }
}