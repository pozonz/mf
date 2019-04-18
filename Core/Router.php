<?php

namespace MillenniumFalcon\Core;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\Nestable\Tree;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

abstract class Router extends Controller
{
    /**
     * @var Tree
     */
    protected $tree;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * Router constructor.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        $this->tree = new Tree($this->getNodes());
    }

    /**
     * @param $requestUri
     * @return array
     */
    function getParams($requestUri)
    {
        $fragments = explode('/', trim($requestUri, '/'));
        $args = array();
        $node = $this->tree->getNodeByUrl($requestUri);
        if (!$node) {
            for ($i = count($fragments), $il = 0; $i > $il; $i--) {
                $parts = array_slice($fragments, 0, $i);
                $node = $this->tree->getNodeByUrl('/' . implode('/', $parts) . '/');
                if (!$node) {
                    $node = $this->tree->getNodeByUrl('/' . implode('/', $parts));
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
        return array(
            'args' => $args,
            'fragments' => $fragments,
            'node' => $node,
            'root' => $this->tree->getRoot(),
            'returnUrl' => '',
        );
    }

    /**
     * @return mixed
     */
    abstract function getNodes();

}