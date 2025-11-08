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
            // Find resourcepack folder first (needed for both items.yml and fallback detection)
            $this->findResourcePack();

            // Find items.yml file
            $itemsYmlPath = $this->findItemsYml();
            if ($itemsYmlPath) {
                $this->parseItemsYml($itemsYmlPath);
            } else {
                // If no items.yml found, try to detect items from resource pack structure
                $this->detectItemsFromResourcePack();
            }

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
        // First check if the input path itself is a resource pack (contains assets folder)
        if (is_dir($this->packPath . '/assets')) {
            $this->resourcePackPath = $this->packPath;
            return;
        }

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
        // Use RecursiveDirectoryIterator to find assets folder at any depth
        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->packPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isDir() && $file->getFilename() === 'assets') {
                    // Check if this assets folder contains namespace folders (indicating it's a resource pack)
                    $namespaceIterator = new \DirectoryIterator($file->getPathname());
                    $hasNamespaces = false;
                    foreach ($namespaceIterator as $nsItem) {
                        if ($nsItem->isDir() && !$nsItem->isDot()) {
                            $hasNamespaces = true;
                            break;
                        }
                    }
                    
                    if ($hasNamespaces) {
                        $this->resourcePackPath = $file->getPath();
                        return;
                    }
                }
            }
        } catch (\Exception $e) {
            // Ignore errors during directory traversal
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

    /**
     * Detect items from resource pack structure when items.yml is not available
     * Scans textures/item/ and textures/items/ folders to find items
     */
    private function detectItemsFromResourcePack(): void
    {
        if (!$this->resourcePackPath) {
            return;
        }

        $assetsPath = $this->resourcePackPath . '/assets';
        if (!is_dir($assetsPath)) {
            return;
        }

        // Scan all namespaces in the assets folder
        $iterator = new \DirectoryIterator($assetsPath);
        
        foreach ($iterator as $namespaceDir) {
            if (!$namespaceDir->isDir() || $namespaceDir->isDot()) {
                continue;
            }

            $namespace = $namespaceDir->getFilename();
            
            // Skip minecraft namespace (vanilla items)
            if ($namespace === 'minecraft') {
                continue;
            }

            // Look for textures/item/ and textures/items/ folders
            $texturePaths = [
                $namespaceDir->getPathname() . '/textures/item',
                $namespaceDir->getPathname() . '/textures/items',
            ];

            foreach ($texturePaths as $texturePath) {
                if (!is_dir($texturePath)) {
                    continue;
                }

                // Scan for texture files
                $textureIterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($texturePath, \RecursiveDirectoryIterator::SKIP_DOTS)
                );

                foreach ($textureIterator as $textureFile) {
                    if (!$textureFile->isFile()) {
                        continue;
                    }

                    $ext = strtolower($textureFile->getExtension());
                    if (!in_array($ext, ['png', 'tga'])) {
                        continue;
                    }

                    // Get relative path from texture folder
                    $relativePath = str_replace($texturePath . DIRECTORY_SEPARATOR, '', $textureFile->getPathname());
                    $relativePath = str_replace('\\', '/', $relativePath);
                    
                    // Remove extension to get item ID
                    $itemId = pathinfo($relativePath, PATHINFO_FILENAME);
                    
                    // Handle subdirectories - use the full path as item ID
                    $subDir = dirname($relativePath);
                    if ($subDir !== '.') {
                        $itemId = str_replace('/', '_', $subDir) . '_' . $itemId;
                    }

                    $fullId = $namespace . ':' . $itemId;

                    // Skip if we already have this item
                    if (isset($this->items[$fullId])) {
                        continue;
                    }

                    // Try to find model file
                    $modelPath = $this->findModelForItem($namespace, $itemId, $textureFile->getPathname());

                    // Try to extract custom model data from model file
                    $customModelData = $this->extractCustomModelData($modelPath);

                    // Create item entry
                    $this->items[$fullId] = [
                        'namespace' => $namespace,
                        'id' => $itemId,
                        'fullId' => $fullId,
                        'displayName' => ucfirst(str_replace('_', ' ', $itemId)),
                        'resource' => null,
                        'material' => 'DIAMOND', // Default material
                        'customModelData' => $customModelData,
                        'durability' => null,
                        'maxStackSize' => 64,
                        'lore' => [],
                        'enchantments' => [],
                        'texture' => $textureFile->getPathname(),
                        'model' => $modelPath,
                        'raw' => []
                    ];
                }
            }
        }
    }

    /**
     * Find model file for an item
     */
    private function findModelForItem(string $namespace, string $itemId, string $texturePath): ?string
    {
        if (!$this->resourcePackPath) {
            return null;
        }

        // Try to find model in various locations
        $basePath = $this->resourcePackPath . '/assets/' . $namespace . '/models';
        
        // Remove extension and subdirectory info from item ID for model lookup
        $modelId = $itemId;
        if (strpos($modelId, '_') !== false) {
            $parts = explode('_', $modelId);
            $modelId = end($parts);
        }

        $modelPaths = [
            $basePath . '/item/' . $modelId . '.json',
            $basePath . '/items/' . $modelId . '.json',
            $basePath . '/item/ia_auto/' . $modelId . '.json',
        ];

        // Also try to find model based on texture path structure
        $textureRelative = str_replace($this->resourcePackPath . '/assets/' . $namespace . '/textures/', '', $texturePath);
        $textureRelative = str_replace(['item/', 'items/'], '', $textureRelative);
        $textureRelative = dirname($textureRelative);
        
        if ($textureRelative !== '.') {
            $modelPaths[] = $basePath . '/item/' . str_replace('/', '/', $textureRelative) . '/' . pathinfo($texturePath, PATHINFO_FILENAME) . '.json';
        }

        foreach ($modelPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Extract custom model data from model file if it references a parent with predicate
     */
    private function extractCustomModelData(?string $modelPath): ?int
    {
        if (!$modelPath || !file_exists($modelPath)) {
            return null;
        }

        try {
            $modelContent = file_get_contents($modelPath);
            $modelData = json_decode($modelContent, true);

            if (!$modelData) {
                return null;
            }

            // ItemsAdder models often use predicates with custom_model_data
            if (isset($modelData['overrides'])) {
                foreach ($modelData['overrides'] as $override) {
                    if (isset($override['predicate']['custom_model_data'])) {
                        return (int)$override['predicate']['custom_model_data'];
                    }
                }
            }

            // Try to extract from parent model name if it contains a number
            if (isset($modelData['parent'])) {
                $parent = $modelData['parent'];
                if (preg_match('/(\d+)/', $parent, $matches)) {
                    return (int)$matches[1];
                }
            }
        } catch (\Exception $e) {
            // Ignore errors in model parsing
        }

        return null;
    }
}

