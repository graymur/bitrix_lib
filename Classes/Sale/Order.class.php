<?
namespace Cpeople\Classes\Sale;

class Order
{
    protected $data = null;
    protected $orderProps = null;
    protected $products = null;

    public static function getOrders($userId)
    {
        if(!\CModule::IncludeModule("sale")) throw new Exception("can't load sale module");

        $orders = self::getOrdersList($userId);
//        echo '<pre>'.htmlspecialchars(print_r($orders,true)).'</pre>';

        if(!$orders) return false;

        return $orders;
    }

    public static function getOrdersList($userId = false)
    {
        $arFilter = $userId ? array('USER_ID' => $userId) : array();
        $rs = \CSaleOrder::GetList(array('ID' => 'DESC'), $arFilter, false, false, array());
        $orders = array();
        while($ar = $rs->GetNext(true, false))
        {
            $orders[] = new static($ar);
        }
        return $orders;
    }

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getPrice()
    {
        return $this->data['PRICE'] - $this->getDeliveryPrice();
    }

    public function getDeliveryPrice()
    {
        return $this->data['PRICE_DELIVERY'];
    }

    public function getTotalPrice()
    {
        return $this->data['PRICE'];
    }

    public function getPriceFormatted()
    {
        return number_format($this->getPrice(), 2, '', ' ');
    }

    public function getDeliveryPriceFormatted()
    {
        return number_format($this->getDeliveryPrice(), 2, '', ' ');
    }

    public function getTotalPriceFormatted()
    {
        return number_format($this->getTotalPrice(), 2, '', ' ');
    }

    public function getDate($format = 'd.m.Y')
    {
        return FormatDate($format, strtotime($this->data['DATE_INSERT']));
    }

    public function getCurrency()
    {
        return $this->data['CURRENCY'];
    }

    public function getId()
    {
        return $this->data['ID'];
    }

    private function fillDelivery()
    {
        $this->data['DELIVERY'] = Delivery::getDelivery($this->data['DELIVERY_ID']);
    }

    public function getDeliveryName()
    {
        if(!isset($this->data['DELIVERY'])) $this->fillDelivery();
        return is_object($this->data['DELIVERY']) ? $this->data['DELIVERY']->getName() : false;
    }

    private function fillPayment()
    {
        $this->data['PAYMENT'] = Payment::getPayment($this->data['PAY_SYSTEM_ID']);
    }

    public function getPaymentName()
    {
        if(!isset($this->data['PAYMENT'])) $this->fillPayment();
        return is_object($this->data['PAYMENT']) ? $this->data['PAYMENT']->getName() : false;
    }

    public function getOrderProps()
    {
        if($this->orderProps === null)
        {
//            $this->orderProps = OrderProp::getOrderProps();
            $this->orderProps = OrderPropsValue::getOrderPropsValue($this->getId(), true);
        }

        return $this->orderProps;
    }

    public function getOrderPropValue($code)
    {
        $orderProps = $this->getOrderProps();
        return isset($orderProps[$code]) ? $orderProps[$code]->getValue() : false;
    }

    public function getProducts($cartClassName = '\Cpeople\Classes\Catalog\Cart')
    {
        if($this->products === null)
        {
            $cart = new $cartClassName();
            $this->products = $cart->getItems($this->data['ID']);
        }

        return $this->products;
    }
}






