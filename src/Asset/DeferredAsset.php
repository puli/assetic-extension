<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Extension\Assetic\Asset;

use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterCollection;
use Assetic\Filter\FilterInterface;
use Puli\Extension\Assetic\Factory\PuliAssetFactory;

/**
 * A proxy for an asset that is loaded lazily.
 *
 * The inner asset is set as soon as {@link setValues()} was called. The reason
 * for that is that we need a variable-free input string to determine whether
 * a path is a Puli path or a file system path.
 *
 * For example, the relative path "trans/messages.{locale}.txt" could be both
 * a Puli path (relative to Puli's current directory) and a file system path
 * (relative to one of the predefined roots). Once the "locale" variable has
 * been set - for example to "en" - we can figure out whether one of the
 * following paths exists:
 *
 *  * {$currentDir}/trans/messages.en.txt
 *  * {$roots[0]}/trans/messages.en.txt
 *  * ...
 *  * {$roots[n]}/trans/messages.en.txt
 *
 * The current Puli directory is the Puli directory of the Twig template that
 * contains the asset definition.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DeferredAsset implements AssetInterface
{
    /**
     * @var PuliAssetFactory
     */
    private $factory;

    /**
     * @var AssetInterface
     */
    private $innerAsset;

    /**
     * @var string
     */
    private $input;

    /**
     * @var FilterCollection
     */
    private $filters;

    /**
     * @var string|null
     */
    private $content;

    /**
     * @var string|null
     */
    private $targetPath;

    /**
     * @var string
     */
    private $currentDir;

    /**
     * @var string[]
     */
    private $roots;

    /**
     * @var array
     */
    private $vars;

    /**
     * @var array
     */
    private $values = array();

    /**
     * Creates a deferred asset.
     *
     * @param PuliAssetFactory $factory    The factory for lazily creating the
     *                                     inner asset.
     * @param string           $input      The raw path of the asset.
     * @param string           $currentDir The current Puli directory.
     * @param string[]         $roots      The file system root directories.
     * @param string[]         $vars       The variables that can be set in
     *                                     input by calling {@link setValues()}.
     */
    public function __construct(PuliAssetFactory $factory, $input, $currentDir, array $roots = array(), array $vars = array())
    {
        $this->factory = $factory;
        $this->input = $input;
        $this->currentDir = $currentDir;
        $this->roots = $roots;
        $this->vars = $vars;
        $this->filters = new FilterCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function __clone()
    {
        $this->filters = clone $this->filters;
    }

    /**
     * {@inheritdoc}
     */
    public function ensureFilter(FilterInterface $filter)
    {
        if ($this->innerAsset) {
            $this->innerAsset->ensureFilter($filter);

            return;
        }

        // Store filters for later
        $this->filters->ensure($filter);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        if ($this->innerAsset) {
            return $this->innerAsset->getFilters();
        }

        return $this->filters->all();
    }

    /**
     * {@inheritdoc}
     */
    public function clearFilters()
    {
        if ($this->innerAsset) {
            $this->innerAsset->clearFilters();

            return;
        }

        $this->filters->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function load(FilterInterface $additionalFilter = null)
    {
        if (!$this->innerAsset) {
            $this->createInnerAsset();
        }

        return $this->innerAsset->load($additionalFilter);
    }

    /**
     * {@inheritdoc}
     */
    public function dump(FilterInterface $additionalFilter = null)
    {
        if (!$this->innerAsset) {
            $this->createInnerAsset();
        }

        return $this->innerAsset->dump($additionalFilter);
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        if (!$this->innerAsset) {
            $this->createInnerAsset();
        }

        return $this->innerAsset->getContent();
    }

    /**
     * {@inheritdoc}
     */
    public function setContent($content)
    {
        if ($this->innerAsset) {
            $this->innerAsset->setContent($content);

            return;
        }

        $this->content = $content;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceRoot()
    {
        if ($this->innerAsset) {
            return $this->innerAsset->getSourceRoot();
        }

        // Not accurate, but this method is called by AssetCollectionIterator
        // before setValues() is called, so we need to return something
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourcePath()
    {
        if ($this->innerAsset) {
            return $this->innerAsset->getSourcePath();
        }

        // Not 100% accurate, but this method is called by
        // AssetCollectionIterator before setValues() is called, so we need
        // to return something
        return $this->input;
    }

    /**
     * {@inheritdoc}
     */
    public function getSourceDirectory()
    {
        if (!$this->innerAsset) {
            $this->createInnerAsset();
        }

        return $this->innerAsset->getSourceDirectory();
    }

    /**
     * {@inheritdoc}
     */
    public function getTargetPath()
    {
        if ($this->innerAsset) {
            return $this->innerAsset->getTargetPath();
        }

        return $this->targetPath;
    }

    /**
     * {@inheritdoc}
     */
    public function setTargetPath($targetPath)
    {
        if ($this->innerAsset) {
            $this->innerAsset->setTargetPath($targetPath);

            return;
        }

        if ($this->vars) {
            foreach ($this->vars as $var) {
                if (false === strpos($targetPath, $var)) {
                    throw new \RuntimeException(sprintf('The asset target path "%s" must contain the variable "{%s}".', $targetPath, $var));
                }
            }
        }

        // Store target path for later
        $this->targetPath = $targetPath;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified()
    {
        if (!$this->innerAsset) {
            $this->createInnerAsset();
        }

        return $this->innerAsset->getLastModified();
    }

    /**
     * {@inheritdoc}
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * {@inheritdoc}
     */
    public function setValues(array $values)
    {
        if ($this->innerAsset) {
            throw new \RuntimeException('The asset values must not be changed once the inner asset was created.');
        }

        $this->values = $values;

        // Create th inner asset as soon as the values were set
        $this->createInnerAsset();
    }

    /**
     * {@inheritdoc}
     */
    public function getValues()
    {
        return $this->values;
    }

    private function createInnerAsset()
    {
        if ($this->innerAsset) {
            throw new \RuntimeException('The inner asset must be created only once.');
        }

        $this->innerAsset = $this->factory->parseInputWithFixedValues($this->input, $this->currentDir, $this->roots, $this->vars, $this->values);

        foreach ($this->filters as $filter) {
            $this->innerAsset->ensureFilter($filter);
        }

        if (null !== $this->content) {
            $this->innerAsset->setContent($this->content);
        }

        if (null !== $this->targetPath) {
            $this->innerAsset->setTargetPath($this->targetPath);
        }

        // GC
        $this->factory = null;
        $this->input = null;
        $this->currentDir = null;
        $this->roots = null;
        $this->content = null;
        $this->targetPath = null;
    }
}
