<?php

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

    public function parse(Episode $episode, $extra = false)
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

        $htmlDom = new \DOMDocument();
        $htmlDom->loadHTML($htmlContents);

        $htmlXpath = new \DOMXPath($htmlDom);

        $url = $htmlXpath->query('.//link[@title="OPML"]')->item(0)->getAttribute('href');

        $response = $this->client->get($url);
        $contents = $response->getBody()->getContents();

        $dom = new \DOMDocument();
        $dom->loadXML($contents);

        $xpath = new \DOMXPath($dom);

        $data['executiveProducers'] = $this->parseExecutiveProducers($xpath);
        $data['associateExecutiveProducers'] = $this->parseAssociateExecutiveProducers($xpath);

        $coverArtistText = $xpath->query('.//outline[starts-with(@text, "Art By:")]')->item(0)->getAttribute('text');
        $data['coverArtist'] = trim(str_replace('Art By:', '', $coverArtistText));

        if ($extra) {
            $nameText = $xpath->query('.//outline[starts-with(@text, "No Agenda Ep")]')->item(0)->getAttribute('text');
            preg_match('/"([^\"]+)"/', $nameText, $matches);
            $episode->setName($matches[1]);

            $linkText = $xpath->query('.//outline[starts-with(@text, "Direct [")]')->item(0)->getAttribute('text');
            $linkText = str_replace(['Direct [<a href="', '">link</a>] to the mp3 file'], '', $linkText);
            $episode->setRecordingUri($linkText);

            $imgText = $xpath->query('.//outline[@text="Cover Art"]/outline[1]')->item(0)->getAttribute('text');
            preg_match('/src="([^\"]+)"/', $imgText, $matches);
            $episode->setCoverUri($matches[1]);

            preg_match('/-' . $episode->getCode() . '-(\d+)-(\d+)-(\d+)/', $linkText, $matches);
            $publishedAt = new \DateTime("${matches[1]}-${matches[2]}-${matches[3]}");
            $episode->setPublishedAt($publishedAt);
        }

        return $data;
    }

    private function parseExecutiveProducers(\DOMXPath $xpath): array
    {
        $producers = [];

        $executiveProducerElements = $xpath->query('.//outline[@text="Executive Producers: "]/outline');

        foreach ($executiveProducerElements as $executiveProducerElement) {
            /** @var \DOMElement $executiveProducerElement */
            $producers[] = $executiveProducerElement->getAttribute('text');
        }

        $executiveProducerElements = $xpath->query('.//outline[@text="Executive Producer: "]/outline');

        foreach ($executiveProducerElements as $executiveProducerElement) {
            /** @var \DOMElement $executiveProducerElement */
            $producers[] = $executiveProducerElement->getAttribute('text');
        }

        $executiveProducerElement = $xpath->query('.//outline[starts-with(@text, "Executive Producer:")]');
        if (!count($producers) && isset($executiveProducerElement[0])) {
            $executiveProducer = $executiveProducerElement[0]->getAttribute('text');

            $prefix = 'Executive Producer:';

            $producers[] = substr($executiveProducer, strlen($prefix));
        }

        if (!count($producers)) {
            $executiveProducerElement = $xpath->query('.//outline[starts-with(@text, "Executive Producers:")]');
            if (!count($producers) && isset($executiveProducerElement[0])) {
                $executiveProducer = $executiveProducerElement[0]->getAttribute('text');

                $prefix = 'Executive Producers:';

                $executiveProducer = substr($executiveProducer, strlen($prefix));

                $producers = explode(',', $executiveProducer);
                $producers = array_map('trim', $producers);
            }
        }

        return array_map('trim', $producers);
    }

    private function parseAssociateExecutiveProducers(\DOMXPath $xpath): array
    {
        $producers = [];

        $associateExecutiveProducerElements = $xpath->query('.//outline[@text="Associate Executive Producers: "]/outline');

        foreach ($associateExecutiveProducerElements as $associateExecutiveProducerElement) {
            /** @var \DOMElement $associateExecutiveProducerElement */
            $producers[] = $associateExecutiveProducerElement->getAttribute('text');
        }

        $associateExecutiveProducerElements = $xpath->query('.//outline[@text="Associate Executive Producer: "]/outline');

        foreach ($associateExecutiveProducerElements as $associateExecutiveProducerElement) {
            /** @var \DOMElement $associateExecutiveProducerElement */
            $producers[] = $associateExecutiveProducerElement->getAttribute('text');
        }

        $associateExecutiveProducerElement = $xpath->query('.//outline[starts-with(@text, "Associate Executive Producer:")]');
        if (!count($producers) && isset($associateExecutiveProducerElement[0])) {
            $executiveProducer = $associateExecutiveProducerElement[0]->getAttribute('text');

            $prefix = 'Associate Executive Producer:';

            $producers[] = substr($executiveProducer, strlen($prefix));
        }

        if (!count($producers)) {
            $executiveProducerElement = $xpath->query('.//outline[starts-with(@text, "Associate Executive Producers:")]');
            if (!count($producers) && isset($executiveProducerElement[0])) {
                $executiveProducer = $executiveProducerElement[0]->getAttribute('text');

                $prefix = 'Associate Executive Producers:';

                $executiveProducer = substr($executiveProducer, strlen($prefix));

                $producers = explode(',', $executiveProducer);
                $producers = array_map('trim', $producers);
            }
        }

        return array_map('trim', $producers);
    }
}
