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
BQ_KEY_FILE=google-service-account-json-key-file.json
BQ_DATASET=bigquery-dataset-name

DB_DATABASE_NAME=mysql-database-name
DB_USERNAME=mysql_username
DB_PASSWORD=mysql_password
DB_HOST=mysql-host
DB_DRIVER=pdo_mysql
```

PS: To create the `Google Service Account JSON Key File`, access [https://console.cloud.google.com/apis/credentials/serviceaccountkey](https://console.cloud.google.com/apis/credentials/serviceaccountkey)

Run:

```bash
vendor/bin/console sync table-name
```

If you want to auto create the table on BigQuery:

```bash
vendor/bin/console sync table-name --create-table
```

## Credits

:heart: Memed SA ([memed.com.br](https://memed.com.br))

## License

MIT license, see [LICENSE](LICENSE)
