<?php
//Last updated: 2019-09-16 21:43:24
namespace MillenniumFalcon\Core\Orm\Traits;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Service\ModelService;

trait ProductTrait
{
    /**
     * @param $customer
     * @return float|int
     */
    public function objCompareAtPrice($customer) {
        $price = $this->getCompareAtPrice() ?: 0;

        if ($this->getNoMemberDiscount() || gettype($customer) != 'object') {
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
    public function objFromPrice($customer) {
        $price = $this->getFromPrice() ?: 0;
        if ($this->getNoMemberDiscount() || gettype($customer) != 'object') {
            return $price;
        }
        $customerMembership = $customer->objMembership();
        if (!$customerMembership) {
            return $price;
        }
        return $price * ((100 - $customerMembership->getDiscount()) / 100);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function objRelatedProducts()
    {
        $result = $this->getRelatedProducts() ? json_decode($this->getRelatedProducts()) : [];
        if (!count($result)) {
            return [];
        }

        $ids = array_map(function ($itm) {
            return '?';
        }, $result);
        $sql = "m.id IN (" . join(',', $ids) . ")";

        $fullClass = ModelService::fullClass($this->getPdo(), 'Product');
        return $fullClass::data($this->getPdo(), [
            'whereSql' => $sql,
            'params' => $result,
        ]);
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
        $result = $this->getCategories() ? json_decode($this->getCategories()) : [];

        $sql = '';
        if (count($result)) {
            $ids = array_map(function ($itm) {
                return '?';
            }, $result);
            $sql = "m.id IN (" . join(',', $ids) . ")";
        }

        $fullClass = ModelService::fullClass($this->getPdo(), 'ProductCategory');
        return $fullClass::data($this->getPdo(), [
            'whereSql' => $sql,
            'params' => $result,
        ]);
    }


    public function objSuitableFor()
    {
        $pdo = $this->getPdo();
        $fullClass = ModelService::fullClass($pdo, 'ProductCategory');
        $tree = $tree = new \BlueM\Tree($fullClass::data($pdo, [
            "select" => 'm.id AS id, m.parentId AS parent, m.title',
            "sort" => 'm.rank',
            "order" => 'ASC',
            "orm" => 0,
        ]), [
            'rootId' => null,
        ]);

        $suitableFor = [];
        $objCategories = $this->objCategories();
        foreach ($objCategories as $objCategory) {
            $node = $tree->getNodeById($objCategory->getId());
            $ancesters = $node->getAncestors();
            $ancester = end($ancesters);

            if (!isset($suitableFor[$ancester->title])) {
                $suitableFor[$ancester->title] = [];
            }
            $suitableFor[$ancester->title][] = $objCategory->getTitle();
        }
        return $suitableFor;
    }

    /**
     * @return string
     */
    public function objTitle()
    {
        return rtrim(
            ($this->getSubtitle() ? "({$this->getSubtitle()}) " : '')
            . ($this->getBrand() ? "{$this->getBrand()} " : '')
            . ($this->getType() ? "{$this->getType()} " : '')
            . ($this->getTitle() ? "{$this->getTitle()} " : '')
        );
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
     * @param bool $doubleCheckExistence
     * @throws \Exception
     */
    public function save($doubleCheckExistence = false)
    {
        $this->objSuitableFor();

        $slugify = new Slugify(['trim' => false]);
        $this->setUrl($slugify->slugify("{$this->objTitle()}-{$this->getId()}", [
            'trim' => true,
        ]));

        $searchContent = $this->objTitle();
        $searchContent .= ' ' . $this->getSku();
        $searchContent .= ' ' . strip_tags($this->getDescription());
        foreach ($this->objCategories() as $itm) {
            $searchContent .= " {$itm->getTitle()} ";
        }

        //Set on sale active
        $this->setOnSaleActive($this->objOnSaleActive() ? 1 : 0);
        $this->setThumbnail($this->objThumbnail());

        $fullClass = ModelService::fullClass($this->getPdo(), 'ProductVariant');
        $data = $fullClass::active($this->getPdo(), [
            'whereSql' => 'm.productUniqid = ?',
            'params' => [$this->getUniqid()],
        ]);

        $outOfStock = 0;
        $price = null;
        foreach ($data as $itm) {
            if ($itm->getAlertIfLessThan() > $itm->getStock()) {
                $outOfStock = 1;
            }

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

        $this->setOutOfStock($outOfStock);
        $this->setContent($searchContent);

        parent::save($doubleCheckExistence);
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
        $obj->objThumbnail = $this->objThumbnail();
        return $obj;
    }
}