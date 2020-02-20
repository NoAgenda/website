<?php

namespace App\Crawling;

use App\Entity\Episode;
use Doctrine\ORM\EntityManagerInterface;
use Http\Client\Common\HttpMethodsClient;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

class EpisodeShownotesCrawler
{
    use LoggerAwareTrait;

    private $entityManager;
    private $httpClient;

    public function __construct(EntityManagerInterface $entityManager, HttpMethodsClient $shownotesClient)
    {
        $this->entityManager = $entityManager;
        $this->httpClient = $shownotesClient;
        $this->logger = new NullLogger();
    }

    public function crawl(Episode $episode): void
    {
        $data = [
            'url' => null,
            'executiveProducers' => [],
            'associateExecutiveProducers' => [],
            'coverArtist' => null,
        ];

        libxml_use_internal_errors(true);

        $frontResponse = $this->httpClient->get(sprintf('http://%s.noagendanotes.com', $episode->getCode()));

        $data['url'] = $frontResponse->getHeaderLine('Location');

        $htmlResponse = $this->httpClient->get($data['url']);
        $htmlContents = $htmlResponse->getBody()->getContents();

        $htmlDom = new \DOMDocument();
        $htmlDom->loadHTML($htmlContents);

        $htmlXpath = new \DOMXPath($htmlDom);

        $url = $htmlXpath->query('.//link[@title="OPML"]')->item(0)->getAttribute('href');

        $response = $this->httpClient->get($url);
        $contents = $response->getBody()->getContents();

        $dom = new \DOMDocument();
        $dom->loadXML($contents);

        $xpath = new \DOMXPath($dom);

        $data['executiveProducers'] = $this->parseExecutiveProducers($xpath);
        $data['associateExecutiveProducers'] = $this->parseAssociateExecutiveProducers($xpath);
        $data['coverArtist'] = $this->parseCoverArtist($xpath);

        $episode->setShownotes($data);

        $this->entityManager->persist($episode);
    }

    private function parseExecutiveProducers(\DOMXPath $xpath): array
    {
        $producers = [];

        $producerElements = $xpath->query('.//outline[@text="Executive Producers: "]/outline');

        foreach ($producerElements as $producerElement) {
            /** @var \DOMElement $producerElement */
            $producers[] = $producerElement->getAttribute('text');
        }

        $producerElements = $xpath->query('.//outline[@text="Executive Producer: "]/outline');

        foreach ($producerElements as $producerElement) {
            /** @var \DOMElement $producerElement */
            $producers[] = $producerElement->getAttribute('text');
        }

        if (!count($producers)) {
            $producerElements = $xpath->query('.//outline[starts-with(@text, "Executive Producer:")]');

            foreach ($producerElements as $producerElement) {
                /** @var \DOMElement $producerElement */
                $producers[] = str_replace('Executive Producer:', '', $producerElement->getAttribute('text'));
            }
        }

        if (!count($producers)) {
            $producersElements = $xpath->query('.//outline[starts-with(@text, "Executive Producers:")]');

            foreach ($producersElements as $producersElement) {
                /** @var \DOMElement $producersElement */
                $matchedProducers = str_replace('Executive Producers:', '', $producersElement->getAttribute('text'));

                $producers = array_merge($producers, explode(',', $matchedProducers));
            }
        }

        return array_map('trim', $producers);
    }

    private function parseAssociateExecutiveProducers(\DOMXPath $xpath): array
    {
        $producers = [];

        $producerElements = $xpath->query('.//outline[@text="Associate Executive Producers: "]/outline');

        foreach ($producerElements as $producerElement) {
            /** @var \DOMElement $producerElement */
            $producers[] = $producerElement->getAttribute('text');
        }

        $producerElements = $xpath->query('.//outline[@text="Associate Executive Producer: "]/outline');

        foreach ($producerElements as $producerElement) {
            /** @var \DOMElement $producerElement */
            $producers[] = $producerElement->getAttribute('text');
        }

        if (!count($producers)) {
            $producerElements = $xpath->query('.//outline[starts-with(@text, "Associate Executive Producer:")]');

            foreach ($producerElements as $producerElement) {
                /** @var \DOMElement $producerElement */
                $producers[] = str_replace('Associate Executive Producer:', '', $producerElement->getAttribute('text'));
            }
        }

        if (!count($producers)) {
            $producersElements = $xpath->query('.//outline[starts-with(@text, "Associate Executive Producers:")]');

            foreach ($producersElements as $producersElement) {
                /** @var \DOMElement $producersElement */
                $matchedProducers = str_replace('Associate Executive Producers:', '', $producersElement->getAttribute('text'));

                $producers = array_merge($producers, explode(',', $matchedProducers));
            }
        }

        return array_map('trim', $producers);
    }

    private function parseCoverArtist(\DOMXPath $xpath): string
    {
        $element = $xpath->query('.//outline[starts-with(@text, "Art By:")]')->item(0);

        return trim(str_replace('Art By:', '', $element->getAttribute('text')));
    }
}
