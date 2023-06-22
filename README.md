## Technologi
- Laravel for backend RESTful API
- CRUD operations for managing data
- Searching and pagination functionality
- JWT Authentication

## Installation

1. Clone this repository to your local machine:

```
git clone https://github.com/nijarhock/tudas-coding-backend.git
```

2. Install the dependencies for the Laravel project:

```
composer install
```

3. Create a .env file for your Laravel project and configure your database settings:

```
cp .env.example .env
```

4. Generate a new APP_KEY for your Laravel project:

```
php artisan key:generate
```


5. Run database migrations:

```
php artisan migrate:fresh --seed
```


6. Run JWT Secret and Storage Link:

```
php artisan jwt:secret
php artisan storage:link
```

7. Start the development server for the Laravel project:

```
php artisan serve
```

8. Email & Password Login

```
Email : admin@example.com
Password : admin
```
