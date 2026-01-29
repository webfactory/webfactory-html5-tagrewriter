<?php

namespace Webfactory\Html5TagRewriter;

use Dom\Document;
use Dom\Element;
use Dom\Node;
use Dom\XPath;

/**
 * Interface for handlers that transform specific HTML elements.
 *
 * Handlers are registered with a TagRewriter and are invoked during document processing.
 * The processing flow for each handler is:
 *   1. appliesTo() is called to get the XPath expression
 *   2. match() is called for each element matching the XPath
 *   3. afterMatches() is called once after all matches have been processed
 */
interface RewriteHandler
{
    /**
     * Returns an XPath expression that selects the elements this handler should process.
     *
     * The following namespace prefixes are pre-registered and must be used:
     *   - 'html:' for HTML5 elements (e.g., '//html:a' for all <a> tags)
     *   - 'svg:' for SVG elements (e.g., '//svg:circle')
     *   - 'mathml:' for MathML elements (e.g., '//mathml:mrow')
     *
     * Examples:
     *   - '//html:a[@href]' - all <a> elements with an href attribute
     *   - '//html:img[not(@alt)]' - all <img> elements without an alt attribute
     *   - '//html:div[@class="content"]//html:p' - all <p> elements inside <div class="content">
     *
     * @return string An XPath expression selecting the elements to process
     */
    public function appliesTo(): string;

    /**
     * Called for each element that matches the XPath expression from appliesTo().
     *
     * Use this method to inspect or modify the element. Common operations include:
     *   - Reading/modifying attributes: $element->getAttribute(), $element->setAttribute()
     *   - Modifying content: $element->innerHTML, $element->textContent
     *   - Removing the element: $element->parentNode->removeChild($element)
     *   - Adding child elements: $element->appendChild()
     *
     * If you need to perform batch operations after all elements have been visited,
     * collect the elements here and process them in afterMatches().
     *
     * @param Node $node The DOM node that matched the XPath expression
     */
    public function match(Node $node): void;

    /**
     * Called once after all matching elements have been passed to match().
     *
     * Use this method for operations that require knowledge of all matched elements,
     * or that need to modify the document structure in ways that would interfere
     * with the XPath iteration (e.g., moving elements to a different location).
     *
     * This method is called even if no elements matched the XPath expression.
     *
     * @param Document $document The DOM document being processed
     * @param XPath $xpath The XPath instance with pre-registered namespaces, useful for additional queries
     */
    public function afterMatches(Document $document, XPath $xpath): void;
}
