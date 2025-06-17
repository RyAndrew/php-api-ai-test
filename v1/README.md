# Laravel API Creation

## Installation & Setup
```bash
composer create-project laravel/laravel apps-api
cd apps-api
composer require firebase/jwt guzzlehttp/guzzle
php artisan make:model App -mcr --api
php artisan make:resource AppResource
php artisan make:resource AppCollection
php artisan make:request StoreAppRequest
php artisan make:request UpdateAppRequest
php artisan make:middleware JwtAuthMiddleware
php artisan make:policy AppPolicy
```
# .env config

OKTA_DOMAIN=your-domain.okta.com
OKTA_AUDIENCE=api://default
OKTA_ISSUER=https://your-domain.okta.com/oauth2/default
APP_VERSION=1.0.0

## Key Laravel Features Used:

### ✅ **Route Model Binding**
`Route::apiResource('apps', AppController::class)->parameters(['apps' => 'app:app_id'])`
- Automatically resolves App model from URL parameter
- Returns 404 if not found automatically

### ✅ **Form Request Validation**
- `StoreAppRequest` and `UpdateAppRequest` classes
- Automatic validation with custom error messages
- Clean separation of validation logic

### ✅ **API Resources**
- `AppResource` for single items
- `AppCollection` for paginated lists
- Consistent response formatting

### ✅ **Model Events & Observers**
- Automatic ID generation on create
- Timestamp management in model

### ✅ **Laravel's Pagination**
- Built-in pagination with `paginate()`
- Automatic page/limit/offset handling

### ✅ **Configuration Management**
- Environment-based config
- Clean config organization

### ✅ **Exception Handling**
- Global exception handling in bootstrap
- Automatic validation error formatting

## Comparison with Our Custom Implementation:

**Lines of Code:**
- **Our Custom:** ~800 lines across multiple files
- **Laravel:** ~400 lines (50% less code!)

**Features:**
- **Same functionality** with half the code
- **More robust** pagination, validation, caching
- **Better testing** support out of the box
- **Automatic documentation** via API resources