<?php

declare(strict_types=1);

namespace Webfactory\Html5TagRewriter\Handler;

use Dom\Document;
use Dom\Node;
use Dom\XPath;
use Override;
use Webfactory\Html5TagRewriter\RewriteHandler;

/**
 * Abstract base class for RewriteHandler implementations.
 */
abstract class BaseRewriteHandler implements RewriteHandler
{
    #[Override]
    public function match(Node $node): void
    {
    }

    #[Override]
    public function afterMatches(Document $document, XPath $xpath): void
    {
    }
}
