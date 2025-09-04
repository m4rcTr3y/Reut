# REUT Backend Framework

REUT is a lightweight PHP framework designed to simplify web development by providing an intuitive structure for routing, database management, and authentication.

Built on top of [Slim PHP](https://www.slimframework.com/) for routing, REUT uses JWT (JSON Web Tokens) for secure authentication and offers a unique approach to database interaction—no need to manually create tables! Define your data structure in a PHP class, and REUT handles the rest, including automatic CRUD API generation for the defined model.

## Features

- **Slim PHP Routing**: Fast and flexible routing powered by Slim.
- **Model-Based Database Management**: Define your database tables as PHP classes in the `models` directory—no manual SQL table creation required.
- **Automatic CRUD API**: Default CRUD endpoints are generated for each defined model.
- **File Upload Handling**: Automatically manages file uploads when defined in model fields.
- **Customizable Routes**: Add custom routes in the `routers` directory with optional authentication middleware.
- **Configurable Setup**: Database connection details can be set in `.env` or `config.php`.

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/m4rcTr3y/reut.git
cd reut
```

### 2. Install Dependencies

Ensure you have [Composer](https://getcomposer.org/) installed, then run:

```bash
composer install
```

### 3. Configure Environment

- Copy the `.env.example` file to `.env`:

    ```bash
    cp .env.example .env
    ```

- Edit `.env` with your database connection details:

    ```env
    DB_HOST=localhost
    DB_NAME=your_database
    DB_USER=your_username
    DB_PASS=your_password
    ```

- Alternatively, update `config.php` with the same details.

### 4. Set Up File Permissions

Ensure the `uploads` folder is writable:

```bash
chmod -R 775 uploads
```

## Usage

### Defining a Model

Create a class in the `models` directory to describe your table structure.  
For example:

```php
<?php

namespace Reut\Models;

use Reut\DB\DataBase;
use Reut\DB\Types\Varchar;
use Reut\DB\Types\Integer;

class MessagesTable extends DataBase {
        public function __construct($config) {
                parent::__construct($config, [], 'accounts', true, 0, ['all']);

                // Add columns
                $this->addColumn('id', new Integer(false, true, true));
                $this->addColumn('name', new Varchar(255, false));
                // More fields
        }

        // Custom functions or methods
}
```

### Create Table from CLI

To create the tables in the database based on the definitions in your model classes, run the following command (command always run first on new project):

```bash
php manage.php create
```

This will automatically generate the necessary database tables as defined in your PHP classes within the `models` directory.  


### update table from CLI
To update the table column if you added or removed a new definition e.g. `  $this->addColumn('name', new Varchar(255, false));` in the models, this command can be used to update the database 


```bash
php manage.php migrate
```
### Display migrations and migration status

To display information about pending migrations in the project or in the models so as the definitions are in sync with the database.

```bash
php manage.php status
```
### Generate Router configurations for the models

To generate router configurations for the model, this will automatically generate the router file for each model in the project and also register it in the index.php so as to be accessed on the api

```bash
php manage.php generate:routes
```

### Generate model definitions or template

This command can be used to generate model template for a table , arguments for a table name are required in the command

```bash
php manage.php generate:models Users
```
Here `Users` is the table name and are required to start with an uppercase letter as is used to create the className too, the table name can be later changed if required in the code.




### Automatic Table Creation and CRUD Endpoints

This will automatically create a `messages` table with the specified fields and generate CRUD endpoints for it.

### Example API Requests

#### 1. Get All Records

**Endpoint:** `GET http://localhost:9000/Messages/all`

**Request:**

```bash
curl -X GET http://localhost:9000/Messages/all
```
- **The data here returned is paginated too** 
---

#### 2. Add a New Record

**Endpoint:** `POST http://localhost:9000/Messages/add`

**Request:**

```bash
curl -X POST http://localhost:9000/Messages/add \
-H "Content-Type: application/json" \
-d '{
    "name": "New Message"
}'
```

---

#### 3. Update an Existing Record

**Endpoint:** `PUT http://localhost:9000/Messages/update/{id}`

**Request:**

```bash
curl -X PUT http://localhost:9000/Messages/update/1 \
-H "Content-Type: application/json" \
-d '{
    "name": "Updated Message"
}'
```

This endpoint requires the `id` of the record to be updated as a URL parameter and the fields to update as a JSON payload in the request body.

---

#### 4. Delete a Record

**Endpoint:** `DELETE http://localhost:9000/Messages/delete/{id}`

**Request:**

```bash
curl -X DELETE http://localhost:9000/Messages/delete/1
```
This endpoint requires the `id` of the record to be deleted as a URL parameter

s---

### Adding Custom Routes

To add custom routes, create a file in the `routers` directory.  
For example:

```php
<?php

namespace Reut\Routers;

use Slim\Routing\RouteCollectorProxy;

class MessageRouter {
        public function __construct($app) {
                $this->register($app);
        }

        public static function register($app) {
                $app->group('/custom', function (RouteCollectorProxy $group) {
                        $group->get('/example', function ($request, $response) {
                                $response->getBody()->write("This is a custom route!");
                                return $response;
                        });
                });
        }
}
```

You can also apply middleware for authentication or other purposes or extend the `Auth` class:

```php
namespace Reut\Routers;

use Reut\Models\MessagesTable;
use Reut\Auth\Auth;
use Slim\App;
use Slim\Routing\RouteCollectorProxy as CollectionProxy;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class MessagesRouter extends Auth {
        protected $config, $jwtAuth;

        public function __construct(App $app, array $config) {
                $this->config = $config;
                parent::__construct($app, $config);
        }

        protected function genRoutes() {
                $this->app->group('/messages', function (CollectionProxy $group) {
                        $group->get('/all', function (Request $request, Response $response) {
                                $table = new MessagesTable($this->config);
                                $table->findAll();
                                $data = $table->paginate();
                                $response->getBody()->write(json_encode($data));
                                return $response->withHeader('Content-Type', 'application/json');
                        });
                });
        }
}
```

### Running the Application

Start the built-in PHP server for development:

```bash
php -S localhost:8000 -t .
```
Visit [http://localhost:8000](http://localhost:8000) in your browser to see your application in action.

## Planned Improvements

While REUT provides a solid foundation for web development, there are several enhancements and fixes planned for future updates:

- **Additional Data Types**: Expanding the supported data types for table definitions to provide more flexibility.
- **Custom Router Implementation**: Replacing Slim PHP with a custom router for better control and optimization.
- **Enhanced Security**: Strengthening the framework's security features, including improved input validation and protection against common vulnerabilities.
- **Bug Fixes**: Addressing known issues and improving overall stability.
- **Performance Optimization**: Refining the framework to ensure faster execution and reduced resource usage.
- **Extended Documentation**: Providing more detailed guides and examples for developers.
- **Support PostgreSQL**: adding support for postgresql for this case database connection.

Stay tuned for updates and feel free to contribute to the project on GitHub!


