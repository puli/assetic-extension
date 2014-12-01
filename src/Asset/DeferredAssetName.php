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

use Puli\Extension\Assetic\Factory\PuliAssetFactory;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DeferredAssetName
{
    /**
     * @var PuliAssetFactory
     */
    private $factory;

    private $inputs;

    private $filters;

    private $options;

    private $name;

    public function __construct(PuliAssetFactory $factory, array $inputs, array $filters, array $options)
    {
        $this->factory = $factory;
        $this->inputs = $inputs;
        $this->filters = $filters;
        $this->options = $options;
    }

    public function setCurrentDir($currentDir)
    {
        if ($this->name) {
            return;
        }

        $this->name = $this->factory->generateAssetNameForCurrentDir($currentDir, $this->inputs, $this->filters, $this->options);

        // GC
        $this->factory = null;
        $this->inputs = null;
        $this->filters = null;
        $this->options = null;
    }

    public function __toString()
    {
        return $this->name;
    }
}
