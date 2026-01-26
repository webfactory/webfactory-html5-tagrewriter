# webfactory HTML5 TagRewriter library

A small library that uses a handler pattern to transform HTML documents. Based on the PHP 8.4+ HTML5 parser and DOM extension. 

Useful to make manipulations to HTML5 documents that may not be so easy when generating the HTML output (e.g. a template engine
like Twig), but are rather trivial when looking at the final DOM.

Examples:
- Add `target="_blank"` and `rel="noopener"` to all external links
- Find all `<img>` in a page that have a `data-credits` attribute, and place all credits information in a section in the page footer
- Find all headings within the `<main>` section of the page, generate a table of contents with anchor links and place it at the beginning of the page

## Usage

### Basic Usage

```php
use Webfactory\Html5TagRewriter\Implementation\Html5TagRewriter;

$rewriter = new Html5TagRewriter();

// Process a complete HTML5 document
$html = '<!DOCTYPE html><html><body><p>Hello</p></body></html>';
$result = $rewriter->process($html);

// Process an HTML fragment
$fragment = '<p>Hello <strong>World</strong></p>';
$result = $rewriter->processBodyFragment($fragment);
```

> [!NOTE]
> The `processBodyFragment()` method is currently limited in that it can only process
> HTML strings that come from within the `<body>` section. This has to do with the 
> HTML 5 parsing rules defining different [parsing states](https://html.spec.whatwg.org/multipage/parsing.html#parse-state),
> and the PHP DOM API for the HTML 5 parser does currently not expose
> a (documented) way to create fragments and passing the required context information.
> For correct results, you should limit its usage to fragments that shall be processed 
> starting in the `in body` parsing state and where the `data state` [tokenization mode](https://html.spec.whatwg.org/multipage/parsing.html#tokenization)
> is active.

### Creating a Handler

Implement the `RewriteHandler` interface or extend `BaseRewriteHandler` to create custom tag transformations.
The `BaseRewriteHandler` provides empty default implementations, so you only need to override the methods you need:

```php
use Dom\Element;
use Webfactory\Html5TagRewriter\Handler\BaseRewriteHandler;

class ExternalLinkHandler extends BaseRewriteHandler
{
    public function appliesTo(): string
    {
        // XPath expression to match elements
        // Use 'html:' prefix for HTML5 elements, 'svg:' for SVG and 'mathml:' for MathML
        return '//html:a[@href]';
    }

    public function match(Element $element): void
    {
        $href = $element->getAttribute('href');
        if (str_starts_with($href, 'http')) {
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener');
        }
    }
}
```

### Registering Handlers

```php
$rewriter = new Html5TagRewriter();
$rewriter->register(new ExternalLinkHandler());
$rewriter->register(new AnotherHandler());

$result = $rewriter->process($html);
```

### XPath Namespaces

The following namespaces are pre-registered for XPath queries:

| Prefix   | Namespace URI                        |
|----------|--------------------------------------|
| `html`   | `http://www.w3.org/1999/xhtml`       |
| `svg`    | `http://www.w3.org/2000/svg`         |
| `mathml` | `http://www.w3.org/1998/Math/MathML` |

### ESI Tag Support

The library handles Edge Side Includes (ESI) tags, converting empty ESI tags to self-closing format:

```php
// Input
'<esi:include src="url"></esi:include>'

// Output
'<esi:include src="url" />'
```
## Credits, Copyright and License

This library is based on internal work that we have been using at webfactory GmbH, Bonn, at least
since 2012. However, that (old) code was written with the legacy PHP DOM extension, leading to 
several quirks in HTML processing and requiring the use of [Polyglot HTML 5](https://www.w3.org/TR/html-polyglot/)
which is processable as XML.

- <https://www.webfactory.de>

Copyright 2026 webfactory GmbH, Bonn. Code released under [the MIT license](LICENSE).   
