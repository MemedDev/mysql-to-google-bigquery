<?php
namespace MysqlToGoogleBigQuery\Console\Commands;

use MysqlToGoogleBigQuery\Services\SyncService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('sync')
            ->setDescription('Sync a MySQL table to BigQuery')
            ->setHelp('This commands syncs data between a MySQL table and BigQuery')
            ->addArgument('table-name', InputArgument::REQUIRED, 'The name of the table you want to sync')
            ->addOption('create-table', 'c', InputOption::VALUE_NONE, 'If BigQuery table doesn\'t exist, create it')
            ->addOption('delete-table', 'd', InputOption::VALUE_NONE, 'Delete the BigQuery table before syncing')
            ->addOption('ignore-column', 'i', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Ignore a column from syncing. You can use this option multiple times')
            ->addOption('database-name', null, InputOption::VALUE_OPTIONAL, 'MySQL database name');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = \DI\ContainerBuilder::buildDevContainer();

        $ignoreColumns = $input->getOption('ignore-column');

        if (empty($ignoreColumns) && isset($_ENV['IGNORE_COLUMNS'])) {
            $ignoreColumns = explode(',', $_ENV['IGNORE_COLUMNS']);
        }

        $databaseName = $input->getOption('database-name');

        if (empty($databaseName)) {
            $databaseName = $_ENV['DB_DATABASE_NAME'];
        }

        $service = $container->get('MysqlToGoogleBigQuery\Services\SyncService');
        $service->execute(
            $databaseName,
            $input->getArgument('table-name'),
            $input->getOption('create-table'),
            $input->getOption('delete-table'),
            $ignoreColumns,
            $output
        );
    }
}
