# No Agenda Website

The source code of [noagendashow.net](https://www.noagendashow.net),
a [Symfony](https://symfony.com/) application.

This application is an online media player and archive specifically made for
The No Agenda Show podcast. The project started in 2016 as a fan project with
the intention to aggregate all resources related to the show into one
easy-to-use interface. It is now the official No Agenda Show's website.

Because the podcast and many producer-made resources are published separately
this application has a built-in crawler to fetch data from different sources.
To control the flow of crawling jobs the application uses a messenger queue
wich requires to be run separately from the main application.

## Installation

You need [Docker](https://www.docker.com/) to run this application. For more
information on managing the application, see the [Symfony 5.4 documentation](https://symfony.com/doc/5.4/index.html).

See `.env` for configuration options (create `.env.local` to override options).

To initialize the application, simply start it with Docker Compose:

```bash
# Start the Docker project with locally built containers
docker compose up -d

# or start with production containers from the web
APP_TAG=latest docker compose up -d

# or start the expanded configuration with extra services
docker compose -f docker-compose.yaml -f docker-compose.services.yaml up -d

# View container logs
docker compose logs -f
```

After a short setup, the application should be running on [http://localhost:8033](http://localhost:8033).

## CLI Commands

Useful commands:

```bash
# Start a Terminal session inside the main Docker container
docker compose exec app bash

# Load demo data
docker compose exec app bin/console doctrine:fixtures:load

# Create resized versions of the episode covers
docker compose exec app bin/console refresh-cover-cache
```

### Crawling

Crawling can be done in one of two ways: by manual execution or through the
Messenger queue.

Types of data to crawl:
* bat_signal
* cover (requires episode code)
* duration (requires episode code)
* feed
* shownotes (requires episode code)
* transcript (requires episode code)
* youtube

```bash
# Crawl directly from the command line
docker compose exec app bin/console crawl <data>
docker compose exec app bin/console crawl <data> --episode <code>

# Add a crawling job to the messenger queue
docker compose exec app bin/console enqueue <data>
docker compose exec app bin/console enqueue <data> --episode <code>
```

### Messenger Queue

While the messenger queue is currently only used for crawling jobs, it's
important to always have the queue running in a live environment because
crawling jobs can schedule new jobs, like re-downloading a resource or
crawling the resources for a new episode.

There is a separate service defined for the
messenger in the `docker-compose.services.yaml` file, but it's still possible
to manually run the messenger queue. Note that the messenger needs the ability
to handle large files so there's a separate image with an increased memory
limit specifically for crawling.

```bash
docker compose exec app bin/console messenger:consume crawler
```

See the [Symfony Messenger documentation](https://symfony.com/doc/4.4/messenger.html)
for information on the messenger queue.

## Push notifications

To enable push notification support you'll need to generate VAPID keys.

```shell
npx web-push generate-vapid-keys
```

Add the keys to your `.env.local` file.

## Testing

### PHP

To execute the PHP/Symfony unit tests run:
```bash
php bin/phpunit
```

### JavaScript

To execute the JavaScript unit tests run:
```bash
docker exec -t noagenda_assets_1 npm run test
```

You can also have the test run automatically when a file changes while developing by running:
```bash
docker exec -t noagenda_assets_1 npm run test-watch
```
