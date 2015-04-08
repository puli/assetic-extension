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

use Assetic\Asset\BaseAsset;
use Assetic\Filter\FilterInterface;
use Assetic\Util\VarUtils;
use Puli\Repository\Api\Resource\BodyResource;
use Puli\Repository\Api\ResourceRepository;
use RuntimeException;

/**
 * An asset for a Puli path.
 *
 * The resource is loaded from the Puli repository as soon as this is necessary.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliPathAsset extends BaseAsset implements PuliAsset
{
    /**
     * @var ResourceRepository
     */
    private $repo;

    /**
     * string
     */
    private $path;

    /**
     * @var BodyResource
     */
    private $resource;

    /**
     * @var bool
     */
    private $loaded = false;

    /**
     * Creates the asset.
     *
     * You can pass asset variables that occur in the path. You can later set
     * the variables by calling {@link setValues()}.
     *
     * @param ResourceRepository $repo The resource repository.
     * @param string             $path The Puli path.
     * @param array              $vars The asset variables.
     */
    public function __construct(ResourceRepository $repo, $path, array $vars = array())
    {
        parent::__construct(array(), null, $path, $vars);

        $this->repo = $repo;
        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function load(FilterInterface $additionalFilter = null)
    {
        if (!$this->loaded) {
            $this->loadResourceFromRepo();
        }

        $this->doLoad($this->resource->getBody(), $additionalFilter);
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified()
    {
        if (!$this->loaded) {
            $this->loadResourceFromRepo();
        }

        return $this->resource->getMetadata()->getModificationTime();
    }

    private function loadResourceFromRepo()
    {
        $path = VarUtils::resolve($this->path, $this->getVars(), $this->getValues());

        // Lazily load the resource. If the resource needs to be fetched from
        // the database, we only want to fetch it when really necessary.
        $resource = $this->repo->get($path);

        if (!$resource instanceof BodyResource) {
            throw new RuntimeException(sprintf(
                'The loaded resource is not a BodyResource. Got: %s',
                is_object($resource) ? get_class($resource) : gettype($resource)
            ));
        }

        $this->resource = $resource;
    }
}
