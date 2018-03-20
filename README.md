# Animus API

## Install

First of all install [composer](https://getcomposer.org/download/). Then run the makefile:

`$ make`

## Configure

Open `app/config/parameters.yml` and set all `database_`- and `mailer_`-parameters. After that, add a `frontend_url`-parameter with value of the angular app url. For local development use `http://localhost:4200`. The file should look something like this:

```yml
parameters:
    database_host: 127.0.0.1
    database_port: ~
    database_name: symfony
    database_user: root
    database_password: ~
    mailer_transport: smtp
    mailer_host: 127.0.0.1
    mailer_user: ~
    mailer_password: ~
    secret: supersecret
    frontend_url: http://localhost:4200
```

## Create entities

Run `$ php bin/console doctrine:database:create` to create the database configured in [Configure](#configure). Then run `$ php bin/console doctrine:schema:update --force` to create the tables for the entities.

## Serve

To locally serve the REST API simply run:

`$ php bin/console server:run`

This will run the REST API at `http://localhost:8000`.
