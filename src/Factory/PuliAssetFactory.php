<?php

/*
 * This file is part of the puli/assetic-extension package.
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
use Assetic\Util\VarUtils;
use Puli\Extension\Assetic\Asset\LazyAsset;
use Puli\Extension\Assetic\Asset\LazyAssetCollection;
use Puli\Extension\Assetic\Asset\LazyAssetName;
use Puli\Extension\Assetic\Asset\PuliGlobAsset;
use Puli\Extension\Assetic\Asset\PuliPathAsset;
use Puli\Repository\Api\Resource\FilesystemResource;
use Puli\Repository\Api\ResourceNotFoundException;
use Puli\Repository\Api\ResourceRepository;
use RuntimeException;
use Webmozart\PathUtil\Path;

/**
 * An asset factory that loads assets from a Puli repository.
 *
 * The factory is able to load the following input strings:
 *
 *  * an asset reference starting with "@"
 *  * a HTTP URI
 *  * an absolute file path
 *  * a file path relative to the factory's root directory
 *  * a file path relative to one of the root directories passed to the "root"
 *    option
 *  * an absolute Puli path
 *  * a Puli path relative to the Puli directory of the loaded Twig template
 *  * a Puli URI
 *
 * When an absolute path is passed, the factory will resolve it like this:
 *
 *  1. If a file with that path exists, a file asset is created.
 *  2. If a resource with the path exists in the Puli repository, a Puli asset
 *     is created.
 *  3. Otherwise an exception is thrown.
 *
 * When a relative path is passed, the factory proceeds like this:
 *
 *  1. If the path exists in the current Puli directory, a Puli asset is created.
 *  2. If the path exists in one of the roots passed to the "root" option,
 *     a file asset is created.
 *  3. If the path exists in the factory's root directory, a file asset is
 *     created.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliAssetFactory extends AssetFactory
{
    /**
     * @var ResourceRepository
     */
    private $repo;

    /**
     * @var string
     */
    private $root;

    /**
     * Creates the factory.
     *
     * @param ResourceRepository $repo  The Puli resource repository.
     * @param string             $root  The root directory of file resources.
     * @param bool               $debug Whether to enable debugging.
     */
    public function __construct(ResourceRepository $repo, $root, $debug = false)
    {
        parent::__construct($root, $debug);

        $this->repo = $repo;
        $this->root = $root;
    }

    /**
     * Generates an asset name.
     *
     * The method {@link LazyAssetName::setCurrentDir()} must be called
     * before the name is usable.
     *
     * The name is generated from the passed inputs, filters and options.
     * An "input" in Assetic's terminology is a reference string to an asset,
     * such as "css/*.css", "/webmozart/puli/style.css",
     * "@AcmeDemoBundle/Resources/css/style.css" etc.
     *
     * The inputs are normalized before generating the name:
     *
     *  * Absolute file system paths are made relative to the factory's root
     *    directory.
     *  * Absolute Puli paths are fetched from the repository, converted to
     *    file system paths (if possible) and made relative to the factory's
     *    root directory.
     *  * Relative Puli paths are made absolute based on the Puli directory of
     *    the currently loaded Twig templates. The resulting absolute Puli paths
     *    are converted to file system paths.
     *
     * @param string|string[] $inputs  An array of input strings.
     * @param string|string[] $filters An array of filter names.
     * @param array           $options An array of options.
     *
     * @return LazyAssetName The generated asset name.
     */
    public function generateAssetName($inputs, $filters = array(), $options = array())
    {
        // generateAssetNameForCurrentDir() is called as soon as the current
        // directory is known
        return new LazyAssetName($this, (array) $inputs, (array) $filters, (array) $options);
    }

    /**
     * Generates an asset name for a list of inputs relative to a given Puli
     * directory.
     *
     * @param string|null $currentDir The Puli directory of the currently loaded
     *                                Twig template. This is `null` if the
     *                                template was not loaded through Puli.
     * @param string[]    $inputs     An array of input strings.
     * @param string[]    $filters    An array of filter names.
     * @param array       $options    An array of options.
     *
     * @return string The asset name.
     *
     * @see generateAssetName()
     */
    public function generateAssetNameForCurrentDir($currentDir, array $inputs = array(), array $filters = array(), array $options = array())
    {
        $factory = $this;
        $flatInputs = array();

        array_walk_recursive($inputs, function ($input) use ($factory, $currentDir, &$flatInputs) {
            $flatInputs[] = $factory->normalizeInputForAssetName($input, $currentDir);
        });

        return parent::generateAssetName($flatInputs, $filters, $options);
    }

    /**
     * Normalizes an input in order to generate the name for an asset.
     *
     * An "input" in Assetic's terminology is a reference string to an asset,
     * such as "css/*.css", "/webmozart/puli/style.css",
     * "@AcmeDemoBundle/Resources/css/style.css" etc.
     *
     * The normalization logic is described in {@link generateAssetName()}.
     *
     * @param string      $input      The input string.
     * @param string|null $currentDir The Puli directory of the currently loaded
     *                                Twig template. This is `null` if the
     *                                template was not loaded through Puli.
     *
     * @return string The normalized input.
     *
     * @see generateAssetName()
     *
     * @internal This method is public so it can be used as callback. It should
     *           not be used by user code.
     */
    public function normalizeInputForAssetName($input, $currentDir)
    {
        if (!$this->mayBePuliInput($input)) {
            return $input;
        }

        // If $input is a Puli URI, return it unchanged
        if (false !== strpos($input, '://')) {
            return $input;
        }

        // The "root" option is not always set when generateAssetName() is
        // called, so we should depend on the global root only. If not, the
        // normalized paths differ for generateAssetName() and createAsset()

        // If $input is within $this->root, return the relative path from
        // $this->root
        if (Path::isBasePath($this->root, $input)) {
            return Path::makeRelative($input, $this->root);
        }

        // If $input is a glob, keep it as it is
        if (false !== strpos($input, '*')) {
            return $input;
        }

        // If $input is relative, check whether the absolute path based on
        // $this->root is a file and return the relative path in that case
        if (is_file(Path::makeAbsolute($input, $this->root))) {
            // If the path points to a file, return the relative path so that
            // the resulting generated names are independent of the root
            // directory
            return $input;
        }

        // If $input is an absolute or relative Puli path, return the corresponding
        // file system path if possible
        // Don't query the Puli repository for relative paths if the current
        // Puli directory is not set
        if (null !== $currentDir || Path::isAbsolute($input)) {
            try {
                $resource = $this->repo->get(Path::makeAbsolute($input, $currentDir));

                return $resource instanceof FilesystemResource
                    ? Path::makeRelative($resource->getFilesystemPath(), $this->root)
                    : $resource->getPath();
            } catch (ResourceNotFoundException $e) {
                // Continue
            }
        }

        // If we reach this point, then input may be:
        // * an absolute file path with variables: /dir/trans/messages.{locale}.tx
        //   that is not based on $this->root
        // * a relative file path with variables: trans/messages.{locale}.txt
        // * an absolute Puli path with variables: /webmozart/puli/trans/messages.{locale}.txt
        // * a relative Puli path with variables: ../trans/messages.{locale}.txt

        // We can't make any further decisions here, so return $input unchanged
        return $input;
    }

    /**
     * Creates an asset.
     *
     * The method {@link LazyAssetCollection::setCurrentDir()} must be
     * called before the asset is usable.
     *
     * See {@link PuliAssetFactory} for a description of the resolution logic
     * of inputs to assets.
     *
     * @param string|string[] $inputs  An array of input strings.
     * @param string|string[] $filters An array of filter names.
     * @param array           $options An array of options.
     *
     * @return LazyAssetCollection The created asset collection.
     *
     * @see PuliAssetFactory
     */
    public function createAsset($inputs = array(), $filters = array(), array $options = array())
    {
        // createAssetForCurrentDir() is called as soon as the current
        // directory is known
        return new LazyAssetCollection($this, (array) $inputs, (array) $filters, $options);
    }

    /**
     * Creates an asset for a list of inputs relative to the given Puli
     * directory.
     *
     * See {@link PuliAssetFactory} for a description of the resolution logic
     * of inputs to assets.
     *
     * @param string|null $currentDir The Puli directory of the currently loaded
     *                                Twig template. This is `null` if the
     *                                template was not loaded through Puli.
     * @param string[]    $inputs     An array of input strings.
     * @param string[]    $filters    An array of filter names.
     * @param array       $options    An array of options.
     *
     * @return AssetCollectionInterface The created asset.
     *
     * @see PuliAssetFactory
     */
    public function createAssetForCurrentDir($currentDir, array $inputs = array(), array $filters = array(), array $options = array())
    {
        if (isset($options['name'])) {
            // If the name is already set to a generated name, resolve that
            // name now
            if ($options['name'] instanceof LazyAssetName) {
                $options['name']->setCurrentDir($currentDir);
            }
        } else {
            // Always generate a name if none is set
            $options['name'] = $this->generateAssetNameForCurrentDir($currentDir, $inputs, $filters, $options);
        }

        // Remember the current directory for parseInput()
        $options['current_dir'] = $currentDir;

        return parent::createAsset($inputs, $filters, $options);
    }

    /**
     * Converts an input to an asset.
     *
     * An "input" in Assetic's terminology is a reference string to an asset,
     * such as "css/*.css", "/webmozart/puli/style.css",
     * "@AcmeDemoBundle/Resources/css/style.css" etc.
     *
     * The input may contain variables whose name are passed in the "vars"
     * option. For example, an input with the variable "locale" could look like
     * this: "js/messages.{locale}.js".
     *
     * If the input contains no variables, the decision whether the input
     * refers to a file or a Puli asset is made immediately using the resolution
     * logic described in {@link PuliAssetFactory}.
     *
     * If the input contains variables, that decision is deferred until the
     * values of the variables are known. In this case, a {@link LazyAsset}
     * is returned.
     *
     * @param string $input   The input string.
     * @param array  $options Additional options to be used.
     *
     * @return AssetInterface The created asset.
     */
    protected function parseInput($input, array $options = array())
    {
        if (!$this->mayBePuliInput($input)) {
            return parent::parseInput($input, $options);
        }

        // Puli URIs can be resolved immediately
        if (false !== (strpos($input, '://'))) {
            return $this->createPuliAsset($input, $options['vars']);
        }

        // Check whether we deal with a Puli asset or a file asset as soon as
        // the variable values have been set
        if (0 === count($options['vars'])) {
            return $this->parseInputWithFixedValues($input, $options['current_dir'], $options['root']);
        }

        // parseInputWithFixedValues() is called as soon as the variable values
        // are set
        return new LazyAsset($this, $input, $options['current_dir'], $options['root'], $options['vars']);
    }

    /**
     * Converts an input to an asset using the given variable values.
     *
     * An "input" in Assetic's terminology is a reference string to an asset,
     * such as "css/*.css", "/webmozart/puli/style.css",
     * "@AcmeDemoBundle/Resources/css/style.css" etc.
     *
     * Contrary to {@link parseInput()}, this method converts an input to an
     * asset that contains variables and whose variable values are already
     * known. For example, if the input is "js/messages.{locale}.js" with the
     * variable "locale" and its value "en", an asset is created for the input
     * "js/messages.en.js" using the resolution logic described in
     * {@link PuliAssetFactory}.
     *
     * @param string      $input      An input string containing variables.
     * @param string|null $currentDir The Puli directory of the currently loaded
     *                                Twig template. This is `null` if the
     *                                template was not loaded through Puli.
     * @param string[]    $roots      The file system root directories to search
     *                                for relative inputs.
     * @param string[]    $vars       The variables that may occur in the input.
     * @param string[]    $values     A mapping of variable names to values.
     *
     * @return AssetInterface The created asset.
     *
     * @see parseInput()
     */
    public function parseInputWithFixedValues($input, $currentDir, array $roots = array(), array $vars = array(), array $values = array())
    {
        if (Path::isAbsolute($input)) {
            return $this->parseAbsoluteInput($input, $roots, $vars, $values);
        }

        return $this->parseRelativeInput($input, $currentDir, $roots, $vars, $values);
    }

    /**
     * Creates a Puli asset.
     *
     * The path may contain variables in the form of "{var}". These can be
     * populated by calling {@link AssetInterface::setValues()} later on.
     *
     * @param string   $path   The Puli path of the asset.
     * @param string[] $vars   The asset variables.
     *
     * @return PuliPathAsset The created asset.
     */
    protected function createPuliAsset($path, array $vars = array())
    {
        if (false !== strpos($path, '*')) {
            return new PuliGlobAsset($this->repo, $path, $vars);
        }

        return new PuliPathAsset($this->repo, $path, $vars);
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
     *  * HTTP URLs: "http://www.google.com/favicon.ico" unless the backing
     *    repository is a URI repository which knows the scheme
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

        return true;
    }

    private function parseAbsoluteInput($input, array $roots, array $vars, array $values)
    {
        // If $input is an absolute file path with one of the given roots,
        // return a file asset
        foreach ($roots as $root) {
            if (Path::isBasePath($root, $input)) {
                $relative = Path::makeRelative($input, $root);
                $asset = $this->createFileAsset($input, $root, $relative, $vars);
                $asset->setValues($values);

                return $asset;
            }
        }

        $inputWithoutVars = VarUtils::resolve($input, $vars, $values);

        // If $input is an absolute file path with none of the given roots,
        // return a file asset with its root set to null
        if (is_file($inputWithoutVars)) {
            $asset = $this->createFileAsset($input, null, $input, $vars);
            $asset->setValues($values);

            return $asset;
        }

        // Otherwise assume to have an absolute Puli path
        if ($this->repo->contains($inputWithoutVars)) {
            $asset = $this->createPuliAsset($input, $vars);
            $asset->setValues($values);

            return $asset;
        }

        throw new RuntimeException(sprintf(
            'The asset "%s" could not be found.',
            $inputWithoutVars
        ));
    }

    private function parseRelativeInput($input, $currentDir, array $roots, array $vars, array $values)
    {
        // If the Puli resource relative to the current directory exists,
        // prefer to return that
        $inputWithoutVars = VarUtils::resolve($input, $vars, $values);

        if (null !== $currentDir && $this->repo->contains(Path::makeAbsolute($inputWithoutVars, $currentDir))) {
            $asset = $this->createPuliAsset(Path::makeAbsolute($input, $currentDir), $vars);
            $asset->setValues($values);

            return $asset;
        }


        // Otherwise check whether the relative path can be found in the roots
        foreach ($roots as $root) {
            if (is_file(Path::makeAbsolute($inputWithoutVars, $root))) {
                $absolute = Path::makeAbsolute($input, $root);
                $asset = $this->createFileAsset($absolute, $root, $input, $vars);
                $asset->setValues($values);

                return $asset;
            }
        }

        throw new RuntimeException(sprintf(
            'The asset "%s" could not be found. Searched the Puli directory %s '.
            'and the file system directories %s.',
            $inputWithoutVars,
            $currentDir,
            implode(', ', $roots)
        ));
    }
}
