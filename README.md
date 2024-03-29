
# Crud Generator

Lumen package for generate crud controller,model and routes
## Installation

Run commands below:

```bash
composer require vladmunj/crud-generator
```
## Environment Variables

After installing package change database connection settings and put SWAGGER_VERSION,PACKAGE_AUTHOR variable to your .env file:

`DB_CONNECTION=YOUR_DB_TYPE[for example mysql,pgsql]`\
`DB_HOST=DATABASE_HOST`\
`DB_PORT=DATABASE_PORT`\
`DB_DATABASE=DATABASE_NAME`\
`DB_USERNAME=DATABASE_USERNAME`\
`DB_PASSWORD=DATABASE_PASSWORD`

`SWAGGER_VERSION=3.0`\
`PACKAGE_AUTHOR=AUTHOR_NAME`

## Configuration

Add CrudGeneratorProvider to providers section in bootstrap/app.php:

```php
/*
|--------------------------------------------------------------------------
| Register Service Providers
|--------------------------------------------------------------------------
|
| Here we will register all of the application's service providers which
| are used to bind services into the container. Service providers are
| totally optional, so you are not required to uncomment this line.
|
*/
// $app->register(App\Providers\AppServiceProvider::class);
// $app->register(App\Providers\AuthServiceProvider::class);
// $app->register(App\Providers\EventServiceProvider::class);
$app->register(Flipbox\LumenGenerator\LumenGeneratorServiceProvider::class);
$app->register(Vladmunj\CrudGenerator\CrudGeneratorServiceProvider::class);
```

Uncomment the $app->withEloquent() and $app->withFacades() call in your bootstrap/app.php:

```php
/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
|
| Here we will load the environment and create the application instance
| that serves as the central piece of this framework. We'll use this
| application as an "IoC" container and router for this framework.
|
*/

$app = new Laravel\Lumen\Application(
    dirname(__DIR__)
);

$app->withFacades();

$app->withEloquent();
```

Run package migrations:
```bash
php artisan migrate
```

## Usage

Before use command you need to create and run migration, that creates table for CRUD operations, for example:
```bash
php artisan make:migration create_tests_table
```
```bash
php artisan migrate
```
## Commands
```bash
php artisan make:crud
```
Command will ask you required parameters to make CRUD:

`Controller name:`\
`>`\
`CRUD url:`\
`>`\
`Model name:`\
`>`\
`Table name:`\
`>`

- Controller name: name of controller for CRUD operations.
- CRUD url: route for CRUD operations. For example, value api/test will generate routes like this:
```php
/**
* Controller routes
*/
$router->group(["prefix"=>"api/test"],function() use($router){
    // CRUD
    $router->post("/","TestController@create");
    $router->get("/","TestController@all");
    $router->get("/{id}","TestController@get");
    $router->put("/{id}","TestController@update");
    $router->delete("/{id}","TestController@delete");
});
```
- Model name: name of model for CRUD operations.
- Table name: name of table for CRUD operations.

You can check new routes with command
```bash
php artisan route:list
```

## Additional commands:
```bash
php artisan crud:route
```
Delete crud route group by id, or all route groups, if you set id = 0

```bash
php artisan make:crud:table
```
Generate CRUD for all your tables. You can set names of tables, that will be excluded from generation.
Default names of tables, that will be excluded: 'users','crud_route_groups','migrations'.
