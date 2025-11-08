<?php

namespace IAGeyser\Parser;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Filesystem\Filesystem;

class ItemsAdderParser
{
    private Filesystem $filesystem;
    private string $packPath;
    private array $items = [];
    private ?string $resourcePackPath = null;

    public function __construct(string $packPath)
    {
        $this->filesystem = new Filesystem();
        $this->packPath = $packPath;
    }

    public function parse(): array
    {
        if (!is_dir($this->packPath) && !is_file($this->packPath)) {
            throw new \RuntimeException("ItemsAdder pack path does not exist: {$this->packPath}");
        }

        // If it's a ZIP file, extract it temporarily
        $isZip = false;
        $tempDir = null;
        if (is_file($this->packPath) && pathinfo($this->packPath, PATHINFO_EXTENSION) === 'zip') {
            $tempDir = sys_get_temp_dir() . '/iageyser_' . uniqid();
            $this->extractZip($this->packPath, $tempDir);
            $this->packPath = $tempDir;
            $isZip = true;
        }

        try {
            // Find items.yml file
            $itemsYmlPath = $this->findItemsYml();
            if ($itemsYmlPath) {
                $this->parseItemsYml($itemsYmlPath);
            }

            // Find resourcepack folder
            $this->findResourcePack();

            return [
                'items' => $this->items,
                'resourcePackPath' => $this->resourcePackPath,
                'packPath' => $this->packPath
            ];
        } finally {
            // Clean up temp directory if we extracted a ZIP
            if ($isZip && $tempDir && is_dir($tempDir)) {
                $this->filesystem->remove($tempDir);
            }
        }
    }

    private function findItemsYml(): ?string
    {
        $possiblePaths = [
            $this->packPath . '/items.yml',
            $this->packPath . '/contents/items.yml',
            $this->packPath . '/configs/items.yml',
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        // Search recursively
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->packPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === 'items.yml') {
                return $file->getPathname();
            }
        }

        return null;
    }

    private function parseItemsYml(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        try {
            $content = file_get_contents($path);
            $data = Yaml::parse($content);

            if (!is_array($data)) {
                return;
            }

            // ItemsAdder items.yml structure: items: namespace: id: {properties}
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $namespace => $namespaceItems) {
                    if (!is_array($namespaceItems)) {
                        continue;
                    }

                    foreach ($namespaceItems as $itemId => $itemData) {
                        if (!is_array($itemData)) {
                            continue;
                        }

                        $fullId = $namespace . ':' . $itemId;
                        
                        $this->items[$fullId] = [
                            'namespace' => $namespace,
                            'id' => $itemId,
                            'fullId' => $fullId,
                            'displayName' => $itemData['display_name'] ?? $itemData['display-name'] ?? $itemId,
                            'resource' => $itemData['resource'] ?? null,
                            'material' => $itemData['material'] ?? 'DIAMOND',
                            'customModelData' => $itemData['custom_model_data'] ?? $itemData['custom-model-data'] ?? null,
                            'durability' => $itemData['durability'] ?? null,
                            'maxStackSize' => $itemData['max_stack_size'] ?? $itemData['max-stack-size'] ?? 64,
                            'lore' => $itemData['lore'] ?? [],
                            'enchantments' => $itemData['enchantments'] ?? [],
                            'texture' => $this->findTexture($namespace, $itemId, $itemData),
                            'model' => $this->findModel($namespace, $itemId, $itemData),
                            'raw' => $itemData
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to parse items.yml: " . $e->getMessage());
        }
    }

    private function findResourcePack(): void
    {
        $possiblePaths = [
            $this->packPath . '/resourcepack',
            $this->packPath . '/resource_pack',
            $this->packPath . '/rp',
            $this->packPath . '/contents/resourcepack',
        ];

        foreach ($possiblePaths as $path) {
            if (is_dir($path)) {
                $this->resourcePackPath = $path;
                return;
            }
        }

        // Search for assets folder (Java resource pack structure)
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->packPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isDir() && $file->getFilename() === 'assets') {
                $this->resourcePackPath = $file->getPath();
                return;
            }
        }
    }

    private function findTexture(string $namespace, string $itemId, array $itemData): ?string
    {
        // Check if texture is explicitly defined
        if (isset($itemData['texture']) && is_string($itemData['texture'])) {
            return $itemData['texture'];
        }

        if (isset($itemData['resource']['texture']) && is_string($itemData['resource']['texture'])) {
            return $itemData['resource']['texture'];
        }

        // Default texture path for ItemsAdder
        if ($this->resourcePackPath) {
            $texturePaths = [
                $this->resourcePackPath . '/assets/' . $namespace . '/textures/item/' . $itemId . '.png',
                $this->resourcePackPath . '/assets/' . $namespace . '/textures/items/' . $itemId . '.png',
                $this->resourcePackPath . '/assets/itemsadder/textures/item/' . $itemId . '.png',
                $this->resourcePackPath . '/assets/itemsadder/textures/items/' . $itemId . '.png',
            ];

            foreach ($texturePaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    private function findModel(string $namespace, string $itemId, array $itemData): ?string
    {
        if (isset($itemData['model']) && is_string($itemData['model'])) {
            return $itemData['model'];
        }

        if (isset($itemData['resource']['model']) && is_string($itemData['resource']['model'])) {
            $modelPath = $itemData['resource']['model'];
            // If it's a relative path, try to resolve it
            if ($this->resourcePackPath && !file_exists($modelPath)) {
                $fullPath = $this->resourcePackPath . '/assets/' . $namespace . '/models/item/' . $modelPath;
                if (file_exists($fullPath)) {
                    return $fullPath;
                }
            }
            return $modelPath;
        }

        if ($this->resourcePackPath) {
            $modelPaths = [
                $this->resourcePackPath . '/assets/' . $namespace . '/models/item/' . $itemId . '.json',
                $this->resourcePackPath . '/assets/itemsadder/models/item/' . $itemId . '.json',
            ];

            foreach ($modelPaths as $path) {
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        return null;
    }

    private function extractZip(string $zipPath, string $destination): void
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) === true) {
            $zip->extractTo($destination);
            $zip->close();
        } else {
            throw new \RuntimeException("Failed to extract ZIP file: {$zipPath}");
        }
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getResourcePackPath(): ?string
    {
        return $this->resourcePackPath;
    }
}

