<?php

declare(strict_types=1);

namespace R3H6\Typo3BrowserkitTesting;

use GuzzleHttp\Psr7\Utils;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Symfony\Component\BrowserKit\AbstractBrowser;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\FileFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\UploadedFile as Typo3UploadedFile;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

/**
 * @template-extends AbstractBrowser<Request, HttpFoundationResponse>
 */
final class Typo3Browser extends AbstractBrowser
{
    /** @var array<string, string> */
    private array $pendingFileUploads = [];
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
            } elseif ($type === 'file') {
                $this->queueFileUpload($node, (string)$value);
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

    public function submit(Form $form, array $values = [], array $serverParameters = []): Crawler
    {
        $appliedFields = $this->applyPendingFileUploads($form);
        try {
            return parent::submit($form, $values, $serverParameters);
        } finally {
            $this->forgetAppliedFileUploads($appliedFields);
        }
    }

    /**
     * @param \Symfony\Component\BrowserKit\Request $request
     * @return HttpFoundationResponse
     */
    protected function doRequest(object $request): object
    {
        $typo3Request = (new InternalRequest($request->getUri()))->withMethod($request->getMethod());

        $headers = $this->getHeaders($request);
        foreach ($headers as $name => $value) {
            $typo3Request = $typo3Request->withHeader($name, $value);
        }

        $uploadedFiles = $this->createUploadedFiles($request->getFiles());
        if ($uploadedFiles !== []) {
            $typo3Request = $typo3Request->withUploadedFiles($uploadedFiles);
        }

        $body = $this->prepareBody($request);
        if ($body !== null) {
            $typo3Request = $typo3Request->withBody(Utils::streamFor($body));
        }

        $typo3Request = $typo3Request->withCookieParams($request->getCookies());

        $typo3Context = $this->context ?? new InternalRequestContext();
        $typo3Response = null;
        try {
            $typo3Response = $this->testCase->doFrontendRequest($typo3Request, $typo3Context);
        } finally {
            $this->makeSnapshot($typo3Request, $typo3Response);
        }

        GeneralUtility::makeInstance(PersistenceManagerInterface::class)->persistAll();

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

    private function makeSnapshot(ServerRequestInterface $typo3Request, ?ResponseInterface $typo3Response): void
    {
        $markdown = "# Snapshot\n\n";
        $markdown .= "## Request\n";
        $markdown .= '- **Method:** ' . $typo3Request->getMethod() . "\n";
        $markdown .= '- **URI:** ' . $typo3Request->getUri() . "\n";
        $markdown .= "- **Headers:**\n";
        foreach ($typo3Request->getHeaders() as $name => $value) {
            $markdown .= "    - `$name`: " . implode(', ', $value) . "\n";
        }
        $markdown .= "- **Body:**\n```\n" . $typo3Request->getBody() . "\n```\n";
        $markdown .= "- **Uploaded files:**\n";
        /** @var UploadedFileInterface $file */
        foreach (ArrayUtility::flatten($typo3Request->getUploadedFiles()) as $key =>  $file) {
            $markdown .= "    - `$key=" . $file->getClientFilename() . '` (' . $file->getClientMediaType() . ', ' . $file->getSize() . " bytes)\n";
        }
        $markdown .= "- **Cookies:**\n";
        foreach ($typo3Request->getCookieParams() as $name => $value) {
            $markdown .= "    - `$name`: $value\n";
        }
        $markdown .= "\n## Response\n";
        if ($typo3Response !== null) {
            $markdown .= '- **Status Code:** ' . $typo3Response->getStatusCode() . "\n";
            $markdown .= "- **Headers:**\n";
            foreach ($typo3Response->getHeaders() as $name => $value) {
                $markdown .= "    - `$name`: " . implode(', ', $value) . "\n";
            }
            $markdown .= "- **Body:**\n\n```html\n" . $typo3Response->getBody() . "\n```\n";
        } else {
            $markdown .= '- **Status Code:** N/A' . "\n";
            $markdown .= "- **Headers:** N/A\n";
            $markdown .= "- **Body:** N/A\n";
        }

        $path = Environment::getVarPath() . '/typo3-browserkit-testing/' . uniqid('snapshot-', true) . '.md';
        GeneralUtility::mkdir_deep(dirname($path));
        GeneralUtility::writeFile($path, $markdown);
    }

    private function queueFileUpload(\DOMElement $node, string $filePath): void
    {
        $name = (string)$node->getAttribute('name');
        if ($name === '') {
            throw new \RuntimeException('File input requires a name attribute.');
        }
        if (!is_file($filePath) || !is_readable($filePath)) {
            throw new \RuntimeException('File for upload is not readable: ' . $filePath);
        }

        $this->pendingFileUploads[$name] = $filePath;
    }

    /**
     * @return string[]
     */
    private function applyPendingFileUploads(Form $form): array
    {
        $applied = [];
        $this->traverseFormFields($form->all(), function (FileFormField $field) use (&$applied): void {
            $name = $field->getName();
            if (!isset($this->pendingFileUploads[$name])) {
                return;
            }
            $field->upload($this->pendingFileUploads[$name]);
            $applied[] = $name;
        });

        return $applied;
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function traverseFormFields(array $fields, callable $callback): void
    {
        foreach ($fields as $field) {
            if (\is_array($field)) {
                $this->traverseFormFields($field, $callback);
                continue;
            }
            if ($field instanceof FileFormField) {
                $callback($field);
            }
        }
    }

    /**
     * @param string[] $applied
     */
    private function forgetAppliedFileUploads(array $applied): void
    {
        foreach ($applied as $name) {
            unset($this->pendingFileUploads[$name]);
        }
    }

    private function prepareBody(Request $request): ?string
    {
        if (\in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return null;
        }

        if ($content = $request->getContent()) {
            return $content;
        }

        $fields = $request->getParameters();
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

        return http_build_query($fields, '', '&');
    }

    // {{{ Copied from HttpBrowser

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

    // }}}

    /**
     * @param array<mixed> $files
     * @return array<mixed>
     */
    private function createUploadedFiles(array $files): array
    {
        $normalized = [];
        foreach ($files as $name => $file) {
            if ($file instanceof Typo3UploadedFile) {
                $normalized[$name] = $file;
                continue;
            }
            if (!\is_array($file)) {
                continue;
            }
            if (isset($file['tmp_name']) && !\is_array($file['tmp_name']) && $file['tmp_name'] === '') {
                continue;
            }
            $result = $this->createUploadedFileFromSpec($file);
            if (\is_array($result) && $result === []) {
                continue;
            }
            $normalized[$name] = $result;
        }

        return $normalized;
    }

    /**
     * @return Typo3UploadedFile|array<mixed>
     */
    private function createUploadedFileFromSpec(array $spec): Typo3UploadedFile|array
    {
        if (!isset($spec['tmp_name'])) {
            return $this->createUploadedFiles($spec);
        }

        if (\is_array($spec['tmp_name'])) {
            $files = [];
            foreach ($spec['tmp_name'] as $key => $tmpName) {
                if ((string)$tmpName === '') {
                    continue;
                }
                $files[$key] = $this->createUploadedFileFromSpec([
                    'tmp_name' => $tmpName,
                    'name' => $spec['name'][$key] ?? null,
                    'type' => $spec['type'][$key] ?? null,
                    'error' => $spec['error'][$key] ?? \UPLOAD_ERR_NO_FILE,
                    'size' => $spec['size'][$key] ?? 0,
                ]);
            }
            return $files;
        }

        $tmpName = (string)$spec['tmp_name'];
        $input = $tmpName !== '' && is_file($tmpName)
            ? $tmpName
            : fopen('php://temp', 'rb+');
        if ($input === false) {
            throw new \RuntimeException('Unable to create temporary stream for uploaded file.');
        }

        return new Typo3UploadedFile(
            $input,
            (int)($spec['size'] ?? 0),
            (int)($spec['error'] ?? \UPLOAD_ERR_NO_FILE),
            isset($spec['name']) ? (string)$spec['name'] : null,
            isset($spec['type']) ? (string)$spec['type'] : null,
        );
    }
}
