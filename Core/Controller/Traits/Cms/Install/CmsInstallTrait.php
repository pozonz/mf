<?php

namespace MillenniumFalcon\Core\Controller\Traits\Cms\Install;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\ORM\_Model;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

trait CmsInstallTrait
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var string[]
     */
    protected $IGNORE_FOLDERS_UNDER_ORM = [
        '.',
        '..',
        'CmsConfig',
        'Generated',
        'Traits',
    ];

    /**
     * @Route("/install/init")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function init()
    {
        ini_set('max_execution_time', 9999);
        ini_set('memory_limit', '9999M');

        $webPath = $this->kernel->getProjectDir();

        $this->populateDb($webPath . '/vendor/pozoltd/mf/Core/ORM', "MillenniumFalcon\\Core\\ORM\\");
        if (file_exists($webPath . '/src/ORM/')) {
            $this->populateDb($webPath . '/src/ORM/', "App\\ORM\\");
        }

        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CmsController.php',
        ]);
    }

    /**
     * @param $dir
     * @param $namespace
     * @throws \Doctrine\DBAL\ConnectionException
     */
    public function populateDb($dir, $namespace)
    {
        $files = array_diff(scandir($dir), $this->IGNORE_FOLDERS_UNDER_ORM);

        //Create table
        $this->connection->beginTransaction();
        foreach ($files as $file) {
            $className = pathinfo($file, PATHINFO_FILENAME);
            $fullClass = $namespace . $className;

            $tableName = $fullClass::getTableName();
            $created = $this->tableExists($tableName);
            if (!$created) {
                $fullClass::sync($this->connection);
            }
        }
        $this->connection->commit();

        sleep(5);

        //Update model
        $this->connection->beginTransaction();
        foreach ($files as $file) {
            $className = pathinfo($file, PATHINFO_FILENAME);
            if ($className == '_Model') {
                continue;
            }
            $fullClass = $namespace . $className;
            $fullClass::updateModel($this->connection);
        }
        $this->connection->commit();

        sleep(5);

//        //Init data
        $models = _Model::data($this->connection);
        $this->connection->beginTransaction();
        foreach ($models as $model) {
            $fullClass = $namespace . $model->getClassName();
            $data = $fullClass::data($this->connection);
            if (!count($data)) {
                $fullClass::initData($this->connection);
            }
        }
        $this->connection->commit();
    }

    /**
     * @Route("/install/sync")
     */
    public function sync()
    {
        ini_set('max_execution_time', 9999);
        ini_set('memory_limit', '9999M');

        _Model::sync($this->connection);

        /** @var _Model[] $models */
        $models = _Model::data($this->connection);
        foreach ($models as $model) {
            $fullClass = ModelService::fullClass($model->getClassName());
            $fullClass::sync($this->connection);
        }

        return $this->json([
            'message' => 'Really! Again?',
            'path' => 'src/Controller/CmsController.php',
        ]);
    }


    /**
     * @param $tableName
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    private function tableExists($tableName)
    {
        $results = $this->connection->query("SHOW TABLES LIKE '$tableName'");
        if (!$results) {
            return false;
        }
        if ($results->rowCount() > 0) {
            return true;
        }
    }
}