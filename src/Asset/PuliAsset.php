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

use Assetic\Asset\AssetInterface;

/**
 * Marks assets whose source path is a Puli path.
 *
 * For each asset that implements this interface, the {@link getSourcePath()}
 * method must return an absolute Puli path:
 *
 *     echo $asset->getSourcePath();
 *     // => "/webmozart/puli/css/style.css"
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface PuliAsset extends AssetInterface
{
}
