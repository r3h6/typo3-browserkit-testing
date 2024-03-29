<?php

declare(strict_types=1);

namespace R3H6\WebTestCase\Controller;

use Psr\Log\LoggerAwareTrait;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerAwareInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class ExampleController extends ActionController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    private RequestFactory $requestFactory;

    public function __construct(
        RequestFactory $requestFactory
    ) {
        $this->requestFactory = $requestFactory;
    }

    public function redirectAction (): ResponseInterface
    {
        $this->logger->info('Redirect to "show"');
        $this->addFlashMessage('Redirected from ' . __METHOD__);
        return $this->redirect('show');
    }

    public function responseAction (): ResponseInterface
    {
        $uri = $this->uriBuilder->uriFor('show');
        $this->logger->info('Redirect to ' . $uri);
        $this->addFlashMessage('Redirected from ' . __METHOD__);
        return new RedirectResponse($uri);
    }

    /**
     * @phpstan-return never
     */
    public function propagateResponseAction()
    {
        $uri = $this->uriBuilder->uriFor('show');
        $this->logger->info('Redirect to ' . $uri);
        $this->addFlashMessage('Redirected from ' . __METHOD__);
        throw new PropagateResponseException(new RedirectResponse($uri), 1477070964);
    }

    public function showAction(): ResponseInterface
    {
        return $this->htmlResponse();
    }

    public function apiAction(): ResponseInterface
    {
        $url = 'https://jsonplaceholder.typicode.com/posts';
        $options = [
            RequestOptions::JSON => [
                'title' => 'TYPO3',
                'body' => 'Inspire people to share',
                'userId' => 1,
            ]
        ];
        $response = $this->requestFactory->request($url, 'POST', $options);
        return $this->htmlResponse((string) $response->getBody());
    }
}
