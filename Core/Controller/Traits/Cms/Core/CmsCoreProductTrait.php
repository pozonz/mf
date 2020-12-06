<?php

namespace MillenniumFalcon\Core\Controller\Traits\Cms\Core;

use BlueM\Tree;
use BlueM\Tree\Node;
use Cocur\Slugify\Slugify;

use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Twig\Extension;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

trait CmsCoreProductTrait
{
    /**
     * @route("/manage/orms/ProductVariant/{ormId}")
     * @route("/manage/admin/orms/ProductVariant/{ormId}")
     * @route("/manage/pages/orms/ProductVariant/{ormId}")
     * @route("/manage/orms/ProductVariant/{ormId}/version/{versionUuid}")
     * @route("/manage/admin/orms/ProductVariant/{ormId}/version/{versionUuid}")
     * @route("/manage/pages/orms/ProductVariant/{ormId}/version/{versionUuid}")
     * @return Response
     */
    public function productVariant(Request $request, $ormId, $versionUuid = null)
    {
        $productUniqid = $request->get('productUniqid');

        $className = 'ProductVariant';
        $fullCalss = ModelService::fullClass($this->connection, $className);
        $data = $fullCalss::data($this->connection, [
            'joins' => 'LEFT JOIN product AS p ON p.uniqid = m.productUniqid',
            'whereSql' => 'p.id IS NULL AND m.productUniqid != ?',
            'params' => [$productUniqid],
        ]);
        foreach ($data as $itm) {
            $itm->delete();
        }

        if ($request->get('fragment') == 1 && $_SERVER['APP_ENV'] == 'dev') {
            $this->container->get('profiler')->disable();
        }

        $orm = $this->_orm($request, $className, $ormId);
        if ($versionUuid) {
            $orm = $orm->getByVersionUuid($versionUuid);
        }
        return $this->_ormPageWithForm($request, $className, $orm);
    }
}