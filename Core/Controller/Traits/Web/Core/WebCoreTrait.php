<?php

namespace MillenniumFalcon\Core\Controller\Traits\Web\Core;

use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Tree\RawData;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

trait WebCoreTrait
{
//    /**
//     * @route("/sitemap.xml")
//     * @return Response
//     */
//    public function sitemap()
//    {
//        $sitemap = [];
//        $request = Request::createFromGlobals();
//        $this->connection = $this->container->get('doctrine.dbal.default_connection');
//        $fullClass = ModelService::fullClass($this->connection, "Page");
//        $orms = $fullClass::active($this->connection, [
//            'whereSql' => '(m.hideFromWebNav != 1 OR m.hideFromWebNav IS NULL)'
//        ]);
//        foreach ($orms as $orm) {
//            $sitemap[] = [
//                'url' => $request->getSchemeAndHttpHost() . $orm->getUrl(),
//            ];
//        }
//
//        /** @var _Model[] $models */
//        $models = _Model::active($this->connection);
//        foreach ($models as $model) {
//            if ($model->getSiteMapUrl()) {
//                $url = $model->getSiteMapUrl();
//                $fullClass = ModelService::fullClass($this->connection, $model->getClassName());
//                $fields = array_keys($fullClass::getFields());
//                $orms = $fullClass::active($this->connection);
//                foreach ($orms as $orm) {
//                    $ormUrl = $url;
//                    foreach ($fields as $field) {
//                        $attr = 'get' . ucfirst($field);
//                        $replace = '{' . $field . '}';
//                        $value = $orm->$attr();
//                        $ormUrl = str_replace($replace, $value, $ormUrl);
//                    }
//                    $sitemap[] = [
//                        'url' => $request->getSchemeAndHttpHost() . $ormUrl,
//                    ];
//                }
//            }
//        }
//
//        return $this->render('cms/sitemap/sitemap.xml.twig', [
//            'sitemap' => $sitemap,
//        ]);
//    }

    /**
     * @route("/{page}", requirements={"page" = ".*"})
     * @return Response
     */
    public function web(Request $request)
    {
        $params = $this->getTemplateParams($request);
        return $this->render($params['theNode']->template, $params);
    }

    /**
     * @return array
     */
    public function getNodes()
    {
        $request = Request::createFromGlobals();
        $previewPageToken = $request->get('__preview_Page');

        $nodes = [];
        $pages = [];

        try {
            $fullClass = ModelService::fullClass($this->connection, 'Page');
            if ($previewPageToken) {
                $pages = $fullClass::data($this->connection, [
                    'whereSql' => 'm.versionUuid = ?',
                    'params' => [$previewPageToken],
                    'includePreviousVersion' => 1,
                ]);
            } else {
                $pages = $fullClass::data($this->connection);
            }
        } catch (\Exception $ex) {
        }

        foreach ($pages as $page) {
            $nodes[] = (array)new RawData([
                'id' => $page->getId(),
                'parent' => null,
                'title' => $page->getTitle(),
                'url' => $page->getUrl(),
                'template' => $page->objPageTempalte()->getFilename(),
                'status' => 1,
                'icon' => $page->getIcon(),
                'allowExtra' => $page->getAllowExtra(),
                'maxParams' => $page->getMaxParams(),
                'extraInfo' => $page,
            ]);
        }

        return $nodes;
    }
}