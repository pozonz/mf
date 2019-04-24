<?php

namespace MillenniumFalcon\Core\Twig;

use Pz\Orm\_Model;
use Pz\Orm\Page;
use Pz\Redirect\RedirectException;
use Pz\Router\Tree;
use Pz\Service\DbService;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
    /**
     * @var Container
     */
    private $container;

    /**
     * Extension constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return array(
            'getenv' => new TwigFunction('getenv', 'getenv'),
            'css' => new TwigFunction('css', array($this, 'css')),
            'redirect' => new TwigFunction('redirect', [$this, 'throwRedirectException']),
            'not_found' => new TwigFunction('not_found', [$this, 'throwNotFoundException']),
            'http_exception' => new TwigFunction('http_exception', [$this, 'throwHttpException'])
        );
    }

    /**
     * @return array
     */
    public function getFilters()
    {
        return array(
            'jsondecode' => new TwigFilter('jsondecode', array($this, 'jsondecode')),
            'block' => new TwigFilter('block', array($this, 'block')),
            'section' => new TwigFilter('section', array($this, 'section')),
            'sections' => new TwigFilter('sections', array($this, 'sections')),
            'nestable' => new TwigFilter('nestable', array($this, 'nestable')),
            'nestablePges' => new TwigFilter('nestablePges', array($this, 'nestablePges')),
        );

    }

    public function css($path)
    {
//        while (@ob_end_clean());
//        var_dump($this->container->getParameter('kernel.project_dir') . '/public/' . $path);exit;
        return file_get_contents($this->container->getParameter('kernel.project_dir') . '/public/' . $path);
    }

    public function jsondecode($value)
    {
        return json_decode($value);
    }

    public function block($block)
    {
        if (!isset($block->status) || !$block->status || $block->status == 0) {
            return '';
        }
        return $this->container->get('twig')->render("fragments/{$block->twig}", (array)$block->values);
    }

    public function section($section)
    {
        if (!isset($section->status) || !$section->status || $section->status == 0) {
            return '';
        }
        $html = '';
        foreach ($section->blocks as $block) {
            $html .= $this->block($block);
        }
        return $html;
    }

    public function sections($sections)
    {
        if (gettype($sections) == 'string') {
            $sections = json_decode($sections);
        }

        $html = '';
        foreach ($sections as $section) {
            $html .= $this->section($section);
        }
        return $html;
    }

    /**
     * @param array $orms
     * @param _Model $model
     * @return \Pz\Router\InterfaceNode
     */
    public static function nestable(array $orms, _Model $model)
    {
        $tree = new Tree($orms);
        return $tree->getRoot();
    }

    /**
     * @param $status
     * @param $message
     */
    public function throwHttpException($status = Response::HTTP_INTERNAL_SERVER_ERROR, $message)
    {
        throw new HttpException($status, $message);
    }

    /**
     * @param $status
     * @param $location
     */
    public function throwRedirectException($status = Response::HTTP_FOUND, $location)
    {
        throw new RedirectException($location, $status);
    }

    /**
     * @param $message
     */
    public function throwNotFoundException($message = '')
    {
        throw new NotFoundHttpException($message);
    }
}