<p align="center"><img src="https://cloud.githubusercontent.com/assets/2197005/19776979/f4abd1be-9c54-11e6-9842-212f26e765a5.png" alt="MySQL to Google BigQuery Logo" /></p>

<h1 align="center">MySQL to Google BigQuery Sync Tool</h1>

## Table of Contents

+ [How it works](#how-it-works)
+ [Requirements](#requirements)
+ [Usage](#usage)
+ [Credits](#credits)
+ [License](#license)

## How it works

Steps when no order column has been supplied:

+ Count MySQL table rows
+ Count BigQuery table rows
+ MySQL rows > BigQuery rows?
+ Get the rows diff, split in batches of XXXXX rows/batch

Steps when order column has been supplied:

+ Get max value for order column from MySQL table
+ Get max value for order column from BigQuery table
+ Max value MySQL > Max value BigQuery?
+ Delete all rows with order column value = max value BigQuery 
to make sure no duplicate records are being created in BigQuery
+ Get max value for order column from BigQuery table
+ Get the rows diff based on new max value BigQuery, 
split in batches of XXXXX rows/batch

Final three steps:

+ Dump MySQL rows to a JSON
+ Send JSON to BigQuery
+ Repeat until all batches are sent

Tip: Create a cron job for keep syncing the tables using an interval like 15 minutes (respect the Load Jobs [quota policy](https://cloud.google.com/bigquery/quota-policy))

## Requirements

The following PHP versions are supported:

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

IGNORE_COLUMNS=password,hidden_column,another_column
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

If you want to delete (and create) the table on BigQuery for a full dump:

```bash
vendor/bin/console sync table-name --delete-table
```

## Credits

:heart: Memed SA ([memed.com.br](https://memed.com.br))

## License

MIT license, see [LICENSE](LICENSE)
