# Melastic: Meilisearch + Eloquent

Melastic: Eloquent Query Builder + Meilisearch.

Melastic it's an extension that extend [Laravel Scout](https://laravel.com/docs/10.x/scout) package to implement missing [Eloquent Builder](https://laravel.com/docs/10.x/queries) capabilities using Elasticsearch with [Meilisearch](https://www.meilisearch.com/).

# Introduction

Laravel Scout provides a simple, driver based solution for adding full-text search to your Eloquent models Using model observers. Laravel Scout only supports simple filters only using AND boolean between expressions by default, Melastic allows for more flexible queries that support `AND`, `OR`, and combined multiple scopes using nested closures.

# Improvements over Laravel Scout

In this guide you will see how to configure and use Meilisearch filters in a hypothetical movie database.

Laravel Scout current state supporting only limiting to use `AND` operator:

```php
// Search through users filterable attributes looking for "Pimienta", and applying filters to look where age = 38 AND where name = 'Edgar'
User::search('Pimienta')->where('age', 38)->where('name', 'Edgar')->get();
```

If we need to implement a custom filter using OR we need to write a raw expression using this approach:

   ```php
   User::search('Pimienta', function ($engine, $query, $options) {
      $options['filter'] = "age = 38 OR name = 'Edgar'";
      // ... extra filters
      return $meilisearch->search($query, $options);
   })->get()
```

## What about more custom filters?

   ```php
   $ids	=	[1,2,3];
   User::search("Pimienta", function ($engine, $query, $options) use ($ids) {
      $options['filter'] = "(age = 38 OR name = 'Edgar') OR id NOT IN [" . implode(', ', $ids) . "]";
      return $meilisearch->search($query, $options);
   })->get()
```

IMHO It feels kinda bloated and difficult to maintain using different dynamic filters.

# Difference with Melastic

With Melastic, it is now possible to filter using natural Eloquent Builder methods, let's rewrite same example using Laravel style:

   ```php
   User::search('Pimienta')
   ->where(function ($query) {
       $query->where('age', 38)
             ->orWhere('name', 'Edgar');
   })
   ->orWhereNotIn('id',[1,2,3])
   ->get();
   ```

I have ported all missing functions from `Illuminate\Database\Query\Builder` into `Laravel\Scout\Builder`. These include `where`, `orWhere`, `whereIn`, `orWhereIn`, `whereNotIn`, `orWhereNotIn`, `whereExists`, `orWhereExists`, `whereNotExists`, `whereBetween`, `whereNull`, `orWhereNull`, `whereNotNull`, `whereIsEmpty`, `orWhereIsEmpty`, `whereIsNotEmpty`, and `when` to use dynamic closures enabling us to practically use the same functionality as with Eloquent models.

# Installation

You can install the package via composer:

```shell
composer require guestpectacular/melastic
```

After installing Scout, you should publish the Scout configuration file using the `vendor:publish` Artisan command. This command will publish the `scout.php` configuration file to your application's `config` directory:

```shell
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
````

Update your config to use `Meilisearch`:
```php
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default search connection that gets used while
    | using Laravel Scout. This connection is used when syncing all models
    | to the search service. You should adjust this based on your needs.
    |
    | Supported: "algolia", "meilisearch", "database", "collection", "null"
    |
    */

    'driver' => 'meilisearch',
```

And finally, update your models with just one line of code to use `Guestpectacular\Melastic\Searchable` trait to the model you would like to make searchable:

```php
<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
use Guestpectacular\Melastic\Searchable;
 
class User extends Model
{
    use Searchable;
}
```

## Changelog

## Contributing

## Security Vulnerabilities

## Credits

- [Edgar Pimienta](https://github.com/Guestpectacular)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
