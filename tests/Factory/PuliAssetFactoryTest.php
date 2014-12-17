<?php

/*
 * This file is part of the Puli package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\Tests\Extension\Assetic\Factory;

use Assetic\Asset\AssetReference;
use Assetic\Asset\FileAsset;
use Assetic\Asset\HttpAsset;
use Assetic\AssetManager;
use Puli\Extension\Assetic\Asset\DeferredAsset;
use Puli\Extension\Assetic\Asset\PuliPathAsset;
use Puli\Extension\Assetic\Asset\PuliResourceAsset;
use Puli\Extension\Assetic\Factory\PuliAssetFactory;
use Puli\Repository\InMemoryRepository;
use Puli\Repository\Uri\UriRepository;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliAssetFactoryTest extends \PHPUnit_Framework_TestCase
{
    private static $fixturesDir;

    /**
     * @var InMemoryRepository
     */
    private $repo;

    /**
     * @var PuliAssetFactory
     */
    private $factory;

    public static function setUpBeforeClass()
    {
        self::$fixturesDir = __DIR__.'/Fixtures';
    }

    protected function setUp()
    {
        $this->repo = new InMemoryRepository();
        $this->repo->add('/webmozart/puli', self::$fixturesDir);
        $this->factory = new PuliAssetFactory($this->repo, self::$fixturesDir);
    }

    public function testCreatePuliAsset()
    {
        $asset = $this->factory->createAsset(
            array('/webmozart/puli/css/style.css')
        );

        $asset->setCurrentDir('/webmozart/puli');
        $assets = iterator_to_array($asset);

        /** @var PuliPathAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliPathAsset', $assets[0]);
        $this->assertNull($assets[0]->getSourceRoot());
        $this->assertSame('/webmozart/puli/css/style.css', $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertSame(array(), $assets[0]->getValues());

        $asset->load();

        $this->assertSame("/* style.css */\n", $assets[0]->getContent());
    }

    public function testCreatePuliAssetWithPredefinedName()
    {
        $asset = $this->factory->createAsset(
            array('/webmozart/puli/css/style.css'),
            array(),
            array('name' => 'mystyle')
        );

        $asset->setCurrentDir('/webmozart/puli');
        $assets = iterator_to_array($asset);

        $this->assertSame('assetic/mystyle.css', $asset->getTargetPath());

        /** @var PuliPathAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliPathAsset', $assets[0]);
        $this->assertNull($assets[0]->getSourceRoot());
        $this->assertSame('/webmozart/puli/css/style.css', $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertSame(array(), $assets[0]->getValues());
        $this->assertSame('assetic/mystyle_style_1.css', $assets[0]->getTargetPath());

        $asset->load();

        $this->assertSame("/* style.css */\n", $assets[0]->getContent());
    }

    public function testCreatePuliAssetWithPregeneratedName()
    {
        $name = $this->factory->generateAssetName(
            array('/webmozart/puli/css/style.css')
        );

        $asset = $this->factory->createAsset(
            array('/webmozart/puli/css/style.css'),
            array(),
            array('name' => $name)
        );

        $asset->setCurrentDir('/webmozart/puli');
        $assets = iterator_to_array($asset);

        // The current directory of the name should have been set
        $this->assertNotEmpty($name->__toString());
        $this->assertSame(sprintf('assetic/%s.css', $name), $asset->getTargetPath());

        /** @var PuliPathAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliPathAsset', $assets[0]);
        $this->assertNull($assets[0]->getSourceRoot());
        $this->assertSame('/webmozart/puli/css/style.css', $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertSame(array(), $assets[0]->getValues());
        $this->assertSame(sprintf('assetic/%s_style_1.css', $name), $assets[0]->getTargetPath());

        $asset->load();

        $this->assertSame("/* style.css */\n", $assets[0]->getContent());
    }

    public function testCreatePuliAssetWithVariable()
    {
        $asset = $this->factory->createAsset(
            array('/webmozart/puli/js/messages.{locale}.js'),
            array(),
            array('vars' => array('locale'))
        );

        $asset->setCurrentDir('/webmozart/puli');
        $asset->setValues(array('locale' => 'en'));
        $assets = iterator_to_array($asset);

        /** @var DeferredAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\DeferredAsset', $assets[0]);
        $this->assertNull($assets[0]->getSourceRoot());
        $this->assertSame('/webmozart/puli/js/messages.{locale}.js', $assets[0]->getSourcePath());
        $this->assertSame(array('locale'), $assets[0]->getVars());
        $this->assertSame(array('locale' => 'en'), $assets[0]->getValues());

        $asset->load();

        $this->assertSame("/* messages.en.js */\n", $assets[0]->getContent());
    }

    public function testCreatePuliAssetWithRelativePath()
    {
        $asset = $this->factory->createAsset(
            array('css/style.css')
        );

        $asset->setCurrentDir('/webmozart/puli');
        $assets = iterator_to_array($asset);

        /** @var PuliPathAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliPathAsset', $assets[0]);
        $this->assertNull($assets[0]->getSourceRoot());
        $this->assertSame('/webmozart/puli/css/style.css', $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertSame(array(), $assets[0]->getValues());

        $asset->load();

        $this->assertSame("/* style.css */\n", $assets[0]->getContent());
    }

    public function testCreatePuliAssetWithRelativePathAndVariables()
    {
        $asset = $this->factory->createAsset(
            array('js/messages.{locale}.js'),
            array(),
            array('vars' => array('locale'))
        );

        $asset->setCurrentDir('/webmozart/puli');
        $asset->setValues(array('locale' => 'en'));
        $assets = iterator_to_array($asset);

        /** @var PuliPathAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\DeferredAsset', $assets[0]);
        $this->assertNull($assets[0]->getSourceRoot());
        $this->assertSame('/webmozart/puli/js/messages.{locale}.js', $assets[0]->getSourcePath());
        $this->assertSame(array('locale'), $assets[0]->getVars());
        $this->assertSame(array('locale' => 'en'), $assets[0]->getValues());

        $asset->load();

        $this->assertSame("/* messages.en.js */\n", $assets[0]->getContent());
    }

    public function testCreatePuliGlobAsset()
    {
        $asset = $this->factory->createAsset(
            array('/webmozart/puli/css/*.css')
        );

        $asset->setCurrentDir('/webmozart/puli');

        $this->assertSame(array(), $asset->getVars());

        $assets = iterator_to_array($asset);

        /** @var PuliResourceAsset[] $assets */
        $this->assertCount(2, $assets);
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliResourceAsset', $assets[0]);
        $this->assertNull($assets[0]->getSourceRoot());
        $this->assertSame('/webmozart/puli/css/reset.css', $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertSame(array(), $assets[0]->getValues());
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliResourceAsset', $assets[1]);
        $this->assertNull($assets[1]->getSourceRoot());
        $this->assertSame('/webmozart/puli/css/style.css', $assets[1]->getSourcePath());
        $this->assertSame(array(), $assets[1]->getVars());
        $this->assertSame(array(), $assets[1]->getValues());

        $asset->load();

        $this->assertSame("/* reset.css */\n", $assets[0]->getContent());
        $this->assertSame("/* style.css */\n", $assets[1]->getContent());
    }

    public function testCreatePuliGlobAssetWithVariables()
    {
        $this->markTestSkipped('Assetic does not accept variables in glob assets.');

        $asset = $this->factory->createAsset(
            array('/webmozart/puli/js/*.{locale}.js'),
            array(),
            array('vars' => array('locale'), 'values' => array('locale' => 'en'))
        );

        $this->assertSame(array('locale'), $asset->getVars());

        $asset->setCurrentDir('/webmozart/puli');
        $asset->setValues(array('locale' => 'en'));
        $assets = iterator_to_array($asset);

        /** @var PuliResourceAsset[] $assets */
        $this->assertCount(2, $assets);
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliResourceAsset', $assets[0]);
        $this->assertNull($assets[0]->getSourceRoot());
        $this->assertSame('/webmozart/puli/js/errors.en.js', $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertSame(array(), $assets[0]->getValues());
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliResourceAsset', $assets[1]);
        $this->assertNull($assets[1]->getSourceRoot());
        $this->assertSame('/webmozart/puli/js/messages.en.js', $assets[1]->getSourcePath());
        $this->assertSame(array(), $assets[1]->getVars());
        $this->assertSame(array(), $assets[1]->getValues());

        $asset->load();

        $this->assertSame("/* errors.en.js */\n", $assets[0]->getContent());
        $this->assertSame("/* messages.en.js */\n", $assets[1]->getContent());
    }

    public function testCreatePuliGlobAssetWithRelativePath()
    {
        $asset = $this->factory->createAsset(
            array('css/*.css')
        );

        $asset->setCurrentDir('/webmozart/puli');

        $this->assertSame(array(), $asset->getVars());

        $assets = iterator_to_array($asset);

        /** @var PuliResourceAsset[] $assets */
        $this->assertCount(2, $assets);
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliResourceAsset', $assets[0]);
        $this->assertNull($assets[0]->getSourceRoot());
        $this->assertSame('/webmozart/puli/css/reset.css', $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliResourceAsset', $assets[1]);
        $this->assertNull($assets[1]->getSourceRoot());
        $this->assertSame('/webmozart/puli/css/style.css', $assets[1]->getSourcePath());
        $this->assertSame(array(), $assets[1]->getVars());

        $asset->load();

        $this->assertSame("/* reset.css */\n", $assets[0]->getContent());
        $this->assertSame("/* style.css */\n", $assets[1]->getContent());
    }

    public function testCreatePuliGlobAssetWithRelativePathAndVariables()
    {
        $this->markTestSkipped('Assetic does not accept variables in glob assets.');

        $asset = $this->factory->createAsset(
            array('js/*.{locale}.js'),
            array(),
            array('vars' => array('locale'))
        );

        $this->assertSame(array('locale'), $asset->getVars());

        $asset->setCurrentDir('/webmozart/puli');
        $asset->setValues(array('locale' => 'en'));
        $assets = iterator_to_array($asset);

        /** @var PuliResourceAsset[] $assets */
        $this->assertCount(2, $assets);
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliResourceAsset', $assets[0]);
        $this->assertNull($assets[0]->getSourceRoot());
        $this->assertSame('/webmozart/puli/js/errors.en.js', $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertSame(array(), $assets[0]->getValues());
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliResourceAsset', $assets[1]);
        $this->assertNull($assets[1]->getSourceRoot());
        $this->assertSame('/webmozart/puli/js/messages.en.js', $assets[1]->getSourcePath());
        $this->assertSame(array(), $assets[1]->getVars());
        $this->assertSame(array(), $assets[1]->getValues());

        $asset->load();

        $this->assertSame("/* errors.en.js */\n", $assets[0]->getContent());
        $this->assertSame("/* messages.en.js */\n", $assets[1]->getContent());
    }

    public function testCreateFileAsset()
    {
        $asset = $this->factory->createAsset(
            array(self::$fixturesDir.'/css/style.css')
        );

        $asset->setCurrentDir('/webmozart/puli');
        $assets = iterator_to_array($asset);

        /** @var FileAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Assetic\Asset\FileAsset', $assets[0]);
        $this->assertSame(self::$fixturesDir, $assets[0]->getSourceRoot());
        $this->assertSame('css/style.css', $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertSame(array(), $assets[0]->getValues());

        $asset->load();

        $this->assertSame("/* style.css */\n", $assets[0]->getContent());
    }

    public function testCreateFileAssetWithPathRelativeToFactoryRoot()
    {
        $asset = $this->factory->createAsset(
            array('css/style.css')
        );

        $asset->setCurrentDir('/foo/bar');
        $assets = iterator_to_array($asset);

        /** @var FileAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Assetic\Asset\FileAsset', $assets[0]);
        $this->assertSame(self::$fixturesDir, $assets[0]->getSourceRoot());
        $this->assertSame('css/style.css', $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertSame(array(), $assets[0]->getValues());

        $asset->load();

        $this->assertSame("/* style.css */\n", $assets[0]->getContent());
    }

    public function testRelativePathSearchedInRootOptionBeforeFactoryRoot()
    {
        // css/style.css exists in:
        // * the factory root
        // * the root passed via the "root" option

        // The root passed via the "root" option wins
        $asset = $this->factory->createAsset(
            array('css/style.css'),
            array(),
            array('root' => self::$fixturesDir.'/custom-root')
        );

        $asset->setCurrentDir('/foo/bar');
        $assets = iterator_to_array($asset);

        /** @var FileAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Assetic\Asset\FileAsset', $assets[0]);
        $this->assertSame(self::$fixturesDir.'/custom-root', $assets[0]->getSourceRoot());
        $this->assertSame('css/style.css', $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertSame(array(), $assets[0]->getValues());

        $asset->load();

        $this->assertSame("/* custom style.css */\n", $assets[0]->getContent());
    }

    public function testRelativePathsSearchedInPuliRepositoryBeforeFileSystem()
    {
        // css/style.css exists in:
        // * the factory root
        // * the root passed via the "root" option
        // * the current Puli directory

        // The current Puli directory wins
        $asset = $this->factory->createAsset(
            array('css/style.css'),
            array(),
            array('root' => self::$fixturesDir.'/custom-root')
        );

        $asset->setCurrentDir('/webmozart/puli');
        $assets = iterator_to_array($asset);

        /** @var PuliPathAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliPathAsset', $assets[0]);
        $this->assertNull($assets[0]->getSourceRoot());
        $this->assertSame('/webmozart/puli/css/style.css', $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertSame(array(), $assets[0]->getValues());

        $asset->load();

        $this->assertSame("/* style.css */\n", $assets[0]->getContent());
    }

    public function testRelativePathsNotSearchedInPuliRepositoryIfCurrentDirectoryNull()
    {
        // css/style.css exists in:
        // * the factory root
        // * the root passed via the "root" option
        // * the current Puli directory

        // The current Puli directory wins
        $asset = $this->factory->createAsset(
            array('css/style.css'),
            array(),
            array('root' => self::$fixturesDir.'/custom-root')
        );

        $asset->setCurrentDir(null);
        $assets = iterator_to_array($asset);

        /** @var PuliPathAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Assetic\Asset\FileAsset', $assets[0]);
        $this->assertSame(self::$fixturesDir.'/custom-root', $assets[0]->getSourceRoot());
        $this->assertSame('css/style.css', $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertSame(array(), $assets[0]->getValues());

        $asset->load();

        $this->assertSame("/* custom style.css */\n", $assets[0]->getContent());
    }

    public function testCreateFileAssetWithVariables()
    {
        $asset = $this->factory->createAsset(
            array(self::$fixturesDir.'/js/messages.{locale}.js'),
            array(),
            array('vars' => array('locale'))
        );

        $asset->setCurrentDir('/webmozart/puli');
        $asset->setValues(array('locale' => 'en'));
        $assets = iterator_to_array($asset);

        /** @var DeferredAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\DeferredAsset', $assets[0]);
        $this->assertSame(self::$fixturesDir, $assets[0]->getSourceRoot());
        $this->assertSame('js/messages.{locale}.js', $assets[0]->getSourcePath());
        $this->assertSame(array('locale'), $assets[0]->getVars());
        $this->assertSame(array('locale' => 'en'), $assets[0]->getValues());

        $asset->load();

        $this->assertSame("/* messages.en.js */\n", $assets[0]->getContent());
    }

    public function getHttpUrls()
    {
        return array(
            array('http://example.com/foo.css', 'http://example.com', 'foo.css'),
            array('https://example.com/foo.css', 'https://example.com', 'foo.css'),
            array('//example.com/foo.css', 'http://example.com', 'foo.css'),
        );
    }

    /**
     * @dataProvider getHttpUrls
     */
    public function testCreateHttpAsset($sourceUrl, $sourceRoot, $sourcePath)
    {
        $asset = $this->factory->createAsset(
            array($sourceUrl)
        );

        $asset->setCurrentDir('/webmozart/puli');
        $assets = iterator_to_array($asset);

        /** @var HttpAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Assetic\Asset\HttpAsset', $assets[0]);
        $this->assertSame($sourceRoot, $assets[0]->getSourceRoot());
        $this->assertSame($sourcePath, $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertSame(array(), $assets[0]->getValues());
    }

    public function testCreateHttpAssetWithVariables()
    {
        $asset = $this->factory->createAsset(
            array('http://example.com/js/{locale}.json'),
            array(),
            array('vars' => array('locale'))
        );

        $asset->setCurrentDir('/webmozart/puli');
        $asset->setValues(array('locale' => 'en'));
        $assets = iterator_to_array($asset);

        /** @var HttpAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Assetic\Asset\HttpAsset', $assets[0]);
        $this->assertSame('http://example.com', $assets[0]->getSourceRoot());
        $this->assertSame('js/{locale}.json', $assets[0]->getSourcePath());
        $this->assertSame(array('locale'), $assets[0]->getVars());
        $this->assertSame(array('locale' => 'en'), $assets[0]->getValues());
    }

    /**
     * @dataProvider getHttpUrls
     */
    public function testCreateHttpAssetWithUriLocator($sourceUrl, $sourceRoot, $sourcePath)
    {
        $uriLocator = new UriRepository();
        $uriLocator->register('resource', $this->repo);
        $this->factory = new PuliAssetFactory($uriLocator, self::$fixturesDir);

        $asset = $this->factory->createAsset(
            array($sourceUrl)
        );

        $asset->setCurrentDir('/webmozart/puli');
        $assets = iterator_to_array($asset);

        /** @var HttpAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Assetic\Asset\HttpAsset', $assets[0]);
        $this->assertSame($sourceRoot, $assets[0]->getSourceRoot());
        $this->assertSame($sourcePath, $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertSame(array(), $assets[0]->getValues());
    }

    public function testCreateHttpAssetWithVariablesWithUriLocator()
    {
        $uriLocator = new UriRepository();
        $uriLocator->register('resource', $this->repo);
        $this->factory = new PuliAssetFactory($uriLocator, self::$fixturesDir);

        $asset = $this->factory->createAsset(
            array('http://example.com/js/{locale}.json'),
            array(),
            array('vars' => array('locale'))
        );

        $asset->setCurrentDir('/webmozart/puli');
        $asset->setValues(array('locale' => 'en'));
        $assets = iterator_to_array($asset);

        /** @var HttpAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Assetic\Asset\HttpAsset', $assets[0]);
        $this->assertSame('http://example.com', $assets[0]->getSourceRoot());
        $this->assertSame('js/{locale}.json', $assets[0]->getSourcePath());
        $this->assertSame(array('locale'), $assets[0]->getVars());
        $this->assertSame(array('locale' => 'en'), $assets[0]->getValues());
    }

    public function testCreatePuliUriAsset()
    {
        $uriLocator = new UriRepository();
        $uriLocator->register('resource', $this->repo);
        $this->factory = new PuliAssetFactory($uriLocator, self::$fixturesDir);

        $asset = $this->factory->createAsset(
            array('resource:///webmozart/puli/css/style.css')
        );

        $asset->setCurrentDir('/webmozart/puli');
        $assets = iterator_to_array($asset);

        /** @var PuliPathAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliPathAsset', $assets[0]);
        $this->assertNull($assets[0]->getSourceRoot());
        $this->assertSame('resource:///webmozart/puli/css/style.css', $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertSame(array(), $assets[0]->getValues());

        $asset->load();

        $this->assertSame("/* style.css */\n", $assets[0]->getContent());
    }

    public function testCreatePuliUriAssetWithVariables()
    {
        $uriLocator = new UriRepository();
        $uriLocator->register('resource', $this->repo);
        $this->factory = new PuliAssetFactory($uriLocator, self::$fixturesDir);

        $asset = $this->factory->createAsset(
            array('resource:///webmozart/puli/js/messages.{locale}.js'),
            array(),
            array('vars' => array('locale'))
        );

        $asset->setCurrentDir('/webmozart/puli');
        $asset->setValues(array('locale' => 'en'));
        $assets = iterator_to_array($asset);

        /** @var PuliPathAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliPathAsset', $assets[0]);
        $this->assertNull($assets[0]->getSourceRoot());
        $this->assertSame('resource:///webmozart/puli/js/messages.{locale}.js', $assets[0]->getSourcePath());
        $this->assertSame(array('locale'), $assets[0]->getVars());
        $this->assertSame(array('locale' => 'en'), $assets[0]->getValues());

        $asset->load();

        $this->assertSame("/* messages.en.js */\n", $assets[0]->getContent());
    }

    public function testCreatePuliUriGlobAsset()
    {
        $uriLocator = new UriRepository();
        $uriLocator->register('resource', $this->repo);
        $this->factory = new PuliAssetFactory($uriLocator, self::$fixturesDir);

        $asset = $this->factory->createAsset(
            array('resource:///webmozart/puli/css/*.css')
        );

        $asset->setCurrentDir('/webmozart/puli');
        $assets = iterator_to_array($asset);

        /** @var PuliResourceAsset[] $assets */
        $this->assertCount(2, $assets);
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliResourceAsset', $assets[0]);
        $this->assertNull($assets[0]->getSourceRoot());
        // see https://github.com/puli/puli/issues/5
        $this->assertSame('/webmozart/puli/css/reset.css', $assets[0]->getSourcePath());
        $this->assertSame(array(), $assets[0]->getVars());
        $this->assertSame(array(), $assets[0]->getValues());
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliResourceAsset', $assets[1]);
        $this->assertNull($assets[1]->getSourceRoot());
        // see https://github.com/puli/puli/issues/5
        $this->assertSame('/webmozart/puli/css/style.css', $assets[1]->getSourcePath());
        $this->assertSame(array(), $assets[1]->getVars());
        $this->assertSame(array(), $assets[1]->getValues());

        $asset->load();

        $this->assertSame("/* reset.css */\n", $assets[0]->getContent());
        $this->assertSame("/* style.css */\n", $assets[1]->getContent());
    }

    public function testCreatePuliUriGlobAssetWithVariables()
    {
        $this->markTestSkipped('Assetic does not accept variables in glob assets.');

        $uriLocator = new UriRepository();
        $uriLocator->register('resource', $this->repo);
        $this->factory = new PuliAssetFactory($uriLocator, self::$fixturesDir);

        $asset = $this->factory->createAsset(
            array('resource:///webmozart/puli/js/*.{locale}.js'),
            array(),
            array('vars' => array('locale'))
        );

        $asset->setCurrentDir('/webmozart/puli');
        $asset->setValues(array('locale' => 'en'));
        $assets = iterator_to_array($asset);

        /** @var PuliResourceAsset[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliResourceAsset', $assets[0]);
        $this->assertNull($assets[0]->getSourceRoot());
        // see https://github.com/puli/puli/issues/5
        $this->assertSame('/webmozart/puli/js/errors.{locale}.js', $assets[0]->getSourcePath());
        $this->assertSame(array('locale'), $assets[0]->getVars());
        $this->assertSame(array('locale' => 'en'), $assets[0]->getValues());
        $this->assertInstanceOf('Puli\Extension\Assetic\Asset\PuliResourceAsset', $assets[1]);
        $this->assertNull($assets[1]->getSourceRoot());
        // see https://github.com/puli/puli/issues/5
        $this->assertSame('/webmozart/puli/js/messages.{locale}.js', $assets[1]->getSourcePath());
        $this->assertSame(array('locale'), $assets[1]->getVars());
        $this->assertSame(array('locale' => 'en'), $assets[1]->getValues());

        $asset->load();

        $this->assertSame("/* errors.en.js */\n", $assets[0]->getContent());
        $this->assertSame("/* messages.en.js */\n", $assets[1]->getContent());
    }

    public function testCreateAssetReference()
    {
        $reference = $this->getMock('Assetic\Asset\AssetInterface');
        $am = new AssetManager();
        $am->set('reference', $reference);

        $this->factory->setAssetManager($am);

        $asset = $this->factory->createAsset(
            array('@reference')
        );

        $asset->setCurrentDir('/webmozart/puli');
        $assets = iterator_to_array($asset);

        /** @var AssetReference[] $assets */
        $this->assertCount(1, $assets);
        $this->assertInstanceOf('Assetic\Asset\AssetReference', $assets[0]);
    }

    public function testGenerateNameSameNameForAbsoluteAndRelativePuliAssets()
    {
        $absolute = $this->factory->generateAssetName(
            array('/webmozart/puli/css/style.css')
        );
        $relative = $this->factory->generateAssetName(
            array('style.css')
        );

        // Don't use "/webmozart/puli" here, because that also corresponds to
        // the root directory of the factory
        $absolute->setCurrentDir('/webmozart/puli/css');
        $relative->setCurrentDir('/webmozart/puli/css');

        $this->assertSame($absolute->__toString(), $relative->__toString());
    }

    public function testGenerateDifferentNameForAbsoluteAndRelativePuliAssetsWithVariables()
    {
        // There's no way we can know whether the relative path is a Puli path
        // or a file system path
        // Name generation takes place before the variable values are known
        $absolute = $this->factory->generateAssetName(
            array('/webmozart/puli/js/messages.{locale}.js'),
            array(),
            array('vars' => array('locale'))
        );
        $relative = $this->factory->generateAssetName(
            array('js/messages.{locale}.js'),
            array(),
            array('vars' => array('locale'))
        );

        $absolute->setCurrentDir('/webmozart/puli');
        $relative->setCurrentDir('/webmozart/puli');

        $this->assertNotSame($absolute->__toString(), $relative->__toString());
    }

    public function testGenerateNameSameNameForAbsoluteAndRelativeFileAssets()
    {
        $absolute = $this->factory->generateAssetName(
            array(self::$fixturesDir.'/css/style.css')
        );
        $relative = $this->factory->generateAssetName(
            array('css/style.css')
        );

        $absolute->setCurrentDir('/foo/bar');
        $relative->setCurrentDir('/foo/bar');

        $this->assertSame($absolute->__toString(), $relative->__toString());
    }

    public function testGenerateDifferentNameForFileAssetsWithDifferentRoots()
    {
        // There's no way we can know whether the relative path is a Puli path
        // or a file system path
        // Name generation takes place before the variable values are known
        $customRoot = $this->factory->generateAssetName(
            array('css/style.css'),
            array(),
            array('root' => self::$fixturesDir.'/custom-root')
        );
        $factoryRoot = $this->factory->generateAssetName(
            array('css/style.css'),
            array(),
            array()
        );

        $customRoot->setCurrentDir('/webmozart/puli');
        $factoryRoot->setCurrentDir('/webmozart/puli');

        $this->assertNotSame($customRoot->__toString(), $factoryRoot->__toString());
    }

    public function testGenerateNameSameNameForPuliAndFileAssets()
    {
        $file = $this->factory->generateAssetName(
            array(self::$fixturesDir.'/css/style.css')
        );
        $puli = $this->factory->generateAssetName(
            array('/webmozart/puli/css/style.css')
        );

        $file->setCurrentDir('/webmozart/puli');
        $puli->setCurrentDir('/webmozart/puli');

        $this->assertSame($file->__toString(), $puli->__toString());
    }

    public function testGenerateDifferentNameForPuliAndFileGlobs()
    {
        // There's no way we can know that these globs correspond to the same
        // set of files
        $file = $this->factory->generateAssetName(
            array('css/*.css')
        );
        $puli = $this->factory->generateAssetName(
            array('*.css')
        );

        $file->setCurrentDir('/webmozart/puli/css');
        $puli->setCurrentDir('/webmozart/puli/css');

        $this->assertNotSame($file->__toString(), $puli->__toString());
    }

    public function testGenerateNameWithRelativePathDoesNotQueryPuliRepoIfCurrentDirNull()
    {
        $this->repo = $this->getMock('Puli\Repository\ResourceRepository');
        $this->repo->expects($this->never())
            ->method('get');
        $this->factory = new PuliAssetFactory($this->repo, self::$fixturesDir);

        $name = $this->factory->generateAssetName(
            array('style.css')
        );

        // Don't use "/webmozart/puli" here, because that also corresponds to
        // the root directory of the factory
        $name->setCurrentDir(null);

        $this->assertNotEmpty($name->__toString());
    }
}
