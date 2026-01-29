<?php

declare(strict_types=1);

namespace Webfactory\Html5TagRewriter\Tests\Implementation;

use Dom\Element;
use Dom\Node;
use Dom\Text;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Webfactory\Html5TagRewriter\Implementation\Html5TagRewriter;
use Webfactory\Html5TagRewriter\Tests\Fixtures\TestRewriteHandler;

class Html5TagRewriterTest extends TestCase
{
    private Html5TagRewriter $rewriter;

    protected function setUp(): void
    {
        $this->rewriter = new Html5TagRewriter();
    }

    public static function providePreservedHtml(): iterable
    {
        yield 'complete HTML5 document' => [
            '<!DOCTYPE html><html><head><title>Test</title></head><body><p>Hello</p></body></html>',
        ];

        yield 'doctype can be omitted' => [
            '<html><head><title>Test</title></head><body><p>Hello</p></body></html>',
        ];

        yield 'attributes are preserved' => [
            '<!DOCTYPE html><html><head><title>Test</title></head><body><a href="/link" target="_blank" data-custom="value">Link</a></body></html>',
        ];

        yield 'HTML with Unicode and special characters' => [
            '<!DOCTYPE html><html><head><title>Test</title></head><body><p>Größe: 5€ — "Zitat" &amp; mehr</p></body></html>',
        ];

        yield 'HTML with Vue.js attributes' => [
            '<!DOCTYPE html><html><head><title>Test</title></head><body><div v-bind:x="y" @click="foo"></div></body></html>',
        ];

        yield 'HTML with ESI tag' => [
            '<!DOCTYPE html><html><head><title>Test</title></head><body><esi:include src="url" /></body></html>',
        ];

        yield 'HTML with ESI comment' => [
            '<!DOCTYPE html><html><head><title>Test</title></head><body><esi:comment text="test" /></body></html>',
        ];

        yield 'HTML with nested ESI tags' => [
            '<!DOCTYPE html><html><head><title>Test</title></head><body><esi:try><esi:attempt><esi:include src="url" /></esi:attempt></esi:try></body></html>',
        ];
    }

    #[Test]
    #[DataProvider('providePreservedHtml')]
    public function process_preserves_HTML(string $html): void
    {
        $result = $this->rewriter->process($html);

        self::assertSame($html, $result);
    }

    public static function provideHtmlCleanedUp(): iterable
    {
        yield 'empty document' => [
            '',
            '<html><head></head><body></body></html>',
        ];

        yield 'body element only' => [
            '<body><p>Hello</p></body>',
            '<html><head></head><body><p>Hello</p></body></html>',
        ];

        yield 'incorrect nesting' => [
            '<div>foo<span>bar</div></span>',
            '<html><head></head><body><div>foo<span>bar</span></div></body></html>',
        ];

        yield 'tags left open' => [
            '<div>foo<div>bar</div>',
            '<html><head></head><body><div>foo<div>bar</div></div></body></html>',
        ];
    }

    #[Test]
    #[DataProvider('provideHtmlCleanedUp')]
    public function process_cleans_up_HTML(string $input, string $expected): void
    {
        $result = $this->rewriter->process($input);

        self::assertSame($expected, $result);
    }

    #[Test]
    public function process_applies_registered_handler(): void
    {
        $handler = new TestRewriteHandler('//html:p');
        $handler->onMatch(function (Element $element) {
            $element->setAttribute('class', 'modified');
        });

        $this->rewriter->register($handler);
        $result = $this->rewriter->process('<!DOCTYPE html><html><body><p>Test</p></body></html>');

        self::assertStringContainsString('<p class="modified">', $result);
    }

    #[Test]
    public function process_applies_multiple_handlers_in_registration_order(): void
    {
        $order = [];

        $handler1 = new TestRewriteHandler('//html:p');
        $handler1->onMatch(function () use (&$order) {
            $order[] = 'handler1';
        });

        $handler2 = new TestRewriteHandler('//html:p');
        $handler2->onMatch(function () use (&$order) {
            $order[] = 'handler2';
        });

        $this->rewriter->register($handler1);
        $this->rewriter->register($handler2);
        $this->rewriter->process('<!DOCTYPE html><html><body><p>Test</p></body></html>');

        self::assertSame(['handler1', 'handler2'], $order);
    }

