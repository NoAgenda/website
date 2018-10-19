<?php

namespace App;

use Colorfield\Mastodon\MastodonAPI;

class BatSignalReceiver
{
    private $api;

    public function __construct(?MastodonAPI $api)
    {
        $this->api = $api;
    }

    public function receive()
    {
        if (!$this->api) {
            throw new \RuntimeException('Connection to the Mastodon instance was not initialized.');
        }

        $entries = $this->api->get('/accounts/1/statuses');

        $signal = null;

        foreach ($entries as $entry) {
            if (strpos($entry['content'], '#@pocketnoagenda') === false) {
                continue;
            }

            $signal = $entry;

            break;
        }

        if ($signal === null) {
            throw new \RuntimeException('The sky is dark. No bat signal was found.');
        }

        preg_match('/episode (\d+)/', $signal['content'],$matches);
        list(, $code) = $matches;

        return [
            'code' => $code,
            'deployedAt' => new \DateTime($signal['created_at']),
        ];
    }
}
