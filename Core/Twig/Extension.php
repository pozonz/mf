<?php

namespace MillenniumFalcon\Core\Twig;

use BlueM\Tree;
use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\ORM\_Model;
use MillenniumFalcon\Core\ORM\FragmentBlock;
use MillenniumFalcon\Core\SymfonyKernel\RedirectException;

use MillenniumFalcon\Core\Tree\RawData;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class Extension extends AbstractExtension
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * Extension constructor.
     * @param Connection $connection
     * @param KernelInterface $kernel
     * @param Environment $environment
     */
    public function __construct(Connection $connection, KernelInterface $kernel, Environment $environment)
    {
        $this->connection = $connection;
        $this->kernel = $kernel;
        $this->environment = $environment;
    }

    /**
     * @return array
     */
    public function getFunctions(): array
    {
        return array(
            'getenv' => new TwigFunction('getenv', fn($arg) => $_ENV[$arg] ?? null),
            'redirect' => new TwigFunction('redirect', [$this, 'throwRedirectException']),
            'not_found' => new TwigFunction('not_found', [$this, 'throwNotFoundException']),
            'http_exception' => new TwigFunction('http_exception', [$this, 'throwHttpException']),
            'file_exists' => new TwigFunction('file_exists', [$this, 'file_exists']),
        );
    }

    /**
     * @param $filepath
     * @return bool
     */
    public function file_exists($filepath)
    {
        $dir = __DIR__ . '/../../../../../public';
        return file_exists($dir . $filepath);
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

    /**
     * @return array
     */
    public function getFilters()
    {
        return array(
            'json_decode' => new TwigFilter('json_decode', array($this, 'json_decode')),
            'ksort' => new TwigFilter('ksort', array($this, 'ksort')),
            'sections' => new TwigFilter('sections', [$this, 'sections'], ['needs_environment'=> true, 'needs_context' => true]),
            'section' => new TwigFilter('section', [$this, 'section'], ['needs_environment'=> true, 'needs_context' => true]),
            'block' => new TwigFilter('block', [$this, 'block'],['needs_environment'=> true, 'needs_context' => true]),
            'nestablePges' => new TwigFilter('nestablePges', array($this, 'nestablePges')),
        );
    }

    /**
     * @param $value
     * @return mixed
     */
    public function json_decode($value)
    {
        return json_decode($value);
    }

    /**
     * @param $array
     * @return mixed
     */
    public function ksort($array)
    {
        ksort($array);
        return $array;
    }

    /**
     * @param $block
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function block(Environment $env, $context, $block)
    {
        if (!isset($block->status) || !$block->status || $block->status == 0) {
            return '';
        }

        /** @var FragmentBlock $blockOrig */
        $blockOrig = FragmentBlock::getById($this->connection, $block->block);

        if (file_exists("{$this->kernel->getProjectDir()}/templates/fragments/{$blockOrig->getTwig()}")) {
            return $this->environment->render("fragments/{$blockOrig->getTwig()}", array_merge($context, (array)$block->values, [
                '__block' => $block,
            ]));
        }
        return '';
    }

    /**
     * @param $section
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function section(Environment $env, $context, $section)
    {
        if (!isset($section->status) || !$section->status || $section->status == 0) {
            return '';
        }
        $html = '';
        foreach ($section->blocks as $block) {
            $html .= $this->block($env, $context, $block);
        }
        return $html;
    }

    /**
     * @param $sections
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function sections(Environment $env, $context, $sections)
    {
        if (gettype($sections) == 'string') {
            $sections = json_decode($sections);
        }

        foreach ($sections as $section) {
            $result = [];
            foreach ($section->blocks as $block) {
                if ($block->twig == 'indexing-block-section-anchor.twig' || $block->twig == 'indexing-block-section-anchor-sublevel.twig') {
                    if ($block->status == 1) {

                        if ($block->twig == 'indexing-block-section-anchor.twig' && $block->values->headingOnly != 1) {

                            $block->_idx = count($result) + 1;
                            $block->_children = [];
                            $result[] = $block;

                        } elseif ($block->twig == 'indexing-block-section-anchor-sublevel.twig' && count($result) > 0) {

                            $lastBlock = $result[count($result) - 1];
                            $block->_idx = $lastBlock->_idx . '.' . (count($lastBlock->_children) + 1);
                            $lastBlock->_children[] = $block;

                        }
                    }
                }
            }
        }


        $html = '';
        foreach ($sections as $section) {
            $html .= $this->section($env, $context, $section);
        }
        return $html;
    }

    /**
     * @param $pages
     * @param $cat
     * @return Tree
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

            $nodes[] = (array)new RawData([
                'id' => $page->getId(),
                'title' => $page->getTitle(),
                'parent' => $categoryParentValue,
                'rank' => (int)$categoryRankValue,
                'status' => $page->getStatus(),
                'closed' => $categoryClosedValue,
                'extraInfo' => $page->getUrl(),
                'extra1' => $page->getHideFromCmsNav(),
                'extra2' => $page->getHideFromWebNav(),
            ]);
        }

        usort($nodes, function($a, $b) {
            return $a['rank'] >= $b['rank'];
        });

        $tree = new Tree($nodes, [
            'buildwarningcallback' => function () {},
        ]);
        return $tree;
    }
}
