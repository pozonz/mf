<?php

namespace MillenniumFalcon\Core\Controller\Traits\Web\Core;

use Cocur\Slugify\Slugify;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\SymfonyKernel\RedirectException;
use MillenniumFalcon\Core\Tree\RawData;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

trait WebCoreTrait
{
    /**
     * @route("/sitemap.xml")
     * @return Response
     */
    public function sitemap()
    {
        $sitemap = [];
        $request = Request::createFromGlobals();
        $fullClass = ModelService::fullClass($this->connection, "Page");
        $orms = $fullClass::active($this->connection, [
            'whereSql' => '(m.hideFromWebNav != 1 OR m.hideFromWebNav IS NULL)'
        ]);
        foreach ($orms as $orm) {
            $sitemap[] = [
                'url' => $request->getSchemeAndHttpHost() . $orm->getUrl(),
            ];
        }

        /** @var _Model[] $models */
        $models = _Model::active($this->connection);
        foreach ($models as $model) {
            if ($model->getSiteMapUrl()) {
                $url = $model->getSiteMapUrl();
                $fullClass = ModelService::fullClass($this->connection, $model->getClassName());
                if (!$fullClass) {
                    continue;
                }
                $fields = array_keys($fullClass::getFields());
                $orms = $fullClass::active($this->connection);
                foreach ($orms as $orm) {
                    $ormUrl = $url;
                    foreach ($fields as $field) {
                        $attr = 'get' . ucfirst($field);
                        $replace = '{{' . $field . '}}';
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
    public function web(Request $request)
    {
        $abTestToken = $request->get(static::AB_TEST_TOKEN_NAME);

        $requestQuery = $request->getRequestUri();
        $requestUri = $request->getPathInfo();
        $fullClass = ModelService::fullClass($this->connection, 'ABTest');
        $aBTest = $fullClass::getByField($this->connection, 'url', $requestUri . '/');
        if (!$aBTest) {
            $aBTest = $fullClass::getByField($this->connection, 'url', $requestUri);
        }

        if ($aBTest && $aBTest->getStatus() == 1) {
            $jsonPages = json_decode($aBTest->getPages());
            if (!$abTestToken) {
                $slugify = new Slugify();
                $tokenAttr = $slugify->slugify(static::AB_TEST_TOKEN_NAME . '-' . $aBTest->getId() . '-' . $aBTest->getVersion());
                if ($request->cookies->get($tokenAttr)) {
                    $tokenValue = $request->cookies->get($tokenAttr);
                } else {
                    $tokenValue = null;

                    $result = [];
                    $count = 0;
                    foreach ($jsonPages as $jsonPage) {
                        $start = $count + 1;
                        $count += $jsonPage->chance;
                        $result[] = [
                            'start' => $start,
                            'end' => $count,
                            'jsonPage' => $jsonPage,
                        ];
                    }

                    $rand = rand(1, $count);
                    $selected = array_filter($result, function ($itm) use ($rand) {
                        if ($rand >= $itm['start'] && $rand <= $itm['end']) {
                            return true;
                        }
                        return false;
                    });
                    $selected = array_shift($selected);
                    if ($selected) {
                        $selected = $selected['jsonPage'];
                        $tokenValue = $selected->token;
                    }
                }

                if ($tokenValue) {
                    $response = new RedirectResponse($requestQuery . ((strpos($requestQuery, '?') === false) ? '?' : '&') . static::AB_TEST_TOKEN_NAME . '=' . $tokenValue);
                    $response->headers->setCookie(new Cookie($tokenAttr, $tokenValue, time() + (86400 * 365), '/', null, false, false));
                    return $response;
                }
            }
        }

        $params = $this->getTemplateParams($request, $aBTest, $abTestToken);
        $theNode = $params['theNode'];
        $pageOrm = $theNode->getExtraInfo();
        if ($pageOrm->getType() == 2 && $pageOrm->getRedirectTo() && $pageOrm->getRedirectTo() != $pageOrm->getUrl()) {
            $redirectTo = $pageOrm->getRedirectTo();
            if (strpos($redirectTo, '?') === false and $request->getQueryString()) {
                $redirectTo .= '?' . $request->getQueryString();
            }
            return new RedirectResponse($redirectTo);
        }

        return $this->render($params['theNode']->extraInfo->objPageTempalte()->getFilename(), $params);
    }

    /**
     * @return array
     */
    public function getRawData()
    {
        $nodes = [];
        $pages = [];

        try {
            $fullClass = ModelService::fullClass($this->connection, 'Page');
            $pages = $fullClass::active($this->connection, [
                'ignorePreview' => 1,
            ]);

        } catch (\Exception $ex) {
        }

        foreach ($pages as $page) {
            $nodes[] = (array)new RawData([
                'id' => $page->getId(),
                'parent' => null,
                'title' => $page->getTitle(),
                'url' => $page->getUrl(),
                'status' => 1,
                'icon' => method_exists($page, 'getIcon') ? $page->getIcon() : null,
                'allowExtra' => $page->getAllowExtra(),
                'maxParams' => $page->getMaxParams(),
                'extraInfo' => $page,
            ]);
        }

        return $nodes;
    }
}
