<?php
//Last updated: 2019-04-30 11:33:32
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Orm\AssetOrm;
use MillenniumFalcon\Core\Service\AssetService;
use MillenniumFalcon\Core\Service\UtilsService;

trait AssetTrait
{
    /**
     * @var array
     */
    private $children = array();

    /**
     * @var string
     */
    private $text;

    /**
     * @var int
     */
    private $state = array();

    /**
     * @return null|string
     */
    public function getText()
    {
        return $this->getTitle();
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    /**
     * @return array
     */
    public function getState(): array
    {
        return $this->state;
    }

    /**
     * @param array $state
     */
    public function setState(array $state)
    {
        $this->state = $state;
    }

    /**
     * @param $idx
     * @param $value
     */
    public function setStateValue($idx, $value)
    {
        $this->state[$idx] = $value;
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    public function jsonSerialize()
    {
        $fields = array_keys(static::getFields());

        $obj = new \stdClass();
        foreach ($fields as $field) {
            $getMethod = "get" . ucfirst($field);
            $obj->{$field} = $this->$getMethod();
        }
        $obj->text = $this->getText();
        $obj->state = $this->getState();
        $obj->children = $this->getChildren();
        return $obj;
    }

    /**
     * @return array|null
     */
    public function getParentFolder() {
        return static::getById($this->getPdo(), $this->getParentId());
    }

    /**
     * @param bool $doubleCheckExistence
     * @return mixed
     */
    public function save($doubleCheckExistence = false) {
        if (!$this->getId()) {
            do {
                $code = UtilsService::generateHex(4);
                $orm = static::getByField($this->getPdo(), 'code', $code);
            } while($orm);
            $this->setCode($code);
        }
        return parent::save($doubleCheckExistence);
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
        }

        return parent::delete();
    }

    /**
     * @return array|null
     */
    public function getChildAssets() {
        return static::data($this->getPdo(), array(
            'whereSql' => 'm.parentId = ?',
            'params' => array($this->getId())
        ));
    }

    /**
     * @return string
     */
    public function formattedSize() {
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
    public function formattedSizeUnit() {
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
    static public function getCmsOrmsTwig() {
        return 'cms/files/files.html.twig';
    }

    /**
     * @return mixed
     */
    static public function getCmsOrmTwig() {
        return 'cms/files/file.html.twig';
    }
}