    #[Test]
    public function process_handles_nested_elements(): void
    {
        $matchedTags = [];
        $handler = new TestRewriteHandler('//html:span');
        $handler->onMatch(function (Element $element) use (&$matchedTags) {
            $matchedTags[] = $element->textContent;
        });

        $this->rewriter->register($handler);
        $this->rewriter->process('<!DOCTYPE html><html><body><div><span>Outer<span>Inner</span></span></div></body></html>');

        self::assertCount(2, $matchedTags);
    }

    public static function providePreservedFragments(): iterable
    {
        yield 'empty fragment' => [
            '',
        ];

        yield 'text only' => [
            'Just plain text',
        ];

        yield 'simple element from body (parsing in "data state")' => [
            '<p>Hello</p>',
        ];

        // Results for processing <head> content are undefined for now
        // yield 'tags from head (parsing in "data state")' => [
        //     '<title>Test no tags here</title><meta name="description" content="test">',
        // ];

        yield 'mixed content' => [
            'Text before <em>emphasized</em> text after',
        ];

        yield 'multiple top-level elements' => [
            '<p>First</p><p>Second</p><p>Third</p>',
        ];

        yield 'nested elements' => [
            '<div><p><span>Nested</span></p></div>',
        ];

        yield 'whitespace in pre' => [
            "<pre>  Line 1\n  Line 2\n  Line 3</pre>",
        ];

        yield 'ESI tag' => [
            '<esi:include src="url" />',
        ];
    }

    #[Test]
    #[DataProvider('providePreservedFragments')]
    public function processBodyFragment_preserves_fragment(string $fragment): void
    {
        $result = $this->rewriter->processBodyFragment($fragment);

        self::assertSame($fragment, $result);
    }

    public static function provideFragmentsCleanedUp(): iterable
    {
        yield 'empty ESI include tag' => [
            '<esi:include src="url">',
            '<esi:include src="url" />',
        ];

        yield 'qouted entities are replaced' => [
            '<p>&lt;script&gt; &amp; &quot;quotes&quot;</p>',
            '<p>&lt;script&gt; &amp; "quotes"</p>',
        ];

        yield 'textarea uses RCDATA state' => [
            '<textarea><h1>Some HTML</h1><p>textarea content</p></textarea>',
            '<textarea>&lt;h1&gt;Some HTML&lt;/h1&gt;&lt;p&gt;textarea content&lt;/p&gt;</textarea>',
        ];

        // Results for processing <head> content are undefined for now
        // yield 'title tag from head uses RCDATA state' => [
        //     '<title>Test <h1> no tags here</title>',
        //     '<title>Test &lt;h1&gt; no tags here</title>',
        // ];
    }

    #[Test]
    #[DataProvider('provideFragmentsCleanedUp')]
    public function processBodyFragment_cleans_up_fragment(string $input, string $expected): void
    {
        $result = $this->rewriter->processBodyFragment($input);

        self::assertSame($expected, $result);
    }

    public static function provideFragmentsWithBrokenHandling(): iterable
    {
        yield 'full head section' => [
            '<head><title>Test</title><meta name="description" content="test"></head>',
        ];

        yield 'empty head section' => [
            '<head></head>',
        ];

        yield 'body with content' => [
            '<body><p>test</p><p>more test</p></body>',
        ];

        yield 'empty body' => [
            '<body></body>',
        ];

        yield 'head and body with content' => [
            '<head><title>Test</title></head><body><p>test</p></body>',
        ];

        yield 'empty head and body' => [
            '<head></head><body></body>',
        ];
    }

    #[Test]
    #[DataProvider('provideFragmentsWithBrokenHandling')]
    public function processBodyFragment_currently_cannot_process_fragments_with_head_or_body_tags(string $input): void
    {
        $this->markTestSkipped('Parsing of fragments that are not part of the `body` is currently not supported.');
    }

    #[Test]
    public function processBodyFragment_applies_handler(): void
    {
        $handler = new TestRewriteHandler('//html:strong');
        $handler->onMatch(function (Element $element) {
            $element->setAttribute('class', 'bold');
        });

        $this->rewriter->register($handler);
        $result = $this->rewriter->processBodyFragment('<strong>Text</strong>');

        self::assertSame('<strong class="bold">Text</strong>', $result);
    }

