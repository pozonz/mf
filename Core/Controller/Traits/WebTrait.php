<?php

namespace MillenniumFalcon\Core\Controller\Traits;

use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

trait WebTrait
{
    /**
     * @route("/sitemap.xml")
     * @return Response
     */
    public function sitemap()
    {
        $sitemap = [];
        $request = Request::createFromGlobals();
        $pdo = $this->container->get('doctrine.dbal.default_connection');
        $fullClass = ModelService::fullClass($pdo, "Page");
        $orms = $fullClass::active($pdo, [
            'whereSql' => '(m.hideFromWebNav != 1 OR m.hideFromWebNav IS NULL)'
        ]);
        foreach ($orms as $orm) {
            $sitemap[] = [
                'url' => $request->getSchemeAndHttpHost() . $orm->getUrl(),
            ];
        }

        /** @var _Model[] $models */
        $models = _Model::active($pdo);
        foreach ($models as $model) {
            if ($model->getSiteMapUrl()) {
                $url = $model->getSiteMapUrl();
                $fullClass = ModelService::fullClass($pdo, $model->getClassName());
                $fields = array_keys($fullClass::getFields());
                $orms = $fullClass::active($pdo);
                foreach ($orms as $orm) {
                    $ormUrl = $url;
                    foreach ($fields as $field) {
                        $attr = 'get' . ucfirst($field);
                        $replace = '{' . $field . '}';
                        $value = $orm->$attr();
                        $ormUrl = str_replace($replace, $value, $ormUrl);
                    }
                    $sitemap[] = [
                        'url' => $request->getSchemeAndHttpHost() . $ormUrl,
                    ];
                }
            }
        }

        return $this->render('cms/sitemap/sitemap.xml.twig', [
            'sitemap' => $sitemap,
        ]);
    }

    /**
     * @route("/{page}", requirements={"page" = ".*"})
     * @return Response
     */
    public function web()
    {
        $request = Request::createFromGlobals();
        $path = rtrim($request->getPathInfo(), '/');
        $params = $this->getParams($path);
        return $this->render($params['node']->getTemplate(), $params);
    }

    /**
     * @return array
     */
    public function getNodes()
    {
        $pdo = $this->container->get('doctrine.dbal.default_connection');
        $request = Request::createFromGlobals();
        $previewPageToken = $request->get('__preview_Page');

        try {
            $fullClass = ModelService::fullClass($pdo, 'Page');
            if ($previewPageToken) {
                return $fullClass::data($pdo, [
                    'whereSql' => 'm.versionUuid = ?',
                    'params' => [$previewPageToken],
                    'includePreviousVersion' => 1,
                ]);
            } else {
                return $fullClass::data($pdo);
            }

        } catch (\Exception $ex) {
        }
        return [];
    }
}