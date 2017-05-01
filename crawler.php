#!/usr/bin/php
<?php namespace App;

require __DIR__.'/vendor/autoload.php';

use Curl\Curl;
use PHPHtmlParser\Dom;

class Crawler
{
    /**
     * links that have been visited.
     *
     * @var array
     */
    protected $visited = [];

    /**
     * Links to visit (stack)
     *
     * @var array
     */
    protected $links = [];

    /**
     * Broken links found.
     *
     * @var array
     */
    protected $broken = [];

    /**
     * Curl object.
     *
     * @var \Curl\Curl
     */
    protected $http;

    /**
     * The initial url to get absolute paths.
     *
     * @var string
     */
    protected $base_url;

    /**
     * Where to find broken urls.
     *
     * @var array
     */
    protected $providers = [];

    /**
     * Number of scanned urls.
     *
     * @var int
     */
    protected $scanned = 0;

    /**
     * Number of successful scans.
     *
     * @var int
     */
    protected $successful = 0;

    /**
     * Number of erred scans.
     *
     * @var int
     */
    protected $erred = 0;

    /**
     * List of options to parse.
     *
     * @var array
     */
    protected $options = [
        // One page scan
        'once',
        // Show help list
        'help',
        // The url to start scanning from
        'url:',
        // Exclude a certain path
        'exclude:',
    ];

    /**
     * Options short hand.
     *
     * @var array
     */
    protected $short_options = [
        // once
        'o',
        // help
        'h',
        // url
        'u:',
        // exclude
        'x:',
    ];

    /**
     * Whether to scan recursively.
     *
     * @var bool
     */
    protected $recursive = true;

    /**
     * Path to exclude from scans.
     *
     * @var null|string
     */
    protected $exclude = [];

    /**
     * Crawler constructor.
     *
     * @return void
     */
    public function __construct()
    {
        $this->http = $curl = new Curl();

        $this->parseOptions();
        $this->start();
        $this->results();
    }

    /**
     * Print results to the screen.
     *
     * @return void
     */
    public function results()
    {
        echo "Scan Completed\n";
        echo "--------------\n";
        echo "Number of scanned links: {$this->scanned}\n";
        echo "Number of successful links: {$this->successful}\n";
        echo "Number of broken links: {$this->erred}\n";
        echo "--------------------------------------\n";

        if ($this->erred > 0) {
            echo "Broken Links:\n";
            echo "-------------\n";
        }

        foreach ($this->broken as $url => $link) {
            echo "$url\n";
            echo "Status: {$link['status']}\n";
            echo "Appeared in {$link['count']} pages\n";
            echo "Can be found in the following pages:\n";
            foreach ($link['found_at'] as $found_url) {
                echo "{$found_url}\n";
            }
            echo str_pad('', strlen($url), '-')."\n";
        }

        echo "END REPORT\n";
    }

    /**
     * Parse command line options.
     *
     * @return void
     */
    protected function parseOptions()
    {
        $short_options = implode('', $this->short_options);
        $options = getopt($short_options, $this->options);
        $url = null;

        foreach ($options as $key => $option) {
            switch ($key) {
                case 'url':
                case 'u':
                    $url = $option;
                    break;
                case 'once':
                case 'o':
                    $this->recursive = false;
                case 'help':
                case 'h':
                    $this->printHelp();
                    break;
                case 'x':
                case 'exclude':
                    $this->exclude = $option ? explode(',',$option) : [];
                    break;
            }
        }

        if (! $url) {
            die("Please specify a url. Example: php crawler.php --url http://example.com\n");
        }

        $this->setBaseURL($url);
    }

    /**
     * Set the base url.
     *
     * @param $url
     */
    protected function setBaseURL($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            die("Please provide a valid url. Example --url http://example.com\n");
        }

        $parsed = parse_url($url);
        $this->base_url = "{$parsed['scheme']}://{$parsed['host']}";
        if (isset($parsed['port'])) {
            $this->base_url .= ":{$parsed['port']}";
        }
        $this->base_url .= '/';

        $url = '';
        if (isset($parsed['path'])) {
            $url = "{$parsed['path']}/";
        }
        if (isset($parsed['query'])) {
            $url .= "?{$parsed['query']}";
        }

