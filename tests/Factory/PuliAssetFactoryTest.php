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
use Puli\Repository\ResourceRepository;
use Puli\Repository\Uri\UriRepository;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliAssetFactoryTest extends \PHPUnit_Framework_TestCase
{
    private static $fixturesDir;

    /**
     * @var ResourceRepository
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
        $this->repo = new ResourceRepository();
        $this->repo->add('/webmozart/puli', self::$fixturesDir);
        $this->factory = new PuliAssetFactory($this->repo, self::$fixturesDir);
    }

    public function testCreatePuliResourceAsset()
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
    }

    public function testCreatePuliResourceAssetWithVariable()
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
    }

    public function testCreatePuliResourceAssetWithRelativePath()
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
    }

    public function testCreatePuliResourceAssetWithRelativePathAndVariables()
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
    }

    public function testCreateFileAssetWithVariables()
    {
        $asset = $this->factory->createAsset(
            array(self::$fixturesDir.'/js/messages.{locale}.css'),
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
        $this->assertSame('js/messages.{locale}.css', $assets[0]->getSourcePath());
        $this->assertSame(array('locale'), $assets[0]->getVars());
        $this->assertSame(array('locale' => 'en'), $assets[0]->getValues());
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
            array('http://example.com/trans/{locale}.json'),
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
        $this->assertSame('trans/{locale}.json', $assets[0]->getSourcePath());
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
            array('http://example.com/trans/{locale}.json'),
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
        $this->assertSame('trans/{locale}.json', $assets[0]->getSourcePath());
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
    }

    public function testCreatePuliUriAssetWithVariables()
    {
        $uriLocator = new UriRepository();
        $uriLocator->register('resource', $this->repo);
        $this->factory = new PuliAssetFactory($uriLocator, self::$fixturesDir);

        $asset = $this->factory->createAsset(
            array('resource:///webmozart/puli/trans/messages.{locale}.js'),
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
        $this->assertSame('resource:///webmozart/puli/trans/messages.{locale}.js', $assets[0]->getSourcePath());
        $this->assertSame(array('locale'), $assets[0]->getVars());
        $this->assertSame(array('locale' => 'en'), $assets[0]->getValues());
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
}
