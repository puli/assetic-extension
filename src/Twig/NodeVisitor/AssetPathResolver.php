<?php

/*
 * This file is part of the puli/assetic-extension package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AsseticExtension\Twig\NodeVisitor;

use Assetic\Extension\Twig\AsseticNode;
use Puli\AsseticExtension\Asset\LazyAssetCollection;
use Puli\TwigExtension\NodeVisitor\AbstractPathResolver;
use Puli\TwigExtension\PuliExtension;
use Twig_Node;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class AssetPathResolver extends AbstractPathResolver
{
    /**
     * Returns the priority for this visitor.
     *
     * Priority should be between -10 and 10 (0 is the default).
     *
     * @return integer The priority level
     */
    public function getPriority()
    {
        return PuliExtension::RESOLVE_PATHS;
    }

    /**
     * {@inheritdoc}
     */
    protected function processNode(Twig_Node $node)
    {
        if ($node instanceof AsseticNode) {
            $asset = $node->getAttribute('asset');

            if ($asset instanceof LazyAssetCollection) {
                $asset->setCurrentDir($this->currentDir);
            }
        }
    }
}
