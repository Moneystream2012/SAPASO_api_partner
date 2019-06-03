<?php

namespace Tests;

use Slim\App;
use Slim\Container;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;

class ContainerAwareBaseTestCase extends BaseTestCase
{

    /** @var App */
    protected $app;
    /** @var Container */
    protected $container;
    /** @var array */
    protected $settings;

    public function setUp(): void
    {
        parent::setUp();

        $this->app = (new \App\App())->get();
        $this->container = $this->app->getContainer();
        $this->settings = $this->container->get('settings');

        // disable error handlers to make exceptions visible
        unset($this->app->getContainer()['errorHandler']);
        unset($this->app->getContainer()['phpErrorHandler']);
    }

    public function tearDown(): void
    {
        $this->container->get('em')->getConnection()->close();
        unset($this->app);
        unset($this->container);
        unset($this->settings);
    }

    protected function createRequest(string $method, string $uri, array $bodyData = [], array $uploadedFiles = null, array $getParams = []) : Request
    {
        $method = strtoupper($method);

        $env = Environment::mock([
            'REQUEST_METHOD' => strtoupper($method),
            'REQUEST_URI'    => $uri,
            'CONTENT_TYPE'   => 'application/x-www-form-urlencoded',
            'QUERY_STRING'   => http_build_query($getParams)
        ]);

        $request = Request::createFromEnvironment($env)->withParsedBody($bodyData);
        if ($uploadedFiles) {
            // \Psr\Http\Message\ServerRequestInterface are immutable and setters cannot be chained
            $request = $request->withUploadedFiles($uploadedFiles);
        }
        return $request;
    }

    protected function sendHttpRequest(Request $request): Response
    {
        $response = $this->app->process($request, new Response());

        return $response;
    }
}
