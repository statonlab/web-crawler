<?php

namespace App;

use stringEncode\Exception;

class exists
{
    /**
     * Path to public document root.
     *
     * @var null
     */
    protected $base_path = null;

    /**
     * CLI options.
     * @var array
     */
    protected $options = [
        'input:',
        'path:',
    ];

    /**
     * Shorthand CLI options.
     * @var array
     */
    protected $short_options = [
        'i:',
        'p:',
    ];

    /**
     * Output file.
     *
     * @var resource
     */
    protected $f_out;

    /**
     * Create and start the exists instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->parseOptions();
        $this->start();
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
                    $this->base_path = rtrim($option, '/');
                    break;
                case 'input':
                case 'i':
                    $output = $option;
                    break;
            }
        }

        if (! $this->base_path) {
            fwrite(STDERR, "Please specify a document root to scan for files. Example: --path /var/www/html\n");
            exit(1);
        }

        $this->setFileDescriptor($output);
    }

    /**
     * Create output file descriptor.
     *
     * @param $filename
     *
     * @return void
     */
    protected function setFileDescriptor($filename)
    {
        if ($filename) {
            try {
                $this->f_out = fopen($filename, 'w');
            } catch (Exception $e) {
                fwrite(STDERR, "The input file is not writable or does not exist\n");
                exit(1);
            }

            return;
        }

        $this->f_out = fopen('php://stdout', 'w');
    }

    /**
     * Start reading from STDIN.
     *
     * @return void
     */
    protected function start()
    {
        while ($line = trim(fgets(STDIN))) {
            $this->exists($line);
        }
    }

    /**
     * Check if file exits and print message.
     *
     * @param string $url
     *
     * @return  void
     */
    protected function exists($url)
    {
        $file = $this->getPath($url);

        if (!empty($file) && ! file_exists($file)) {
            fwrite($this->f_out, "$file\n");
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
        return $this->base_path.$path;
    }
}

// Create and run the program.
return new exists();
