# README #

This is an api service for the sapaso partners

## How do I get set up? ##
The skeleton needs only to have the correct values in the .env file located in the
root of your project.
You may copy `$ cp .env.example .env` and paste real values
```
ENV=development/production/test
DB_HOST=<DB_IP_NR>
DB_PORT=3306
DB_NAME=database_name
DB_USER=username
DB_PASSWORD=secret_password
DISPLAY_ERRORS=true
ENABLE_LOG=true
...
```

### Summary of development set up ###
Basically you will kust need to clone this repo and: 

1. `$ composer install`

2. (assume that you already have `.env` with all needed variables)

3. `$ docker-compose up -d`

4. go to `http://localhost:8085/health`

5. if you met some permission troubles, run `$ ./scripts/dev_cache_logs_permission_fix.sh`



Note that the 'sapaso/*' modules are under our own development, check the most 
current version in the repo: https://bitbucket.org/sapaso/

As you seee we use PHP7 with the [SlimFamework](https://www.slimframework.com/), 
we use Monolog for the logging (at the moment locally). And Doctrine for the database
connection. The chadicus modules are used for oauth and the vlucas module is used 
to have an .env file containing our environment settings.

The JMS Serializer is being pulled in by the sapaso/sapaso module.


### How to run tests ###
1. it's better to go inside a container first `$ docker exec -ti SAPASO_API_PARTNER bash`
2. run tests via `./vendor/bin/phpunit`


### Things ###

- Since 2019-04-01 (temporary): To have permissions on the developer machine, you may need to run some DML statements:
```sql
 INSERT INTO `sapaso`.`Partners` (`id`, `email`, `password`) 
 VALUES (1, 'partner@sapaso.com', '$2a$12$Ekf4vQtF0g3wPDspHjICMuJiBOT2cR4ap10drFFUX3gzeOyIVSNou');
 
 UPDATE sapaso.ClientCustomers
 SET dunning_partner_id = 1, collection_partner_id = 1
 WHERE ROUND(RAND());

```
With this update statement you allow to see a 1st partner ~ 1/2 of client customers

After that just use Oauth 2.0 ClientCredentials: 

Login: partner@sapaso.com
Password: supersecret 




### Who do I talk to? ###

* Repo owner or admin
* Other community or team contact
