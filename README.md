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
The tool consists of three parts:
- **crawler.php:** crawls a given url and prints a report of broken links. It also provides a list of all files found in found_files.txt and a list of broken links in broken_links.txt. Example usage below: 
```shell
# Scan all links found on this page recursively including this page. 
php crawler.php --url https://example.com

# Exclude any pages within the path https://example.com/path/
php crawler.php --url https://example.com --exclude /path/

# Help and Options
php crawler.php --help
```
- **sys_crawler.php**: Given a list of urls, this script prints unused files that exist in the filesystem but not in the list of given urls. Example usage below:
```shell
# Scan all files in /var/www/html and return unused files.
php sys_crawler.php --path /var/www/html < found_files.txt

# Scan all files in /var/www/html but only return results that contain /sites/default/ in their path.
# This allows users to ignore the site's script files such as .php and .html files 
# and include only files within the specified directory.
php sys_crawler.php --path /var/www/html --in sites/default/ < found_files.txt
 
# Help message
php sys_crawler.php --help
```
- **exists.php**: Given a set of urls and the public path in the filesystem, this script will print a list of files that **DO NOT** exists in the system. Example usage below:
```shell
# Scan /var/www/html for files listed in found_files.txt
php exists.php --input found_files.txt --path /var/www/html/
```

## Issues & Contributions
Please report any issues in the GitHub [issue tracker](https://github.com/statonlab/web-crawler/issues).

Contributions are always welcome and appreciated!

## License
This tool is licensed under the Apache 2.0 license. The details of which can be found on the [Apache website](http://www.apache.org/licenses/LICENSE-2.0).

The license notice can be found in the [License](https://github.com/statonlab/web-crawler/blob/master/License) file.
