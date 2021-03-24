<?php
//Last updated: 2019-09-27 09:24:36
namespace MillenniumFalcon\Core\ORM\Traits;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Service\ModelService;

trait OrderTrait
{
    protected $_orderItems;

    public function __construct(Connection $pdo)
    {
        $this->setCategory(0);
        $this->setCreateAnAccount(0);
        $this->setBillingSame(1);
        $this->setBillingSave(1);
        $this->setBillingUseExisting(0);
        $this->setShippingSave(1);
        $this->setShippingUseExisting(0);
        $this->setIsPickup(2);

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
            $result = $itm->update($this, $customer);
            if ($result) {
                $orderItemSubtotal = $itm->getPrice() * $itm->getQuantity();
                $orderItemWeight = $itm->getWeight() * $itm->getQuantity();

                $subtotal += $orderItemSubtotal;
                if ($this->getDiscountType() == 2 && !$itm->objVariant()->objProduct()->getNoPromoDiscount()) {
                    $discount += round($orderItemSubtotal * ($this->getDiscountValue() / 100), 2);
                }

                $weight += $orderItemWeight;
            }
        }


        if ($this->getDiscountType() == 1) {
            $discount = min($subtotal, $this->getDiscountValue());
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
     * @return mixed
     */
    public function objHummRequestQuery()
    {
        return (array)json_decode($this->getHummRequestQuery());
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