<?php

namespace MillenniumFalcon\Cart\ControllerTraits;

use MillenniumFalcon\Cart\Form\CheckoutAccountForm;
use MillenniumFalcon\Cart\Form\CheckoutPaymentForm;
use MillenniumFalcon\Cart\Form\CheckoutShippingForm;
use MillenniumFalcon\Core\SymfonyKernel\RedirectException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use MillenniumFalcon\Core\Service\ModelService;
use Twig\Environment;

trait ShopPageTrait
{
    /**
     * @route("/shop")
     * @route("/shop/{categories}", requirements={"categories" = ".*"})
     * @param Request $request
     * @return mixed
     */
    public function shop(Request $request, $categories = null)
    {
        $category = null;
        if ($categories) {
            $categories = explode('/', $categories);
            $category = array_pop($categories);
        }
        $params = array_merge($this->getTemplateParamsByUrl('/shop'), $this->filterProductResult($request, $category));
        return $this->render('/cart/products.twig', $params);
    }

    /**
     * @route("/product/{slug}")
     * @param Request $request
     * @return mixed
     */
    public function product(Request $request, $slug)
    {
        $params = $this->getTemplateParams($request);

        $fullClass = ModelService::fullClass($this->connection, 'Product');
        $params['orm'] = $fullClass::getBySlug($this->connection, $slug);
        return $this->render('/cart/product.twig', $params);
    }

    /**
     * @param Request $request
     * @param null $category
     * @return array
     */
    protected function filterProductResult(Request $request, $category = null)
    {
        $limit = getenv('PRODUCT_LISTING_LIMIT') ?: 21;
        $productCategorySlug = $category ?? $request->get('category');
        $productBrandSlug = $request->get('brand');
        $productKeyword = $request->get('keyword');
        $pageNum = $request->get('pageNum') ?: 1;
        $sortby = $request->get('sortby');
        $sort = 'CAST(m.pageRank AS UNSIGNED)';
        $order = 'DESC';

        if ($sortby == 'price-high-to-low') {
            $sort = 'CAST(m.price AS UNSIGNED)';
            $order = 'DESC';
        } elseif ($sortby == 'price-low-to-high') {
            $sort = 'CAST(m.price AS UNSIGNED)';
            $order = 'ASC';
        } elseif ($sortby == 'newest') {
            $sort = 'm.added';
            $order = 'DESC';
        } elseif ($sortby == 'oldest') {
            $sort = 'm.added';
            $order = 'ASC';
        }

        $productCategoryFullClass = ModelService::fullClass($this->connection, 'ProductCategory');
        $productBrandFullClass = ModelService::fullClass($this->connection, 'ProductBrand');
        $productFullClass = ModelService::fullClass($this->connection, 'Product');

        $allBrands = $productBrandFullClass::active($this->connection);
        $brands = array_filter($allBrands, function ($itm) use ($limit, $productCategorySlug, $productKeyword, $pageNum, $sortby, $sort, $order, $allBrands) {
            $result = $this->_filterProductResult($limit, $productCategorySlug, $itm->getSlug(), $productKeyword, $pageNum, $sortby, $sort, $order, $allBrands, true);
            return $result['total']['count'] > 0 ? 1 : 0;
        });

        return array_merge($this->_filterProductResult($limit, $productCategorySlug, $productBrandSlug, $productKeyword, $pageNum, $sortby, $sort, $order, $brands), [
            'brands' => $brands,
        ]);
    }

    /**
     * @param $limit
     * @param $productCategorySlug
     * @param $productBrandSlug
     * @param $productKeyword
     * @param $pageNum
     * @param $sortby
     * @param $sort
     * @param $order
     * @param $brands
     * @return array
     */
    protected function _filterProductResult($limit, $productCategorySlug, $productBrandSlug, $productKeyword, $pageNum, $sortby, $sort, $order, $brands, $productCountOnly = false)
    {
        if ($sortby == 'price-high-to-low') {
            $sort = 'CAST(m.price AS UNSIGNED)';
            $order = 'DESC';
        } elseif ($sortby == 'price-low-to-high') {
            $sort = 'CAST(m.price AS UNSIGNED)';
            $order = 'ASC';
        } elseif ($sortby == 'newest') {
            $sort = 'm.added';
            $order = 'DESC';
        } elseif ($sortby == 'oldest') {
            $sort = 'm.added';
            $order = 'ASC';
        }

        $productCategoryFullClass = ModelService::fullClass($this->connection, 'ProductCategory');
        $productBrandFullClass = ModelService::fullClass($this->connection, 'ProductBrand');
        $productFullClass = ModelService::fullClass($this->connection, 'Product');

        $categories = new \BlueM\Tree($productCategoryFullClass::active($this->connection, [
            "select" => 'm.id AS id, m.parentId AS parent, m.title, m.slug, m.status',
            "sort" => 'm.rank',
            "order" => 'ASC',
            "orm" => 0,
        ]), [
            'rootId' => null,
        ]);

        $selectedProductCategory = $productCategoryFullClass::getBySlug($this->connection, $productCategorySlug);
        if ($selectedProductCategory) {
            $selectedProductCategory = $categories->getNodeById($selectedProductCategory->getId());
        }

        $selectedProductBrand = null;
        foreach ($brands as $itm) {
            if ($itm->getSlug() == $productBrandSlug) {
                $selectedProductBrand = $itm;
            }
        }

        $whereSql = '';
        $params = [];

        if ($selectedProductCategory) {
            $descendants = $selectedProductCategory->getDescendants();
            $selectedProductCategoryIds = array_merge([$selectedProductCategory->get('id')], array_map(function ($itm) {
                return $itm->getId();
            }, $descendants));

            $s = array_map(function ($itm) {
                return "m.categories LIKE ?";
            }, $selectedProductCategoryIds);
            $p = array_map(function ($itm) {
                return '%"' . $itm . '"%';
            }, $selectedProductCategoryIds);
            $whereSql .= ($whereSql ? ' AND ' : '') . '(' . implode(' OR ', $s) . ')';
            $params = array_merge($params, $p);
        }

        if ($selectedProductBrand) {
            $whereSql .= ($whereSql ? ' AND ' : '') . '(m.brand = ?)';
            $params = array_merge($params, [$selectedProductBrand->getId()]);
        }

        if ($productKeyword) {
            $whereSql .= ($whereSql ? ' AND ' : '') . '(m.title LIKE ? OR m.sku LIKE ? OR m.description LIKE ?)';
            $params = array_merge($params, ['%' . $productKeyword . '%', '%' . $productKeyword . '%', '%' . $productKeyword . '%']);
        }

        $products = null;
        if (!$productCountOnly) {
            $products = $productFullClass::active($this->connection, [
                'whereSql' => $whereSql,
                'params' => $params,
                'page' => $pageNum,
                'limit' => $limit,
                'sort' => $sort,
                'order' => $order,
                'debug' => 0,
            ]);
        }

        $total = $productFullClass::active($this->connection, [
            'whereSql' => $whereSql,
            'params' => $params,
            'count' => 1
        ]);

        $pageTotal = ceil($total['count'] / $limit);

        return [
            'orms' => $products,
            'categories' => $categories,
            'selectedProductCategory' => $selectedProductCategory,
            'selectedProductBrand' => $selectedProductBrand,
            'productKeyword' => $productKeyword,
            'pageNum' => $pageNum,
            'pageTotal' => $pageTotal,
            'total' => $total,
            'sortby' => $sortby,
            'limit' => $limit,
        ];
    }
}