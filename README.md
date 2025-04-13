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

# Run the command

```cli
docker-compose exec symfony php bin/console app:process-payment aci \
                                --amount=100.00 \
                                --currency=USD \
                                --cardNumber=4111111111111111 \
                                --cardExpMonth=12 \
                                --cardExpYear=2025 \
                                --cardCvv=123
```
