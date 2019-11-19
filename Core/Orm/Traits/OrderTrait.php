<?php
//Last updated: 2019-09-27 09:24:36
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait OrderTrait
{
    protected $orderItems;

    /**
     * @param $pdo
     */
    static public function initData($pdo, $container)
    {

    }
    
    /**
     * @return mixed
     */
    public function getCountryCode()
    {
        return $this->getBillingSame() ? $this->getBillingCountry() : $this->getShippingCountry();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function objShippingOptions()
    {
        $countryCode = $this->getCountryCode();
        $fullClass = ModelService::fullClass($this->getPdo(), 'ShippingCountry');
        $country = $fullClass::getByField($this->getPdo(), 'code', $countryCode);

        $fullClass = ModelService::fullClass($this->getPdo(), 'ShippingOptionMethod');
        $method = $fullClass::getByField($this->getPdo(), 'selected', 1);
        if ($method->getClassName() == 'ShippingOptionFlat') {

            $fullClass = ModelService::fullClass($this->getPdo(), $method->getClassName());
            $result = $fullClass::active($this->getPdo());

            if (!$country) {
                foreach ($result as $itm) {
                    $itm->setPrice(-1);
                }
                return $result;
            }

            $shippingOptions = [];
            foreach ($result as $itm) {
                $objCountryIds = $itm->objCountryIds();
                if (in_array($country->getId(), $objCountryIds)) {
                    $shippingOptions[] = $itm;
                }
            }
            return $shippingOptions;

        } elseif ($method->getClassName() == 'ShippingOption') {

            $fullClass = ModelService::fullClass($this->getPdo(), $method->getClassName());
            $result = $fullClass::active($this->getPdo());
            foreach ($result as $itm) {
                $itm->setPrice(-1);
            }

            if (!$country) {
                return $result;
            }

            $shippingOptions = [];
            foreach ($result as $itm) {
                $itm->calculatePrice($this);
                $valid = false;
                $objContent = $itm->objContent();
                foreach ($objContent as $section) {
                    foreach ($section->blocks as $block) {
                        if (in_array($country->getId(), $block->values->countries)) {
                            $valid = true;
                        }
                    }
                }
                if ($valid) {
                    $itm->calculatePrice($this);
                    $shippingOptions[] = $itm;
                }
            }
            return $shippingOptions;

        } elseif ($method->getClassName() == 'ShippingOptionBOC') {

            $fullClass = ModelService::fullClass($this->getPdo(), $method->getClassName());
            $result = $fullClass::active($this->getPdo());

            if (!$country) {
                foreach ($result as $itm) {
                    $itm->setPrice(-1);
                }
            }

            $shippingOptions = [];
            foreach ($result as $itm) {
                $afterDiscount = $this->getAfterDiscount();
                if ($afterDiscount >= $itm->getFrom() && $afterDiscount <= $itm->getTo()) {
                    $shippingOptions[] = $itm;
                }
            }
            return $shippingOptions;

        }


    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function update($customer)
    {
        $shippingValid = false;
        $objShippingOptions = $this->objShippingOptions();
        foreach ($objShippingOptions as $objShippingOption) {
            if ($objShippingOption->getId() == $this->getShippingId()) {
                $shippingValid = true;
            }
        }

        if (!$shippingValid) {
            $this->setShippingId(null);
            $this->setShippingTitle(null);
            $this->setShippingCost(null);
        }

        $resultPrice = 0;
        $resultPriceToDiscount = 0;
        $resultWeight = 0;

        $orderItems = $this->objOrderItems();
        foreach ($orderItems as $idx => $itm) {
            $itm->update($customer);

            $variant = $itm->objProductVariant();
            if ($variant) {
                $product = $variant->objProduct();
                if (!$product->getNoPromoDiscount()) {
                    $resultPriceToDiscount += $itm->getTotalPrice();
                }

                $resultPrice += $itm->getTotalPrice();
                $resultWeight += $itm->getTotalWeight();
            }
        }
        $subtotal = $resultPrice;
        $weight = $resultWeight;

        $discount = 0;
        $freeDelivery = 0;

        $fullClass = ModelService::fullClass($this->getPdo(), 'PromoCode');
        $promoCode = $fullClass::getByField($this->getPdo(), 'code', $this->getPromoCode());
        if ($promoCode && $promoCode->isValid()) {
            if ($promoCode->getPerc() == 1) {
                $discount = round(($promoCode->getValue() / 100) * $resultPriceToDiscount, 2);
            } else {
                $discount = $promoCode->getValue();
            }
        }

        $afterDiscount = $subtotal - $discount;
        $gst = round(($afterDiscount * 3) / 23, 2);

        $deliveryFee = $this->getShippingCost() ?: 0;
        $total = $afterDiscount + max($deliveryFee, 0);

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
        if (!$this->orderItems) {
            $fullClass = ModelService::fullClass($this->getPdo(), 'OrderItem');
            $this->orderItems = $fullClass::active($this->getPdo(), array(
                'whereSql' => 'm.orderId = ?',
                'params' => array($this->getId()),
            ));
        }
        return $this->orderItems;
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
     *
     */
    public function clearOrderItemsCache()
    {
        $this->orderItems = null;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $obj = parent::jsonSerialize();
        $obj->countryCode = $this->getCountryCode();
        $obj->objShippingOptions = $this->objShippingOptions();
        $obj->objOrderItems = $this->objOrderItems();
        return $obj;
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