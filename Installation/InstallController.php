<?php

namespace MillenniumFalcon\Installation;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class InstallController extends AbstractController
{
    /**
     * @Route("/install")
     */
    public function index()
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        var_dump($pdo);exit;

        $pdo->beginTransaction();



        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CmsController.php',
        ]);
    }
}