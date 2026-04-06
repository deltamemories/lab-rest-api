# Blog REST API lab  

## Installation
1. Create .env  
    create .env from .env.dist  
    fill in the fields  

2. Build container  
    ```sh
    docker compose build
    ```
3. Up container
    ```sh
    docker compose up -d
    ```

4. Install dependencies  
    ```sh
    docker compose exec php composer install
    ```
5. Generate JWT keys  
    ```sh
    docker compose exec php php bin/console lexik:jwt:generate-keypair
    ```
6. Make migrations for DB
    ```sh
    docker compose exec php php bin/console doctrine:migrations:migrate
    ```

    #### Now container is ready to get requests  

## Run
```sh
docker compose up -d
```

## Run tests
1. Create .env.test file with the following content and fill in the fields:
    ```
    KERNEL_CLASS='App\Kernel'
    APP_ENV=test
    DB_NAME=
    DB_USER=
    DB_PASSWORD=
    DB_ROOT_PASSWORD=
    DATABASE_URL=mysql://${DB_USER}:${DB_PASSWORD}@mysql:3306/${DB_NAME}?serverVersion=9.6&charset=utf8

    JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
    JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
    JWT_PASSPHRASE=

    REDIS_URL=redis://redis:6379/1

    MESSENGER_TRANSPORT_DSN=amqp://guest:guest@rabbitmq:5672/%2f/messages
    ```
2. Create *_test DB
    ```sh
    docker compose exec mysql mysql -uroot -p -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME}_test;"
    ```
    Enter ROOT PASSWORD for DB

    ```sh
    docker compose exec mysql mysql -uroot -p
    ```
    Enter ROOT PASSWORD for DB

    DB_NAME - paste your DB_NAME instead this in command
    DB_USER - paste your DB_USER instead this in command
    ```sh
    GRANT ALL PRIVILEGES ON DB_NAME_test.* TO 'DB_USER'@'%';
    ```

    ```sh
    FLUSH PRIVILEGES;
    ```

    ```sh
    EXIT;
    ```

    ```sh
    docker compose exec php php bin/console doctrine:database:create --env=test
    ```

    ```sh
    docker compose exec php php bin/console doctrine:migrations:migrate --env=test
    ```

    ```sh
    docker compose run -e "APP_ENV=test" -e "REDIS_URL=redis://redis:6379/1" php bin/phpunit
    ```

## API requests examples available in /api/doc  
