<?php
//Last updated: 2019-09-16 21:43:24
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Service\ModelService;

trait ProductTrait
{

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
     * @return |null
     */
    public function objThumbnail()
    {
        if ($this->getThumbnail()) {
            return $this->getThumbnail();
        }
        $objGallery = $this->objGallery();
        if (count($objGallery) > 0) {
            return $objGallery[0]->getTitle();
        }
        return null;
    }

    /**
     * @return array|null
     */
    public function objCategories()
    {
        $fullClass = ModelService::fullClass($this->getPdo(), 'ProductCategory');
        $categories = [];
        $sql = '';
        $params = [];
        $result = $this->getCategories() ? json_decode($this->getCategories()) : [];
        foreach ($result as $itm) {
            $categories[] = $fullClass::getById($this->getPdo(), $itm);
        }
        return $categories;
    }

    /**
     * @param bool $doubleCheckExistence
     * @throws \Exception
     */
    public function save($doubleCheckExistence = false)
    {
        $searchContent = '';
        foreach ($this->objCategories() as $itm) {
            $searchContent .= "{$itm->getTitle()} ";
        }

        //Set on sale active
        $this->setOnSaleActive($this->objOnSaleActive() ? 1 : 0);
        $this->setThumbnail($this->objThumbnail());

        $fullClass = ModelService::fullClass($this->getPdo(), 'ProductVariant');
        $data = $fullClass::active($this->getPdo(), [
            'whereSql' => 'm.productUniqid = ?',
            'params' => [$this->getUniqid()],
        ]);

        $price = null;
        foreach ($data as $itm) {
            $searchContent .= "{$itm->getTitle()} {$itm->getSku()} ";
            if ($price === null || $price > $itm->getPrice()) {
                $price = $itm->getPrice();
                if ($this->getOnSaleActive()) {
                    $this->setFromPrice($itm->getSalePrice() ?: $price);
                    $this->setCompareAtPrice($price);
                } else {
                    $this->setFromPrice($price);
                    $this->setCompareAtPrice(null);
                }
            }
        }

        $this->setContent($searchContent);

        parent::save($doubleCheckExistence);
    }

    /**
     * @return array|null
     */
    public function objGallery()
    {
        $fullClass = ModelService::fullClass($this->getPdo(), 'AssetOrm');
        return $fullClass::active($this->getPdo(), array(
            'whereSql' => 'm.modelName = ? AND m.attributeName = ? AND m.ormId = ?',
            'params' => array('Product', 'orm_gallery', $this->getUniqid()),
            'sort' => 'm.myRank',
//            'debug' => 1,
        ));
    }

    /**
     * @return string
     */
    static public function getCmsOrmsTwig()
    {
        return 'cms/orms/orms-custom-product.html.twig';
    }

    /**
     * @return string
     */
    static public function getCmsOrmTwig()
    {
        return 'cms/orms/orm-custom-product.html.twig';
    }
}