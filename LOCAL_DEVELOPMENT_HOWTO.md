Redump the database
===================

````bash
    mysql -u root -h 127.0.0.1 -P 32780 --password=<DB_PASSWORD> < mydump.sql
````

Useful Commands
===============

check containers health status

````bash
docker inspect --format "{{json .State.Health }}" www-openstack-model-db-local | jq '.