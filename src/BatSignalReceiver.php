<?php

namespace App;

use Zend\Feed\Reader\Entry\Rss as RssEntry;
use Zend\Feed\Reader\Reader;

class BatSignalReceiver
{
    public function receive()
    {
        $data = file_get_contents('https://twitrss.me/twitter_user_to_rss/?user=adamcurry');

        $feed = Reader::importString($data);
        $signal = null;

        foreach ($feed as $entry) {
            if (strpos($entry->getTitle(), '#@pocketnoagenda') === false) {
                continue;
            }

            /** @var RssEntry $signal */
            $signal = $entry;

            break;
        }

        if ($signal === null) {
            return null;
        }

        preg_match('/episode (\d+)/', $signal->getTitle(),$matches);
        list(, $code) = $matches;

        return [
            'code' => $code,
            'deployedAt' => $signal->getDateCreated(),
        ];
    }
}
