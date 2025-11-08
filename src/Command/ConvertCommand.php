<?php

namespace IAGeyser\Command;

use IAGeyser\Parser\ItemsAdderParser;
use IAGeyser\Generator\GeyserMappingsGenerator;
use IAGeyser\Converter\ResourcePackConverter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

class ConvertCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('convert')
            ->setDescription('Convert ItemsAdder pack to Geyser format')
            ->addOption('input', 'i', InputOption::VALUE_REQUIRED, 'Path to ItemsAdder pack (folder or ZIP file)')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Output directory for converted pack')
            ->addOption('pack-name', null, InputOption::VALUE_OPTIONAL, 'Name for the output pack', 'itemsadder-geyser-pack')
            ->addOption('create-zip', null, InputOption::VALUE_NONE, 'Create a ZIP file of the converted pack');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();

        $inputPath = $input->getOption('input');
        $outputPath = $input->getOption('output');
        $packName = $input->getOption('pack-name');
        $createZip = $input->getOption('create-zip');

        if (!$inputPath) {
            $io->error('Input path is required. Use --input or -i to specify the ItemsAdder pack path.');
            return Command::FAILURE;
        }

        if (!$outputPath) {
            $outputPath = getcwd() . '/output';
            $io->note("No output path specified. Using: {$outputPath}");
        }

        if (!file_exists($inputPath)) {
            $io->error("Input path does not exist: {$inputPath}");
            return Command::FAILURE;
        }

        $io->title('ItemsAdder to Geyser Converter');
        $io->section('Step 1: Parsing ItemsAdder pack');

        try {
            // Parse ItemsAdder pack
            $parser = new ItemsAdderParser($inputPath);
            $parsedData = $parser->parse();

            $items = $parsedData['items'];
            $resourcePackPath = $parsedData['resourcePackPath'];

            $io->success(sprintf('Found %d items in the pack', count($items)));

            if ($resourcePackPath) {
                $io->info("Resource pack found at: {$resourcePackPath}");
            } else {
                $io->warning('No resource pack found. Only mappings will be generated.');
            }

            // Create output directories
            $packOutputPath = $outputPath . '/' . $packName;
            $mappingsOutputPath = $outputPath . '/mappings';

            $filesystem->mkdir($packOutputPath);
            $filesystem->mkdir($mappingsOutputPath);

            // Generate Geyser mappings
            $io->section('Step 2: Generating Geyser mappings');
            $mappingsGenerator = new GeyserMappingsGenerator($items);
            $mappingsJson = $mappingsGenerator->generateJson();

            $mappingsFile = $mappingsOutputPath . '/items.json';
            file_put_contents($mappingsFile, $mappingsJson);
            $io->success("Mappings saved to: {$mappingsFile}");

            // Convert resource pack
            if ($resourcePackPath) {
                $io->section('Step 3: Converting resource pack');
                $converter = new ResourcePackConverter($resourcePackPath, $packOutputPath, $items);
                $converter->convert();
                $io->success("Resource pack converted to: {$packOutputPath}");

                // Create ZIP if requested
                if ($createZip) {
                    $io->section('Step 4: Creating ZIP file');
                    $zipPath = $outputPath . '/' . $packName . '.zip';
                    $converter->createZip($zipPath);
                    $io->success("ZIP file created: {$zipPath}");
                }
            }

            // Display summary
            $io->section('Conversion Summary');
            $io->table(
                ['Item', 'Value'],
                [
                    ['Items found', count($items)],
                    ['Resource pack', $resourcePackPath ? 'Yes' : 'No'],
                    ['Mappings file', $mappingsFile],
                    ['Output pack', $packOutputPath],
                ]
            );

            $io->success('Conversion completed successfully!');
            $io->note('Next steps:');
            $io->listing([
                'Place the mappings file in Geyser\'s custom_mappings folder',
                'Place the resource pack in Geyser\'s packs folder',
                'Restart your server or reload Geyser'
            ]);

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Conversion failed: ' . $e->getMessage());
            $io->error($e->getTraceAsString());
            return Command::FAILURE;
        }
    }
}

