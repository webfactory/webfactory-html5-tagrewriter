<?php

namespace Webfactory\Html5TagRewriter\Tests\Test;

use Dom\Element;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\ExpectationFailedException;
use Webfactory\Html5TagRewriter\Test\TagRewriterTestCase;
use Webfactory\Html5TagRewriter\Tests\Fixtures\TestRewriteHandler;

class TagRewriterTestCaseTest extends TagRewriterTestCase
{
    #[Test]
    public function assertRewriteResultEquals_when_RewriteHandler_registered(): void
    {
        $handler = new TestRewriteHandler('//html:p');
        $handler->onMatch(function (Element $element) {
            $element->textContent = 'bar';
        });
        $this->rewriter->register($handler);

        $this->assertRewriteResultEquals('<p>bar</p>', '<p>foo</p>');
    }

    #[Test]
    public function assertRewriteResultEquals_when_no_RewriteHandler_fails(): void
    {
        $this->expectException(ExpectationFailedException::class);

        $this->assertRewriteResultEquals('<p>bar</p>', '<p>foo</p>');
    }

    #[Test]
    public function assertRewriteResultUnchanged_fails_when_RewriteHandler_registered(): void
    {
        $handler = new TestRewriteHandler('//html:p');
        $handler->onMatch(function (Element $element) {
            $element->textContent = 'bar';
        });
        $this->rewriter->register($handler);

        $this->expectException(ExpectationFailedException::class);

        $this->assertRewriteResultUnchanged('<p>foo</p>');
    }

    #[Test]
    public function assertRewriteResultUnchanged_when_no_RewriteHandler_fails(): void
    {
        $this->assertRewriteResultUnchanged('<p>bar</p>', '<p>foo</p>');
    }
}
