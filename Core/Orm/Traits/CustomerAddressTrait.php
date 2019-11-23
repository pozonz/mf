<?php
//Last updated: 2019-09-27 10:39:11
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait CustomerAddressTrait
{
    protected $country;

    /**
     * @param $pdo
     */
    static public function initData($pdo, $container)
    {

    }
    
    /**
     * @return string
     */
    public function __toString()
    {
        return
            $this->getFirstname() . ' ' . $this->getLastname() . ' (PH: ' . $this->getPhone() . '), ' .
            $this->getAddress() . ($this->getAddress2() ? ', ' . $this->getAddress2() : '') . ', ' .
            $this->getCity() . ' ' . $this->getPostcode() . ($this->getState() ? ', ' . $this->getState() : '') .
            ($this->objCountry() ? ', ' . $this->objCountry()->getTitle() : '');
    }

    /**
     *
     */
    public function delete() {
        if ($this->getPrimaryAddress() == 1) {
            $customerAddresses = static::active($this->getPdo(), array(
                'whereSql' => 'm.customerId = ? AND m.id != ?',
                'params' => array($this->getCustomerId(), $this->getId()),
            ));

            if (count($customerAddresses)) {
                $customerAddresses[0]->setPrimaryAddress(1);
                $customerAddresses[0]->save();
            }
        }
        parent::delete();
    }

    /**
     * @return Country|null
     */
    public function objCountry() {
        if ($this->country) {
            $fullClass = ModelService::fullClass($this->getPdo(), 'ShippingCountry');
            $this->country = $fullClass::getByField($this->getPdo(), 'code', $this->getCountry());
        }
        return $this->country;
    }
}