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

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Asset\AssetInterface;
use Assetic\Filter\FilterInterface;
use Puli\Extension\Assetic\Factory\PuliAssetFactory;

/**
 * Collection of assets that is loaded lazily.
 *
 * The method {@link setCurrentDir()} needs to be called before any other method
 * may be used on this class.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DeferredAssetCollection implements \IteratorAggregate, AssetCollectionInterface
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

    public function __construct(PuliAssetFactory $factory, array $inputs, array $filters, array $options)
    {
        $this->factory = $factory;
        $this->inputs = $inputs;
        $this->filters = $filters;
        $this->options = $options;
    }

    public function setCurrentDir($currentDir)
    {
        if ($this->innerCollection) {
            return;
        }

        // Load the inner collection
        $this->innerCollection = $this->factory->createAssetForCurrentDir($currentDir, $this->inputs, $this->filters, $this->options);

        // GC
        $this->factory = null;
        $this->inputs = null;
        $this->filters = null;
        $this->options = null;
    }

    public function all()
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->all();
    }

    public function add(AssetInterface $asset)
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->add($asset);
    }

    public function removeLeaf(AssetInterface $leaf, $graceful = false)
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->removeLeaf($leaf, $graceful);
    }

    public function replaceLeaf(AssetInterface $needle, AssetInterface $replacement, $graceful = false)
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->replaceLeaf($needle, $replacement, $graceful);
    }

    public function ensureFilter(FilterInterface $filter)
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->ensureFilter($filter);
    }

    public function getFilters()
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->getFilters();
    }

    public function clearFilters()
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->clearFilters();
    }

    public function load(FilterInterface $additionalFilter = null)
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->load($additionalFilter);
    }

    public function dump(FilterInterface $additionalFilter = null)
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->dump($additionalFilter);
    }

    public function getContent()
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->getContent();
    }

    public function setContent($content)
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->setContent($content);
    }

    public function getSourceRoot()
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->getSourceRoot();
    }

    public function getSourcePath()
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->getSourcePath();
    }

    public function getSourceDirectory()
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->getSourceDirectory();
    }

    public function getTargetPath()
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->getTargetPath();
    }

    public function setTargetPath($targetPath)
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->setTargetPath($targetPath);
    }

    public function getLastModified()
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->getLastModified();
    }

    public function getVars()
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->getVars();
    }

    public function setValues(array $values)
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->setValues($values);
    }

    public function getValues()
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return $this->innerCollection->getValues();
    }

    public function getIterator()
    {
        if (!$this->innerCollection) {
            throw new \BadMethodCallException('The current directory must be set first.');
        }

        return new \IteratorIterator($this->innerCollection);
    }
}
