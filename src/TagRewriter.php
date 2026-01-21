<?php

namespace Webfactory\Html5TagRewriter;

interface TagRewriter
{
    public function register(RewriteHandler $handler): void;

    public function process(string $html5): string;

    public function processFragment(string $html5Fragment): string;
}
