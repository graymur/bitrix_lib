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
}