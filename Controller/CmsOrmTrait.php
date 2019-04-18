<?php

namespace MillenniumFalcon\Controller;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\Db;
use MillenniumFalcon\Core\Form\Builder\Model;
use MillenniumFalcon\Core\Form\Builder\Orm;
use MillenniumFalcon\Core\Nestable\PageNode;
use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Orm\AssetSize;
use MillenniumFalcon\Core\Orm\DataGroup;
use MillenniumFalcon\Core\Orm\PageCategory;
use MillenniumFalcon\Core\Orm\PageTemplate;
use MillenniumFalcon\Core\Orm\User;
use MillenniumFalcon\Core\Redirect\RedirectException;
use MillenniumFalcon\Core\Router;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;

trait CmsOrmTrait
{
    /**
     * @route("/manage/orms/{className}")
     * @route("/manage/admin/orms/{className}")
     * @return Response
     */
    public function orms($className)
    {
        $params = $this->prepareParams();

        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $request = Request::createFromGlobals();
        $pageNum = $request->get('pageNum');

        $fragments = $params['fragments'];
        /** @var _Model $model */
        $model = _Model::getByField($pdo, 'className', end($fragments));
        $fullClassname = $model->getNamespace() . '\\' . $model->getClassName();
        $orms = $fullClassname::data($pdo, array(
            "page" => $pageNum,
            "limit" => $model->getNumberPerPage(),
            "sort" => $model->getDefaultSortBy(),
            "order" => $model->getDefaultOrder() == 0 ? 'ASC' : 'DESC',
        ));

        $params['model'] = $model;
        $params['orms'] = $orms;
        return $this->render($params['node']->getTemplate(), $params);
    }

    /**
     * @route("/manage/orms/{className}/{ormId}")
     * @route("/manage/admin/orms/{className}/{ormId}")
     * @return Response
     */
    public function orm($className, $ormId)
    {
        $params = $this->prepareParams();

        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $fragments = $params['fragments'];
        /** @var _Model $model */
        $model = _Model::getByField($pdo, 'className', $fragments[count($fragments) - 2]);
        $fullClassname = $model->getNamespace() . '\\' . $model->getClassName();
        $orm = $fullClassname::getById($pdo, $ormId);
        if (!$orm) {
            $orm = new $fullClassname($pdo);
        }

        $request = Request::createFromGlobals();
        $returnUrl = $request->get('returnUrl') ?: '/manage/orms/' . $model->getClassName();

        $form = $this->container->get('form.factory')->create(Orm::class, $orm, array(
            'model' => $model,
            'orm' => $orm,
        ));

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $orm->getId() ? 0 : 1;
            $orm->save();

            $baseUrl = '/manage/orms/' . $model->getClassName();
            if ($request->get('submit') == 'Apply') {
                throw new RedirectException($baseUrl . '/' . $orm->getId());
            } else if ($request->get('submit') == 'Save') {
                throw new RedirectException($returnUrl);
            }
        }

        $params['returnUrl'] = $returnUrl;
        $params['form'] = $form->createView();
        $params['model'] = $model;
        $params['orm'] = $orm;
        return $this->render($params['node']->getTemplate(), $params);
    }
}