# Animus API

## Install

First of all install [composer](https://getcomposer.org/download/). Then run the makefile:

`$ make`

## Configure

Open `app/config/parameters.yml` and add `frontend_url`-parameter with value of the angular app url. For local development use `http://localhost:4200`. The file should look something like this:

```yml
parameters:
    ...
    secret: supersecret
    frontend_url: http://localhost:4200
```

## Serve

To locally serve the REST API simply run:

`$ php bin/console server:run`

This will run the REST API at `http://localhost:8000`.
