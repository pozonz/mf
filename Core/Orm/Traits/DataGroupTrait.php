<?php
//Last updated: 2019-04-18 11:48:32
namespace MillenniumFalcon\Core\ORM\Traits;

trait DataGroupTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo)
    {
        $orm = new static($pdo);
        $orm->setTitle('Pages');
        $orm->setIcon('cms_viewmode_cms');
        $orm->setBuiltInSection(1);
        $orm->setBuiltInSectionCode('pages');
        $orm->setBuiltInSectionTemplate('cms/pages.html.twig');
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('Modules');
        $orm->setIcon('cms_viewmode_sitecodetables');
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('Files');
        $orm->setIcon('cms_viewmode_asset');
        $orm->setBuiltInSection(1);
        $orm->setBuiltInSectionCode('files');
        $orm->setBuiltInSectionTemplate('cms/files/files.html.twig');
        $orm->save();

        $orm = new static($pdo);
        $orm->setTitle('Admin');
        $orm->setIcon('cms_viewmode_admin');
        $orm->setBuiltInSection(1);
        $orm->setBuiltInSectionCode('admin');
        $orm->setBuiltInSectionTemplate('cms/admin.html.twig');
        $orm->save();
    }
}