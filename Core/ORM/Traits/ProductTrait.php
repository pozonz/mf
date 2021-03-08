<?php
//Last updated: 2019-09-16 21:43:24
namespace MillenniumFalcon\Core\ORM\Traits;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Service\CartService;
use MillenniumFalcon\Core\Service\ModelService;

trait ProductTrait
{
    protected $_gallery;

    /**
     * To be overwritten
     * @return string
     */
    public function objImageUrl()
    {
        $gallery = $this->objGallery();
        return count($gallery) > 0 ? "/images/assets/{$gallery[0]->getId()}/medium" : "/images/assets/" . getenv('PRODUCT_PLACEHOLDER_ID') . "/medium";
    }

    /**
     * @return string
     */
    public function objProductPageUrl()
    {
        return $this->getFrontendUrl();
    }

    /**
     * @return mixed
     */
    public function objGallery()
    {
        if (!$this->_gallery) {
            $fullClass = ModelService::fullClass($this->getPdo(), 'AssetOrm');
            $this->_gallery = array_filter(array_map(function ($itm) {
                $fullClass = ModelService::fullClass($this->getPdo(), 'Asset');
                return $fullClass::getById($this->getPdo(), $itm->getTitle());
            }, $fullClass::active($this->getPdo(), [
                'whereSql' => 'm.ormId = ?',
                'params' => [$this->getUniqid()],
            ])));
        }
        return $this->_gallery;
    }

    /**
     * @param $customer
     * @return float|int
     */
    public function calculatedSalePrice($customer)
    {
        $price = $this->getSalePrice() ?: 0;
        return CartService::getCalculatedPrice($this, $customer, $price);
    }

    /**
     * @param $customer
     * @return float|int
     */
    public function calculatedPrice($customer)
    {
        $price = $this->getPrice() ?: 0;
        return CartService::getCalculatedPrice($this, $customer, $price);
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objVariants()
    {
        $fullClass = ModelService::fullClass($this->getPdo(), 'ProductVariant');
        return $fullClass::active($this->getPdo(), [
            'whereSql' => 'm.productUniqid = ? AND m.status = 1',
            'params' => [$this->getUniqid()],
        ]);
    }

    /**
     * @return bool
     */
    public function objOnSaleActive()
    {
        if (!$this->getOnSale()) {
            return false;
        }
        if ($this->getSaleStart() && strtotime($this->getSaleStart()) > time()) {
            return false;
        }
        if ($this->getSaleEnd() && strtotime($this->getSaleEnd()) < time()) {
            return false;
        }
        return true;
    }

    /**
     * @param bool $doNotSaveVersion
     * @param array $options
     * @return mixed|null
     * @throws \Exception
     */
    public function save($doNotSaveVersion = false, $options = [])
    {
        $fullClass = ModelService::fullClass($this->getPdo(), 'ProductVariant');
        $data = $fullClass::active($this->getPdo(), [
            'whereSql' => 'm.productUniqid = ?',
            'params' => [$this->getUniqid()],
        ]);

        $lowStock = 0;
        $outOfStock = 1;

        foreach ($data as $itm) {
            if ($itm->objLowStock() == 1) {
                $lowStock = 1;
            }

            if ($itm->objOutOfStock() == 0) {
                $outOfStock = 0;
            }

            if ($this->getPrice() == null || $this->getPrice() > $itm->getPrice()) {
                $this->setPrice($itm->getPrice());
                $this->setSalePrice($itm->getSalePrice());
            }
        }

        $this->setLowStock($lowStock);
        $this->setOutOfStock($outOfStock);

        return parent::save($doNotSaveVersion, $options);
    }

    /**
     * @return string
     */
    static public function getCmsOrmTwig()
    {
        return 'cms/orms/orm-custom-product.twig';
    }

    /**
     * @return string
     */
    static public function getCmsOrmsTwig()
    {
        return 'cms/orms/orms-custom-product.html.twig';
    }
}