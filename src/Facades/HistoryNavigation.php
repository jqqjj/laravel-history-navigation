<?php

namespace Jqqjj\LaravelHistoryNavigation\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class HistoryNavigation
 * @package Jqqjj\LaravelHistoryNavigation\Facades
 *
 * @method static string current()
 * @method static string prev($defaultUrl = null)
 * @method static string prevUrl($url, $defaultUrl = null)
 * @method static string prevRoute($route, $defaultUrl = null)
 *
 * @see \Jqqjj\LaravelHistoryNavigation\HistoryNavigation
 */
class HistoryNavigation extends Facade
{
    /**
     * @var string
     */
    public static $k = '_referer';

    /**
     * @var string
     */
    public static $defaultUrl = '/';

    protected static function getFacadeAccessor()
    {
        return "laravel.history.navigation";
    }
}
