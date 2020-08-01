<?php

namespace App;

use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

class RemoteFileResponse extends Response
{
    private $file;

    public function __construct(ResponseInterface $file)
    {
        $this->file = $file;

        $headers = $file->getHeaders();

        parent::__construct(null, 200, $headers);
    }

    public function sendContent()
    {
        if (!$this->isSuccessful()) {
            return parent::sendContent();
        }

        echo $this->file->getBody()->getContents();

        return $this;
    }
}
