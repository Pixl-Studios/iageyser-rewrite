<?php

namespace IAGeyser\Converter;

use Symfony\Component\Filesystem\Filesystem;

class ResourcePackConverter
{
    private Filesystem $filesystem;
    private string $javaPackPath;
    private string $outputPath;
    private array $items;
    private array $blocks;
    private array $itemTextureMap = [];
    private array $terrainTextureMap = [];

    public function __construct(string $javaPackPath, string $outputPath, array $items, array $blocks = [])
    {
        $this->filesystem = new Filesystem();
        $this->javaPackPath = $javaPackPath;
        $this->outputPath = $outputPath;
        $this->items = $items;
        $this->blocks = $blocks;
    }

    public function convert(): array
    {
        // Create output directory structure
        $this->filesystem->mkdir($this->outputPath);
        $this->filesystem->mkdir($this->outputPath . '/textures');
        $this->filesystem->mkdir($this->outputPath . '/models');
        $this->filesystem->mkdir($this->outputPath . '/models/blocks');
        $this->filesystem->mkdir($this->outputPath . '/attachables');
        $this->filesystem->mkdir($this->outputPath . '/animations');
        $this->filesystem->mkdir($this->outputPath . '/animation_controllers');

        // Create manifest.json for Bedrock resource pack
        $this->createManifest();

        // Copy and convert textures (preserving namespace structure)
        $this->copyTextures();

        // Generate item_texture.json and terrain_texture.json
        $this->generateTextureMappings();

        // Convert models and create attachables
        $this->convertModels();
        
        // Return texture maps for use in mappings generation
        return [
            'itemTextureMap' => $this->itemTextureMap,
            'terrainTextureMap' => $this->terrainTextureMap
        ];
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
                'min_engine_version' => [1, 20, 0]
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

    private function copyTextures(): void
    {
        if (!is_dir($this->javaPackPath)) {
            return;
        }

        $assetsPath = $this->javaPackPath . '/assets';
        if (!is_dir($assetsPath)) {
            return;
        }

        // Copy all textures preserving namespace structure
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($assetsPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $itemPath = $item->getPathname();
            $relativePath = str_replace($assetsPath . DIRECTORY_SEPARATOR, '', $itemPath);
            $relativePath = str_replace('\\', '/', $relativePath);

            // Only process texture files
            if ($item->isFile()) {
                $ext = strtolower($item->getExtension());
                if (in_array($ext, ['png', 'tga'])) {
                    // Skip .mcmeta files
                    if (pathinfo($item->getFilename(), PATHINFO_EXTENSION) === 'mcmeta') {
                        continue;
                    }

                    // Check if it's in a textures folder
                    if (strpos($relativePath, '/textures/') !== false) {
                        // Extract namespace and texture path
                        $parts = explode('/textures/', $relativePath, 2);
                        if (count($parts) === 2) {
                            $namespace = explode('/', $parts[0])[0];
                            $texturePath = $parts[1];
                            
                            // Create destination path preserving structure
                            $destPath = $this->outputPath . '/textures/' . $namespace . '/' . $texturePath;
                            $destDir = dirname($destPath);
                            $this->filesystem->mkdir($destDir);
                            $this->filesystem->copy($itemPath, $destPath, true);
                        }
                    }
                }
            }
        }

        // Also copy font, materials, sounds if they exist
        $this->copyAdditionalAssets();
    }

    private function copyAdditionalAssets(): void
    {
        // Copy font folder if it exists
        $fontSource = $this->javaPackPath . '/assets';
        if (is_dir($fontSource)) {
            // Look for font textures in the pack
        $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($fontSource, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
                if ($item->isFile() && strpos($item->getPathname(), 'font') !== false && strtolower($item->getExtension()) === 'png') {
                    $relativePath = str_replace($fontSource . DIRECTORY_SEPARATOR, '', $item->getPathname());
                    $relativePath = str_replace('\\', '/', $relativePath);
                    
                    // Extract just the font part
                    if (preg_match('#font/([^/]+\.png)$#', $relativePath, $matches)) {
                        $fontDir = $this->outputPath . '/font';
                        $this->filesystem->mkdir($fontDir);
                        $this->filesystem->copy($item->getPathname(), $fontDir . '/' . $matches[1], true);
                    }
                }
            }
        }

        // Copy materials if they exist (Bedrock format)
        // Copy sounds if they exist
    }

    private function generateTextureMappings(): void
    {
        // Generate item_texture.json
        $this->generateItemTextureJson();

        // Generate terrain_texture.json
        $this->generateTerrainTextureJson();
    }

    private function generateItemTextureJson(): void
    {
        $textureData = [];

        // First, scan all textures in the output directory to build complete mapping
        // This ensures we get correct relative paths
        $texturesDir = $this->outputPath . '/textures';
        if (is_dir($texturesDir)) {
            $this->scanTexturesForMapping($texturesDir, $textureData, 'item');
        }

            // Then, map items to texture IDs based on the scanned textures
            // Try to match items to textures by filename or path
            foreach ($this->items as $fullId => $item) {
                if (!$item['texture']) {
                    continue;
                }

                // Try to find the texture in the scanned data by matching filename or path
                $filename = basename($item['texture'], '.png');
                $textureId = null;
                
                // Get the expected texture path (relative from textures/ folder)
                $expectedTexturePath = $this->getTexturePathForMapping($item['texture']);
                
                if (!$expectedTexturePath) {
                    continue;
                }
                
                // Remove "textures/" prefix to get relative path for ID generation
                $relativeTexturePath = preg_replace('/^textures\//', '', $expectedTexturePath);
                
                // Generate texture ID from the texture path (this ensures consistency)
                $textureId = $this->generateTextureIdFromPath($relativeTexturePath);
                
                // Make sure this texture is in the texture data
                if (!isset($textureData[$textureId])) {
                    $textureData[$textureId] = [
                        'textures' => $expectedTexturePath
                    ];
                }
                
                $this->itemTextureMap[$fullId] = $textureId;
            }

        $itemTextureJson = [
            'resource_pack_name' => 'geyser_custom',
            'texture_name' => 'atlas.items',
            'texture_data' => $textureData
        ];

        file_put_contents(
            $this->outputPath . '/textures/item_texture.json',
            json_encode($itemTextureJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function generateTerrainTextureJson(): void
    {
        $textureData = [];

        // First, scan all block textures in the output directory
        $texturesDir = $this->outputPath . '/textures';
        if (is_dir($texturesDir)) {
            $this->scanTexturesForMapping($texturesDir, $textureData, 'block');
        }

        // Then process blocks to map them to texture IDs
        foreach ($this->blocks as $fullId => $block) {
            if (!$block['texture']) {
                continue;
            }

            // Try to find the texture in the scanned data
            $expectedTexturePath = $this->getTexturePathForMapping($block['texture']);
            $textureId = null;
            
            // Search through texture data to find matching texture
            foreach ($textureData as $id => $data) {
                $texturePathInData = $data['textures'];
                if ($expectedTexturePath && $texturePathInData === $expectedTexturePath) {
                    $textureId = $id;
                    break;
                }
            }
            
            // If not found, generate a new ID (block texture IDs don't include namespace, just hash with 't' prefix)
            if (!$textureId) {
                // Block texture IDs are shorter and don't include namespace prefix
                $texturePath = $expectedTexturePath ?: $this->getTexturePathForMapping($block['texture']);
                if ($texturePath) {
                    // Generate block texture ID: just 't' + hash (no namespace)
                    $textureId = 't' . substr(md5($texturePath), 0, 11);
                    if (!isset($textureData[$textureId])) {
                        $textureData[$textureId] = [
                            'textures' => $texturePath
                        ];
                    }
                }
            }
            
            $this->terrainTextureMap[$fullId] = $textureId;
        }

        $terrainTextureJson = [
            'resource_pack_name' => 'geyser_custom',
            'texture_name' => 'atlas.terrain',
            'padding' => 8,
            'num_mip_levels' => 4,
            'texture_data' => $textureData
        ];

        file_put_contents(
            $this->outputPath . '/textures/terrain_texture.json',
            json_encode($terrainTextureJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }

    private function scanTexturesForMapping(string $texturesDir, array &$textureData, string $type): void
    {
        // Get absolute normalized base path
        $basePath = realpath($texturesDir);
        if (!$basePath) {
            // If realpath fails, use the provided path and make it absolute
            $basePath = $texturesDir;
            if (!str_starts_with($basePath, '/') && !preg_match('/^[A-Z]:/', $basePath)) {
                // Relative path - make it absolute from current working directory
                $basePath = getcwd() . DIRECTORY_SEPARATOR . $basePath;
            }
        }
        // Normalize to forward slashes and ensure trailing slash
        $basePath = str_replace('\\', '/', $basePath);
        $basePath = rtrim($basePath, '/') . '/';
        $basePathLen = strlen($basePath);

        $directoryIterator = new \RecursiveDirectoryIterator($texturesDir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($directoryIterator);

        foreach ($iterator as $item) {
            if (!$item->isFile()) {
                continue;
            }

            $ext = strtolower($item->getExtension());
            if ($ext !== 'png') {
                continue;
            }

            // Get the full file path and normalize it
            $fileRealPath = $item->getRealPath();
            if (!$fileRealPath) {
                $fileRealPath = $item->getPathname();
            }
            $filePath = str_replace('\\', '/', $fileRealPath);
            
            // Calculate relative path from base directory
            // Ensure the file path starts with the base path
            if (strpos($filePath, $basePath) === 0) {
                $relativePath = substr($filePath, $basePathLen);
            } else {
                // Paths don't match - this shouldn't happen, but try case-insensitive or different normalization
                $filePathLower = strtolower($filePath);
                $basePathLower = strtolower($basePath);
                if (strpos($filePathLower, $basePathLower) === 0) {
                    $relativePath = substr($filePath, strlen($basePath));
                } else {
                    // Skip this file if we can't determine relative path
                    continue;
                }
            }
            
            // Remove .png extension
            $relativePath = preg_replace('/\.png$/i', '', $relativePath);

            // Determine if it's an item or block texture
            $isItem = strpos($relativePath, '/item/') !== false || strpos($relativePath, '/items/') !== false;
            $isBlock = strpos($relativePath, '/block/') !== false || strpos($relativePath, '/blocks/') !== false;
            
            // Also check for entity textures (like _iainternal/entity) - these are items
            $isEntity = strpos($relativePath, '/entity/') !== false;
            // Check for GUI/HUD/icons - these can be items too
            $isGui = strpos($relativePath, '/gui/') !== false || strpos($relativePath, '/hud/') !== false || strpos($relativePath, '/icons/') !== false;
            
            // Include _iainternal namespace items (not blocks)
            $isInternal = strpos($relativePath, '_iainternal/') === 0 || strpos($relativePath, '_iainternal/') !== false;

            if ($type === 'item' && ($isItem || $isEntity || ($isGui && $isInternal) || ($isInternal && !$isBlock))) {
            } elseif ($type === 'block' && $isBlock && !$isInternal) {
            } else {
                continue;
            }
            
            if (($type === 'item' && ($isItem || $isEntity || ($isGui && $isInternal) || ($isInternal && !$isBlock))) || 
                ($type === 'block' && $isBlock && !$isInternal)) {
                // Generate texture ID
                $textureId = $this->generateTextureIdFromPath($relativePath);
                
                // Check if we already have this texture ID
                if (!isset($textureData[$textureId])) {
                    $texturePath = 'textures/' . $relativePath;
                    $textureData[$textureId] = [
                        'textures' => $texturePath
                    ];
                }
            }
        }
    }

    private function generateTextureId(string $fullId, string $prefix = ''): string
    {
        // Generate a short hash-based ID similar to ItemsAdder's format
        $hash = substr(md5($fullId . $prefix), 0, 11);
        
        // Extract namespace and id
        $parts = explode(':', $fullId);
        $namespace = $parts[0] ?? 'unknown';
        $id = $parts[1] ?? $fullId;
        
        // Create ID in format: namespace_hash (similar to ItemsAdder)
        return $namespace . '_f' . $hash;
    }

    private function generateTextureIdFromPath(string $path): string
    {
        // Generate ID from texture path
        // The path should be relative from textures/ folder (e.g., "summer/ia_auto_gen/berrysnowcone")
        // Remove .png extension if present
        $path = preg_replace('/\.png$/i', '', $path);
        
        // Remove "textures/" prefix if present
        $path = preg_replace('/^textures\//', '', $path);
        
        // Normalize path: replace "item/" or "items/" with "ia_auto_gen/" for consistency
        // This matches ItemsAdder's Geyser conversion format
        $path = preg_replace('#/(item|items)/#', '/ia_auto_gen/', $path);
        
        // Also handle cases where the texture is directly in a subdirectory
        // e.g., "namespace/item/texture" -> "namespace/ia_auto_gen/texture"
        $path = str_replace('/item/', '/ia_auto_gen/', $path);
        $path = str_replace('/items/', '/ia_auto_gen/', $path);
        
        // Generate hash from the normalized path
        $hash = substr(md5($path), 0, 11);
        
        // Extract namespace (first part of path)
        $parts = explode('/', $path);
        $namespace = $parts[0] ?? 'unknown';
        
        return $namespace . '_f' . $hash;
    }

    private function getTexturePathForMapping(?string $texturePath): ?string
    {
        if (!$texturePath) {
            return null;
        }

        // Convert absolute path to relative path from output textures folder
        $texturesDir = $this->outputPath . '/textures/';
        
        // Check if it's already in the output directory
        if (strpos($texturePath, $texturesDir) === 0) {
            $relativePath = str_replace($texturesDir, '', $texturePath);
            $relativePath = str_replace('\\', '/', $relativePath);
            $relativePath = str_replace('.png', '', $relativePath);
            return 'textures/' . $relativePath;
        }

        // If it's the source texture path, find where we copied it to
        $filename = basename($texturePath);
        $filenameWithoutExt = basename($texturePath, '.png');
        
        // Search for the texture in output textures directory
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->outputPath . '/textures', \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && basename($file->getPathname(), '.png') === $filenameWithoutExt) {
                $relativePath = str_replace($this->outputPath . '/textures/', '', $file->getPathname());
                $relativePath = str_replace('\\', '/', $relativePath);
                $relativePath = str_replace('.png', '', $relativePath);
                return 'textures/' . $relativePath;
            }
        }

        // If not found, try to construct path from item data
        // Extract namespace and path from source texture
        if (strpos($texturePath, '/assets/') !== false) {
            $parts = explode('/assets/', $texturePath, 2);
            if (count($parts) === 2) {
                $afterAssets = $parts[1];
                $pathParts = explode('/textures/', $afterAssets, 2);
                if (count($pathParts) === 2) {
                    $namespace = explode('/', $pathParts[0])[0];
                    $textureRelative = $pathParts[1];
                    $textureRelative = str_replace('.png', '', $textureRelative);
                    return 'textures/' . $namespace . '/' . $textureRelative;
                }
            }
        }

        return null;
    }

    private function convertModels(): void
    {
        // This is a complex conversion - Java models to Bedrock geometry
        // For now, we'll copy the structure and create basic attachables
        // Full model conversion would require parsing Java JSON models and converting to Bedrock format
        
        // Create attachables for items
        foreach ($this->items as $fullId => $item) {
            if ($item['model']) {
                $this->createAttachable($item, $fullId);
            }
        }

        // Convert block models
        foreach ($this->blocks as $fullId => $block) {
            if ($block['model']) {
                $this->convertBlockModel($block, $fullId);
            }
        }
    }

    private function createAttachable(array $item, string $fullId): void
    {
        // Create attachable file for item
        $namespace = $item['namespace'];
        $itemId = $item['id'];
        $textureId = $this->itemTextureMap[$fullId] ?? $this->generateTextureId($fullId);
        
        // Generate geometry ID
        $geometryId = $this->generateGeometryId($fullId);
        
        // Create attachable structure
        $attachable = [
            'format_version' => '1.10.0',
            'minecraft:attachable' => [
                'description' => [
                    'identifier' => 'geyser_custom:' . str_replace(':', '_', $fullId),
                    'materials' => [
                        'default' => 'entity_alphatest_one_sided',
                        'enchanted' => 'entity_alphatest_one_sided_glint'
                    ],
                    'textures' => [
                        'default' => 'textures/furnace_items/' . substr($textureId, -10), // Simplified
                        'enchanted' => 'textures/misc/enchanted_item_glint'
                    ],
                    'geometry' => [
                        'default' => 'geometry.furnace.' . $geometryId
                    ],
                    'scripts' => [
                        'pre_animation' => [
                            'v.main_hand = c.item_slot == \'main_hand\';',
                            'v.off_hand = c.item_slot == \'off_hand\';',
                            'v.head = c.item_slot == \'head\';'
                        ],
                        'animate' => [
                            ['thirdperson_main_hand' => 'v.main_hand && !c.is_first_person'],
                            ['thirdperson_off_hand' => 'v.off_hand && !c.is_first_person'],
                            ['thirdperson_head' => 'v.head && !c.is_first_person'],
                            ['firstperson_main_hand' => 'v.main_hand && c.is_first_person'],
                            ['firstperson_off_hand' => 'v.off_hand && c.is_first_person'],
                            ['firstperson_head' => 'c.is_first_person && v.head']
                        ]
                    ],
                    'animations' => [
                        'thirdperson_main_hand' => 'animation.furnace.' . $geometryId . '.thirdperson_main_hand',
                        'thirdperson_off_hand' => 'animation.furnace.' . $geometryId . '.thirdperson_off_hand',
                        'thirdperson_head' => 'animation.furnace.' . $geometryId . '.head',
                        'firstperson_main_hand' => 'animation.furnace.' . $geometryId . '.firstperson_main_hand',
                        'firstperson_off_hand' => 'animation.furnace.' . $geometryId . '.firstperson_off_hand',
                        'firstperson_head' => 'animation.furnace.disable'
                    ],
                    'render_controllers' => ['controller.render.item_default']
                ]
            ]
        ];

        // Determine attachable path based on namespace and item structure
        $attachableDir = $this->outputPath . '/attachables/' . $namespace;
        $this->filesystem->mkdir($attachableDir);
        
        // Try to preserve subdirectory structure if it exists in the model path
        $modelPath = $item['model'];
        $subPath = '';
        if (strpos($modelPath, '/item/') !== false) {
            $subPath = '/item';
        } elseif (strpos($modelPath, '/items/') !== false) {
            $subPath = '/items';
        }

        if ($subPath) {
            $attachableDir .= $subPath;
            $this->filesystem->mkdir($attachableDir);
        }

        $attachableFile = $attachableDir . '/' . $itemId . '.json';
        file_put_contents($attachableFile, json_encode($attachable, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function convertBlockModel(array $block, string $fullId): void
    {
        // Convert Java block model to Bedrock geometry
        // This is complex - for now, create a basic structure
        $namespace = $block['namespace'];
        $blockId = $block['id'];
        $geometryId = $this->generateGeometryId($fullId);

        // Read Java model if it exists
        $javaModel = null;
        if ($block['model'] && file_exists($block['model'])) {
            $javaModelContent = file_get_contents($block['model']);
            $javaModel = json_decode($javaModelContent, true);
        }

        // Create basic Bedrock geometry
        $bedrockGeometry = [
            'format_version' => '1.21.0',
            'minecraft:geometry' => [
                [
                    'description' => [
                        'identifier' => 'geometry.furnace.' . str_replace(':', '_', $fullId),
                        'texture_width' => 16,
                        'texture_height' => 16,
                        'visible_bounds_width' => 5,
                        'visible_bounds_height' => 5,
                        'visible_bounds_offset' => [0, 0.75, 0]
                    ],
                    'bones' => [
                        [
                            'name' => 'furnacemodel',
                            'pivot' => [0, 8, 0],
                            'binding' => 'c.item_slot == \'head\' ? \'head\' : q.item_slot_to_bone_name(c.item_slot)'
                        ],
                        [
                            'name' => 'furnacemodel_x',
                            'parent' => 'furnacemodel',
                            'pivot' => [0, 8, 0]
                        ],
                        [
                            'name' => 'furnacemodel_y',
                            'parent' => 'furnacemodel_x',
                            'pivot' => [0, 8, 0]
                        ],
                        [
                            'name' => 'furnacemodel_z',
                            'parent' => 'furnacemodel_y',
                            'pivot' => [0, 8, 0],
                            'cubes' => [
                                [
                                    'rotation' => [0, 0, 0],
                                    'size' => [16.0, 16.0, 16.0],
                                    'origin' => [-8, 0, -8],
                                    'pivot' => [8, 0, -8],
                                    'uv' => [
                                        'down' => ['uv' => [0, 0], 'uv_size' => [16, 16]],
                                        'up' => ['uv' => [0, 0], 'uv_size' => [16, 16]],
                                        'north' => ['uv' => [0, 0], 'uv_size' => [16, 16]],
                                        'south' => ['uv' => [0, 0], 'uv_size' => [16, 16]],
                                        'west' => ['uv' => [0, 0], 'uv_size' => [16, 16]],
                                        'east' => ['uv' => [0, 0], 'uv_size' => [16, 16]]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Determine model path
        $modelDir = $this->outputPath . '/models/blocks/' . $namespace;
        
        // Check if there's a subdirectory in the original model path
        if ($block['model']) {
            $modelPath = $block['model'];
            if (strpos($modelPath, '/ia_auto/') !== false || strpos($modelPath, '/ia_auto_gen/') !== false) {
                $modelDir .= '/ia_auto_gen';
            }
        }
        
        $this->filesystem->mkdir($modelDir);
        $modelFile = $modelDir . '/' . $blockId . '.json';
        file_put_contents($modelFile, json_encode($bedrockGeometry, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    private function generateGeometryId(string $fullId): string
    {
        // Generate geometry ID similar to ItemsAdder format
        $hash = substr(md5($fullId), 0, 11);
        $parts = explode(':', $fullId);
        $namespace = $parts[0] ?? 'unknown';
        $id = $parts[1] ?? $fullId;
        return str_replace(':', '_', $fullId) . '_f' . $hash;
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
