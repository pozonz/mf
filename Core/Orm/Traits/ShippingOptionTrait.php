<?php
//Last updated: 2019-09-27 10:00:30
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait ShippingOptionTrait
{
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
}