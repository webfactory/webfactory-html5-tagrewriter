<?php

declare(strict_types=1);

namespace Webfactory\Html5TagRewriter\Implementation;

use Dom\Document;
use Dom\HTMLDocument;
use Dom\Node;
use Dom\XPath;
use Override;
use Webfactory\Html5TagRewriter\RewriteHandler;
use Webfactory\Html5TagRewriter\TagRewriter;

final class Html5TagRewriter implements TagRewriter
{
    /** @var list<RewriteHandler> */
    private array $rewriteHandlers = [];

    #[Override]
    public function register(RewriteHandler $handler): void
    {
        $this->rewriteHandlers[] = $handler;
    }

    #[Override]
    public function process(string $html5): string
    {
        $document = HTMLDocument::createFromString($html5, LIBXML_NOERROR);

        $this->applyHandlers($document, $document);

        return $this->cleanup($document->saveHtml());
    }

    #[Override]
    public function processFragment(string $html5Fragment): string
    {
        $document = HTMLDocument::createEmpty();
        $container = $document->createElement('container');
        $document->appendChild($container);

        $temp = $document->createElement('temp');
        $temp->innerHTML = $html5Fragment;

        while ($temp->firstChild) {
            $container->appendChild($temp->firstChild);
        }

        $this->applyHandlers($document, $container);

        return $this->cleanup($container->innerHTML);
    }

    private function applyHandlers(Document $document, Node $context): void
    {
        $xpath = new XPath($document);
        $xpath->registerNamespace('html', 'http://www.w3.org/1999/xhtml');
        $xpath->registerNamespace('svg', 'http://www.w3.org/2000/svg');
        $xpath->registerNamespace('mathml', 'http://www.w3.org/1998/Math/MathML');

        foreach ($this->rewriteHandlers as $handler) {
            $elements = $xpath->query($handler->appliesTo(), $context);
            foreach ($elements as $element) {
                $handler->match($element);
            }
            $handler->afterMatches($document, $xpath);
        }
    }

    private function cleanup(string $html): string
    {
        return preg_replace('#(<esi:([a-z]+)(?:[^>]*))></esi:\\2>#', '$1 />', $html) ?? $html;
    }
}
