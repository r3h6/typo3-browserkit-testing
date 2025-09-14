<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting;

use GuzzleHttp\Psr7\Utils;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\Exception\LogicException;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Component\Mime\Part\TextPart;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

/**
 * @template-extends AbstractBrowser<Request, HttpFoundationResponse>
 */
final class Typo3Browser extends AbstractBrowser
{
    public function __construct(
        private readonly WebTestCase $testCase,
        private ?InternalRequestContext $context = null,
    ) {
        parent::__construct();
    }

    public function setDefaultContext(?InternalRequestContext $context): void
    {
        $this->context = $context;
    }

    public function findElement(string $selector, ?string $context = null): Crawler
    {
        $crawler = $context ? $this->crawler->filter($context) : $this->crawler;

        $nodes = $crawler->filter($selector);
        if ($nodes->count() > 0) {
            return $nodes;
        }
        $nodes = $crawler->selectLink($selector);
        if ($nodes->count() > 0) {
            return $nodes;
        }
        $nodes = $crawler->selectButton($selector);
        if ($nodes->count() > 0) {
            return $nodes;
        }
        $nodes = $crawler->selectImage($selector);
        if ($nodes->count() > 0) {
            return $nodes;
        }
        // Find by label text by xpath
        $nodes = $crawler->filterXPath('//label[contains(normalize-space(.), "' . $selector . '")]');
        if ($nodes->count() > 0) {
            return $nodes;
        }
        // Find input/textarea/select by name attribute
        $nodes = $crawler->filter('input:not([type="hidden"])[name="' . $selector . '"], textarea[name="' . $selector . '"], select[name="' . $selector . '"]');
        if ($nodes->count() > 0) {
            return $nodes;
        }

        throw new \RuntimeException('Could not find element with selector/text: ' . $selector);
    }

    public function clickButton(Crawler|string $selector, array $values = []): Crawler
    {
        $nodes = $selector instanceof Crawler ? $selector : $this->findElement($selector);
        $button = $nodes->getNode(0);
        $name = (string)($button->attributes['name']->value ?? '');
        if ($name !== '') {
            $values[$name] = $button->attributes['value']->value ?? '';
        }
        $form = $nodes->form($values);
        return $this->submit($form);
    }

    /**
     * @param string $selector A CSS selector, link text or button text
     * @param string|null $context Optional CSS selector to limit the search context
     */
    public function clickElement(string $selector, ?string $context = null): Crawler
    {
        $nodes = $this->findElement($selector, $context);
        if ($nodes->matches('a')) {
            $link = $nodes->link();
            return $this->click($link);
        }
        if ($nodes->matches('button, input[type="submit"], input[type="button"], input[type="image"]')) {
            return $this->clickButton($nodes);
        }
        // Check for label to click associated input
        if ($nodes->matches('label')) {
            $for = (string)($nodes->attr('for') ?? '');
            if ($for !== '') {
                return $this->clickElement('#' . $for);
            }
        }
        // Handle checkboxes and radio buttons
        if ($nodes->matches('input[type="checkbox"], input[type="radio"]')) {
            foreach ($nodes as $node) {
                if ($node instanceof \DOMElement) {
                    $node->removeAttribute('checked');
                }
            }
            $input = $nodes->getNode(0);
            if ($input instanceof \DOMElement) {
                $input->setAttribute('checked', 'checked');
            }
            return $nodes;
        }

        return $nodes;
    }

    public function setInputValue(string $selector, string|int $value): void
    {
        $nodes = $this->findElement($selector);

        /** @var \DOMNode|null $node */
        $node = $nodes->getNode(0);
        if (!($node instanceof \DOMElement)) {
            throw new \RuntimeException('Selected node is not a DOMElement.');
        }

        $tag = strtolower($node->tagName);

        if ($tag === 'input') {
            $type = strtolower($node->getAttribute('type') ?: 'text');
            if ($type === 'checkbox' || $type === 'radio') {
                $stringValue = (string)$value;
                // If value matches input value or truthy, check it; otherwise uncheck.
                if ($stringValue === $node->getAttribute('value') || ($node->getAttribute('value') === '' && (bool)$value)) {
                    $node->setAttribute('checked', 'checked');
                } else {
                    $node->removeAttribute('checked');
                }
            } else {
                $node->setAttribute('value', (string)$value);
            }
            return;
        }

        if ($tag === 'textarea') {
            // Replace text content
            while ($node->firstChild) {
                $node->removeChild($node->firstChild);
            }
            $node->appendChild($node->ownerDocument->createTextNode((string)$value));
            return;
        }

        if ($tag === 'select') {
            $stringValue = (string)$value;
            $options = $node->getElementsByTagName('option');
            foreach ($options as $i => $option) {
                $optionValue = $option->getAttribute('value');
                $optionText = trim($option->textContent ?? '');
                if ($optionValue === $stringValue || $optionText === $stringValue) {
                    $option->setAttribute('selected', 'selected');
                } else {
                    $option->removeAttribute('selected');
                }
            }
            return;
        }

        throw new \RuntimeException('Selected element is not a supported form field (input/textarea/select/option).');
    }

