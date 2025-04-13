# Installation

> Docker and docker-compose are required.

1. Clone the repo.
2. `cd task_ahmed_wagdy`
3. `docker-compose build`
4. `docker-compose up -d`.
5. `docker-compose exec symfony composer install`

Open the browser and navigate to the [API Doc](http://localhost:8010/api/doc).

# Run test cases

`./vendor/bin/phpunit`
