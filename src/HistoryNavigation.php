<?php

namespace Jqqjj\LaravelHistoryNavigation;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Jqqjj\LaravelHistoryNavigation\Facades\HistoryNavigation as H;

class HistoryNavigation
{
    /**
     * @var Request
     */
    protected $request;
    protected $stacks = [];

    public function __construct()
    {
        $this->request = app('request');
        $prevStr = $this->request->has(H::$k) ? $this->request->input(H::$k) : $this->request->server("HTTP_REFERER");
        if (!empty($prevStr)) {
            $this->stacks = $this->parseStacks($prevStr);
        }
    }

    public function current()
    {
        $currentUrl = $this->request->fullUrlWithoutQuery([H::$k]);
        $currentStacks = $this->parseStacks($currentUrl);

        $isLastStack = count($this->stacks) && count($this->stacks) - 1 == $this->getStackIndex($currentStacks[0]);
        $stacks = array_slice($this->stacks, 0, $isLastStack ? count($this->stacks) - 1 : count($this->stacks));
        array_push($stacks, $currentStacks[0]);

        return $this->formatStacks($stacks);
    }

    public function prev($defaultUrl = null)
    {
        if (empty($this->stacks)) {
            return $defaultUrl ?? (H::$defaultUrl ?? '/');
        }
        return $this->formatStacks($this->stacks);
    }

    public function prevUrl($url, $defaultUrl = null)
    {
        if (empty($this->stacks)) {
            return $defaultUrl ?? (H::$defaultUrl ?? '/');
        }
        $stacks = $this->parseStacks($url);
        $index = $this->getStackIndex($stacks[0]);
        if ($index == -1) {
            return $defaultUrl ?? (H::$defaultUrl ?? '/');
        }
        return $this->formatStacks(array_slice($this->stacks, 0, $index + 1));
    }

    public function prevRoute($route, $defaultUrl = null)
    {
        return $this->prevUrl(route($route), $defaultUrl);
    }

    protected function parseStacks($url)
    {
        $urls = [];

        if (empty($url)) {
            return $urls;
        }

        //scheme host port path query fragment
        $urlInfo = parse_url(app('url')->isValidUrl($url) ? $url : url($url));
        if (!$urlInfo) {
            return $urls;
        }

        [$prev, $params] = $this->splitPrev($urlInfo['query']??'');
        if (!empty($prev)) {
            $urls = array_merge($this->parseStacks($prev));
        }

        $isSecure = !empty($urlInfo['scheme']) && $urlInfo['scheme'] == 'https';
        array_push($urls, [
            'secure' => $isSecure,
            'host' => $urlInfo['host'] ?? $this->request->getHost(),
            'port' => $urlInfo['port'] ?? ($isSecure ? 443 : 80),
            'path' => !empty($urlInfo['path']) ? '/' . ltrim($urlInfo['path'], '/') : '/',
            'params' => !empty($params) ? $params : [],
            'fragment' => $urlInfo['fragment'] ?? '',
        ]);

        return $urls;
    }

    protected function splitPrev($query)
    {
        if (empty($query)) {
            return '';
        }
        parse_str($query, $queryInfo);

        $prev = !empty($queryInfo[H::$k]) ? $queryInfo[H::$k] : '';
        if (isset($queryInfo[H::$k])) {
            unset($queryInfo[H::$k]);
        }
        return [$prev, $queryInfo];
    }

    protected function getStackIndex($stack)
    {
        for ($i = count($this->stacks) - 1; $i >= 0; $i--) {
            if ($this->stacks[$i]['secure'] == $stack['secure'] && $this->stacks[$i]['host'] == $stack['host']
                && $this->stacks[$i]['port'] == $stack['port'] && $this->stacks[$i]['path'] == $stack['path']) {
                return $i;
            }
        }
        return -1;
    }

    protected function formatStacks($stacks)
    {
        $secure = $this->request->getScheme() == 'https';
        $host = $this->request->getHost();
        $port = $this->request->getPort();

        $url = '';
        foreach ($stacks as $stack) {
            $stackUrl = '';
            if ($secure != $stack['secure'] || $host != $stack['host'] || $port != $stack['port']) {
                $stackUrl .= $stack['secure'] ? 'https://' : 'http://';
                $stackUrl .= $stack['host'];
                if ($stack['secure'] && $stack['port'] != 443 || !$stack['secure'] && $stack['port'] != 80) {
                    $stackUrl .= ':'.$stack['port'];
                }
            }
            $stackUrl .= $stack['path'];
            $query = empty($url) ? Arr::query($stack['params']) : Arr::query(array_merge($stack['params'], [H::$k => $url]));
            if (!empty($query)) {
                $stackUrl .= '?' . $query;
            }
            if (!empty($stack['fragment'])) {
                $stackUrl .= '#'.$stack['fragment'];
            }
            $url = $stackUrl;
        }
        return $url;
    }
}
