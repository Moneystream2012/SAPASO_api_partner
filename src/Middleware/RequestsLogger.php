<?php

namespace App\Middleware;

use Doctrine\ORM\EntityManager;
use Psr\Container\ContainerInterface;
use Slim\Container,
    Slim\Http\Request,
    Slim\Http\Response;

class RequestsLogger
{
    const LOG_LEVEL_ONLY_CALLER_AND_URI           = 2;
    const LOG_LEVEL_CALLER_URI_AND_FORM_DATA      = 4;

    const DONT_LOG_URI = array('/health');

    const TOKEN_TYPE_OAUTH              = 'oauth';
    const TOKEN_TYPE_WORKER             = 'worker';
    const TOKEN_TYPE_WRONG              = 'wrong';
    const TOKEN_TYPE_MISSING            = 'missing';

    const WORKER_USERNAME = 'worker';
    const MAX_FORM_DATA_STORED_LENGTH = 1024 * 100; // max 100kb par request

    /** @var \Monolog\Logger */
    private $requestsLogger;
    /** @var EntityManager */
    private $entityManager;
    /** @var ContainerInterface */
    private $container;
    /** @var int */
    private $logLevel;

    public function __construct(ContainerInterface $container) {
        $this->container = $container;
        $this->requestsLogger = $this->container->get('requests_logger');
        $this->entityManager = $this->container->get('em');
        $this->logLevel = $this->container->get('settings')['requests_logger']['level'];
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param $next
     * @return mixed
     * @throws \Exception
     */
    public function __invoke(Request $request, Response $response, $next)
    {

        if($this->isRequestSkippable((string)$request->getUri()->getPath()) === true) {
            return $next($request, $response);
        }

        $accessToken = $request->getParam('access_token');

        $accessTokenData = null;
        $username = null;
        $tokenType = null;

        if($accessToken) {
            /** @var \Sapaso\Entity\AccessToken $accessTokenData */
            $accessTokenData = $this->entityManager
                ->getRepository(\Sapaso\Entity\AccessToken::class)
                ->findOneBy(['access_token' => $accessToken]);

            $nowDatetime = new \DateTime('now');

            if($accessTokenData && $accessTokenData->getExpires() >= $nowDatetime) {
                $username = $accessTokenData->getClientId(); // clientId in this case is the User email address
                $tokenType = self::TOKEN_TYPE_OAUTH;
            }
            else {
                // token not present in oauth table, it still can be a worker which requires only an access_key (loaded as env variable)
                $bankApiKey = $this->container->get('settings')['bankApiKey'];

                if($accessToken === $bankApiKey) {
                    $username = self::WORKER_USERNAME;

                    $tokenType = self::TOKEN_TYPE_WORKER;
                } else {
                    $tokenType = self::TOKEN_TYPE_WRONG;
                }
            }

        } // end if($accessToken)
        else {
            $tokenType = self::TOKEN_TYPE_MISSING;
        }

        $isAuthorizedRequest = false;
        if(in_array($tokenType, array(self::TOKEN_TYPE_OAUTH, self::TOKEN_TYPE_WORKER))) {
            $isAuthorizedRequest = true;
        }

        $logDetails = array(
            'uri' => (string)$request->getUri(),
            'username' => $username,
            'token_type' => $tokenType
        );

        if($this->logLevel === self::LOG_LEVEL_CALLER_URI_AND_FORM_DATA) {
            // add also form data to the log details
            $formData = $request->getParsedBody();
            $jsonFormData = json_encode($formData, JSON_PRETTY_PRINT);

            if(strlen($jsonFormData) > self::MAX_FORM_DATA_STORED_LENGTH) {
                // truncating will result in invalid json logged form data for that entry, but it can avoid a possible log flood
                $jsonFormData = substr($jsonFormData, 0, self::MAX_FORM_DATA_STORED_LENGTH);
            }

            $logDetails['form_data'] = $jsonFormData;
        }

        $this->requestsLogger->info(
            $isAuthorizedRequest === true ? 'authorized' : 'unauthorized',
            $logDetails
        );

        return $next($request, $response);
    }

    private function isRequestSkippable(string $requestUri) : bool
    {
        $baseUri = $this->container->get('settings')['baseUri'] ?? '';
        foreach (self::DONT_LOG_URI as $currentIgnoredUri) {
            $completeIgnoredUri = $baseUri.$currentIgnoredUri;

            if($requestUri === $completeIgnoredUri) {
                return true;
            }
        }

        return false;
    }

}
