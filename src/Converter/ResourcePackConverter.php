<?php

namespace IAGeyser\Converter;

use Symfony\Component\Filesystem\Filesystem;

class ResourcePackConverter
{
    private Filesystem $filesystem;
    private string $javaPackPath;
    private string $outputPath;
    private array $items;

    public function __construct(string $javaPackPath, string $outputPath, array $items)
    {
        $this->filesystem = new Filesystem();
        $this->javaPackPath = $javaPackPath;
        $this->outputPath = $outputPath;
        $this->items = $items;
    }

    public function convert(): void
    {
        // Create output directory structure
        $this->filesystem->mkdir($this->outputPath);
        $this->filesystem->mkdir($this->outputPath . '/textures/items');
        $this->filesystem->mkdir($this->outputPath . '/textures/blocks');

        // Create manifest.json for Bedrock resource pack
        $this->createManifest();

        // Convert textures
        $this->convertTextures();

        // Copy other assets if needed
        $this->copyOtherAssets();
    }

    private function createManifest(): void
    {
        $manifest = [
            'format_version' => 2,
            'header' => [
                'name' => 'ItemsAdder Geyser Pack',
                'description' => 'Converted ItemsAdder pack for Geyser',
                'uuid' => $this->generateUuid(),
                'version' => [1, 0, 0],
                'min_engine_version' => [1, 16, 0]
            ],
            'modules' => [
                [
                    'type' => 'resources',
                    'uuid' => $this->generateUuid(),
                    'version' => [1, 0, 0]
                ]
            ]
        ];

        $manifestPath = $this->outputPath . '/manifest.json';
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function convertTextures(): void
    {
        // Copy textures from items first
        foreach ($this->items as $fullId => $item) {
            if (!$item['texture'] || !file_exists($item['texture'])) {
                continue;
            }

            $sourcePath = $item['texture'];
            
            // Use item ID as filename to avoid conflicts
            // This ensures unique texture names
            $itemId = $item['id'] ?? basename($sourcePath, '.' . pathinfo($sourcePath, PATHINFO_EXTENSION));
            $filename = $itemId . '.png';
            
            $destinationPath = $this->outputPath . '/textures/items/' . $filename;

            // Copy texture (Java and Bedrock use same PNG format for textures)
            if (is_file($sourcePath)) {
                $this->filesystem->copy($sourcePath, $destinationPath, true);
            }
        }

        // Also copy all textures from the resource pack
        if (is_dir($this->javaPackPath)) {
            $assetsPath = $this->javaPackPath . '/assets';
            if (is_dir($assetsPath)) {
                $this->copyTexturesRecursive($assetsPath, $this->outputPath);
            }
        }
    }

    private function copyTexturesRecursive(string $source, string $destination): void
    {
        if (!is_dir($source)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = $iterator->getSubPathname();
            $destinationPath = $destination . '/' . $relativePath;

            if ($item->isDir()) {
                $this->filesystem->mkdir($destinationPath);
            } elseif ($item->isFile()) {
                // Only copy texture files
                $ext = strtolower($item->getExtension());
                if (in_array($ext, ['png', 'tga'])) {
                    // Map Java texture paths to Bedrock paths
                    $bedrockPath = $this->mapJavaToBedrockPath($destinationPath);
                    $this->filesystem->copy($item->getPathname(), $bedrockPath, true);
                }
            }
        }
    }

    private function mapJavaToBedrockPath(string $javaPath): string
    {
        // Convert Java texture paths to Bedrock format
        // Java: assets/namespace/textures/item/texture.png
        // Bedrock: textures/items/texture.png

        $path = str_replace('\\', '/', $javaPath);
        
        // Extract texture name
        if (preg_match('#/textures/(item|items|block|blocks)/([^/]+\.(png|tga))$#i', $path, $matches)) {
            $type = strtolower($matches[1]);
            $textureName = $matches[2];
            
            $bedrockType = ($type === 'block' || $type === 'blocks') ? 'blocks' : 'items';
            return $this->outputPath . '/textures/' . $bedrockType . '/' . $textureName;
        }

        return $javaPath;
    }

    private function copyOtherAssets(): void
    {
        // Copy other assets like sounds, animations, etc. if they exist
        $possibleAssets = ['sounds', 'sounds.json', 'animations', 'animation_controllers'];

        foreach ($possibleAssets as $asset) {
            $sourcePath = $this->javaPackPath . '/' . $asset;
            if (is_dir($sourcePath) || is_file($sourcePath)) {
                $destPath = $this->outputPath . '/' . $asset;
                if (is_dir($sourcePath)) {
                    $this->filesystem->mirror($sourcePath, $destPath);
                } else {
                    $this->filesystem->copy($sourcePath, $destPath, true);
                }
            }
        }
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    public function createZip(string $zipPath): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException("Cannot create ZIP file: {$zipPath}");
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->outputPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($this->outputPath) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
    }
}

