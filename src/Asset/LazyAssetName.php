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

use Puli\AsseticExtension\Factory\PuliAssetFactory;
use RuntimeException;

/**
 * Proxy for a generated asset name.
 *
 * Asset names are generated from the raw asset paths (the "inputs"). Inputs
 * can have several forms:
 *
 *  * absolute file system paths
 *  * file system paths relative to one of a few predefined root directories
 *  * absolute Puli paths
 *  * Puli paths relative to the Puli directory of the loaded Twig template
 *  * ...
 *
 * We want to make sure that assets corresponding to the same file on the file
 * system receive the same generated name, regardless of whether they are
 * referred to by an absolute or a relative, by a Puli or by a file system path.
 * Therefore we need to delay the generation of the name until the following
 * information is known:
 *
 *  * the predefined root directories
 *  * the current Puli directory
 *
 * The root directories are static. The Puli directory, however, depends on the
 * loaded Twig template. Hence this class delays the name generation until the
 * method {@link setCurrentDir()} was called.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class LazyAssetName
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
     * @var string
     */
    private $name = '';

    /**
     * Creates a new asset name proxy.
     *
     * @param PuliAssetFactory $factory The factory used to generate the name.
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
     * The asset name is generated when this method is called. Once generated,
     * the name cannot be changed anymore. Hence this method can be called only
     * once.
     *
     * @param string $currentDir The current Puli directory.
     *
     * @throws RuntimeException If the method was already called before.
     */
    public function setCurrentDir($currentDir)
    {
        if ($this->name) {
            throw new RuntimeException('The current directory must be set only once.');
        }

        $this->name = $this->factory->generateAssetNameForCurrentDir($currentDir, $this->inputs, $this->filters, $this->options);

        // GC
        $this->factory = null;
        $this->inputs = null;
        $this->filters = null;
        $this->options = null;
    }

    /**
     * Returns the generated name.
     *
     * @return string The generated name.
     */
    public function __toString()
    {
        return $this->name;
    }
}
