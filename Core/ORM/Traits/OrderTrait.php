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
        $discountType = 0;
        $discountValue = 0;

        $fullClass = ModelService::fullClass($this->getPdo(), 'PromoCode');
        $promoCode = $fullClass::getByField($this->getPdo(), 'code', $this->getPromoCode());
        if ($promoCode && $promoCode->isValid()) {
            $discountType = $promoCode->getPerc() == 1 ? 1 : 2;
            $discountValue = $promoCode->getValue();
            if ($promoCode->getPerc() == 1) {
                $discountValue = round(($promoCode->getValue() / 100) * $resultPriceToDiscount, 2);
            }

            $this->setPromoId($promoCode->getId());
        } else {
            //invalid promo code
            $this->setPromoId(null);
        }

        $this->setDiscountType($discountType);
        $this->setDiscountValue($discountValue);

        $subtotalCompareAtPrice = 0;
        $subtotalPrice = 0;
        $subtotalWeight = 0;

        $orderItems = $this->objOrderItems();
        foreach ($orderItems as $idx => $itm) {
            $itm->update($this, $customer);

            $subtotalCompareAtPrice += ($itm->getCompareAtPrice() ?: $itm->getPrice()) * $itm->getQuantity();
            $subtotalPrice += $itm->getPrice() * $itm->getQuantity();
            $subtotalWeight += $itm->getWeight() * $itm->getQuantity();
        }

        $subtotalDiscount = $subtotalCompareAtPrice - $subtotalPrice;

        $gst = ($subtotalPrice * 3) / 23;
        $deliveryFee = $this->getShippingCost() ?: 0;
        $total = $subtotalPrice + $deliveryFee;

        $this->setWeight($subtotalWeight);
        $this->setSubtotal($subtotalCompareAtPrice);
        $this->setDiscount($subtotalDiscount);
        $this->setAfterDiscount($subtotalPrice);
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