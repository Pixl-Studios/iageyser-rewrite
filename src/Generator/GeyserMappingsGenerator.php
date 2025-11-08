<?php

namespace IAGeyser\Generator;

class GeyserMappingsGenerator
{
    private array $items;
    private int $nextItemId = 1000; // Start from a high number to avoid conflicts

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function generate(): array
    {
        $mappings = [
            'items' => []
        ];

        foreach ($this->items as $fullId => $item) {
            $geyserItem = $this->createGeyserItem($item, $fullId);
            if ($geyserItem) {
                $mappings['items'][] = $geyserItem;
            }
        }

        return $mappings;
    }

    private function createGeyserItem(array $item, string $fullId): ?array
    {
        // Geyser custom item structure
        // Maps Java Edition items with custom model data to Bedrock Edition items
        $material = $item['material'] ?? 'DIAMOND';
        $javaItemId = $this->getJavaItemId($material);
        
        $geyserItem = [
            'name' => $item['fullId'], // ItemsAdder item identifier (e.g., "itemsadder:custom_item")
            'java_id' => $javaItemId, // Java item identifier (e.g., "minecraft:diamond")
            'bedrock_id' => $this->getBedrockItemId($material), // Bedrock item ID
            'display_name' => $item['displayName'],
        ];

        // Add custom model data if present (required for ItemsAdder items)
        if (isset($item['customModelData']) && $item['customModelData'] !== null) {
            $geyserItem['custom_model_data'] = (int)$item['customModelData'];
        } else {
            // If no custom model data, we might need to generate one or skip this item
            // For now, we'll still include it but note that it might not work correctly
            $geyserItem['custom_model_data'] = $this->generateCustomModelData($fullId);
        }

        // Add icon path if texture exists
        $iconPath = $this->getIconPath($item);
        if ($iconPath) {
            $geyserItem['icon'] = $iconPath;
        }

        // Add optional properties
        if (isset($item['maxStackSize']) && $item['maxStackSize'] !== 64) {
            $geyserItem['stack_size'] = (int)$item['maxStackSize'];
        }

        if (!empty($item['enchantments'])) {
            $geyserItem['enchantable'] = true;
        }

        // Add components for Bedrock-specific properties
        $components = $this->getComponents($item, $material);
        if (!empty($components)) {
            $geyserItem['components'] = $components;
        }

        return $geyserItem;
    }

    private function getJavaItemId(string $material): string
    {
        // Convert material name to Java item identifier format
        // e.g., "DIAMOND" -> "minecraft:diamond"
        $materialLower = strtolower($material);
        return 'minecraft:' . $materialLower;
    }

    private function generateCustomModelData(string $fullId): int
    {
        // Generate a unique custom model data based on the item's full ID
        // This ensures each ItemsAdder item gets a unique custom model data value
        return abs(crc32($fullId)) % 1000000; // Use CRC32 hash to generate a number
    }

    private function getIconPath(array $item): ?string
    {
        if (!$item['texture']) {
            return null;
        }

        // Return relative path from resource pack root
        // Geyser expects: textures/items/filename.png
        // Use item ID to ensure consistency with resource pack converter
        $itemId = $item['id'] ?? basename($item['texture'], '.' . pathinfo($item['texture'], PATHINFO_EXTENSION));
        $filename = $itemId . '.png';
        
        return 'textures/items/' . $filename;
    }

