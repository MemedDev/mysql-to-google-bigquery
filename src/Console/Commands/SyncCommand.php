<?php
namespace MysqlToGoogleBigQuery\Console\Commands;

use MysqlToGoogleBigQuery\Services\SyncService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SyncCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('sync')
            ->setDescription('Sync a MySQL table to BigQuery')
            ->setHelp('This commands syncs data between a MySQL table and BigQuery')
            ->addArgument('table_name', InputArgument::REQUIRED, 'The name of the table you want to sync');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = \DI\ContainerBuilder::buildDevContainer();

        $service = $container->get('MysqlToGoogleBigQuery\Services\SyncService');
        $service->execute($input->getArgument('table_name'), $output);
    }
}
