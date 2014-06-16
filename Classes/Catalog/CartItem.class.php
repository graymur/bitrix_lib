<?php
/**
 * Created by PhpStorm.
 * User: Администратор
 * Date: 16.06.14
 * Time: 11:56
 */

namespace Cpeople\Classes\Catalog;

class CartItem implements \ArrayAccess
{
    protected $container;
    protected $productClassName = '\Cpeople\Classes\Catalog\Product';

    /**
     * @var \Cpeople\Classes\Catalog\Cart
     */
    protected $cart;

    /**
     * @var \Cpeople\Classes\Block\Object
     */
    protected $product;

    public function __construct($data = array(), \Cpeople\Classes\Catalog\Cart $cart = null)
    {
        $this->container = $data;
        $this->cart = $cart;
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

    public function getProduct($className = null)
    {
        if (!isset($this->product))
        {
            if (empty($className)) $className = $this->productClassName;

            $this->product = \Cpeople\Classes\Block\Getter::instance()
                ->setClassName($className)
                ->getById($this['PRODUCT_ID']);
        }

        return $this->product;
    }

    public function getSum()
    {
        return $this->getPrice() * $this->getQuantity();
    }

    public function getPrice()
    {
        return $this['PRICE'];
    }

    public function getQuantity()
    {
        return $this['QUANTITY'];
    }

    public function remove()
    {
        \CSaleBasket::Delete($this['ID']);
    }

    public function setQuantity($quantity)
    {
        $this['QUANTITY'] = floatval($quantity);
        $this->save();
    }

    public function save()
    {
        return \CSaleBasket::Update($this['ID'], array(
            'PRICE' => $this['PRICE'],
            'QUANTITY' => $this['QUANTITY'],
            'CURRENCY' => $this['CURRENCY'],
        ));

        $cart->setTainted(true);
    }
}