    private function getBedrockItemId(string $material): int
    {
        // Map Java Edition materials to Bedrock item IDs
        $materialMap = [
            'DIAMOND' => 264,
            'IRON_INGOT' => 265,
            'GOLD_INGOT' => 266,
            'STICK' => 280,
            'BOWL' => 281,
            'STRING' => 287,
            'FEATHER' => 288,
            'GUNPOWDER' => 289,
            'WOODEN_HOE' => 290,
            'STONE_HOE' => 291,
            'IRON_HOE' => 292,
            'DIAMOND_HOE' => 293,
            'GOLDEN_HOE' => 294,
            'WHEAT_SEEDS' => 295,
            'WHEAT' => 296,
            'BREAD' => 297,
            'LEATHER_HELMET' => 298,
            'LEATHER_CHESTPLATE' => 299,
            'LEATHER_LEGGINGS' => 300,
            'LEATHER_BOOTS' => 301,
            'CHAINMAIL_HELMET' => 302,
            'CHAINMAIL_CHESTPLATE' => 303,
            'CHAINMAIL_LEGGINGS' => 304,
            'CHAINMAIL_BOOTS' => 305,
            'IRON_HELMET' => 306,
            'IRON_CHESTPLATE' => 307,
            'IRON_LEGGINGS' => 308,
            'IRON_BOOTS' => 309,
            'DIAMOND_HELMET' => 310,
            'DIAMOND_CHESTPLATE' => 311,
            'DIAMOND_LEGGINGS' => 312,
            'DIAMOND_BOOTS' => 313,
            'GOLDEN_HELMET' => 314,
            'GOLDEN_CHESTPLATE' => 315,
            'GOLDEN_LEGGINGS' => 316,
            'GOLDEN_BOOTS' => 317,
            'FLINT' => 318,
            'PORKCHOP' => 319,
            'COOKED_PORKCHOP' => 320,
            'PAINTING' => 321,
            'GOLDEN_APPLE' => 322,
            'SIGN' => 323,
            'WOODEN_DOOR' => 324,
            'BUCKET' => 325,
            'MINECART' => 328,
            'SADDLE' => 329,
            'IRON_DOOR' => 330,
            'REDSTONE' => 331,
            'SNOWBALL' => 332,
            'BOAT' => 333,
            'LEATHER' => 334,
            'KELP' => 335,
            'BRICK' => 336,
            'CLAY_BALL' => 337,
            'SUGAR_CANE' => 338,
            'PAPER' => 339,
            'BOOK' => 340,
            'SLIME_BALL' => 341,
            'CHEST_MINECART' => 342,
            'EGG' => 344,
            'COMPASS' => 345,
            'FISHING_ROD' => 346,
            'CLOCK' => 347,
            'GLOWSTONE_DUST' => 348,
            'FISH' => 349,
            'COOKED_FISH' => 350,
            'DYE' => 351,
            'BONE' => 352,
            'SUGAR' => 353,
            'CAKE' => 354,
            'BED' => 355,
            'REPEATER' => 356,
            'COOKIE' => 357,
            'MAP' => 358,
            'SHEARS' => 359,
            'MELON' => 360,
            'PUMPKIN_SEEDS' => 361,
            'MELON_SEEDS' => 362,
            'BEEF' => 363,
            'COOKED_BEEF' => 364,
            'CHICKEN' => 365,
            'COOKED_CHICKEN' => 366,
            'ROTTEN_FLESH' => 367,
            'ENDER_PEARL' => 368,
            'BLAZE_ROD' => 369,
            'GHAST_TEAR' => 370,
            'GOLD_NUGGET' => 371,
            'NETHER_WART' => 372,
            'POTION' => 373,
            'GLASS_BOTTLE' => 374,
            'SPIDER_EYE' => 375,
            'FERMENTED_SPIDER_EYE' => 376,
            'BLAZE_POWDER' => 377,
            'MAGMA_CREAM' => 378,
            'BREWING_STAND' => 379,
            'CAULDRON' => 380,
            'ENDER_EYE' => 381,
            'GLISTERING_MELON_SLICE' => 382,
            'BAT_SPAWN_EGG' => 383,
            'BEE_SPAWN_EGG' => 440,
            'BLAZE_SPAWN_EGG' => 431,
            'CAT_SPAWN_EGG' => 490,
            'CAVE_SPIDER_SPAWN_EGG' => 458,
            'CHICKEN_SPAWN_EGG' => 437,
            'COD_SPAWN_EGG' => 477,
            'COW_SPAWN_EGG' => 439,
            'CREEPER_SPAWN_EGG' => 441,
            'DOLPHIN_SPAWN_EGG' => 481,
            'DONKEY_SPAWN_EGG' => 467,
            'DROWNED_SPAWN_EGG' => 486,
            'ELDER_GUARDIAN_SPAWN_EGG' => 461,
            'ENDERMAN_SPAWN_EGG' => 442,
            'ENDERMITE_SPAWN_EGG' => 460,
            'EVOKER_SPAWN_EGG' => 474,
            'FOX_SPAWN_EGG' => 489,
            'GHAST_SPAWN_EGG' => 443,
            'GUARDIAN_SPAWN_EGG' => 459,
            'HOGLIN_SPAWN_EGG' => 495,
            'HORSE_SPAWN_EGG' => 456,
            'HUSK_SPAWN_EGG' => 463,
            'LLAMA_SPAWN_EGG' => 470,
            'MAGMA_CUBE_SPAWN_EGG' => 445,
            'MOOSHROOM_SPAWN_EGG' => 449,
            'MULE_SPAWN_EGG' => 468,
            'OCELOT_SPAWN_EGG' => 453,
            'PANDA_SPAWN_EGG' => 485,
            'PARROT_SPAWN_EGG' => 479,
            'PHANTOM_SPAWN_EGG' => 488,
            'PIG_SPAWN_EGG' => 438,
            'PIGLIN_SPAWN_EGG' => 494,
            'PIGLIN_BRUTE_SPAWN_EGG' => 496,
            'PILLAGER_SPAWN_EGG' => 493,
            'POLAR_BEAR_SPAWN_EGG' => 465,
            'PUFFERFISH_SPAWN_EGG' => 478,
            'RABBIT_SPAWN_EGG' => 459,
            'RAVAGER_SPAWN_EGG' => 492,
            'SALMON_SPAWN_EGG' => 476,
            'SHEEP_SPAWN_EGG' => 436,
            'SHULKER_SPAWN_EGG' => 469,
            'SILVERFISH_SPAWN_EGG' => 444,
            'SKELETON_SPAWN_EGG' => 446,
            'SKELETON_HORSE_SPAWN_EGG' => 464,
            'SLIME_SPAWN_EGG' => 447,
            'SPIDER_SPAWN_EGG' => 448,
            'SQUID_SPAWN_EGG' => 450,
            'STRAY_SPAWN_EGG' => 462,
            'STRIDER_SPAWN_EGG' => 497,
            'TRADER_LLAMA_SPAWN_EGG' => 471,
            'TROPICAL_FISH_SPAWN_EGG' => 475,
            'TURTLE_SPAWN_EGG' => 480,
            'VEX_SPAWN_EGG' => 473,
            'VILLAGER_SPAWN_EGG' => 451,
            'VINDICATOR_SPAWN_EGG' => 472,
            'WANDERING_TRADER_SPAWN_EGG' => 487,
            'WITCH_SPAWN_EGG' => 452,
            'WITHER_SKELETON_SPAWN_EGG' => 454,
            'WOLF_SPAWN_EGG' => 455,
            'ZOGLIN_SPAWN_EGG' => 498,
            'ZOMBIE_SPAWN_EGG' => 449,
            'ZOMBIE_HORSE_SPAWN_EGG' => 466,
            'ZOMBIE_PIGMAN_SPAWN_EGG' => 446,
            'ZOMBIE_VILLAGER_SPAWN_EGG' => 483,
        ];

        return $materialMap[strtoupper($material)] ?? 264; // Default to diamond
    }

