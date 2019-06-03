<?php
namespace App;

class App
{
    /**
     * Stores an instance of the Slim application.
     *
     * @var \Slim\App
     */
    private $app;

    public function __construct()
    {

        // FIXME: this causes an error when environment is unittest
        if (getenv('ENV') != 'unittest') {
            header("Access-Control-Allow-Origin: *");
            header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE,OPTIONS");
            header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
        }

        if (PHP_SAPI == 'cli-server') {
            // To help the built-in PHP dev server, check if the request was actually for
            // something which should probably be served as a static file
            $url  = parse_url($_SERVER['REQUEST_URI']);
            $file = __DIR__ . $url['path'];
            if (is_file($file)) {
                return false;
            }
        }

        set_time_limit(3600);

        // Bootstrap the JMS custom annotations for Object to Json mapping
        \Doctrine\Common\Annotations\AnnotationRegistry::registerAutoloadNamespace(
            'JMS\Serializer\Annotation',
            dirname(__DIR__).'/vendor/jms/serializer/src'
        );

        // Instantiate the app
        $settings = require __DIR__ . '/../src/settings.php';
        $app = new \Slim\App($settings);


        // Add the custom error handlers
        require __DIR__ . '/../src/ErrorHandlers.php';

        // Set up dependencies
        require __DIR__ . '/../src/dependencies.php';

        // Register middleware
        require __DIR__ . '/../src/middleware.php';

        // Register routes
        require __DIR__ . '/../src/routes.php';

        $this->app = $app;
    }

    /**
     * Get an instance of the application.
     *
     * @return \Slim\App
     */
    public function get()
    {
        return $this->app;
    }
}
