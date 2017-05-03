# web-crawler
This is a PHP CLI tool that can be utilized to find broken links in a website.

## Composer
This tool requires [composer](https://getcomposer.org/) to manage its dependencies. If you need to install composer, follow [their guide](https://getcomposer.org/download/).

## Installation

### Step 1: Get your Copy
Installing this tool can be done in several ways.

**Option 1: Git**

The easiest is to clone form GitHub using the following command:
```
https://github.com/statonlab/web-crawler.git
```

**Option 2: Download**

You can download a copy from the [releases](https://github.com/statonlab/web-crawler/releases) section above.

### Step 2: Install Dependencies
Run the following command within the directory to download dependencies.
```
composer install
```

## Usage
Examples:

```shell
# Scan all links found on this page recursively including this page. 
php crawler.php --url https://example.com

# Exclude any pages within the path https://example.com/path/
php crawler.php --url https://example.com --exclude /path/

# Help and Options
php crawler.php --help
```

## Issues & Contributions
Please report any issues in the GitHub [issue tracker](https://github.com/statonlab/web-crawler/issues).

Contributions are always welcome and appreciated!

## License
This tool is licensed under the Apache 2.0 license. The details of which can be found on the [Apache website](http://www.apache.org/licenses/LICENSE-2.0).

The license notice can be found in the [License](https://github.com/statonlab/web-crawler/blob/master/License) file.
