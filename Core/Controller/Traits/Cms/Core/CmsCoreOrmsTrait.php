<?php

namespace MillenniumFalcon\Core\Controller\Traits\Cms\Core;

use BlueM\Tree;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use MillenniumFalcon\Core\Tree\RawData;

trait CmsCoreOrmsTrait
{
    /**
     * @route("/manage/admin/orms/Page")
     * @return Response
     */
    public function pages(Request $request)
    {
        $params = $this->getCmsTemplateParams($request);

        $model = _Model::getByField($this->connection, 'className', 'Page');
        $params['ormModel'] = $model;

        $fullClass = ModelService::fullClass($this->connection, 'PageCategory');
        $categories = $fullClass::active($this->connection);
        $cat = $request->get('cat') || $request->get('cat') === '0' ? $request->get('cat') : (count($categories) == 0 ? 0 : $categories[0]->getId());
        $params['categories'] = $categories;
        $params['cat'] = $cat;

        return $this->render($params['theNode']->template, $params);
    }

    /**
     * @Route("/manage/orms/Redirect")
     * @param Request $request
     * @return Response
     */
    public function redirects(Request $request)
    {
        $params = $this->getCmsTemplateParams($request);

        $model = _Model::getByField($this->connection, 'className', 'Redirect');
        $params['ormModel'] = $model;

        $fullClass = ModelService::fullClass($this->connection, $model->getClassName());

        $pageNum = $request->get('pageNum') ?: 1;
        $sort = $request->get('sort') ?: $model->getDefaultSortBy();
        $order = $request->get('order') ?: ($model->getDefaultOrder() == 0 ? 'ASC' : 'DESC');
        $keyword = $request->get('keyword') ?: '';
        $status = $request->get('status') ?: 0;

        $params['filterStatus'] = $status;
        $params['filterKeyword'] = $keyword;

        $sqlWhere = '';
        $sqlParams = [];

        if ($status != 0) {
            $sqlWhere .= ($sqlWhere ? ' AND ' : '') . '(m.status = ?)';
            $sqlParams = array_merge($sqlParams, [
                $status == 1 ? 1 : 0
            ]);
        }

        if ($keyword) {
            $sqlWhere .= ($sqlWhere ? ' AND ' : '') . '(m.title LIKE ? OR m.to LIKE ?)';
            $sqlParams = array_merge($sqlParams, [
                "%$keyword%", "%$keyword%"
            ]);
        }

        $orms = $fullClass::data($this->connection, [
            "whereSql" => $sqlWhere,
            "params" => $sqlParams,
            "page" => $pageNum,
            "limit" => $model->getNumberPerPage(),
            "sort" => $sort,
            "order" => $order,
        ]);

        $total = $fullClass::data($this->connection, [
            "whereSql" => $sqlWhere,
            "params" => $sqlParams,
            "count" => 1,
        ]);

        $params['total'] = $total['count'];
        $params['totalPages'] = ceil($total['count'] / $model->getNumberPerPage());
        $params['url'] = $request->getPathInfo() . "?sort=$sort&order=$order";
        $params['urlNoSort'] = $request->getPathInfo();
        $params['pageNum'] = $pageNum;
        $params['sort'] = $sort;
        $params['order'] = $order;
        $params['orms'] = $orms;

        return $this->render($params['theNode']->template, $params);
    }

