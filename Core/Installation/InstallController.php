<?php

namespace MillenniumFalcon\Core\Installation;

use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Orm\DataGroup;
use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;

class InstallController extends Controller
{
    /**
     * @Route("/install")
     */
    public function index()
    {
        $connection = $this->container->get('doctrine.dbal.default_connection');
        /** @var \PDO $pdo */
        $pdo = $connection->getWrappedConnection();

        //Create tables
        static::populateDb($pdo, $this->container->getParameter('kernel.project_dir') . '/vendor/pozoltd/millennium-falcon/Core/Orm', "MillenniumFalcon\\Core\\Orm\\");
        if (file_exists($this->container->getParameter('kernel.project_dir') . '/src/Orm/')) {
            static::populateDb($pdo, $this->container->getParameter('kernel.project_dir') . '/src/Orm/', "App\\Orm\\");
        }

        //Add default data
        static::addDefaults($this, $pdo);

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
    static public function populateDb($pdo, $dir, $namespace)
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
    }

    /**
     * @param $obj
     * @param $pdo
     * @throws \Exception
     */
    static public function addDefaults($obj, $pdo)
    {
        $prefix = 'addDefault';
        $methods = get_class_methods($obj);
        foreach ($methods as $method) {
            if (strpos($method, $prefix) === 0 && $method !== __FUNCTION__) {
                $fullClass = ModelService::fullClass($pdo, str_replace($prefix, '', $method));
                $data = $fullClass::data($pdo);
                if (!count($data)) {
                    static::$method($pdo, $obj, $fullClass);
                }
            }
        }
    }

