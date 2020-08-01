<?php

namespace App\Controller;

use App\RemoteFileResponse;
use Http\Client\Common\HttpMethodsClient;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ProxyController extends Controller
{
    private $domains = [
        'adam' => 'adam.curry.com',
        'fcassets' => 'fcassets.curry.com.s3.wasabisys.com',
    ];

    /**
     * @var HttpMethodsClient
     */
    private $httpClient;

    public function __construct(HttpMethodsClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @Route("/proxy/{domain}/{url}", requirements={"url": ".+"}, name="proxy")
     */
    public function proxy(string $domain, string $url): Response
    {
        if (!isset($this->domains[$domain])) {
            throw new NotFoundHttpException();
        }

        $uri = sprintf('http://%s/%s', $this->domains[$domain], $url);

        $file = $this->httpClient->get($uri);

        return new RemoteFileResponse($file);
    }
}
