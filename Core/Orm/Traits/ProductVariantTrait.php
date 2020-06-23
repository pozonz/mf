<?php
//Last updated: 2019-09-21 10:12:35
namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait ProductVariantTrait
{
    protected $product;

    /**
     * @param $pdo
     */
    static public function initData($pdo)
    {

    }
    
    /**
     * @param $customer
     * @return float|int
     */
    public function objSalePrice($customer) {
        $price = $this->getSalePrice() ?: 0;

        $product = $this->objProduct();
        if ($product->getNoMemberDiscount() || gettype($customer) != 'object') {
            return $price;
        }
        $customerMembership = $customer->objMembership();
        if (!$customerMembership) {
            return $price;
        }
        return $price * ((100 - $customerMembership->getDiscount()) / 100);
    }

    /**
     * @param $customer
     * @return float|int
     */
    public function objPrice($customer) {
        $price = $this->getPrice() ?: 0;

        $product = $this->objProduct();
        if ($product->getNoMemberDiscount() || gettype($customer) != 'object') {
            return $price;
        }
        $customerMembership = $customer->objMembership();
        if (!$customerMembership) {
            return $price;
        }
        return $price * ((100 - $customerMembership->getDiscount()) / 100);
    }

    /**
     * @param bool $doNotSaveVersion
     * @param array $options
     * @return mixed|null
     * @throws \Exception
     */
    public function save($doNotSaveVersion = false, $options = [])
    {
        $result = parent::save($doNotSaveVersion, $options);

        $orm = $this->objProduct();
        if ($orm) {
            $orm->save();
        }
        return $result;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objProduct()
    {
        if (!$this->product) {
            $fullClass = ModelService::fullClass($this->getPdo(), 'Product');
            $this->product = $fullClass::getByField($this->getPdo(), 'uniqid', $this->getProductUniqid());
        }
        return $this->product;
    }

    /**
     * @return mixed
     */
    public function objContent()
    {
        return json_decode($this->getContent());
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
        $obj->objProduct = $this->objProduct();
        return $obj;
    }
}