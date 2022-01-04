# laravel-history-navigation

A way to remember previous urls via browser's location and redirect back for laravel

## installation

```php
composer require jqqjj/laravel-history-navigation
```

## import package
```php
use Jqqjj\LaravelHistoryNavigation\Facades\HistoryNavigation as H;
```

## usage

- get current url
```php
H::current();
```

- get prev page url
```php
H::prev($defaultUrl = null);
```

- get match path prev page url
```php
H::prevUrl($url, $defaultUrl = null)
```

- get match route name prev page url
```php
H::prevRoute($route, $defaultUrl = null)
```

## usage in blade view files

```php
route('home', [H:$k => H::prev()]);
route('home', [H:$k => H::prevUrl('/')]);
route('home', [H:$k => H::prevRoute('home')]);
```

## LICENCE
MIT
