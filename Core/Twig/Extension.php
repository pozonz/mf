<?php

namespace MillenniumFalcon\Core\Twig;

use MillenniumFalcon\Core\Orm\_Model;
use MillenniumFalcon\Core\Redirect\RedirectException;
use MillenniumFalcon\Core\Nestable\Tree;

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
            'json_decode' => new TwigFilter('json_decode', array($this, 'json_decode')),
            'ksort' => new TwigFilter('ksort', array($this, 'ksort')),
            'block' => new TwigFilter('block', array($this, 'block')),
            'section' => new TwigFilter('section', array($this, 'section')),
            'sections' => new TwigFilter('sections', array($this, 'sections')),
            'nestablePges' => new TwigFilter('nestablePges', array($this, 'nestablePges')),
        );

    }


    public function json_decode($value)
    {
        return json_decode($value);
    }

    public function ksort($array)
    {
        ksort($array);
        return $array;
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
     * @param $pages
     * @param $cat
     * @return mixed
     */
    static public function nestablePges($pages, $cat)
    {
        $nodes = array();
        foreach ($pages as $page) {
            $category = $page->getCategory() ? (array)json_decode($page->getCategory()) : [];
            if (!in_array($cat, $category) && !($cat == 0 && count($category) == 0)) {
                continue;
            }
            $categoryParent = (array)json_decode($page->getCategoryParent());
            $categoryRank = (array)json_decode($page->getCategoryRank());
            $categoryClosed = (array)json_decode($page->getCategoryClosed());

            $categoryParentValue = isset($categoryParent["cat$cat"]) ? $categoryParent["cat$cat"] : 0;
            $categoryRankValue = isset($categoryRank["cat$cat"]) ? $categoryRank["cat$cat"] : 0;
            $categoryClosedValue = isset($categoryClosed["cat$cat"]) ? $categoryClosed["cat$cat"] : 0;

            $page->setParentId($categoryParentValue);
            $page->setRank($categoryRankValue);
            $page->setClosed($categoryClosedValue);

            $nodes[] =$page;
        }

        $tree = new Tree($nodes);
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