        // Add the first url to the stack
        $url = $this->base_url.trim($url, '/');
        $this->links[] = $url;
    }

    /**
     * Print help message and die.
     *
     * @return void
     */
    protected function printHelp()
    {
        echo "Crawl pages to find broken links.\n";
        echo "Options:\n";
        echo "    --url, -u      URL to scan. Example: --url http://example.com\n";
        echo "    --once, -o     Scan only the provided URL instead of visiting pages recursively\n";
        echo "    --exclude, -x  Exclude any urls in the specified path. Example: --exclude /feature/\n";
        die();
    }

    /**
     * Start the crawler.
     *
     * @return void
     */
    protected function start()
    {
        while (! empty($this->links)) {
            $link = array_shift($this->links);
            $parsedLink = $link;
            if (strlen($parsedLink) > 50) {
                $parsedLink = substr($link, 0, 50).'...';
            }

            echo "\e[K\r";
            echo "Scanning: $parsedLink\n";
            $this->getContent($link);
            $size = count($this->links);
            echo "\e[k\r";
            echo "Scanned: $this->scanned links\n";
            echo "\e[k\r";
            echo "Remaining links: $size\n";
            echo "\e[k\r";
            echo "Found {$this->erred} broken links so far\n";
            echo "\033[4A\r";
        }

        echo "\033[4B\r";
    }

    /**
     * @param $url
     *
     * @return bool
     */
    protected function isExcluded($url)
    {
        if (empty($this->exclude)) {
            return false;
        }

        foreach ($this->exclude as $exclude) {
            if (strpos($url, $this->base_url.trim($exclude, '/')) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Scan content for links and add them to the list.
     *
     * @param string $url
     * @param string $content
     *
     * @return void
     */
    protected function scan($url, $content)
    {
        $dom = new Dom();
        $dom->load($content);
        $body = $dom->find('body');

        // Process a tags
        foreach ($body->find('a') as $link) {
            $href = $link->href;

            if ($href = $this->parseLink($href, $url)) {
                $this->links[] = $href;
                $this->providers[$href][] = $url;
            }
        }

        // Process img tags.
        foreach ($body->find('img') as $link) {
            $href = $link->src;

            if ($href = $this->parseLink($href, $url)) {
                $this->links[] = $href;
                $this->providers[$href][] = $url;
            }
        }
    }

    /**
     * Parse link for processing.
     *
     * @param $link
     */
    protected function parseLink($link, $parent_link)
    {
        if (! $link || $link === '/' || $link[0] === '#' || $link === 'javascript:;' || $link[0] !== '/') {
            return false;
        }

        // Deal with relative paths
        $parent_link = trim($parent_link, '/');
        if ($link[0] == '.') {
            $link = "{$parent_link}/".ltrim($link, '/');
        }

        if (strpos($link, 'http') !== false) {
            // Ignore external URLs
            if (strpos($link, $this->base_url) === false) {
                return false;
            }
        } else {
            $link = $this->base_url.ltrim($link, '/');
        }

        if (! $this->isExcluded($link) && ! in_array($link, $this->visited) && ! in_array($link, $this->links)) {
            return $link;
        }

        return false;
    }

    /**
     * Get content.
     *
     * @param string $url
     *
     * @return string The status of the response.
     */
    protected function getContent($url)
    {
        // Increase the number of scanned urls and the url to the visited stack
        $this->scanned++;
        $this->visited[] = $url;

        // Get the content type to determine whether a full html scan is necessary.
        $this->http->head($url);
        $status = trim($this->http->responseHeaders['status-line']);

        // Handle links with an error
        if ($status !== 'HTTP/1.1 200 OK') {
            $this->handleError($url, $status);
        } else {
            $this->handleSuccessful($url);
        }

        return $status;
    }

    /**
     * Handle successful requests that returned OK 200.
     *
     * @param string $url
     *
     * @return void
     */
    protected function handleSuccessful($url)
    {
        // Increment successful scans
        $this->successful++;

        // Don't scan again if the --once flag is set
        if (! $this->recursive && $this->scanned > 1) {
            return;
        }

        // If the content is html, scan for more links.
        if (strpos($this->http->responseHeaders['content-type'], 'text/html') !== false) {
            $this->http->get($url);
            $this->scan($url, $this->http->response);
        }
    }

    /**
     * Handle scans that returned anything other than OK 200.
     *
     * @param string $url
     * @param string $status
     *
     * @return void
     */
    protected function handleError($url, $status)
    {
        $this->erred++;
        if (isset($this->broken[$url])) {
            $this->broken[$url]['count']++;
            $this->broken[$url]['found_at'] = isset($this->providers[$url]) ? $this->providers[$url] : [];
        } else {
            $this->broken[$url] = [
                'count' => 1,
                'status' => $status,
                'found_at' => isset($this->providers[$url]) ? $this->providers[$url] : [],
            ];
        }
    }
}

// Initialize the crawler.
return new Crawler();