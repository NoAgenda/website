<?php

namespace App;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\ResponseInterface;

class RemoteFileResponse extends Response
{
    private $file;

    public function __construct(ResponseInterface $file)
    {
        $this->file = $file;

        $headers = $file->getHeaders(false);

        parent::__construct(null, $file->getStatusCode(), $headers);
    }

    public function sendContent()
    {
        if (!$this->isSuccessful()) {
            return parent::sendContent();
        }

        echo $this->file->getContent();

        return $this;
    }
}
