<?php
//Last updated: 2019-09-27 09:24:36
namespace MillenniumFalcon\Core\ORM\Traits;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Service\CartService;
use MillenniumFalcon\Core\Service\ModelService;

trait OrderTrait
{
    protected $_orderItems;

    public function __construct(Connection $pdo)
    {
        $this->setCategory(CartService::STATUS_UNPAID);
        $this->setBillingSame(1);
        parent::__construct($pdo);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function update($customer)
    {
        $fullClass = ModelService::fullClass($this->getPdo(), 'PromoCode');
        $promoCode = $fullClass::getByField($this->getPdo(), 'code', $this->getPromoCode());
        if ($promoCode && $promoCode->isValid()) {
            $this->setDiscountType($promoCode->getType());
            $this->setDiscountValue($promoCode->getValue());
            $this->setPromoId($promoCode->getId());
        } else {
            $this->setDiscountType(null);
            $this->setDiscountValue(null);
            $this->setPromoId(null);
        }

        $subtotal = 0;
        $weight = 0;
        $discount = 0;
        $afterDiscount = 0;

        $orderItems = $this->objOrderItems();
        foreach ($orderItems as $idx => $itm) {
            $itm->update($this, $customer);

            $itmSubtotal = $itm->getPrice() * $itm->getQuantity();

            $subtotal += $itmSubtotal;
            $weight += $itm->getWeight() * $itm->getQuantity();

            if ($this->getDiscountType() == 2) {
                $discount += round($itmSubtotal * ($this->getDiscountValue() / 100), 2);
            }
        }


        if ($this->getDiscountType() == 1) {
            $discount = $this->getDiscountValue();
        }

        $afterDiscount = $subtotal - $discount;
        $gst = ($afterDiscount * 3) / 23;
        $deliveryFee = $this->getShippingCost() ?: 0;
        $total = $afterDiscount + $deliveryFee;

        $this->setWeight($weight);
        $this->setSubtotal($subtotal);
        $this->setDiscount($discount);
        $this->setAfterDiscount($afterDiscount);
        $this->setTax($gst);
        $this->setShippingCost($deliveryFee);
        $this->setTotal($total);
        $this->save();
        return true;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objOrderItems()
    {
        if (!$this->_orderItems) {
            $fullClass = ModelService::fullClass($this->getPdo(), 'OrderItem');
            $this->_orderItems = $fullClass::active($this->getPdo(), array(
                'whereSql' => 'm.orderId = ?',
                'params' => array($this->getId()),
                'sort' => 'm.id',
                'order' => 'DESC',
            ));
        }
        return $this->_orderItems;
    }


    public function objJsonOrderItems()
    {
        return $this->objOrderItems();
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objCustomer()
    {
        $fullClass = ModelService::fullClass($this->getPdo(), 'Customer');
        return $fullClass::active($this->getPdo(), array(
            'whereSql' => 'm.id = ?',
            'params' => array($this->getCustomerId()),
            'limit' => 1,
            'oneOrNull' => 1,
        ));
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function objShippingOptions()
    {

    }

    /**
     * @return string
     */
    static public function getCmsOrmsTwig()
    {
        return 'cms/orms/orms-custom-order.html.twig';
    }

    /**
     * @return string
     */
    static public function getCmsOrmTwig()
    {
        return 'cms/orms/orm-custom-order.html.twig';
    }
}