    private function getComponents(array $item, string $material): array
    {
        $components = [];

        // Food component if applicable
        if (in_array(strtoupper($material), ['APPLE', 'BREAD', 'COOKED_PORKCHOP', 'GOLDEN_APPLE'])) {
            $components['food'] = [
                'nutrition' => 4,
                'saturation_modifier' => 0.6
            ];
        }

        // Durability component
        if (isset($item['durability']) && $item['durability'] > 0) {
            $components['durability'] = [
                'max_durability' => $item['durability']
            ];
        }

        return $components;
    }

    private function getCreativeCategory(array $item): string
    {
        // Map to Bedrock creative categories
        $material = strtoupper($item['material'] ?? 'DIAMOND');
        
        if (strpos($material, 'HELMET') !== false || 
            strpos($material, 'CHESTPLATE') !== false || 
            strpos($material, 'LEGGINGS') !== false || 
            strpos($material, 'BOOTS') !== false) {
            return 'equipment';
        }

        if (strpos($material, 'SWORD') !== false || 
            strpos($material, 'AXE') !== false || 
            strpos($material, 'PICKAXE') !== false || 
            strpos($material, 'SHOVEL') !== false || 
            strpos($material, 'HOE') !== false) {
            return 'items';
        }

        return 'items';
    }

    public function generateJson(): string
    {
        $mappings = $this->generate();
        return json_encode($mappings, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}

