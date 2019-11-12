<?php
//Last updated: 2019-09-27 09:55:00
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait OrderItemTrait
{
    /**
     * @return mixed
     * @throws \Exception
     */
    public function objProductVariant() {
        $fullClass = ModelService::fullClass($this->getPdo(), 'ProductVariant');
        return $fullClass::getById($this->getPdo(), $this->getProductId());
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
        $obj->objProductVariant = $this->objProductVariant();
        return $obj;
    }

    /**
     * @return bool
     */
    public function update($customer) {
        $variant = $this->objProductVariant();
        if (!$variant) {
            $this->delete();
            return false;
        }
        $product = $variant->objProduct();

        if ($product->getOnSaleActive()) {
            $this->setOnSaleActive($product->getOnSaleActive());
            $this->setPrice($variant->objSalePrice($customer));
            $this->setCompareAtPrice($variant->objPrice($customer));
        } else {
            $this->setPrice($variant->objPrice($customer));
        }

        $this->setTotalPrice(($this->getPrice() ?: 0) * $this->getQuantity());
        $this->setTotalWeight(($variant->getWeight() ?: 0) * $this->getQuantity());
        $this->save();
        return true;
    }
}