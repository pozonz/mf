<?php

namespace MillenniumFalcon\Cart\Service;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\ORM\ShippingByWeight;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Service\UtilsService;
use MillenniumFalcon\Core\SymfonyKernel\RedirectException;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class ShopService
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * CartService constructor.
     * @param Connection $container
     */
    public function __construct(Connection $connection, SessionInterface $session, TokenStorageInterface $tokenStorage, Environment $environment)
    {
        $this->connection = $connection;
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->environment = $environment;
    }

    /**
     * @param $productIds
     * @return array
     */
    public function getProducts($productIds)
    {
        if (gettype($productIds) != 'array') {
            $productIds = json_decode($productIds ?: '[]');
        }

        $fullClass = ModelService::fullClass($this->connection, 'Product');
        return array_filter(array_map(function ($itm) use ($fullClass) {
            return $fullClass::getById($this->connection, $itm);
        }, $productIds));
    }

    /**
     * @return array
     */
    public function getCategoriesWithProductCount()
    {
        $arrayToReturn = [];
        $fullClass = ModelService::fullClass($this->connection, 'ProductCategory');
        $categories = $fullClass::active($this->connection, [
            'whereSql' => 'm.parentId IS NULL',
        ]);
        foreach ($categories as $category) {
            $arrayToReturn[] = [
                "title" => $category->getTitle(),
                "image" => $category->getImage(),
                "count" => $category->objProductCount(),
                "slug" => $category->getSlug(),
            ];
        }
        return $arrayToReturn;
    }
}