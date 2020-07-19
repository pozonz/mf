<?php

namespace MillenniumFalcon\Core;

use BlueM\Tree;
use MillenniumFalcon\Core\SymfonyKernel\RedirectException;
use MillenniumFalcon\Core\Tree\TreeUtils;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class RouterController extends AbstractController
{
    /**
     * @var null
     */
    protected $_tree = null;

    /**
     * @var null
     */
    protected $_nodes = null;

    /**
     * @param ContainerInterface $container
     * @return ContainerInterface|null
     */
    public function setContainer(ContainerInterface $container): ?ContainerInterface
    {
        $dir = $container->getParameter('kernel.project_dir') . '/vendor/pozoltd/mf/Resources/views';
        $loader = $container->get('twig')->getLoader();
        $loader->addPath($dir);
        return parent::setContainer($container);
    }

    /**
     * @param $request
     * @return array
     * @throws RedirectException
     */
    public function getTemplateParams($request)
    {
        $requestUri = $request->getPathInfo();
        $requestUri = rtrim($requestUri, '/');
        return $this->getTemplateParamsByUrl($requestUri);
    }

    /**
     * @param $requestUri
     * @return array
     * @throws RedirectException
     */
    protected function getTemplateParamsByUrl($requestUri)
    {
        $tree = $this->getTree();
        $urlFragments = explode('/', trim($requestUri, '/'));
        $urlParams = [];

        $rawData = $this->getRawDataByUrl($requestUri);
        if (!$rawData) {
            for ($i = count($urlFragments), $il = 0; $i > $il; $i--) {
                $parts = array_slice($urlFragments, 0, $i);
                $rawData = $this->getRawDataByUrl('/' . implode('/', $parts) . '/');
                if (!$rawData) {
                    $rawData = $this->getRawDataByUrl('/' . implode('/', $parts));
                }
                if ($rawData) {
                    if ((!$rawData->allowExtra && (count($urlFragments) - count($parts) == 0)) ||
                        ($rawData->allowExtra && $rawData->maxParams >= (count($urlFragments) - count($parts)))) {
                        $urlParams = array_values(array_diff($urlFragments, $parts));
                        break;
                    } else {
                        throw new NotFoundHttpException();
                    }
                }
            }
        }

        if (!$rawData) {
            throw new NotFoundHttpException();
        }

        if ($rawData->type == 2 && $rawData->redirectTo) {
            throw new RedirectException($rawData->getRedirectTo());
        }

        $theNode = $tree->getNodeById($rawData->id);
        return [
            'urlParams' => $urlParams,
            'urlFragments' => $urlFragments,
            'theNode' => $theNode,
        ];
    }

    /**
     * @param $url
     * @return mixed|null
     */
    protected function getRawDataByUrl($url)
    {
        $nodes = $this->getNodes();
        foreach ($nodes as $rawData) {
            $rawData = (object)$rawData;
            if ($rawData->url == $url) {
                return $rawData;
            }
        }
        return null;
    }

    /**
     * @return mixed
     */
    protected function getTree()
    {
        if (!$this->_tree) {
            $nodes = $this->getNodes();
            $this->_tree = new Tree($nodes, [
                'rootId' => null,
                'buildwarningcallback' => function () {
                },
            ]);
        }
        return $this->_tree;
    }

    /**
     * @return mixed
     */
    protected function getNodes()
    {
        if (!$this->_nodes) {
            $this->_nodes = $this->getRawData();
        }
        return $this->_nodes;
    }

    /**
     * @return mixed
     */
    abstract public function getRawData();
}