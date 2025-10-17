<?php

namespace App\Message;

class CreateSubdomainMessage
{
    public function __construct(
        private string $subdomain,
    ) {
    }

    public function getSubdomain(): string
    {
        return $this->subdomain;
    }
}
