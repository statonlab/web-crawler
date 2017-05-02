<?php

namespace App;

class sys_crawler
{
    /**
     * Path to public document root.
     *
     * @var null
     */
    protected $base_path = null;

    /**
     * Files in the scanned directory.
     *
     * @var array
     */
    protected $files = [];

    /**
     * URLs to scan against.
     *
     * @var array
     */
    protected $urls = [];

    /**
     * CLI options.
     *
     * @var array
     */
    protected $options = [
        'path:',
    ];

    /**
     * Shorthand CLI options.
     *
     * @var array
     */
    protected $short_options = [
        'p:',
    ];

    /**
     * sys_crawler constructor.
     */
    public function __construct()
    {
        $this->parseOptions();
        $this->start();
    }

    /**
     * Start the crawling.
     */
    protected function start()
    {
        while ($url = trim(fgets(STDIN))) {
            $path = $this->getPath($url);
            if (! $path) {
                fwrite(STDERR, "$url is an invalid URL.\n");
                continue;
            }
            $this->urls[] = $path;
        }

        echo "Scanning the system starting at path $this->base_path\n";
        $this->scanSystem();
        echo "Done scanning the system.\n";
        echo "List of unused files:\n";
        $unused = array_diff($this->files, $this->urls);

        foreach ($unused as $item) {
            echo "$item\n";
        }
    }

    /**
     * Scan the filesystem.
     */
    protected function scanSystem()
    {
        $dirs = [$this->base_path];

        while (! empty($dirs)) {
            $base = rtrim(array_shift($dirs), '/').'/';
            $files = array_diff(scandir($base), ['.', '..']);
            foreach ($files as $file) {
                if (is_dir($base.$file)) {
                    $dirs[] = $base.$file;
                } else {
                    $this->files[] = $base.$file;
                }
            }
        }
    }

    /**
     * Parses the CLI options.
     *
     * @return void
     */
    protected function parseOptions()
    {
        $short_options = implode('', $this->short_options);
        $options = getopt($short_options, $this->options);
        $output = null;

        foreach ($options as $key => $option) {
            switch ($key) {
                case 'path':
                case 'p':
                    $this->base_path = $option;
                    break;
            }
        }

        if (! $this->base_path) {
            fwrite(STDERR, "Please specify a starting directory to scan recursively. Example: --path /var/www/html\n");
            exit(1);
        }
    }

    /**
     * Get the system path from the URL.
     *
     * @param $url string
     * @return string|null
     */
    protected function getPath($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        return rtrim($this->base_path, '/').'/'.$path;
    }
}

// Instantiate a sys_crawler instance
return new sys_crawler();
