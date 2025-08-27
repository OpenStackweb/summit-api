Run Local Dev Server
====================

1. Create [.env](.env) file with following properties

```dotenv
GITHUB_OAUTH_TOKEN="<GITHUB TOKEN FROM YOUR GITHUB ACCOUNT>"

APP_ENV=local
APP_DEBUG=true
APP_KEY=<YOU LV KEY>
DEV_EMAIL_TO=smarcet@gmail.com
APP_URL=http://localhost

DB_HOST=db_config
DB_DATABASE=api_config
DB_USERNAME=root
DB_PASSWORD=1qaz2wsx

SS_DB_HOST=db_model
SS_DATABASE=api_model
SS_DB_USERNAME=root
SS_DB_PASSWORD=1qaz2wsx

REDIS_HOST=redis
REDIS_PORT=6379
REDIS_DB=0
REDIS_PASSWORD=1qaz2wsx!
REDIS_DATABASES=16
SSL_ENABLED=false
CACHE_DRIVER=redis
SESSION_DRIVER=redis
SESSION_COOKIE_DOMAIN=
SESSION_COOKIE_SECURE=false
QUEUE_DRIVER=redis

REGISTRATION_DEFAULT_PAYMENT_PROVIDER=Stripe
REGISTRATION_DEFAULT_STRIPE_TEST_MODE=true
REGISTRATION_DEFAULT_LIVE_STRIPE_PRIVATE_KEY=
REGISTRATION_DEFAULT_LIVE_STRIPE_PUBLISHABLE_KEY=
REGISTRATION_DEFAULT_LIVE_WEBHOOK_SECRET=
REGISTRATION_DEFAULT_TEST_STRIPE_PRIVATE_KEY=
REGISTRATION_DEFAULT_TEST_STRIPE_PUBLISHABLE_KEY=
REGISTRATION_DEFAULT_TEST_WEBHOOK_SECRET=

BOOKABLE_ROOMS_DEFAULT_PAYMENT_PROVIDER=Stripe
BOOKABLE_ROOMS_DEFAULT_STRIPE_TEST_MODE=true
BOOKABLE_ROOMS_DEFAULT_LIVE_STRIPE_PRIVATE_KEY=
BOOKABLE_ROOMS_DEFAULT_LIVE_STRIPE_PUBLISHABLE_KEY=
BOOKABLE_ROOMS_DEFAULT_LIVE_WEBHOOK_SECRET=
BOOKABLE_ROOMS_DEFAULT_TEST_STRIPE_PRIVATE_KEY=
BOOKABLE_ROOMS_DEFAULT_TEST_STRIPE_PUBLISHABLE_KEY=
BOOKABLE_ROOMS_DEFAULT_TEST_WEBHOOK_SECRET=

```
2.( optional ) Drop here  [docker-compose/mysql/model](docker-compose/mysql/model) the database dump *.sql file
3.Install docker and docker compose see
   [https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-compose-on-ubuntu-22-04](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-compose-on-ubuntu-22-04) and [https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-on-ubuntu-22-04](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-on-ubuntu-22-04)
4.Run script ./start_local_server.sh (http://localhost:8002/)

Redump the database
===================

````bash
    mysql -u root -h 127.0.0.1 -P 32781 --password=<DB_PASSWORD> < mydump.sql
````

Useful Commands
===============

check containers health status

````bash
docker inspect --format "{{json .State.Health }}" www-openstack-model-db-local | jq '.
````

# APC CACHE Page

1. open file at /var/www/public/apc.php
2. add following lines to the beginning 
````php
$AUTHENTICATION = 1;               
define('ADMIN_USERNAME', 'apc');   
define('ADMIN_PASSWORD', '1qaz2wsx!');
````
3. now you can access to http://localhost:8002/apc.php -> [ User Cache Entries ] Section