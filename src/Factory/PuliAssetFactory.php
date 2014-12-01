<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Extension\Assetic\Factory;

use Assetic\Asset\AssetCollectionInterface;
use Assetic\Factory\AssetFactory;
use Puli\Extension\Assetic\Asset\PuliAsset;
use Puli\Extension\Assetic\Asset\DeferredAssetCollection;
use Puli\Extension\Assetic\Asset\DeferredAssetName;
use Puli\Filesystem\Resource\LocalResourceInterface;
use Puli\Repository\ResourceRepositoryInterface;
use Puli\Uri\UriRepositoryInterface;
use Webmozart\PathUtil\Path;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliAssetFactory extends AssetFactory
{
    /**
     * @var ResourceRepositoryInterface
     */
    private $repo;

    public function __construct(ResourceRepositoryInterface $repo, $debug = false)
    {
        parent::__construct('', $debug);

        $this->repo = $repo;
    }

    /**
     * Creates an asset.
     *
     * The method {@link DeferredAssetCollection::setCurrentDir()} must be
     * called before the asset is usable.
     *
     * @param string|string[] $inputs  An array of input strings.
     * @param string|string[] $filters An array of filter names.
     * @param array           $options An array of options.
     *
     * @return DeferredAssetCollection The created asset collection.
     */
    public function createAsset($inputs = array(), $filters = array(), array $options = array())
    {
        return new DeferredAssetCollection($this, (array) $inputs, (array) $filters, $options);
    }

    /**
     * Generates an asset name.
     *
     * The method {@link DeferredAssetName::setCurrentDir()} must be called
     * before the name is usable.
     *
     * @param string|string[] $inputs  An array of input strings.
     * @param string|string[] $filters An array of filter names.
     * @param array           $options An array of options.
     *
     * @return DeferredAssetName The generated asset name.
     */
    public function generateAssetName($inputs, $filters, $options = array())
    {
        return new DeferredAssetName($this, (array) $inputs, (array) $filters, (array) $options);
    }

    /**
     * Creates an asset for the given current directory.
     *
     * @param string   $currentDir The current directory.
     * @param string[] $inputs     An array of input strings.
     * @param string[] $filters    An array of filter names.
     * @param array    $options    An array of options.
     *
     * @return AssetCollectionInterface The created asset.
     */
    public function createAssetForCurrentDir($currentDir, array $inputs = array(), array $filters = array(), array $options = array())
    {
        $this->processInputs($inputs, $currentDir);

        if (isset($options['name'])) {
            // If the name is already set to a generated name, resolve that
            // name now
            if ($options['name'] instanceof DeferredAssetName) {
                $options['name']->setCurrentDir($currentDir);
            }
        } else {
            // Generate a name if none is set
            $options['name'] = $this->generateAssetNameForCurrentDir($currentDir, $inputs, $filters, $options);
        }

        return parent::createAsset($inputs, $filters, $options);
    }

    /**
     * Generates an asset name for the given current directory.
     *
     * @param string   $currentDir The current directory.
     * @param string[] $inputs     An array of input strings.
     * @param string[] $filters    An array of filter names.
     * @param array    $options    An array of options.
     *
     * @return string The asset name.
     */
    public function generateAssetNameForCurrentDir($currentDir, array $inputs = array(), array $filters = array(), array $options = array())
    {
        $this->processInputs($inputs, $currentDir);

        return parent::generateAssetName($inputs, $filters, $options);
    }

    /**
     * Turns relative Puli paths into absolute Puli paths in an array of inputs.
     *
     * @param string[] $inputs     The input strings.
     * @param string   $currentDir The current directory.
     */
    public function processInputs(array &$inputs, $currentDir)
    {
        $factory = $this;

        array_walk_recursive($inputs, function (&$input) use ($factory, $currentDir) {
            $input = $factory->processInput($input, $currentDir);
        });
    }

    /**
     * Turns a relative Puli path into an absolute Puli path.
     *
     * If the path is not a Puli path or is already absolute, it is not
     * modified.
     *
     * @param string $input      The input string.
     * @param string $currentDir The current directory.
     *
     * @return string The processed input string.
     */
    public function processInput($input, $currentDir)
    {
        if ('@' == $input[0]) {
            return $input;
        }

        if (0 === strpos($input, '//')) {
            return $input;
        }

        if (false !== ($offset = strpos($input, '://'))) {
            return $input;
        }

        // Don't execute is_file() for URIs!
        if (is_file($input)) {
            return $input;
        }

        $absolutePath = Path::makeAbsolute($input, $currentDir);

        if ($this->repo->contains($absolutePath)) {
            return $absolutePath;
        }

        return $input;
    }

    protected function parseInput($input, array $options = array())
    {
        if ('@' == $input[0]) {
            return $this->createAssetReference(substr($input, 1));
        }

        if (0 === strpos($input, '//')) {
            return $this->createHttpAsset($input, $options['vars']);
        }

        if (false !== ($offset = strpos($input, '://'))) {
            $scheme = substr($input, 0, $offset);
            $knownScheme = $this->repo instanceof UriRepositoryInterface
                && in_array($scheme, $this->repo->getSupportedSchemes());

            if (!$knownScheme) {
                return $this->createHttpAsset($input, $options['vars']);
            }
        } elseif (is_file($input)) {
            // Don't execute is_file() for URIs!
            return $this->createFileAsset($input, null, null, $options['vars']);
        }

        $resources = $this->repo->find($input);

        if (1 === count($resources)) {
            return $this->createPuliAsset($resources[0], $options['vars']);
        }

        $assets = array();

        foreach ($resources as $entry) {
            $assets[] = $this->createPuliAsset($entry, array());
        }

        return $this->createAssetCollection($assets, $options);
    }

    protected function createPuliAsset(LocalResourceInterface $resource, array $vars)
    {
        return new PuliAsset($resource->getPath(), $resource->getLocalPath(), array(), $vars);
    }
}
