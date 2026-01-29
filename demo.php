<?php

use Dom\Element;
use Dom\Node;
use Webfactory\Html5TagRewriter\Handler\BaseRewriteHandler;
use Webfactory\Html5TagRewriter\Implementation\Html5TagRewriter;

require 'vendor/autoload.php';

class DemoRewriteHandler extends BaseRewriteHandler
{
    public function appliesTo(): string
    {
        return '//html:a';
    }

    public function match(Node $node): void
    {
        assert($node instanceof Element);
        $node->setAttribute('href', 'https://github.com/webfactory/html5-tagrewriter');
        $node->textContent = 'check this out';
    }
}

$tagrewriter = new Html5TagRewriter();
$tagrewriter->register(new DemoRewriteHandler());

$document = <<<HTML
<!DOCTYPE html>
<html>
<body>
    <p><a href="#">link</a></p>
</body>
</html>
HTML;

echo $tagrewriter->process($document);
