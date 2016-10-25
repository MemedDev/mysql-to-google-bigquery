<h1 align="center">MySQL to Google BigQuery Sync Tool</h1>

## Table of Contents

+ [Requirements](#requirements)
+ [Usage](#usage)
+ [Credits](#credits)
+ [License](#license)

## Requirements

The following PHP versions are supported:

+ PHP 5.6
+ PHP 7
+ HHVM
+ PDO Extension with MySQL driver

## Usage

Download the library using [composer](https://packagist.org/packages/memeddev/mysql-to-google-bigquery):

```bash
$ composer require memeddev/mysql-to-google-bigquery
```

Now, define some environment variables or create a `.env` file on the root of the project, replacing the values:

```text
BQ_PROJECT_ID=bigquery-project-id
BQ_KEY_FILE=bigquery-service-account-json-key-file.json
BQ_DATASET=bigquery-dataset-name

DB_DATABASE_NAME=mysql-database-name
DB_USERNAME=mysql_username
DB_PASSWORD=mysql_password
DB_HOST=mysql-host
DB_DRIVER=pdo_mysql
```

Run:

```bash
vendo/bin/console sync write-table-name-here
```

## Credits

:heart: Memed SA

## License

MIT license, see [LICENSE](LICENSE)
