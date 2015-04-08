<?php

/*
 * This file is part of the puli/assetic-extension package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AsseticExtension\Asset;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;
use IteratorAggregate;
use IteratorIterator;
use Puli\AsseticExtension\Factory\PuliAssetFactory;
use RuntimeException;

/**
 * A collection of assets that is loaded lazily.
 *
 * The assets in the collection are loaded as soon as {@link setCurrentDir()}
 * is called. As a result, asset paths may be given relative to the directory
 * of the loaded Twig template.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LazyAssetCollection implements IteratorAggregate, AssetCollectionInterface
{
    /**
     * @var PuliAssetFactory
     */
    private $factory;

    /**
     * @var string[]
     */
    private $inputs;

    /**
     * @var string[]
     */
    private $filters;

    /**
     * @var array
     */
    private $options;

    /**
     * @var AssetCollectionInterface
     */
    private $innerCollection;

    /**
     * Creates a new asset collection.
     *
     * @param PuliAssetFactory $factory The factory used to create the assets.
     * @param string[]         $inputs  The raw asset paths.
     * @param string[]         $filters The asset filters to apply.
     * @param array            $options Additional options for constructing the
     *                                  assets.
     */
    public function __construct(PuliAssetFactory $factory, array $inputs, array $filters, array $options)
    {
        $this->factory = $factory;
        $this->inputs = $inputs;
        $this->filters = $filters;
        $this->options = $options;
    }

    /**
     * Sets the current Puli directory.
     *
     * The current Puli directory is directory of the currently loaded Twig
     * template. For example, if the template "/webmozart/puli/views/index.html"
     * is being loaded, then "/webmozart/puli/views" is the current directory.
     * If asset inputs are given as relative path, the relative paths are
     * searched for in this directory of the Puli repository.
     *
     * The assets are loaded when this method is called. Once loaded, the
     * current directory cannot be changed anymore. Hence this method can only
     * be called once.
     *
     * @param string $currentDir The current Puli directory.
     *
     * @throws RuntimeException If the method was already called before.
     */
    public function setCurrentDir($currentDir)
    {
        if ($this->innerCollection) {
            throw new RuntimeException('The current directory must be set before loading the asset collection.');
        }

        $this->loadCollection($currentDir);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function all()
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->all();
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function add(AssetInterface $asset)
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->add($asset);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function removeLeaf(AssetInterface $leaf, $graceful = false)
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->removeLeaf($leaf, $graceful);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function replaceLeaf(AssetInterface $needle, AssetInterface $replacement, $graceful = false)
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->replaceLeaf($needle, $replacement, $graceful);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function ensureFilter(FilterInterface $filter)
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->ensureFilter($filter);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function getFilters()
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->getFilters();
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function clearFilters()
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->clearFilters();
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function load(FilterInterface $additionalFilter = null)
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->load($additionalFilter);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function dump(FilterInterface $additionalFilter = null)
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->dump($additionalFilter);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function getContent()
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->getContent();
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function setContent($content)
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->setContent($content);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function getSourceRoot()
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->getSourceRoot();
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function getSourcePath()
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->getSourcePath();
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function getSourceDirectory()
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->getSourceDirectory();
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function getTargetPath()
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->getTargetPath();
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function setTargetPath($targetPath)
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->setTargetPath($targetPath);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function getLastModified()
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->getLastModified();
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function getVars()
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->getVars();
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function setValues(array $values)
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->setValues($values);
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function getValues()
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return $this->innerCollection->getValues();
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException If the current directory has not yet been set.
     */
    public function getIterator()
    {
        if (!$this->innerCollection) {
            $this->loadCollection();
        }

        return new IteratorIterator($this->innerCollection);
    }

    protected function loadCollection($currentDir = null)
    {
        if ($this->innerCollection) {
            throw new RuntimeException('The asset collection was loaded already.');
        }

        // Load the inner collection
        // The current Puli directory is null if the currently loaded Twig
        // template was not loaded via the Puli loader
        $this->innerCollection = $this->factory->createAssetForCurrentDir($currentDir, $this->inputs, $this->filters, $this->options);

        // GC
        $this->factory = null;
        $this->inputs = null;
        $this->filters = null;
        $this->options = null;
    }
}
