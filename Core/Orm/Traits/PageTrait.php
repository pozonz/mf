<?php

namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\Service\ModelService;
use Ramsey\Uuid\Uuid;

trait PageTrait
{
    /**
     * @var array
     */
    private $children = array();

    /**
     * PageTrait constructor.
     * @param $pdo
     */
    public function __construct($pdo)
    {
        parent::__construct($pdo);

        $this->setType(1);
    }

    /**
     * @param $pdo
     */
    static public function initData($pdo)
    {
        $templateFullClass = ModelService::fullClass($pdo, 'PageTemplate');

        $navFullClass = ModelService::fullClass($pdo, 'PageCategory');
        $mainNav = $navFullClass::getByField($pdo, 'code', 'main');
        $footerNav = $navFullClass::getByField($pdo, 'code', 'footer');

        $orm = new static($pdo);
        $orm->setTitle('Home');
        $orm->setType(1);
        $orm->setUrl('/');
        $orm->setCategory(json_encode([$mainNav->getId()]));
        $orm->setTemplateFile($templateFullClass::getByField($pdo, 'filename', 'home.html.twig')->getId());
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('About');
        $orm->setType(1);
        $orm->setUrl('/about');
        $orm->setCategory(json_encode([$mainNav->getId()]));
        $orm->setTemplateFile($templateFullClass::getByField($pdo, 'filename', 'about.html.twig')->getId());
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('News');
        $orm->setType(1);
        $orm->setUrl('/news');
        $orm->setCategory(json_encode([$mainNav->getId()]));
        $orm->setTemplateFile($templateFullClass::getByField($pdo, 'filename', 'posts.html.twig')->getId());
        $orm->setAttachedModels(json_encode(array(_Model::getByField($pdo, 'className', 'News')->getId())));
        $orm->save();
        $newsPageId = $orm->getId();

        $orm = new static($pdo);
        $orm->setTitle('News detail');
        $orm->setType(1);
        $orm->setUrl('/news/detail');
        $orm->setCategory(json_encode([$mainNav->getId()]));
        $orm->setTemplateFile($templateFullClass::getByField($pdo, 'filename', 'post.html.twig')->getId());
        $orm->setCategoryParent(json_encode((object)[
            'cat' . $mainNav->getId() => $newsPageId,
        ]));
        $orm->setCategoryRank(json_encode((object)[
            'cat' . $mainNav->getId() => 0,
        ]));
        $orm->setHideFromWebNav(1);
        $orm->setHideFromCmsNav(1);
        $orm->setAllowExtra(1);
        $orm->setMaxParams(1);
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('Contact');
        $orm->setType(1);
        $orm->setUrl('/contact');
        $orm->setCategory(json_encode([$mainNav->getId()]));
        $orm->setTemplateFile($templateFullClass::getByField($pdo, 'filename', 'contact.html.twig')->getId());
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('Terms & Conditions');
        $orm->setType(1);
        $orm->setUrl('/terms-and-conditions');
        $orm->setCategory(json_encode([$footerNav->getId()]));
        $orm->setTemplateFile($templateFullClass::getByField($pdo, 'filename', 'common.html.twig')->getId());
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('Privacy');
        $orm->setType(1);
        $orm->setUrl('/privacy');
        $orm->setCategory(json_encode([$footerNav->getId()]));
        $orm->setTemplateFile($templateFullClass::getByField($pdo, 'filename', 'common.html.twig')->getId());
        $orm->save();
    }

    /**
     * @return mixed
     */
    public function getExtraInfo()
    {
        return $this->getUrl();
    }

    /**
     * @param bool $doubleCheckExistence
     * @throws \Exception
     */
    public function save($doubleCheckExistence = false)
    {
        if (!is_numeric($this->getTemplateFile())) {
            $json = json_decode($this->getTemplateFile());

            $templateName = $json->name;
            $templateFile = preg_replace("/[^a-z0-9\_\-\.]/i", '', $json->file);
            $templateFile = rtrim($templateFile, '.twig') . '.twig';

            $fullClass = ModelService::fullClass($this->getPdo(), 'PageTemplate');
            /** @var \MillenniumFalcon\Core\ORM\PageTemplate $orm */
            $orm = new $fullClass($this->getPdo());
            $orm->setTitle($templateName);
            $orm->setFilename($templateFile);
            $orm->save();

            $this->setTemplateFile($orm->getId());
        }

        parent::save($doubleCheckExistence);
    }

    /**
     * @return array
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param array $children
     */
    public function setChildren(array $children)
    {
        $this->children = $children;
    }

    /**
     * @param NodeInterface $child
     */
    public function addChild(NodeInterface $child)
    {
        $this->children[] = $child;
    }

    /**
     * @return string
     */
    public function getIcon()
    {
        return '';
    }

    /**
     * @return mixed|null|string
     */
    public function getTemplate()
    {
        $objPageTemplate = $this->objPageTempalte();
        return $objPageTemplate ? $objPageTemplate->getFilename() : null;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    public function objPageTempalte()
    {
        $fullClass = ModelService::fullClass($this->getPdo(), 'PageTemplate');
        $pageTemplate = $fullClass::getById($this->getPdo(), $this->getTemplateFile());
        return $pageTemplate;
    }

    /**
     * @return mixed
     */
    public function objContent()
    {
        $result = [];
        $objContent = json_decode($this->getContent());
        if ($objContent === null && json_last_error() !== JSON_ERROR_NONE) {
            $objContent = [];
        }

        foreach ($objContent as $itm) {
            $result[$itm->attr] = $itm;
        }
        return $result;
    }

    /**
     * @return string
     */
    static public function getCmsOrmsTwig()
    {
        return 'cms/orms/orms-custom-page.html.twig';
    }

    /**
     * @return string
     */
    static public function getCmsOrmTwig()
    {
        return 'cms/orms/orm-custom-page.html.twig';
    }
}