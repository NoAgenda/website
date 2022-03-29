# No Agenda

<img src="https://circleci.com/gh/NoAgenda/website.svg?style=shield" alt="CircleCI status">
<img src="https://noagenda.semaphoreci.com/badges/website/branches/develop.svg" alt="Semaphore status">

The source code of [noagendashow.net](https://www.noagendashow.net),
a [Symfony](https://symfony.com/) application.

## Installation

You need [Docker](https://www.docker.com/) to run this application. For more
information on managing the application, see the [Symfony 4.4 documentation](https://symfony.com/doc/4.4/index.html).

See `.env` for configuration options (create `.env.local` to override configuration).

To initialize the application, simply start it with Docker Compose:

```bash
# Start the Docker containers
docker-compose up -d

# Load demo data
docker exec -it noagenda_app_1 bin/console doctrine:fixtures:load

# Create resized versions of the episode covers
docker exec noagenda_app_1 bin/console app:refresh-cover-cache
```

After a short setup, the application should be running on http://localhost:8033

To start a Terminal session inside the main Docker container, run:
```bash
docker exec -it noagenda_app_1 bash
```

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

## Crawling

Crawling can be done in one of two ways: by manual execution or through the 
Messenger queue. 

```bash
# Crawl the podcast feed
bin/console app:crawl feed

# Crawl natrascripts.online for new transcript URIs
bin/console app:crawl transcripts

# Crawl Adam's Mastodon feed for the latest bat signal
bin/console app:crawl bat_signal

# Crawl Youtube for new Animated No Agenda videos
bin/console app:crawl youtube

# Download an episode's cover and recording file
bin/console app:crawl files --episode <code>

# Crawl an episode's shownotes
bin/console app:crawl shownotes --episode <code>

# Crawl an episode's transcript
bin/console app:crawl transcript --episode <code>

# Match an episode's recording time
bin/console app:crawl recording_time --episode <code>

# Match an episode's chat archive
bin/console app:crawl chat_archive --episode <code>

# Record a chunk of the livestream
bin/console app:record livestream

# Record the live chat messages
bin/console app:record chat
```

### Running the queue

See the [Symfony Messenger documentation](https://symfony.com/doc/4.4/messenger.html)
for details.

When running the Docker setup, the queue is automatically processed by the 
`crawler` container, but you can run it manually with:

```bash
bin/console messenger:consume crawler
```

You can also enqueue a task from Terminal with one of the following commands:

```bash
bin/console app:enqueue bat_signal
bin/console app:enqueue feed
bin/console app:enqueue transcripts
bin/console app:enqueue youtube
```
