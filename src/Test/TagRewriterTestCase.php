<?php

namespace Webfactory\Html5TagRewriter\Test;

use Override;
use PHPUnit\Framework\TestCase;
use Webfactory\Html5TagRewriter\Implementation\Html5TagRewriter;
use Webfactory\Html5TagRewriter\TagRewriter;

abstract class TagRewriterTestCase extends TestCase
{
    protected TagRewriter $rewriter;

    #[Override]
    protected function setUp(): void
    {
        $this->rewriter = new Html5TagRewriter();
    }

    public function assertRewriteResultEquals(string $expected, string $input): void
    {
        $result = $this->rewriter->processBodyFragment($input);

        $this->assertXmlStringEqualsXmlString($expected, $result);
    }

    public function assertRewriteResultUnchanged(string $input): void
    {
        $this->assertRewriteResultEquals($input, $input);
    }
}
