
# Install Composer Dependencies

```
composer install
```

# Set Up Environment Variables

```
cp .env.example .env
```

# Generate an Application Key

```
php artisan key:generate
```

# Set Up the Database

```
php artisan migrate --seed
```

# Image upload publicly accessible

```
php artisan storage:link
```

# Serve the Application

```
php artisan serve
```

# Admin Credential

```
email: admin@gmail.com
Password: 123
```