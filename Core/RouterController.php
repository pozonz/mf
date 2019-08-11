<?php

namespace MillenniumFalcon\Core;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Nestable\Tree;
use MillenniumFalcon\Core\Redirect\RedirectException;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class RouterController extends Controller
{
    /**
     * @var Tree
     */
    protected $tree;

    /**
     * @param $requestUri
     * @return array
     */
    function getParams($requestUri)
    {
        $tree = new Tree($this->getNodes());

        $fragments = explode('/', trim($requestUri, '/'));
        $args = array();
        $node = $tree->getNodeByUrl($requestUri);
        if (!$node) {
            for ($i = count($fragments), $il = 0; $i > $il; $i--) {
                $parts = array_slice($fragments, 0, $i);
                $node = $tree->getNodeByUrl('/' . implode('/', $parts) . '/');
                if (!$node) {
                    $node = $tree->getNodeByUrl('/' . implode('/', $parts));
                }
                if ($node) {
                    if ((!$node->getAllowExtra() && (count($fragments) - count($parts) == 0)) ||
                        ($node->getAllowExtra() && $node->getMaxParams() >= (count($fragments) - count($parts)))) {
                        $args = array_values(array_diff($fragments, $parts));
                        break;
                    } else {
                        throw new NotFoundHttpException();
                    }
                }
            }
        }
        if (!$node) {
            throw new NotFoundHttpException();
        }
        if (method_exists($node, 'getType') && method_exists($node, 'getRedirectTo') && $node->getType() == 2) {
            throw new RedirectException($node->getRedirectTo());
        }
        return array(
            'args' => $args,
            'fragments' => $fragments,
            'node' => $node,
            'root' => $tree->getRoot(),
            'returnUrl' => '',
        );
    }

    /**
     * @return mixed
     */
    abstract function getNodes();

}