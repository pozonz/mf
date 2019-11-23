<?php
//Last updated: 2019-04-18 11:47:07
namespace MillenniumFalcon\Core\Orm\Traits;

use MillenniumFalcon\Core\Service\TemplateService;

trait PageTemplateTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo, $container)
    {
        $orm = new static($pdo);
        $orm->setTitle('home.html.twig');
        $orm->setFilename('home.html.twig');
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('about.html.twig');
        $orm->setFilename('about.html.twig');
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('posts.html.twig');
        $orm->setFilename('posts.html.twig');
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('post.html.twig');
        $orm->setFilename('post.html.twig');
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('contact.html.twig');
        $orm->setFilename('contact.html.twig');
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('common.html.twig');
        $orm->setFilename('common.html.twig');
        $orm->save();
    }
    
    /**
     * @param bool $doubleCheckExistence
     * @throws \Exception
     */
    public function save($doubleCheckExistence = false)
    {
        TemplateService::createTemplateFile($this);
        parent::save($doubleCheckExistence);
    }
}