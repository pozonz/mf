<?php
//Last updated: 2019-09-16 21:43:24
namespace MillenniumFalcon\Core\ORM\Traits;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Service\ModelService;

trait ProductTrait
{
    protected $_gallery;

    protected $_variants;

    protected $_brand = null;

    protected $_category = null;

    protected $_categories = [];

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
     *
     */
    public function objThumbnail()
    {
        $gallery = $this->objGallery();
        if (count($gallery) > 0) {
            return $gallery[0];
        } else {
            return [
                'id' => getenv('PRODUCT_PLACEHOLDER_ID'),
                'code' => null,
            ];
        }
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
        if (!$this->_variants) {
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
            $inStock = $fullClass::active($this->getPdo(), [
                'whereSql' => 'm.productUniqid = ? AND m.status = 1 AND (m.stockEnabled != 1 OR m.stock > 0)',
                'params' => [$this->getUniqid()],
            ]);
            $outOfStock = $fullClass::active($this->getPdo(), [
                'whereSql' => 'm.productUniqid = ? AND m.status = 1 AND (m.stockEnabled = 1 AND m.stock <= 0)',
                'params' => [$this->getUniqid()],
            ]);
            $this->_variants = array_merge($inStock, $outOfStock);
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
        $gallery = $this->objGallery();
        $this->setThumbnail(count($gallery) > 0 ? $gallery[0]->getId() : null);
        
        $this->_saveProductCachedData();
        $result = parent::save($doNotSaveVersion, $options);


        $pdo = $this->getPdo();
        $tableName = static::getTableName();
        $sql = "UPDATE `$tableName` SET `slug` = ? WHERE `id` = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([ $this->getSlug() . '-' . $this->getId(), $this->getId() ]);
 
        return $result;
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

        $this->setPrice(null);
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
     * @return array|null
     */
    public function objCategory()
    {
        if (!$this->_category) {
            $categories = $this->objCategories();
            $this->_category = array_shift($categories);
        }
        return $this->_category;
    }

    /**
     * @return array|null
     */
    public function objCategories()
    {
        if (!$this->_categories) {
            $fullClass = ModelService::fullClass($this->getPdo(), 'ProductCategory');
            $this->_categories = array_filter(array_map(function ($itm) use ($fullClass) {
                return $fullClass::getById($this->getPdo(), $itm);
            }, json_decode($this->getCategories() ?: '[]')));
        }
        return $this->_categories;
    }

    /**
     * @param $brand
     */
    public function setObjBrand($brand)
    {
        $this->_brand = $brand;
    }

    /**
     * @return array|null
     */
    public function objBrand()
    {
        if (!$this->_brand) {
            $fullClass = ModelService::fullClass($this->getPdo(), 'ProductBrand');
            $this->_brand = $fullClass::getById($this->getPdo(), $this->getBrand());
        }
        return $this->_brand && $this->_brand !== -1 && $this->_brand->getStatus() ? $this->_brand : null;
    }

    /**
     * @return array
     */
    public function objPriceFromAndTo()
    {
        $product = $this;

        $variants = $product->objVariants();

        $variantsPrices = array_map(function($o) use ($product) {
            if ($product->getOnSale() == 1 && date('Y-m-d') >= $product->getSaleStart() && date('Y-m-d') <= $product->getSaleEnd()){
                return $o->getSalePrice() === null ? $o->getPrice() : $o->getSalePrice();
            }
            return $o->getPrice() === null ? 0 : $o->getPrice();
        }, $variants);

        return [
            'priceFrom' => min($variantsPrices),
            'priceTo' => max($variantsPrices),
        ];
    }

    /**
     * @param $num
     * @return array
     */
    public function objRelatedProducts($num = 3)
    {
        $fullClass = ModelService::fullClass($this->getPdo(), 'Product');

        $objRelatedProducts = explode(',', $this->getRelatedProducts());
        $objRelatedProducts = array_filter(array_map(function ($itm) use ($fullClass) {
            return $fullClass::getById($this->getPdo(), $itm);
        }, $objRelatedProducts));

        if (!count($objRelatedProducts)) {
            $objRelatedProducts = $fullClass::active($this->getPdo(), [
                'whereSql' => 'm.id != ?',
                'params' => [$this->getId()],
                'limit' => $num,
                'sort' => 'rand()',
            ]);
        }
        return $objRelatedProducts;
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