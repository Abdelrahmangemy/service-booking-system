# Laravel Booking System (Laravel 12)

This repository contains a Service Booking and Availability Management REST API built with Laravel 12.

## Features
- **Roles**: Admin, Provider, Customer with role-based access control
- **Services**: Providers create/publish services with pricing and duration
- **Availability**: Recurring & custom availability with timezone support and caching
- **Bookings**: Real-time bookings with double-book prevention and conflict resolution
- **Lifecycle**: Booking status management (pending, confirmed, cancelled, completed)
- **Notifications**: Event-driven email notifications (queued)
- **Reporting**: Admin dashboard with CSV export and analytics
- **Security**: Laravel Passport for API authentication with rate limiting
- **Performance**: Caching for availability calculations, optimized queries
- **Monitoring**: Comprehensive request/response logging for debugging
- **Testing**: Extensive test coverage including edge cases and error scenarios
- **Architecture**: Clean code with API Resources, Service classes, and proper separation of concerns
- **Infrastructure**: Docker support via Laravel Sail, scheduled commands

## Quick setup
1. Create project (if you didn't already):
   ```bash
   composer create-project laravel/laravel booking-system
   cd booking-system
   ```

2. Install dependencies:
   ```bash
   composer install

   ```

3. Generate app key:
 ```bash
 php artisan key:generate
 ```

4. Copy `.env.example` to `.env` and update your database credentials.

5. Migrate and seed:
   ```bash
   php artisan migrate --seed
   ```

6. Install Passport keys:
    ```bash
    php artisan passport:install
    ```

8. Run queue worker (for mail/notifications):
    ```bash
    php artisan queue:work
    ```

9. Run tests:
    ```bash
    ./vendor/bin/phpunit
    ```

10. API Postman collection is in postman_collection.json

11. View API logs:
    ```bash
    tail -f storage/logs/api-*.log
    ```

12. Run specific test suites:
    ```bash
    # Run all tests
    ./vendor/bin/phpunit

    # Run specific test file
    ./vendor/bin/phpunit tests/Feature/AvailabilityServiceTest.php

    # Run with coverage
    ./vendor/bin/phpunit --coverage-html coverage
    ```

13. Notes:
    ```bash
    - Availability is stored with times and timezone; bookings are saved as UTC timestamps
    - Caching is implemented for availability calculations (5-minute cache)
    - All API requests/responses are logged to storage/logs/api-*.log
    - Rate limiting is applied to booking endpoints (10 requests per minute)
    - Add a cron to run php artisan schedule:run each minute when deploying
    ```