    #[Test]
    public function processBodyFragment_matches_multiple_elements(): void
    {
        $handler = new TestRewriteHandler('//html:p');
        $this->rewriter->register($handler);
        $this->rewriter->processBodyFragment('<p>First</p><p>Second</p><p>Third</p>');

        self::assertSame(3, $handler->matchCallCount);
    }

    #[Test]
    public function register_allows_multiple_handlers(): void
    {
        $handler1 = new TestRewriteHandler('//html:p');
        $handler2 = new TestRewriteHandler('//html:div');

        $this->rewriter->register($handler1);
        $this->rewriter->register($handler2);

        $this->rewriter->processBodyFragment('<p>paragraph</p><div>div section</div>');

        self::assertSame(1, $handler1->matchCallCount);
        self::assertSame(1, $handler2->matchCallCount);
    }

    #[Test]
    public function handler_can_match_HTML_elements(): void
    {
        $handler = new TestRewriteHandler('//html:a');
        $this->rewriter->register($handler);

        $this->rewriter->processBodyFragment('<a href="#">Link 1</a><a href="#">Link 2</a>');

        self::assertSame(2, $handler->matchCallCount);
    }

    #[Test]
    public function handler_can_match_SVG_elements(): void
    {
        $handler = new TestRewriteHandler('//svg:circle');
        $this->rewriter->register($handler);

        $svg = '<svg><circle cx="50" cy="50" r="40"/></svg>';
        $this->rewriter->processBodyFragment($svg);

        self::assertSame(1, $handler->matchCallCount);
    }

    #[Test]
    public function handler_can_match_MathML_elements(): void
    {
        $handler = new TestRewriteHandler('//mathml:mrow');
        $this->rewriter->register($handler);

        $mathml = '<math><mrow><mi>x</mi></mrow></math>';
        $this->rewriter->processBodyFragment($mathml);

        self::assertSame(1, $handler->matchCallCount);
    }

    #[Test]
    public function handler_can_match_non_element_nodes(): void
    {
        $handler = new TestRewriteHandler('//html:p/text()');
        $handler->onMatch(function (Node $node) {
            self::assertNotInstanceOf(Element::class, $node);
            self::assertInstanceOf(Text::class, $node);
            self::assertSame('test', $node->textContent);
        });

        $this->rewriter->register($handler);
        $this->rewriter->processBodyFragment('<p>test</p>');

        self::assertSame(1, $handler->matchCallCount);
    }

    #[Test]
    public function handler_match_is_called_for_each_matching_element(): void
    {
        $handler = new TestRewriteHandler('//html:p');
        $this->rewriter->register($handler);

        $this->rewriter->processBodyFragment('<p>1</p><p>2</p><p>3</p><p>4</p><p>5</p>');

        self::assertSame(5, $handler->matchCallCount);
        self::assertCount(5, $handler->matchedNodes);
    }

    #[Test]
    public function handler_after_matches_is_called_after_all_matches(): void
    {
        $callOrder = [];

        $handler = new TestRewriteHandler('//html:p');
        $handler->onMatch(function () use (&$callOrder) {
            $callOrder[] = 'match';
        });
        $handler->onAfterMatches(function () use (&$callOrder) {
            $callOrder[] = 'afterMatches';
        });

        $this->rewriter->register($handler);
        $this->rewriter->processBodyFragment('<p>1</p><p>2</p>');

        self::assertSame(['match', 'match', 'afterMatches'], $callOrder);
    }

    #[Test]
    public function handler_afterMatches_receives_document_and_xpath(): void
    {
        $receivedDocument = null;
        $receivedXPath = null;

        $handler = new TestRewriteHandler('//html:p');
        $handler->onAfterMatches(function ($document, $xpath) use (&$receivedDocument, &$receivedXPath) {
            $receivedDocument = $document;
            $receivedXPath = $xpath;
        });

        $this->rewriter->register($handler);
        $this->rewriter->processBodyFragment('<p>Test</p>');

        self::assertNotNull($receivedDocument);
        self::assertNotNull($receivedXPath);
    }