    /**
     * @Route("/manage/orms/Order")
     * @param Request $request
     * @return Response
     */
    public function orders(Request $request)
    {
        $params = $this->getCmsTemplateParams($request);

        $model = _Model::getByField($this->connection, 'className', 'Order');
        $params['ormModel'] = $model;

        $fullClass = ModelService::fullClass($this->connection, $model->getClassName());

        $pageNum = $request->get('pageNum') ?: 1;
        $sort = $request->get('sort') ?: $model->getDefaultSortBy();
        $order = $request->get('order') ?: ($model->getDefaultOrder() == 0 ? 'ASC' : 'DESC');
        $keyword = $request->get('keyword') ?: '';
        $status = $request->get('status') ?: 0;

//        $params['filterStatus'] = $status;
//        $params['filterKeyword'] = $keyword;

        $sqlWhere = 'm.category != ?';
        $sqlParams = [0];

//        if ($status != 0) {
//            $sqlWhere .= ($sqlWhere ? ' AND ' : '') . '(m.status = ?)';
//            $sqlParams = array_merge($sqlParams, [
//                $status == 1 ? 1 : 0
//            ]);
//        }
//
//        if ($keyword) {
//            $sqlWhere .= ($sqlWhere ? ' AND ' : '') . '(m.title LIKE ? OR m.to LIKE ?)';
//            $sqlParams = array_merge($sqlParams, [
//                "%$keyword%", "%$keyword%"
//            ]);
//        }

        $orms = $fullClass::data($this->connection, [
            "whereSql" => $sqlWhere,
            "params" => $sqlParams,
            "page" => $pageNum,
            "limit" => $model->getNumberPerPage(),
            "sort" => $sort,
            "order" => $order,
        ]);

        $total = $fullClass::data($this->connection, [
            "whereSql" => $sqlWhere,
            "params" => $sqlParams,
            "count" => 1,
        ]);

        $params['total'] = $total['count'];
        $params['totalPages'] = ceil($total['count'] / $model->getNumberPerPage());
        $params['url'] = $request->getPathInfo() . "?sort=$sort&order=$order";
        $params['urlNoSort'] = $request->getPathInfo();
        $params['pageNum'] = $pageNum;
        $params['sort'] = $sort;
        $params['order'] = $order;
        $params['orms'] = $orms;

        return $this->render($params['theNode']->template, $params);
    }

    /**
     * @route("/manage/orms/{className}")
     * @route("/manage/admin/orms/{className}")
     * @route("/manage/pages/orms/{className}")
     * @return Response
     */
    public function orms(Request $request, $className)
    {
        $params = $this->getCmsTemplateParams($request);

        $model = _Model::getByField($this->connection, 'className', $className);
        $params['ormModel'] = $model;

        $fullClass = ModelService::fullClass($this->connection, $model->getClassName());
        if ($model->getListType() == 0) {
            $orms = $fullClass::data($this->connection);

            $total = $fullClass::data($this->connection, [
                "count" => 1,
            ]);
            $params['total'] = $total['count'];

        } elseif ($model->getListType() == 1) {

            $pageNum = $request->get('pageNum') ?: 1;
            $sort = $request->get('sort') ?: $model->getDefaultSortBy();
            $order = $request->get('order') ?: ($model->getDefaultOrder() == 0 ? 'ASC' : 'DESC');

            $orms = $fullClass::data($this->connection, [
                "page" => $pageNum,
                "limit" => $model->getNumberPerPage(),
                "sort" => $sort,
                "order" => $order,
            ]);

            $total = $fullClass::data($this->connection, [
                "count" => 1,
            ]);

            $params['total'] = $total['count'];
            $params['totalPages'] = ceil($total['count'] / $model->getNumberPerPage());
            $params['url'] = $request->getPathInfo() . "?sort=$sort&order=$order";
            $params['urlNoSort'] = $request->getPathInfo();
            $params['pageNum'] = $pageNum;
            $params['sort'] = $sort;
            $params['order'] = $order;

        } elseif ($model->getListType() == 2) {
            $result = $fullClass::data($this->connection);

            $nodes = [];
            foreach ($result as $itm) {
                $nodes[] = (array)new RawData([
                    'id' => $itm->getId(),
                    'parent' => $itm->getParentId(),
                    'title' => $itm->getTitle(),
                    'url' => '/manage/' . ($model->getDataType() == 2 ? 'admin' : '') . '/orms/' . $model->getClassName() . '/',
                    'template' => $fullClass::getCmsOrmTwig(),
                    'status' => $itm->getStatus(),
                    'allowExtra' => 1,
                    'maxParams' => 3,
                    'closed' => $itm->getClosed(),
                ]);
            }

            $tree = new Tree($nodes, [
                'rootId' => null,
                'buildwarningcallback' => function () {},
            ]);
            $orms = $tree->getRootNodes();
        }
        $params['orms'] = $orms;

        return $this->render($params['theNode']->template, $params);
    }
}