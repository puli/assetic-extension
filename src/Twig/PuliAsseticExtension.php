<?php

/*
 * This file is part of the puli/assetic-extension package.
 *
 * (c) Bernhard Schussek <bschussek@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Puli\AsseticExtension\Twig;

use Puli\AsseticExtension\Twig\NodeVisitor\AssetPathResolver;
use Puli\Repository\Api\ResourceRepository;
use Twig_Extension;

/**
 * @since  1.0
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PuliAsseticExtension extends Twig_Extension
{
    /**
     * @var ResourceRepository
     */
    private $repo;

    public function __construct(ResourceRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'puli-assetic';
    }

    public function getNodeVisitors()
    {
        return array(new AssetPathResolver($this->repo));
    }
}
