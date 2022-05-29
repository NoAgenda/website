<?php

namespace App\Controller;

use App\RemoteFileResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProxyController extends AbstractController
{
    private array $domains = [
        'adam' => 'adam.curry.com',
        'fcassets' => 'fcassets.curry.com.s3.wasabisys.com',
    ];

    public function __construct(
        private HttpClientInterface $httpClient,
    ) {}

    #[Route('/proxy/{domain}/{url}', name: 'proxy', requirements: ['url' => '.+'])]
    public function proxy(string $domain, string $url): Response
    {
        if (!isset($this->domains[$domain])) {
            throw new NotFoundHttpException();
        }

        $uri = sprintf('http://%s/%s', $this->domains[$domain], $url);

        $file = $this->httpClient->request('GET', $uri);

        return new RemoteFileResponse($file);
    }
}
