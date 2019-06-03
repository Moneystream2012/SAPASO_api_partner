<?php
namespace App\Middleware;

use Sapaso\Service\Partner;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;

class PartnerByAccessToken
{
    /** @var Container */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, Response $response, $next)
    {
        $queryParams = $request->getQueryParams();
        $accessToken = null;
        if (array_key_exists('access_token', $queryParams)) {
            $accessToken = $queryParams['access_token'];
        }
        if ($accessToken === null) {
            return $next($request, $response);
        }
        $this->container->get('settings')['partner'] = Partner::getPartnerByAccessToken($accessToken, $this->container['em']);

        $response = $next($request, $response);

        return $response;
    }
}
