<?php

namespace IAGeyser\Generator;

class GeyserMappingsGenerator
{
    private array $items;
    private array $blocks;
    private array $itemTextureMap = []; // Maps fullId -> textureId from item_texture.json
    private array $blockTextureMap = []; // Maps fullId -> textureId from terrain_texture.json

    public function __construct(array $items, array $blocks = [], array $itemTextureMap = [], array $blockTextureMap = [])
    {
        $this->items = $items;
        $this->blocks = $blocks;
        $this->itemTextureMap = $itemTextureMap;
        $this->blockTextureMap = $blockTextureMap;
    }

    public function generateItemsJson(): string
    {
        // Group items by Java item ID (material)
        $groupedItems = [];
        
        foreach ($this->items as $fullId => $item) {
            $material = $item['material'] ?? 'DIAMOND';
            $javaItemId = $this->getJavaItemId($material);
            
            if (!isset($groupedItems[$javaItemId])) {
                $groupedItems[$javaItemId] = [];
            }
            
            // Get texture ID from map, or generate one
            $textureId = $this->itemTextureMap[$fullId] ?? $this->generateTextureId($fullId);
            
            // Get custom model data
            $customModelData = $item['customModelData'] ?? null;
            
            $groupedItems[$javaItemId][] = [
                'fullId' => $fullId,
                'textureId' => $textureId,
                'customModelData' => $customModelData,
                'item' => $item
            ];
        }
        
        // Sort items within each group by custom_model_data to ensure consistent ordering
        foreach ($groupedItems as $javaItemId => &$items) {
            usort($items, function($a, $b) {
                $cmdA = $a['customModelData'] ?? PHP_INT_MAX;
                $cmdB = $b['customModelData'] ?? PHP_INT_MAX;
                return $cmdA <=> $cmdB;
            });
        }
        
        // Build the final structure with CMD starting from 10000
        $result = [
            'format_version' => 1,
            'items' => []
        ];
        
        foreach ($groupedItems as $javaItemId => $items) {
            $resultItems = [];
            $cmdCounter = 10000;
            
            foreach ($items as $itemData) {
                $customModelData = $itemData['customModelData'];
                // If no CMD, use the counter; otherwise use the provided CMD
                // But ensure it's at least 10000
                if ($customModelData === null) {
                    $customModelData = $cmdCounter;
                } else {
                    $customModelData = max(10000, (int)$customModelData);
                }
                
                $resultItems[] = [
                    'name' => $itemData['textureId'],
                    'custom_model_data' => $customModelData,
                    'icon' => $itemData['textureId'],
                    'allow_offhand' => true
                ];
                
                $cmdCounter = max($cmdCounter, $customModelData) + 1;
            }
            
            if (!empty($resultItems)) {
                $result['items'][$javaItemId] = $resultItems;
            }
        }
        
        return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function generateBlocksJson(): string
    {
        // Group blocks by Java block ID (material)
        $groupedBlocks = [];
        
        foreach ($this->blocks as $fullId => $block) {
            $material = $block['material'] ?? 'NOTE_BLOCK';
            $javaBlockId = $this->getJavaBlockId($material);
            
            if (!isset($groupedBlocks[$javaBlockId])) {
                $groupedBlocks[$javaBlockId] = [];
            }
            
            // Get texture ID from map
            $textureId = $this->blockTextureMap[$fullId] ?? $this->generateTextureId($fullId, 'block');
            
            // Generate geometry ID
            $geometryId = $this->generateGeometryId($fullId);
            
            $groupedBlocks[$javaBlockId][] = [
                'fullId' => $fullId,
                'textureId' => $textureId,
                'geometryId' => $geometryId,
                'block' => $block
            ];
        }
        
        // Build the final structure
        $result = [
            'format_version' => 1,
            'blocks' => []
        ];
        
        foreach ($groupedBlocks as $javaBlockId => $blocks) {
            $stateOverrides = [];
            $stateCounter = 0;
            
            foreach ($blocks as $blockData) {
                $block = $blockData['block'];
                $textureId = $blockData['textureId'];
                $geometryId = $blockData['geometryId'];
                
                // Generate state override key
                // For note_block: instrument=basedrum,note=X,powered=false
                // For brown_mushroom_block: down=false,east=false,north=false,south=false,up=false,west=false
                $stateKey = $this->generateBlockStateKey($javaBlockId, $stateCounter, count($blocks));
                $stateCounter++;
                
                // Geometry ID should match texture ID for blocks
                $geometryIdForBlock = $textureId;
                
                $stateOverrides[$stateKey] = [
                    'name' => $textureId,
                    'geometry' => 'geometry.furnace.' . $geometryIdForBlock,
                    'material_instances' => [
                        '*' => [
                            'texture' => $textureId,
                            'render_method' => 'alpha_test'
                        ]
                    ]
                ];
            }
            
            if (!empty($stateOverrides)) {
                $result['blocks'][$javaBlockId] = [
                    'name' => str_replace('minecraft:', '', $javaBlockId),
                    'included_in_creative_inventory' => false,
                    'only_override_states' => true,
                    'place_air' => true,
                    'state_overrides' => $stateOverrides
                ];
            }
        }
        
        return json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    private function generateBlockStateKey(string $javaBlockId, int $counter, int $totalBlocks): string
    {
        if ($javaBlockId === 'minecraft:note_block') {
            // Note block states: instrument=basedrum,note=X,powered=false
            // Use different note values (15-25 are common)
            // Start from note 20 and go down, cycling through common values
            $notes = [20, 18, 16, 15, 17, 19, 21, 22, 23, 24];
            $note = $notes[$counter % count($notes)] ?? (15 + ($counter % 11));
            return "instrument=basedrum,note={$note},powered=false";
        } elseif ($javaBlockId === 'minecraft:brown_mushroom_block') {
            // Mushroom block states: down=false,east=false,north=false,south=false,up=false,west=false
            // Vary the sides systematically
            $states = [
                "down=false,east=false,north=false,south=false,up=false,west=false",
                "down=false,east=false,north=false,south=false,up=false,west=true",
                "down=false,east=true,north=false,south=false,up=false,west=false",
                "down=true,east=false,north=false,south=false,up=false,west=false",
                "down=false,east=false,north=true,south=false,up=false,west=false",
                "down=false,east=false,north=false,south=true,up=false,west=false",
                "down=false,east=false,north=false,south=false,up=true,west=false"
            ];
            return $states[$counter % count($states)] ?? $states[0];
        } else {
            // Default: use a simple counter-based state
            return "custom_state={$counter}";
        }
    }

    private function generateGeometryId(string $fullId): string
    {
        // Generate geometry ID similar to ItemsAdder format
        $hash = substr(md5($fullId), 0, 11);
        return str_replace(':', '_', $fullId) . '_f' . $hash;
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

    private function getJavaItemId(string $material): string
    {
        // Convert material name to Java item identifier format
        // e.g., "DIAMOND" -> "minecraft:diamond"
        $materialLower = strtolower($material);
        return 'minecraft:' . $materialLower;
    }

    private function getJavaBlockId(string $material): string
    {
        // Convert material name to Java block identifier format
        $materialLower = strtolower($material);
        return 'minecraft:' . $materialLower;
    }
}
