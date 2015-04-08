<?php

/*
 * This file is part of the puli/assetic-extension package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Extension\Assetic\Asset;

use Assetic\Asset\AssetCollection;
use Assetic\Filter\FilterInterface;
use Assetic\Util\VarUtils;
use Puli\Repository\Api\ResourceRepository;
use Puli\Repository\Resource\FileResource;

/**
 * An asset for a Puli glob.
 *
 * The resources matching the glob are loaded from the Puli repository as soon
 * as this is necessary.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliGlobAsset extends AssetCollection implements PuliAsset
{
    /**
     * @var ResourceRepository
     */
    private $repo;

    /**
     * string
     */
    private $glob;

    /**
     * @var bool
     */
    private $loaded = false;

    /**
     * Creates the asset.
     *
     * You can pass asset variables that occur in the glob. You can later set
     * the variables by calling {@link setValues()}.
     *
     * @param ResourceRepository $repo The resource repository.
     * @param string             $glob The glob.
     * @param array              $vars The asset variables.
     */
    public function __construct(ResourceRepository $repo, $glob, array $vars = array())
    {
        parent::__construct(array(), array(), null, $vars);

        $this->repo = $repo;
        $this->glob = $glob;
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        if (!$this->loaded) {
            $this->loadResourcesFromRepo();
        }

        return parent::all();
    }

    /**
     * {@inheritdoc}
     */
    public function load(FilterInterface $additionalFilter = null)
    {
        if (!$this->loaded) {
            $this->loadResourcesFromRepo();
        }

        parent::load($additionalFilter);
    }

    /**
     * {@inheritdoc}
     */
    public function dump(FilterInterface $additionalFilter = null)
    {
        if (!$this->loaded) {
            $this->loadResourcesFromRepo();
        }

        return parent::dump($additionalFilter);
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified()
    {
        if (!$this->loaded) {
            $this->loadResourcesFromRepo();
        }

        return parent::getLastModified();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        if (!$this->loaded) {
            $this->loadResourcesFromRepo();
        }

        return parent::getIterator();
    }

    private function loadResourcesFromRepo()
    {
        $glob = VarUtils::resolve($this->glob, $this->getVars(), $this->getValues());

        // Lazily load the resources. If the resources are stored in a database,
        // we only want to fetch them if really necessary.
        foreach ($this->repo->find($glob) as $resource) {
            // Ignore non-file resources
            if (!$resource instanceof FileResource) {
                continue;
            }

            $this->add(new PuliResourceAsset($resource));
        }

        $this->loaded = true;
    }
}
