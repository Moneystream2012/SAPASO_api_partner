<?php

putenv('ENV=unittest');

use Doctrine\Common\Annotations\AnnotationRegistry;
use Symfony\Component\Dotenv\Dotenv;

define('APP_ROOT_DIR', realpath(__DIR__.'/..'));
$dotEnvTestFilepath = APP_ROOT_DIR.'/.env-test';

// #### init Doctrine Annotation Registry (used for reading validation annotation from entities)
$vendorAutoloader = require APP_ROOT_DIR.'/vendor/autoload.php';
AnnotationRegistry::registerLoader(array($vendorAutoloader, 'loadClass'));
require_once APP_ROOT_DIR.'/vendor/autoload.php';

// #### load .env-test variable configuration file
try {
    $testDotEnvFileContent = @file_get_contents($dotEnvTestFilepath);
} catch (Exception $e) {
    $testDotEnvFileContent = null;
}

if (empty($testDotEnvFileContent)) {
    dump("missing ".$dotEnvTestFilepath);
    throw new Exception($dotEnvTestFilepath.' NOT FOUND or empty. Without it tests cannot be executed: essential config data are loaded from it.');
}

$testDotEnvParsed = (new Dotenv())->parse($testDotEnvFileContent, $dotEnvTestFilepath);
foreach ($testDotEnvParsed as $envEntryKey => $envEntryValue) {
    $success = putenv("${envEntryKey}=$envEntryValue");
}

$volatileTestDatabaseName = $testDotEnvParsed['DB_NAME'];

echo 'DB NAME used ' . $volatileTestDatabaseName . PHP_EOL;

// #### make sure that the temporary directory for test data is existing
$settings = (require APP_ROOT_DIR.'/src/settings.php')['settings'];
@mkdir($settings['tmp_test_data_dir'], 0777, true); // mode: 0777 (default), recursive: true

// ### show a warning if on the developer system the test database name is the same of the dev db
if (file_exists(APP_ROOT_DIR.'/.env')) {
    $defaultDotEnvParsed = (new Dotenv())->parse(
        file_get_contents(APP_ROOT_DIR . '/.env'),
        APP_ROOT_DIR . '/.env'
    );
    if ($volatileTestDatabaseName == $defaultDotEnvParsed['DB_NAME']) {
        $proceed = filter_var( // filter_var parses a boolean string to a primitive bool. example: "false" => false
            $testDotEnvParsed['IGNORE_DB_SAME_NAME_WARNING'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );
        if ($proceed == false) {
            print "\n\n[WARNING]\n\nInterrupting: the DB_NAME specified in .env-test is the same one of .env\n";
            print "No DB reset will be performed currently.\n";
            print "For disabling this check, add IGNORE_DB_SAME_NAME_WARNING=true to your .env-test\n\n";
            return -1;
        }
    }
}

//$db = new PDO(
//    'mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.$volatileTestDatabaseName,
//    getenv('DB_USER'),
//    getenv('DB_PASSWORD')
//);
//
//
//
//if (!in_array($volatileTestDatabaseName, ['sapaso_test', 'sapaso_test_client'])){
//    echo 'Invalid db name '.$volatileTestDatabaseName.PHP_EOL;
//    return -1;
//}
//
//// drop all tables in databases insted of dropping db
//// drop / create db at aws require manual actions or aws utility call
//$res = $db->query("
//    SET FOREIGN_KEY_CHECKS = 0;
//    SET @tables = NULL;
//    SELECT GROUP_CONCAT('`', table_name, '`') INTO @tables
//    FROM information_schema.tables  WHERE table_schema = '".$volatileTestDatabaseName."';
//
//    SELECT IFNULL(@tables,'dummy') INTO @tables;
//    SET @tables = CONCAT('DROP TABLE IF EXISTS ', @tables);
//    PREPARE stmt FROM @tables;
//    EXECUTE stmt;
//    DEALLOCATE PREPARE stmt;
//    SET FOREIGN_KEY_CHECKS = 1;
//");


//$db = new PDO(
//    'mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.$volatileTestDatabaseName,
//    getenv('DB_USER'),
//    getenv('DB_PASSWORD')
//);
//
//$db->query("DROP DATABASE ${volatileTestDatabaseName};");
//$db->query("CREATE DATABASE ${volatileTestDatabaseName};");



// #### RESET THE CURRENT VOLATILE TEST DATABASE: delete it and re-create it under the same name
//$mysqli = new mysqli(
//    getenv('DB_HOST'),
//    getenv('DB_USER'),
//    getenv('DB_PASSWORD')
//    );
//$res=$mysqli->query("DROP DATABASE ${volatileTestDatabaseName};");
//$mysqli->query("CREATE DATABASE ${volatileTestDatabaseName};");
//$mysqli->close();

// #### load the database structure and add test data (Person, Client, Address ecc.)
// (new \Tests\LoadTestFixturesCommand())->run();

$fixturesLoader = new Sapaso\Migrations\Tests\LoadTestFixturesCommand();
$fixturesLoader->clearDb($volatileTestDatabaseName);
$fixturesLoader->run();
