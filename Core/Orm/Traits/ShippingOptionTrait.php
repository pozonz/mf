<?php
//Last updated: 2019-09-27 10:00:30
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait ShippingOptionTrait
{

    protected $price;

    /**
     * @param $pdo
     */
    static public function initData($pdo, $container)
    {

    }
    
    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price ?: 0;
    }

    /**
     * @param float $price
     */
    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    /**
     * @param $orderContainer
     * @throws \Exception
     */
    public function calculatePrice($orderContainer)
    {
        $this->setPrice(0);

        $countryCode = $orderContainer->getCountryCode();
        if ($countryCode) {
            $fullClass = ModelService::fullClass($this->getPdo(), 'ShippingCountry');
            $country = $fullClass::getByField($this->getPdo(), 'code', $countryCode);
            if ($country) {
                $selectedBlock = null;
                $objContent = $this->objContent();
                foreach ($objContent as $section) {
                    foreach ($section->blocks as $block) {
                        if (in_array($country->getId(), $block->values->countries)) {
                            $selectedBlock = $block;
                        }
                    }
                }
                if ($selectedBlock) {
                    if (!$selectedBlock->values->basePrice) {
                        $this->setPrice(0);
                    }

                    $totalWeight = $orderContainer->getWeight();
                    if ($totalWeight <= $selectedBlock->values->baseWeight || !$selectedBlock->values->baseWeight) {
                        $this->setPrice($selectedBlock->values->basePrice);
                    }

                    if ($selectedBlock->values->baseWeight && $totalWeight > $selectedBlock->values->baseWeight) {
                        $units = ceil(($totalWeight - $selectedBlock->values->baseWeight) / $selectedBlock->values->extraWeight);
                        $deliveryFee = $selectedBlock->values->basePrice + ($units * $selectedBlock->values->extraPrice);
                        $this->setPrice($deliveryFee);
                    }
                }
            }
        }
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
        $result = array();
        $objContent = $this->objContent();
        foreach ($objContent as $section) {
            foreach ($section->blocks as $block) {
                $result = array_merge($result, $block->values->countries);
            }
        }
        return $result;
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
        $obj->price = $this->getPrice();
        return $obj;
    }
}