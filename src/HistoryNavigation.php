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
            $this->stacks = $this->rtrim($this->parseStacks($prevStr), $this->getCurrentUrlStack());
        }
    }

    public function current()
    {
        return $this->formatStacks(array_merge($this->stacks, [$this->getCurrentUrlStack()]));
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
        $index = $this->searchIndex($this->stacks, $this->parseStacks($url)[0]);
        if ($index == -1) {
            return $defaultUrl ?? (H::$defaultUrl ?? '/');
        }
        return $this->formatStacks(array_slice($this->stacks, 0, $index + 1));
    }

    public function prevRoute($route, $defaultUrl = null)
    {
        return $this->prevUrl(route($route), $defaultUrl);
    }

    public function hasPrev()
    {
        return count($this->stacks) > 0;
    }

    protected function getCurrentUrlStack()
    {
        $currentUrl = $this->request->fullUrlWithoutQuery([H::$k]);
        $currentStacks = $this->parseStacks($currentUrl);
        return $currentStacks[0];
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
            $urls = $this->parseStacks($prev);
        }

        $isSecure = !empty($urlInfo['scheme']) && $urlInfo['scheme'] == 'https';
        $currentUrl = [
            'secure' => $isSecure,
            'host' => $urlInfo['host'] ?? $this->request->getHost(),
            'port' => $urlInfo['port'] ?? ($isSecure ? 443 : 80),
            'path' => !empty($urlInfo['path']) ? '/' . ltrim($urlInfo['path'], '/') : '/',
            'params' => !empty($params) ? $params : [],
            'fragment' => $urlInfo['fragment'] ?? '',
        ];
        if (count($urls) > 0) {
            $lastUrl = $urls[count($urls) - 1];
            if ($lastUrl['secure'] == $currentUrl['secure'] && $lastUrl['host'] == $currentUrl['host']
                && $lastUrl['port'] == $currentUrl['port'] && $lastUrl['path'] == $currentUrl['path']) {
                array_pop($urls);
            }
        }
        array_push($urls, $currentUrl);

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

    protected function searchIndex($stacks, $stack)
    {
        for ($i = count($stacks) - 1; $i >= 0; $i--) {
            if ($stacks[$i]['secure'] == $stack['secure'] && $stacks[$i]['host'] == $stack['host']
                && $stacks[$i]['port'] == $stack['port'] && $stacks[$i]['path'] == $stack['path']) {
                return $i;
            }
        }
        return -1;
    }

    protected function rtrim($stacks, $stack)
    {
        $num = 0;
        for ($i = count($stacks) - 1; $i >= 0; $i--) {
            if ($stacks[$i]['secure'] == $stack['secure'] && $stacks[$i]['host'] == $stack['host']
                && $stacks[$i]['port'] == $stack['port'] && $stacks[$i]['path'] == $stack['path']) {
                $num++;
            } else {
                break;
            }
        }
        return array_slice($stacks, 0, count($stacks) - $num);
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
