<?php

namespace MillenniumFalcon\Core\Installation;

use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Reader\Xlsx;
use MillenniumFalcon\Core\Service\ModelService;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class InstallController extends Controller
{
    /**
     * @Route("/install")
     */
    public function index()
    {
        ini_set('max_execution_time', 9999);
        ini_set('memory_limit', '9999M');

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        //Create tables
        static::populateDb($pdo, $this->container->getParameter('kernel.project_dir') . '/vendor/pozoltd/millennium-falcon/Core/Orm', "MillenniumFalcon\\Core\\Orm\\", $this->container);
        if (file_exists($this->container->getParameter('kernel.project_dir') . '/src/Orm/')) {
            static::populateDb($pdo, $this->container->getParameter('kernel.project_dir') . '/src/Orm/', "App\\Orm\\", $this->container);
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
    static public function populateDb($pdo, $dir, $namespace, $obj)
    {
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
        $pdo->commit();

        sleep(5);

        $pdo->beginTransaction();
        foreach ($files as $file) {
            $className = $namespace . substr($file, 0, strrpos($file, '.'));
            $className::updateModel($pdo);
        }
        $pdo->commit();

        $models = _Model::data($pdo);
        $pdo->beginTransaction();
        foreach ($models as $model) {
            $className = $namespace . $model->getClassName();
            $className::initData($pdo, $obj);
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

    /**
     * @Route("/install/sync")
     */
    public function indexSync()
    {
        ini_set('max_execution_time', 9999);
        ini_set('memory_limit', '9999M');

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        /** @var _Model[] $models */
        $models = _Model::data($pdo);
        foreach ($models as $model) {
            $fullClass = ModelService::fullClass($pdo, $model->getClassName());
            $fullClass::sync($pdo);
        }

        return $this->json([
            'message' => 'Really! Again?',
            'path' => 'src/Controller/CmsController.php',
        ]);
    }

    /**
     * @param $pdo
     * @param $dir
     * @param $namespace
     */
    static public function syncDbTable($pdo, $dir, $namespace)
    {
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
            $className::sync($pdo);
        }
        $pdo->commit();
    }
}