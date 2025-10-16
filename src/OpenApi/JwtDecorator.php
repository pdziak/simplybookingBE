<?php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use ApiPlatform\OpenApi\Model\Server;

class JwtDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);

        // Add servers with /api prefix
        $openApi = $openApi->withServers([
            new Server('http://localhost/api', 'Development server'),
            new Server('https://api.benefitowo.com/api', 'Production server')
        ]);

        return $openApi;
    }
}
