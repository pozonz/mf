<?php

namespace MillenniumFalcon\Core\Controller\Traits\Cms\Install;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\ORM\_Model;

use MillenniumFalcon\Core\ORM\ShippingZone;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\SymfonyKernel\RedirectException;
use phpDocumentor\Reflection\Types\Static_;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

trait CmsInstallTrait
{
    protected $IGNORE_FOLDERS_UNDER_ORM = [
        '.',
        '..',
        'CmsConfig',
        'Generated',
        'Data',
        'Traits',
    ];

    /**
     * @Route("/manage/install/model/sync")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function sync()
    {
        ini_set('max_execution_time', 9999);
        ini_set('memory_limit', '9999M');

        _Model::sync($this->connection);

        return new JsonResponse([
            'models' => json_decode($this->createOrUpdateModelsFromFiles()->getContent()),
            'tables' => json_decode($this->createOrUpdateTablesFromModels()->getContent()),
            'data' => json_decode($this->addInitDataToModel()->getContent()),
        ]);
    }

    /**
     * @Route("/manage/install/model/extra/zones")
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function extraZones()
    {
        ini_set('max_execution_time', 9999);
        ini_set('memory_limit', '9999M');

        $data = ShippingZone::data($this->connection);
        foreach ($data as $itm) {
            $itm->delete();
        }

        $phpDir = $this->kernel->getProjectDir() . '/vendor/pozoltd/mf/Migrations/php';

        $countries = require "$phpDir/countries.php";
        $states = require "$phpDir/states.php";

        $count = 0;
        foreach ($countries as $countryIdx => $countryVal) {
            $ormCountry = new ShippingZone($this->connection);
            $ormCountry->setTitle($countryVal[0]);
            $ormCountry->setCode($countryIdx);
            $ormCountry->setClosed(1);
            $ormCountry->setRank($count);
            $ormCountry->save();
            $count++;

            if (isset($states[$countryIdx]) && gettype($states[$countryIdx]) == 'array' && count($states[$countryIdx]) > 0) {
                foreach ($states[$countryIdx] as $stateIdx => $stateVal) {
                    $ormState = new ShippingZone($this->connection);
                    $ormState->setTitle($stateVal[0]);
                    $ormState->setCode($stateIdx);
                    $ormState->setParentId($ormCountry->getId());
                    $ormState->setRank($count);
                    $ormState->save();
                    $count++;
                }
            }
        }

        return new Response($count);
    }

    /**
     * @Route("/manage/install/model/data/files")
     * @return JsonResponse
     */
    public function updateInitDataFiles()
    {
        ini_set('max_execution_time', 9999);
        ini_set('memory_limit', '9999M');

        $response = [];
        $models = _Model::data($this->connection, [
            'sort' => 'className',
        ]);
        foreach ($models as $model) {
            $added = 0;

            $fullClass = ModelService::fullClass($this->connection, $model->getClassName());
            $data = $fullClass::data($this->connection);
            foreach ($data as $itm) {
                if ($itm->getIsBuiltIn() == 1) {
                    $itm->updateBuildInFile();
                    $added++;
                }
            }
            $response[$model->getClassName()] = $added;
        }

        return new JsonResponse($response);
    }

    /**
     * @return JsonResponse
     */
    protected function createOrUpdateModelsFromFiles()
    {
        $unknown = [];
        $added = [];
        $updated = [];
        $nochange = [];

        $files = [];
        $files = array_unique(array_merge($files, array_diff(scandir($this->kernel->getProjectDir() . '/vendor/pozoltd/mf/Core/ORM'), $this->IGNORE_FOLDERS_UNDER_ORM)));
        if (file_exists($this->kernel->getProjectDir() . '/src/ORM/')) {
            $files = array_unique(array_merge($files, array_diff(scandir($this->kernel->getProjectDir() . '/src/ORM/'), $this->IGNORE_FOLDERS_UNDER_ORM)));
        }

        sort($files);

        foreach ($files as $file) {
            $className = pathinfo($file, PATHINFO_FILENAME);
            if ($className == '_Model') {
                continue;
            }
            $fullClass = ModelService::fullClass($this->connection, $className);
            $response = $fullClass::createOrUpdateModel($this->connection);

            if ($response == 0) {
                $unknown[] = $className;
            } elseif ($response == 1) {
                $added[] = $className;
            } elseif ($response == 2) {
                $updated[] = $className;
            } elseif ($response == 3) {
                $nochange[] = $className;
            }
        }

        return new JsonResponse([
            'unknown' => $unknown,
            'added' => $added,
            'updated' => $updated,
            'nochange' => $nochange,
        ]);
    }

    /**
     * @return JsonResponse
     */
    protected function createOrUpdateTablesFromModels()
    {
        $unknown = [];
        $added = [];
        $updated = [];
        $nochange = [];

        /** @var _Model[] $models */
        $models = _Model::data($this->connection);
        foreach ($models as $model) {
            $fullClass = ModelService::fullClass($this->connection, $model->getClassName());
            if (!$fullClass) {
                continue;
            }
            $response = $fullClass::sync($this->connection);

            if ($response == 0) {
                $unknown[] = $model->getClassName();
            } elseif ($response == 1) {
                $added[] = $model->getClassName();
            } elseif ($response == 2) {
                $updated[] = $model->getClassName();
            } elseif ($response == 3) {
                $nochange[] = $model->getClassName();
            }
        }

        return new JsonResponse([
            'unknown' => $unknown,
            'added' => $added,
            'updated' => $updated,
            'nochange' => $nochange,
        ]);
    }

    /**
     * @return JsonResponse
     */
    protected function addInitDataToModel()
    {
        $exist = [];
        $models = _Model::data($this->connection, [
            'sort' => 'title',
        ]);
        foreach ($models as $model) {
            $added = 0;

            $fullClass = ModelService::fullClass($this->connection, $model->getClassName());
            if (!$fullClass) {
                continue;
            }
            $total = $fullClass::data($this->connection, [
                'count' => 1,
            ]);
            $exist[$model->getClassName()] = $total['count'];
        }

        $response = [];
        $dir = $this->kernel->getProjectDir() . '/vendor/pozoltd/mf/Core/ORM/Data';
        $files = scandir($dir);
        foreach ($files as $file) {
            if (is_file("$dir/$file")) {
                $jsonData = json_decode(file_get_contents("$dir/$file"));
                if (isset($exist[$jsonData->className]) && $exist[$jsonData->className] == 0) {
                    $fullClass = ModelService::fullClass($this->connection, $jsonData->className);
                    $orm = new $fullClass($this->connection);
                    foreach ($jsonData->orm as $key => $val) {
                        $setMethod = "set" . ucfirst($key);
                        $orm->$setMethod($val);
                    }
                    $old = $fullClass::getById($this->connection, $orm->getId());
                    if (!$old) {
                        $orm->save(0, [
                            'forceInsert' => 1,
                            'doNotUpdateModified' => 1,
                        ]);
                        if (!isset($response[$jsonData->className])) {
                            $response[$jsonData->className] = 0;
                        }
                        $response[$jsonData->className] = $response[$jsonData->className] + 1;
                    }
                }
            }
        }

        return new JsonResponse($response);
    }

    /**
     * @param $tableName
     * @return bool
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function tableExists($tableName)
    {
        $query = "SHOW TABLES LIKE :tableName";
        $statement = $this->connection->prepare($query);
        $statement->bindParam(':tableName', $tableName, \PDO::PARAM_STR);
        $statement->execute();

        if ($statement->rowCount() > 0) {
            return true;
        } else {
            return false;
        }
    }
}