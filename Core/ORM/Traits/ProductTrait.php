<?php
//Last updated: 2019-09-16 21:43:24
namespace MillenniumFalcon\Core\ORM\Traits;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Service\ModelService;

trait ProductTrait
{
    protected $_gallery;

    protected $_variants;

    /**
     * @param $variant
     */
    public function addVariant($variant)
    {
        if (gettype($this->_variants) != 'array') {
            $this->_variants = [];
        }
        $this->_variants[] = $variant;
    }

    /**
     * To be overwritten
     * @return string
     */
    public function objImageUrl()
    {
        $gallery = $this->objGallery();
        return count($gallery) > 0 ? "/images/assets/{$gallery[0]->getId()}/medium" : "/images/assets/" . getenv('PRODUCT_PLACEHOLDER_ID') . "/1";
    }

    /**
     * To be overwritten
     * @return string
     */
    public function objImage()
    {
        $gallery = $this->objGallery();
        if (count($gallery) > 0) {
            return [
                $gallery[0]->getId(),
                'medium',
            ];
        } else {
            return [
                getenv('PRODUCT_PLACEHOLDER_ID'),
                1,
            ];
        }
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
                'sort' => 'CAST(myRank AS UNSIGNED)',
                'order' => 'ASC',
            ])));
        }
        return $this->_gallery;
    }

    /**
     * @return mixed|null
     * @throws \Exception
     */
    public function objVariant()
    {
        if ($this->_variants) {
            $this->objVariants();
        }
        return count($this->_variants) ? array_shift($this->_variants) : null;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objVariants()
    {
        if (!$this->_variants) {
            $fullClass = ModelService::fullClass($this->getPdo(), 'ProductVariant');
            $this->_variants = $fullClass::active($this->getPdo(), [
                'whereSql' => 'm.productUniqid = ? AND m.status = 1',
                'params' => [$this->getUniqid()],
            ]);
        }
        return $this->_variants;
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
        $this->_saveProductCachedData();
        return parent::save($doNotSaveVersion, $options);
    }

    /**
     * @param array $options
     */
    public function _saveProductCachedData($options = [])
    {
        $fullClass = ModelService::fullClass($this->getPdo(), 'ProductVariant');
        $data = $fullClass::active($this->getPdo(), [
            'whereSql' => 'm.productUniqid = ?',
            'params' => [$this->getUniqid()],
        ]);

        $lowStock = 0;
        $outOfStock = 0;

        foreach ($data as $itm) {
            if (!$itm->objOutOfStock() && $itm->objLowStock() == 1) {
                $lowStock++;
            }

            if ($itm->objOutOfStock() == 1) {
                $outOfStock++;
            }

            if (!isset($options['doNotUpdatePrice']) || $options['doNotUpdatePrice'] != 1) {
                if ($this->getPrice() == null || $this->getPrice() > $itm->getPrice()) {
                    $this->setPrice($itm->getPrice());
                    $this->setSalePrice($itm->getSalePrice());
                }
            }
        }

        $this->setLowStock($lowStock > 0 ? (count($data) == $lowStock ? 1 : 2) : 0);
        $this->setOutOfStock($outOfStock > 0 ? (count($data) == $outOfStock ? 1 : 2) : 0);
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
        return 'cms/orms/orms-custom-product.twig';
    }
}