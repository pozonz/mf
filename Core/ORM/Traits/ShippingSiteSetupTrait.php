<?php
//Last updated: 2019-09-27 09:57:41
namespace MillenniumFalcon\Core\ORM\Traits;

trait ShippingSiteSetupTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo)
    {
        $orm = new static($pdo);
        $orm->setTitle('ShippingFlatCost');
        $orm->save();
    }

    /**
     * @return string
     */
    static public function getCmsOrmsTwig()
    {
        return 'cms/orms/orms-custom-shipping-site-setup.twig';
    }
}