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

use Assetic\Asset\StringAsset;

/**
 * An asset with static content.
 *
 * This asset stores its content in memory. It is mainly useful for testing.
 *
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliStringAsset extends StringAsset implements PuliAsset
{
    /**
     * Creates the asset.
     *
     * @param string $path    The Puli path.
     * @param array  $content The asset's content.
     * @param array  $filters The filters to apply.
     */
    public function __construct($path, $content, $filters = array())
    {
        parent::__construct($content, $filters, '/', $path);
    }
}
