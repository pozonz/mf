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
        $folders = [
            '.',
            '..',
            'CmsConfig',
            'Generated',
            'Trait',
        ];

        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        $files = array();
        $dir = $this->container->getParameter('kernel.project_dir') . '/vendor/pozoltd/millennium-falcon/Core/Orm';
        $files = array_diff(array_merge($files, scandir($dir)), $folders);

        $pdo->beginTransaction();
        foreach ($files as $file) {
            $className = "MillenniumFalcon\\Core\\Orm\\" . substr($file, 0, strrpos($file, '.'));
            $tableName = $className::getTableName();
            $created = $this->tableExists($pdo, $tableName);
            if (!$created) {
                $className::sync($pdo);
            }
        }

        foreach ($files as $file) {
            $className = "MillenniumFalcon\\Core\\Orm\\" . substr($file, 0, strrpos($file, '.'));
            $className::updateModel($pdo);
        }
        $pdo->commit();

        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CmsController.php',
        ]);
    }

    /**
     * @param $pdo
     * @param $id
     * @return bool
     */
    function tableExists($pdo, $id)
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