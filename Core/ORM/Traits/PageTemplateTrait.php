<?php
//Last updated: 2019-04-18 11:47:07
namespace MillenniumFalcon\Core\ORM\Traits;

use MillenniumFalcon\Core\Service\TemplateService;

trait PageTemplateTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo)
    {
        $orm = new static($pdo);
        $orm->setTitle('home.twig');
        $orm->setFilename('home.twig');
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('about.twig');
        $orm->setFilename('about.twig');
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('posts.twig');
        $orm->setFilename('posts.twig');
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('post.twig');
        $orm->setFilename('post.twig');
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('contact.twig');
        $orm->setFilename('contact.twig');
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('common.twig');
        $orm->setFilename('common.twig');
        $orm->save();
    }

    /**
     * @param bool $doNotSaveVersion
     * @param array $options
     * @return mixed|null
     */
    public function save($doNotSaveVersion = false, $options = [])
    {
        TemplateService::createTemplateFile($this);
        return parent::save($doNotSaveVersion, $options);
    }
}