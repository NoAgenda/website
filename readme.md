# No Agenda Experience

The source code of [noagendaexperience.com](https://www.noagendaexperience.com),
a [Symfony](https://symfony.com/) application.

## Installation

To run this application you'll need a server running Apache, PHP 7.2, MySQL 8
and Supervisor.

You'll also need Composer and Yarn (+ NPM) to download third-party modules.

See `.env` for configuration options (copy to `.env.local` for local configuration).

```bash
composer install
php bin/console doctrine:migrations:migrate --no-interaction
yarn && yarn run production
```

To start, direct the web server towards the `public/` directory.

For more information on running and managing the application, see the
[Symfony 4.4 documentation](https://symfony.com/doc/4.4/index.html).

## Crawling

Crawling can be done in one of two ways: by manual execution or through the 
Messenger queue. 

```bash
# Crawl the podcast feed
php bin/console app:crawl feed

# Crawl natrascripts.online for new transcript URIs
php bin/console app:crawl transcripts

# Crawl Adam's Mastodon feed for the latest bat signal
php bin/console app:crawl bat_signal

# Crawl Youtube for new Animated No Agenda videos
php bin/console app:crawl youtube

# Download an episode's cover and recording file
php bin/console app:crawl files --episode <code>

# Crawl an episode's shownotes
php bin/console app:crawl shownotes --episode <code>

# Crawl an episode's transcript
php bin/console app:crawl transcript --episode <code>

# Match an episode's recording time
php bin/console app:crawl recording_time --episode <code>

# Match an episode's chat messages
php bin/console app:crawl chat_messages --episode <code>

# Record a chunk of the livestream
php bin/console app:record livestream

# Record the live chat messages
php bin/console app:record chat
```

### Running the queue

See the [Symfony Messenger documentation](https://symfony.com/doc/4.4/messenger.html)
for details.

```bash
php bin/console app:enqueue
php bin/console messenger:consume crawler
```

## Running continuous tasks
See `docker/supervisor.conf` for an example Supervisor configuration to 
automatically keep the continuous processes running: chat recording, livestream
recording and the Messenger queue.

```bash
sudo service supervisor start
sudo supervisor update
sudo supervisor start all
```
