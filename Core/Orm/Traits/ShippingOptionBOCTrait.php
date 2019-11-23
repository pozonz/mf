<?php
//Last updated: 2019-11-10 12:33:36
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait ShippingOptionBOCTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo, $container)
    {

    }
    
    /**
     * @param $orderContainer
     * @throws \Exception
     */
    public function calculatePrice($orderContainer)
    {
        return $this->getPrice();
    }

    /**
     * @return array|Country[]
     */
    public function objCountries()
    {
        $countries = array();
        $fullClass = ModelService::fullClass($this->getPdo(), 'ShippingCountry');
        $result = $fullClass::active($this->getPdo());
        foreach ($result as $itm) {
            $countries[$itm->getId()] = $itm;
        }

        $result = array();
        $objCountryIds = $this->objCountryIds();
        foreach ($objCountryIds as $itm) {
            if (isset($countries[$itm])) {
                $result[] = $countries[$itm];
            }
        }
        return $result;
    }

    /**
     * @return array
     */
    public function objCountryIds()
    {
        return json_decode($this->getCountries());
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
        $obj->price = $this->getPrice();
        return $obj;
    }
}