    /**
     * @param \Symfony\Component\BrowserKit\Request $request
     * @return HttpFoundationResponse
     */
    protected function doRequest(object $request): object
    {
        $typo3Request = (new InternalRequest($request->getUri()))->withMethod($request->getMethod());
        $headers = $this->getHeaders($this->internalRequest);
        [$body, $extraHeaders] = $this->getBodyAndExtraHeaders($request, $headers);

        foreach ($headers as $name => $value) {
            $typo3Request = $typo3Request->withHeader($name, $value);
        }

        foreach ($extraHeaders as $name => $value) {
            $typo3Request = $typo3Request->withHeader($name, $value);
        }

        if ($body !== null) {
            $typo3Request = $typo3Request->withBody(Utils::streamFor($body));
        }

        $typo3Request = $typo3Request->withCookieParams($request->getCookies());

        $typo3Context = $this->context ?? new InternalRequestContext();
        $typo3Response = $this->testCase->doFrontendRequest($typo3Request, $typo3Context);

        return new HttpFoundationResponse(
            (string)$typo3Response->getBody(),
            $typo3Response->getStatusCode(),
            $typo3Response->getHeaders()
        );
    }

    /**
     * @param \Symfony\Component\HttpFoundation\Response $response
     */
    protected function filterResponse(object $response): Response
    {
        return new Response(
            $response->getContent(),
            $response->getStatusCode(),
            $response->headers->all(),
        );
    }

    // {{{ Copied from HttpBrowser

    /**
     * @return array [$body, $headers]
     */
    private function getBodyAndExtraHeaders(Request $request, array $headers): array
    {
        if (\in_array($request->getMethod(), ['GET', 'HEAD'], true) && !isset($headers['content-type'])) {
            return ['', []];
        }

        if (!class_exists(AbstractPart::class)) {
            throw new LogicException('You cannot pass non-empty bodies as the Mime component is not installed. Try running "composer require symfony/mime".');
        }

        if (null !== $content = $request->getContent()) {
            if (isset($headers['content-type'])) {
                return [$content, []];
            }

            $part = new TextPart($content, 'utf-8', 'plain', '8bit');

            return [$part->bodyToString(), $part->getPreparedHeaders()->toArray()];
        }

        $fields = $request->getParameters();

        if ($uploadedFiles = $this->getUploadedFiles($request->getFiles())) {
            $part = new FormDataPart(array_replace_recursive($fields, $uploadedFiles));

            return [$part->bodyToIterable(), $part->getPreparedHeaders()->toArray()];
        }

        if (!$fields) {
            return ['', []];
        }

        array_walk_recursive($fields, $caster = static function (&$v) use (&$caster) {
            if (\is_object($v)) {
                if ($vars = get_object_vars($v)) {
                    array_walk_recursive($vars, $caster);
                    $v = $vars;
                } elseif ($v instanceof \Stringable) {
                    $v = (string)$v;
                }
            }
        });

        return [http_build_query($fields, '', '&'), ['Content-Type' => 'application/x-www-form-urlencoded']];
    }

    protected function getHeaders(Request $request): array
    {
        $headers = [];
        foreach ($request->getServer() as $key => $value) {
            $key = strtolower(str_replace('_', '-', $key));
            $contentHeaders = ['content-length' => true, 'content-md5' => true, 'content-type' => true];
            if (str_starts_with($key, 'http-')) {
                $headers[substr($key, 5)] = $value;
            } elseif (isset($contentHeaders[$key])) {
                // CONTENT_* are not prefixed with HTTP_
                $headers[$key] = $value;
            }
        }
        $cookies = [];
        foreach ($this->getCookieJar()->allRawValues($request->getUri()) as $name => $value) {
            $cookies[] = $name . '=' . $value;
        }
        if ($cookies) {
            $headers['cookie'] = implode('; ', $cookies);
        }

        return $headers;
    }

    /**
     * Recursively go through the list. If the file has a tmp_name, convert it to a DataPart.
     * Keep the original hierarchy.
     */
    private function getUploadedFiles(array $files): array
    {
        $uploadedFiles = [];
        foreach ($files as $name => $file) {
            if (!\is_array($file)) {
                return $uploadedFiles;
            }
            if (!isset($file['tmp_name'])) {
                $uploadedFiles[$name] = $this->getUploadedFiles($file);
                continue;
            }

            if ($file['tmp_name'] === '') {
                $uploadedFiles[$name] = new DataPart('', '');
                continue;
            }

            $uploadedFiles[$name] = DataPart::fromPath($file['tmp_name'], $file['name']);
        }

        return $uploadedFiles;
    }

    // }}}
}
