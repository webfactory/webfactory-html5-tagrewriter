<?php

declare(strict_types=1);

namespace Webfactory\Html5TagRewriter\Tests\Fixtures;

use Dom\Document;
use Dom\Element;
use Dom\Node;
use Dom\XPath;
use Webfactory\Html5TagRewriter\RewriteHandler;

/**
 * Configurable test handler for unit tests.
 */
class TestRewriteHandler implements RewriteHandler
{
    private string $xpath;

    /** @var list<Element> */
    public array $matchedNodes = [];

    public int $matchCallCount = 0;

    public int $afterMatchesCallCount = 0;

    /** @var callable|null */
    private $matchCallback;

    /** @var callable|null */
    private $afterMatchesCallback;

    public function __construct(string $xpath = '//html:*')
    {
        $this->xpath = $xpath;
    }

    public function appliesTo(): string
    {
        return $this->xpath;
    }

    public function match(Node $node): void
    {
        $this->matchCallCount++;
        $this->matchedNodes[] = $node;

        if ($this->matchCallback !== null) {
            ($this->matchCallback)($node);
        }
    }

    public function afterMatches(Document $document, XPath $xpath): void
    {
        $this->afterMatchesCallCount++;

        if ($this->afterMatchesCallback !== null) {
            ($this->afterMatchesCallback)($document, $xpath);
        }
    }

    public function onMatch(callable $callback): self
    {
        $this->matchCallback = $callback;

        return $this;
    }

    public function onAfterMatches(callable $callback): self
    {
        $this->afterMatchesCallback = $callback;

        return $this;
    }
}
