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
use Assetic\Asset\AssetInterface;
use Assetic\Factory\AssetFactory;
use Puli\Extension\Assetic\Asset\DeferredAssetCollection;
use Puli\Extension\Assetic\Asset\DeferredAssetName;
use Puli\Extension\Assetic\Asset\PuliAsset;
use Puli\Repository\Filesystem\Resource\LocalResourceInterface;
use Puli\Repository\ResourceRepositoryInterface;
use Puli\Repository\Uri\UriRepositoryInterface;
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
        $inputs = $this->inputsToAbsolutePaths($inputs, $currentDir);

        if (isset($options['name'])) {
            // If the name is already set to a generated name, resolve that
            // name now
            if ($options['name'] instanceof DeferredAssetName) {
                $options['name']->setCurrentDir($currentDir);
            }
        } else {
            $inputsForName = $this->inputsToLocalPaths($inputs);

            // Generate a name if none is set
            $options['name'] = parent::generateAssetName($inputsForName, $filters, $options);
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
        // Convert relative Puli paths to absolute paths so that both have the
        // same generated name
        $inputs = $this->inputsToAbsolutePaths($inputs, $currentDir);

        // Convert Puli paths to file paths (where possible) so that both have
        // the same generated name
        $inputs = $this->inputsToLocalPaths($inputs);

        return parent::generateAssetName($inputs, $filters, $options);
    }

    /**
     * Converts an input to an asset.
     *
     * An "input" in Assetic's terminology is a reference string to an asset,
     * such as "css/*.css", "/webmozart/puli/style.css",
     * "@AcmeDemoBundle/Resources/css/style.css" etc.
     *
     * @param string $input   The input.
     * @param array  $options Additional options to be used.
     *
     * @return AssetInterface The created asset.
     */
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

    /**
     * Creates a Puli asset.
     *
     * @param LocalResourceInterface $resource The resource to load.
     * @param array                  $vars     The asset variables.
     *
     * @return PuliAsset The created asset.
     */
    protected function createPuliAsset(LocalResourceInterface $resource, array $vars)
    {
        return new PuliAsset($resource->getPath(), $resource->getLocalPath(), array(), $vars);
    }

    /**
     * Returns whether an input may be a Puli path.
     *
     * An "input" in Assetic's terminology is a reference string to an asset,
     * such as "css/*.css", "/webmozart/puli/style.css",
     * "@AcmeDemoBundle/Resources/css/style.css" etc.
     *
     * This method returns `false` for:
     *
     *  * asset references: "@assetname"
     *  * HTTP assets: "//www.google.com/favicon.ico"
     *  * HTTP URLs: "http://www.google.com/favicon.ico"
     *  * real file paths
     *
     * @param string $input The input string.
     *
     * @return bool Whether the input string may be a Puli path.
     */
    private function mayBePuliInput($input)
    {
        if ('@' == $input[0]) {
            return false;
        }

        if (0 === strpos($input, '//')) {
            return false;
        }

        if (false !== ($offset = strpos($input, '://'))) {
            return false;
        }

        // Don't execute is_file() for URIs!
        if (is_file($input)) {
            return false;
        }

        return true;
    }

    /**
     * Converts inputs to absolute Puli paths.
     *
     * An "input" in Assetic's terminology is a reference string to an asset,
     * such as "css/*.css", "/webmozart/puli/style.css",
     * "@AcmeDemoBundle/Resources/css/style.css" etc.
     *
     * This method checks the array of inputs for relative Puli paths based on
     * the given current directory. Each relative path that can be converted to
     * an absolute path that exists in the repository is replaced by that
     * absolute path.
     *
     * @param string[] $inputs     The input strings.
     * @param string   $currentDir The current directory.
     *
     * @return string[] The processed inputs.
     */
    private function inputsToAbsolutePaths(array &$inputs, $currentDir)
    {
        $factory = $this;
        $output = array();

        array_walk_recursive($inputs, function ($input) use ($factory, $currentDir, &$output) {
            $output[] = $factory->inputToAbsolutePath($input, $currentDir);
        });

        return $output;
    }

    /**
     * Tries to convert an input to an absolute Puli path.
     *
     * @param string $input      The input string.
     * @param string $currentDir The current directory.
     *
     * @return string The processed input.
     *
     * @see inputsToAbsolutePaths()
     */
    public function inputToAbsolutePath($input, $currentDir)
    {
        if (!$this->mayBePuliInput($input)) {
            return $input;
        }

        $absolutePath = Path::makeAbsolute($input, $currentDir);

        if ($this->repo->contains($absolutePath)) {
            return $absolutePath;
        }

        return $input;
    }

    /**
     * Converts inputs to local file paths.
     *
     * An "input" in Assetic's terminology is a reference string to an asset,
     * such as "css/*.css", "/webmozart/puli/style.css",
     * "@AcmeDemoBundle/Resources/css/style.css" etc.
     *
     * This method checks the array of inputs for Puli paths that refer to
     * physical files on the local file system. Each such Puli path is replaced
     * by the path to the file or the paths to the files if the Puli path
     * is a selector with a wildcard: "/webmozart/css/*.css".
     *
     * @param string[] $inputs The input strings.
     *
     * @return string The processed inputs.
     */
    private function inputsToLocalPaths(array &$inputs)
    {
        $factory = $this;
        $output = array();

        array_walk_recursive($inputs, function ($input) use ($factory, &$output) {
            foreach ($factory->inputToLocalPaths($input) as $localPath) {
                $output[] = $localPath;
            }
        });

        return $output;
    }

    /**
     * Tries to convert a Puli path to one or more local file paths.
     *
     * @param string $input The input string.
     *
     * @return string[] The processed inputs.
     *
     * @see inputsToLocalPaths()
     */
    public function inputToLocalPaths($input)
    {
        if (!$this->mayBePuliInput($input)) {
            return array($input);
        }

        $resources = $this->repo->find($input);

        if (count($resources) > 0) {
            $inputs = array();

            foreach ($resources as $resource) {
                // Convert Puli paths to local paths, where possible
                // This is necessary because the generated name hashes depend
                // on the inputs, and we want the same physical resource to
                // have the same hash independent of whether it is referred to
                // by a Puli path or a local (real) path
                $inputs[] = $resource instanceof LocalResourceInterface
                    ? $resource->getLocalPath()
                    : $resource->getPath();
            }

            return $inputs;
        }

        return array($input);
    }
}
