<?php

namespace App\Command;

use App\Template\Processor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProcessCommand extends Command
{
    protected static $defaultName = 'app:process-templates';

    private $processor;

    public function __construct(Processor $processor)
    {
        parent::__construct();
        $this->processor = $processor;
    }

    protected function configure()
    {
        $this->setDescription('Processes template files in the specified folder.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $folderPath = '/template/packs';
        
        if (!is_dir($folderPath)) {
            $output->writeln('<error>Specified folder does not exist.</error>');
            return Command::FAILURE;
        }

        $this->processor->processTemplates($folderPath);
        $output->writeln('<info>Templates processed successfully.</info>');

        return Command::SUCCESS;
    }
}