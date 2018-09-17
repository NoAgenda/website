<?php
/*
 * This file is part of the Onlinq library.
 *
 * (c) Onlinq <info@onlinq.nl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Form;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class TimestampTransformer implements DataTransformerInterface
{
    public function transform($timestampAsInt)
    {
        if (!$timestampAsInt) {
            return null;
        }

        dump($timestampAsInt);die;
    }

    public function reverseTransform($timestampAsString)
    {
        if (!$timestampAsString) {
            return null;
        }

        if (!strpos($timestampAsString, ':')) {
            throw new TransformationFailedException('Invalid timestamp format.');
        }

        $parts = explode(':', $timestampAsString);

        if (count($parts) < 2 || count($parts) > 3) {
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
        }

        return ($hours * 3600) + ($minutes * 60) + $seconds;
    }
}
