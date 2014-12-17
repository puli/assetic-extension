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

use Assetic\Asset\BaseAsset;
use Assetic\Filter\FilterInterface;
use Puli\Repository\Resource\FileResource;

/**
 * An asset for a Puli file resource.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliResourceAsset extends BaseAsset implements PuliAssetInterface
{
    /**
     * @var FileResource
     */
    private $resource;

    /**
     * Creates the asset.
     *
     * You can pass asset variables that can later be set with
     * {@link setValues()}. However, the variable values won't have any effect
     * on the loaded resource.
     *
     * @param FileResource $resource The underlying Puli resource.
     * @param string[]              $vars     The asset variables.
     */
    public function __construct(FileResource $resource, array $vars = array())
    {
        parent::__construct(array(), null, $resource->getPath(), $vars);

        $this->resource = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function load(FilterInterface $additionalFilter = null)
    {
        $this->doLoad($this->resource->getContents(), $additionalFilter);
    }

    /**
     * {@inheritdoc}
     */
    public function getLastModified()
    {
        return $this->resource->getLastModifiedAt();
    }
}