    /**
     * @param $pdo
     * @param $fullClass
     */
    static public function addDefaultDataGroup($pdo, $obj, $fullClass)
    {
        /** @var \MillenniumFalcon\Core\Orm\DataGroup $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Pages');
        $orm->setIcon('cms_viewmode_cms');
        $orm->setBuiltInSection(1);
        $orm->setBuiltInSectionCode('pages');
        $orm->setBuiltInSectionTemplate('cms/pages.html.twig');
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\DataGroup $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Modules');
        $orm->setIcon('cms_viewmode_sitecodetables');
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\DataGroup $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Files');
        $orm->setIcon('cms_viewmode_asset');
        $orm->setBuiltInSection(1);
        $orm->setBuiltInSectionCode('files');
        $orm->setBuiltInSectionTemplate('cms/files/files.html.twig');
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\DataGroup $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Admin');
        $orm->setIcon('cms_viewmode_admin');
        $orm->setBuiltInSection(1);
        $orm->setBuiltInSectionCode('admin');
        $orm->setBuiltInSectionTemplate('cms/admin.html.twig');
        $orm->save();
    }

    /**
     * @param $pdo
     * @param $fullClass
     */
    static public function addDefaultUser($pdo, $obj, $fullClass)
    {
        $result = DataGroup::data($pdo);
        $dataGroup = array_map(function ($itm) {
            return $itm->getId();
        }, $result);

        $password = uniqid();
        /** @var \MillenniumFalcon\Core\Orm\User $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('weida');
        $orm->setPasswordInput($password);
        $orm->setName('Weida Xue');
        $orm->setEmail('luckyweida@gmail.com');
        $orm->setAccessibleSections(json_encode($dataGroup));

        $dir = $obj->container->getParameter('kernel.project_dir') . '/vendor/pozoltd/millennium-falcon/Resources/views';
        $loader = $obj->container->get('twig')->getLoader();
        $loader->addPath($dir);

        $messageBody = $obj->container->get('twig')->render("cms/emails/install/email-welcome.html.twig", array(
            'orm' => $orm,
        ));

        $message = (new \Swift_Message())
            ->setSubject('CMS is ready - ' . date('d M Y@H:i'))
            ->setFrom(array(getenv('EMAIL_FROM')))
            ->setTo($orm->getEmail())
            ->setBcc(array(getenv('EMAIL_BCC')))
            ->setBody($messageBody, 'text/html');
        $obj->container->get('mailer')->send($message);

        $orm->save();

    }

    /**
     * @param $pdo
     * @param $fullClass
     */
    static public function addDefaultAssetSize($pdo, $obj, $fullClass)
    {
        /** @var \MillenniumFalcon\Core\Orm\AssetSize $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('CMS small');
        $orm->setCode('cms_small');
        $orm->setWidth(200);
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\AssetSize $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Small');
        $orm->setCode('small');
        $orm->setWidth(400);
        $orm->setShowInCrop(1);
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\AssetSize $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Medium');
        $orm->setCode('medium');
        $orm->setWidth(1000);
        $orm->setShowInCrop(1);
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\AssetSize $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Large');
        $orm->setCode('large');
        $orm->setWidth(1800);
        $orm->setShowInCrop(1);
        $orm->save();
    }

    /**
     * @param $pdo
     * @param $fullClass
     */
    static public function addDefaultFragmentTag($pdo, $obj, $fullClass)
    {
        /** @var \MillenniumFalcon\Core\Orm\FragmentTag $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Page');
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\FragmentTag $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('CMS');
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\FragmentTag $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Shipping');
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\FragmentTag $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Product');
        $orm->save();
    }

    /**
     * @param $pdo
     * @param $fullClass
     */
    static public function addDefaultFragmentBlock($pdo, $obj, $fullClass)
    {
        $tagFullClass = ModelService::fullClass($pdo, 'FragmentTag');
        $tagOrm = $tagFullClass::getByField($pdo, 'title', 'Page');

        /** @var \MillenniumFalcon\Core\Orm\FragmentBlock $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Heading & Content');
        $orm->setTwig('heading-content.twig');
        $orm->setTags(json_encode(array($tagOrm->getId())));
        $orm->setItems(json_encode(array(
            array(
                "widget" => "0",
                "id" => "heading",
                "title" => "Heading:",
                "sql" => "",
            ),
            array(
                "widget" => "5",
                "id" => "content",
                "title" => "Content:",
                "sql" => "",
            ),
        )));
        $orm->save();
    }

    /**
     * @param $pdo
     * @param $fullClass
     */
    static public function addDefaultFragmentDefault($pdo, $obj, $fullClass)
    {
        $tagFullClass = ModelService::fullClass($pdo, 'FragmentTag');
        $tagOrm = $tagFullClass::getByField($pdo, 'title', 'Page');

        /** @var \MillenniumFalcon\Core\Orm\FragmentDefault $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Page');
        $orm->setAttr('content');
        $orm->setContent(json_encode(array(
            array(
                "id" => "content",
                "title" => "Content:",
                "tags" => array($tagOrm->getId()),
            ),
        )));
        $orm->save();
    }

    /**
     * @param $pdo
     * @param $fullClass
     */
    static public function addDefaultPageCategory($pdo, $obj, $fullClass)
    {
        /** @var \MillenniumFalcon\Core\Orm\PageCategory $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Main nav');
        $orm->setCode('main');
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\PageCategory $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Footer nav');
        $orm->setCode('footer');
        $orm->save();
    }

    /**
     * @param $pdo
     * @param $fullClass
     */
    static public function addDefaultPageTemplate($pdo, $obj, $fullClass)
    {
        /** @var \MillenniumFalcon\Core\Orm\PageTemplate $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('home.html.twig');
        $orm->setFilename('home.html.twig');
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\PageTemplate $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('about.html.twig');
        $orm->setFilename('about.html.twig');
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\PageTemplate $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('posts.html.twig');
        $orm->setFilename('posts.html.twig');
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\PageTemplate $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('post.html.twig');
        $orm->setFilename('post.html.twig');
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\PageTemplate $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('contact.html.twig');
        $orm->setFilename('contact.html.twig');
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\PageTemplate $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('common.html.twig');
        $orm->setFilename('common.html.twig');
        $orm->save();
    }

    /**
     * @param $pdo
     * @param $fullClass
     */
    static public function addDefaultPage($pdo, $obj, $fullClass)
    {
        $templateFullClass = ModelService::fullClass($pdo, 'PageTemplate');

        $navFullClass = ModelService::fullClass($pdo, 'PageCategory');
        /** @var \MillenniumFalcon\Core\Orm\PageCategory $mainNav */
        $mainNav = $navFullClass::getByField($pdo, 'code', 'main');
        $footerNav = $navFullClass::getByField($pdo, 'code', 'footer');

        /** @var \MillenniumFalcon\Core\Orm\Page $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Home');
        $orm->setType(1);
        $orm->setUrl('/');
        $orm->setCategory(json_encode([$mainNav->getId()]));
        $orm->setTemplateFile($templateFullClass::getByField($pdo, 'filename', 'home.html.twig')->getId());
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\Page $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('About');
        $orm->setType(1);
        $orm->setUrl('/about');
        $orm->setCategory(json_encode([$mainNav->getId()]));
        $orm->setTemplateFile($templateFullClass::getByField($pdo, 'filename', 'about.html.twig')->getId());
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\Page $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('News');
        $orm->setType(1);
        $orm->setUrl('/news');
        $orm->setCategory(json_encode([$mainNav->getId()]));
        $orm->setTemplateFile($templateFullClass::getByField($pdo, 'filename', 'posts.html.twig')->getId());
        $orm->setAttachedModels(json_encode(array(_Model::getByField($pdo, 'className', 'News')->getId())));
        $orm->save();
        $newsPageId = $orm->getId();

        /** @var \MillenniumFalcon\Core\Orm\Page $orm */
        $orm = new $fullClass($pdo);
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

        /** @var \MillenniumFalcon\Core\Orm\Page $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Contact');
        $orm->setType(1);
        $orm->setUrl('/contact');
        $orm->setCategory(json_encode([$mainNav->getId()]));
        $orm->setTemplateFile($templateFullClass::getByField($pdo, 'filename', 'contact.html.twig')->getId());
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\Page $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Terms & Conditions');
        $orm->setType(1);
        $orm->setUrl('/terms-and-conditions');
        $orm->setCategory(json_encode([$footerNav->getId()]));
        $orm->setTemplateFile($templateFullClass::getByField($pdo, 'filename', 'common.html.twig')->getId());
        $orm->save();

        /** @var \MillenniumFalcon\Core\Orm\Page $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('Privacy');
        $orm->setType(1);
        $orm->setUrl('/privacy');
        $orm->setCategory(json_encode([$footerNav->getId()]));
        $orm->setTemplateFile($templateFullClass::getByField($pdo, 'filename', 'common.html.twig')->getId());
        $orm->save();
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
}