    #[Test]
    public function handler_afterMatches_is_called_also_with_no_matches(): void
    {
        $handler = new TestRewriteHandler('//html:nonexistent');
        $this->rewriter->register($handler);

        $this->rewriter->processBodyFragment('<p>No matching elements</p>');

        self::assertSame(0, $handler->matchCallCount);
        self::assertSame(1, $handler->afterMatchesCallCount);
    }

    #[Test]
    public function handler_can_modify_element_attributes(): void
    {
        $handler = new TestRewriteHandler('//html:a');
        $handler->onMatch(function (Element $element) {
            $element->setAttribute('rel', 'noopener');
            $element->setAttribute('target', '_blank');
        });

        $this->rewriter->register($handler);
        $result = $this->rewriter->processBodyFragment('<a href="/page">Link</a>');

        self::assertStringContainsString('rel="noopener"', $result);
        self::assertStringContainsString('target="_blank"', $result);
    }

    #[Test]
    public function handler_can_modify_element_content(): void
    {
        $handler = new TestRewriteHandler('//html:p');
        $handler->onMatch(function (Element $element) {
            $element->innerHTML = '<strong>'.$element->textContent.'</strong>';
        });

        $this->rewriter->register($handler);
        $result = $this->rewriter->processBodyFragment('<p>Hello</p>');

        self::assertStringContainsString('<strong>Hello</strong>', $result);
    }

    #[Test]
    public function handler_can_remove_elements(): void
    {
        $handler = new TestRewriteHandler('//html:script');
        $handler->onMatch(function (Element $element) {
            $element->parentNode->removeChild($element);
        });

        $this->rewriter->register($handler);
        $result = $this->rewriter->processBodyFragment('<p>Text</p><script>alert("evil")</script><p>More</p>');

        self::assertStringNotContainsString('<script>', $result);
        self::assertStringNotContainsString('alert', $result);
        self::assertStringContainsString('<p>Text</p>', $result);
        self::assertStringContainsString('<p>More</p>', $result);
    }

    #[Test]
    public function handler_can_add_new_elements(): void
    {
        $handler = new TestRewriteHandler('//html:p');
        $handler->onMatch(function (Element $element) {
            $document = $element->ownerDocument;
            $span = $document->createElement('span');
            $span->textContent = '[added]';
            $element->appendChild($span);
        });

        $this->rewriter->register($handler);
        $result = $this->rewriter->processBodyFragment('<p>Original</p>');

        self::assertSame('<p>Original<span>[added]</span></p>', $result);
    }

    #[Test]
    public function handler_can_collect_and_batch_process(): void
    {
        $elements = [];

        $handler = new TestRewriteHandler('//html:li');
        $handler->onMatch(function (Element $element) use (&$elements) {
            $elements[] = $element;
        });
        $handler->onAfterMatches(function () use (&$elements) {
            // Simulates batch processing: join all collected texts
            self::assertSame('A, B, C', implode(', ', array_map(fn($element) => $element->textContent, $elements)));
        });

        $this->rewriter->register($handler);
        $this->rewriter->processBodyFragment('<ul><li>A</li><li>B</li><li>C</li></ul>');
    }

    #[Test]
    public function process_handles_self_closing_tags(): void
    {
        $html = '<!DOCTYPE html><html><body><img src="test.jpg"><br><hr><input type="text"></body></html>';

        $result = $this->rewriter->process($html);

        self::assertStringContainsString('img', $result);
        self::assertStringContainsString('src="test.jpg"', $result);
    }

    #[Test]
    public function handler_multiple_handlers_can_modify_same_element(): void
    {
        $handler1 = new TestRewriteHandler('//html:a');
        $handler1->onMatch(function (Element $element) {
            $element->setAttribute('class', 'link');
        });

        $handler2 = new TestRewriteHandler('//html:a');
        $handler2->onMatch(function (Element $element) {
            $current = $element->getAttribute('class');
            $element->setAttribute('class', $current.' external');
        });

        $this->rewriter->register($handler1);
        $this->rewriter->register($handler2);

        $result = $this->rewriter->processBodyFragment('<a href="#">Test</a>');

        self::assertStringContainsString('class="link external"', $result);
    }
}
