<?php

namespace App\Form;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TimestampTransformer implements DataTransformerInterface
{
    public function transform($timestampAsInt)
    {
        if (!$timestampAsInt) {
            return '';
        }

        $hours = floor($timestampAsInt / 3600);
        $timestampAsInt = $timestampAsInt - ($hours * 3600);
        $minutes = floor($timestampAsInt / 60);
        $seconds = $timestampAsInt - ($minutes * 60);

        if ($minutes < 10) {
            $minutes = '0' . $minutes;
        }

        if ($seconds < 10) {
            $seconds = '0' . $seconds;
        }

        if ($hours > 0) {
            return "$hours:$minutes:$seconds";
        }

        return "$minutes:$seconds";
    }

    public function reverseTransform($timestampAsString)
    {
        if (!$timestampAsString) {
            $timestampAsString = '0';
        }

        $parts = explode(':', $timestampAsString);

        if (count($parts) > 3) {
            throw new TransformationFailedException('Invalid timestamp format.');
        }

        array_map(function($fragment) {
            if (!ctype_digit($fragment)) {
                throw new TransformationFailedException('Invalid timestamp format.');
            }
        }, $parts);

        if (count($parts) === 3) {
            list($hours, $minutes, $seconds) = $parts;
        }
        else if (count($parts) === 2) {
            $hours = 0;
            list($minutes, $seconds) = $parts;
        } else {
            $hours = 0;
            $minutes = 0;
            list($seconds) = $parts;
        }

        return (string) (($hours * 3600) + ($minutes * 60) + $seconds);
    }
}
