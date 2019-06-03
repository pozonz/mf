<?php

namespace MillenniumFalcon\Core\Installation;

use MillenniumFalcon\Core\Orm\_Model;
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
        if (file_exists($this->container->getParameter('kernel.project_dir') . '/src/Orm/')) {
            static::populateDb($pdo, $this->container->getParameter('kernel.project_dir') . '/src/Orm/', "App\\Orm\\");
        }
        static::populateDb($pdo, $this->container->getParameter('kernel.project_dir') . '/vendor/pozoltd/millennium-falcon/Core/Orm', "MillenniumFalcon\\Core\\Orm\\");

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
    static public function addDefaultUser($pdo, $obj, $fullClass)
    {
        $password = uniqid();
        /** @var \MillenniumFalcon\Core\Orm\User $orm */
        $orm = new $fullClass($pdo);
        $orm->setTitle('weida');
        $orm->setPasswordInput($password);
        $orm->setName('Weida Xue');
        $orm->setEmail('luckyweida@gmail.com');

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