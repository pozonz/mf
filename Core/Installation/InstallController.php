<?php

namespace MillenniumFalcon\Core\Installation;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class InstallController extends Controller
{
    /**
     * @Route("/install")
     */
    public function index()
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        static::populateDb($pdo, $this->container->getParameter('kernel.project_dir') . '/vendor/pozoltd/millennium-falcon/Core/Orm', "MillenniumFalcon\\Core\\Orm\\");
        if (file_exists($this->container->getParameter('kernel.project_dir') . '/src/Orm/')) {
            static::populateDb($pdo, $this->container->getParameter('kernel.project_dir') . '/src/Orm/', "App\\Orm\\");
        }

        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CmsController.php',
        ]);
    }

    /**
     * @param $pdo
     * @param $dir
     * @param $namespace
     */
    static public function populateDb($pdo, $dir, $namespace) {
        $folders = [
            '.',
            '..',
            'CmsConfig',
            'Generated',
            'Traits',
        ];

        $files = array();
        $files = array_diff(array_merge($files, scandir($dir)), $folders);

        $pdo->beginTransaction();
        foreach ($files as $file) {
            $className = $namespace . substr($file, 0, strrpos($file, '.'));
            $tableName = $className::getTableName();
            $created = static::tableExists($pdo, $tableName);
            if (!$created) {
                $className::sync($pdo);
            }
        }

        foreach ($files as $file) {
            $className = $namespace . substr($file, 0, strrpos($file, '.'));
            $className::updateModel($pdo);
        }
        $pdo->commit();
    }

    /**
     * @param $pdo
     * @param $id
     * @return bool
     */
    static public function tableExists($pdo, $id)
    {
        $results = $pdo->query("SHOW TABLES LIKE '$id'");
        if (!$results) {
            return false;
        }
        if ($results->rowCount() > 0) {
            return true;
        }
    }
}