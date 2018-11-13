<?php
/*
 * (c) Tim Goudriaan <tim@codedmonkey.com>
 */

namespace App;

use App\Entity\Episode;
use Http\Client\Common\HttpMethodsClient;

class ShownotesParser
{
    private $client;

    public function __construct(HttpMethodsClient $shownotesClient)
    {
        $this->client = $shownotesClient;
    }

    public function parse(Episode $episode)
    {
        $data = [
            'url' => null,
            'executiveProducers' => [],
            'associateExecutiveProducers' => [],
            'coverArtist' => null,
        ];

        libxml_use_internal_errors(true);

        $frontResponse = $this->client->get(sprintf('http://%s.noagendanotes.com', $episode->getCode()));

        $data['url'] = $frontResponse->getHeaderLine('Location');

        $htmlResponse = $this->client->get($data['url']);
        $htmlContents = $htmlResponse->getBody()->getContents();

        $htmlDom = new \DOMDocument;
        $htmlDom->loadHTML($htmlContents);

        $htmlXpath = new \DOMXPath($htmlDom);

        $url = $htmlXpath->query('.//link[@title="OPML"]')->item(0)->getAttribute('href');

        $response = $this->client->get($url);
        $contents = $response->getBody()->getContents();

        $dom = new \DOMDocument;
        $dom->loadXML($contents);

        $xpath = new \DOMXPath($dom);

        $executiveProducerElements = $xpath->query('.//outline[@text="Executive Producers: "]/outline');

        foreach ($executiveProducerElements as $executiveProducerElement) {
            /** @var \DOMElement $executiveProducerElement */
            $data['executiveProducers'][] = $executiveProducerElement->getAttribute('text');
        }

        $executiveProducerElements = $xpath->query('.//outline[@text="Executive Producer: "]/outline');

        foreach ($executiveProducerElements as $executiveProducerElement) {
            /** @var \DOMElement $executiveProducerElement */
            $data['executiveProducers'][] = $executiveProducerElement->getAttribute('text');
        }

        $associateExecutiveProducerElements = $xpath->query('.//outline[@text="Associate Executive Producers: "]/outline');

        foreach ($associateExecutiveProducerElements as $associateExecutiveProducerElement) {
            /** @var \DOMElement $associateExecutiveProducerElement */
            $data['associateExecutiveProducers'][] = $associateExecutiveProducerElement->getAttribute('text');
        }

        $associateExecutiveProducerElements = $xpath->query('.//outline[@text="Associate Executive Producer: "]/outline');

        foreach ($associateExecutiveProducerElements as $associateExecutiveProducerElement) {
            /** @var \DOMElement $associateExecutiveProducerElement */
            $data['associateExecutiveProducers'][] = $associateExecutiveProducerElement->getAttribute('text');
        }

        $coverArtistText = $xpath->query('.//outline[starts-with(@text, "Art By: ")]')->item(0)->getAttribute('text');
        $data['coverArtist'] = str_replace('Art By: ', '', $coverArtistText);

        return $data;
    }
}
