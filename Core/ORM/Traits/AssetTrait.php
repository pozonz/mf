<?php

namespace MillenniumFalcon\Core\ORM\Traits;

use GuzzleHttp\Client;
use MillenniumFalcon\Core\ORM\FastlyRequest;
use MillenniumFalcon\Core\Service\AssetService;
use MillenniumFalcon\Core\Service\UtilsService;

trait AssetTrait
{
    /**
     * @return array
     */
    public function getFolderPath()
    {
        $path = [];
        $parent = $this;
        do {
            $path[] = $parent;
        } while ($parent = static::getById($this->getPdo(), $parent->getParentId()));
        $path[] = [
            'id' => 0,
            'title' => 'Home',
        ];
        $path = array_reverse($path);
        return $path;
    }

    /**
     * @param bool $doNotSaveVersion
     * @param array $options
     * @return mixed|null
     */
    public function save($doNotSaveVersion = false, $options = [])
    {
        if (!$this->getCode()) {
            do {
                $code = UtilsService::generateHex(4);
                $orm = static::getByField($this->getPdo(), 'code', $code);
            } while ($orm);
            $this->setCode($code);
        }

        if ($this->getId()) {
            if ($_ENV['FASTLY_API_KEY'] && $_ENV['FASTLY_SERVICE_ID']) {
                $clientParams = [
                    'base_uri' => 'https://api.fastly.com',
                    'headers' => [
                        'Fastly-Key' => $_ENV['FASTLY_API_KEY'] ?? null,
                        'Accept' => 'application/json',
                    ]
                ];
                $url = "/service/" . $_ENV['FASTLY_SERVICE_ID'] ?? null . "/purge/asset" . $this->getId();

                $client = new Client($clientParams);
                $response = $client->request('POST', $url);
                $content = $response->getBody()->getContents();

                $fastlyRequest = new FastlyRequest($this->getPdo());
                $fastlyRequest->setTitle($this->getId() . ' / ' . $this->getCode());
                $fastlyRequest->setUrl($url);
                $fastlyRequest->setClientParams(json_encode($clientParams, JSON_PRETTY_PRINT));
                $fastlyRequest->setResponse($content);
                $fastlyRequest->save();
            }

        }

        return parent::save($doNotSaveVersion, $options);
    }

    /**
     * @return mixed
     */
    public function delete()
    {
        AssetService::removeAssetOrms($this->getPdo(), $this);
        AssetService::removeCaches($this->getPdo(), $this);

        if ($this->getIsFolder()) {
            $children = $this->getChildAssets();
            foreach ($children as $itm) {
                $itm->delete();
            }
        } else {
            AssetService::removeFile($this);
            AssetService::removeAssetBinary($this->getPdo(), $this);
        }

        return parent::delete();
    }

    /**
     * @return array|null
     */
    public function getChildAssets()
    {
        return static::data($this->getPdo(), array(
            'whereSql' => 'm.parentId = ?',
            'params' => array($this->getId())
        ));
    }

    /**
     * @return string
     */
    public function formattedSize()
    {
        $fileSize = $this->getFileSize();
        if ($fileSize > 1000000000000) {
            return number_format($fileSize / 1000000000000, 2);
        } elseif ($fileSize > 1000000000) {
            return number_format($fileSize / 1000000000, 2);
        } elseif ($fileSize > 1000000) {
            return number_format($fileSize / 1000000, 2);
        } elseif ($fileSize > 1000) {
            return number_format($fileSize / 1000, 0);
        } else {
            return $fileSize;
        }
    }

    /**
     * @return string
     */
    public function formattedSizeUnit()
    {
        $fileSize = $this->getFileSize();
        if ($fileSize > 1000000000000) {
            return 'TB';
        } elseif ($fileSize > 1000000000) {
            return 'GB';
        } elseif ($fileSize > 1000000) {
            return 'MB';
        } elseif ($fileSize > 1000) {
            return 'KB';
        } else {
            return 'B';
        }
    }
    
    /**
     * @return mixed
     */
    static public function getCmsOrmsTwig()
    {
        return 'cms/files/files.twig';
    }

    /**
     * @return mixed
     */
    static public function getCmsOrmTwig()
    {
        return 'cms/files/file.twig';
    }
}