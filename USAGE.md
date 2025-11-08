# Usage Guide: ItemsAdder to Geyser Converter

This guide will walk you through using the PHP CLI tool to convert ItemsAdder packs to Geyser-compatible format.

## Prerequisites

1. **PHP 8.0 or higher** - Make sure PHP is installed and accessible from the command line
2. **Composer** - For installing dependencies
3. **ItemsAdder Pack** - The pack you want to convert (folder or ZIP file)

## Installation

1. **Clone or download this repository**
   ```bash
   git clone <repository-url>
   cd iageyser-rewrite
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Verify installation**
   ```bash
   php bin/convert --help
   ```
   You should see the help menu with available options.

## Basic Usage

### Convert an ItemsAdder Pack

The most basic command to convert a pack:

```bash
php bin/convert --input=/path/to/itemsadder/pack --output=/path/to/output
```

Or using short options:

```bash
php bin/convert -i /path/to/itemsadder/pack -o /path/to/output
```

### Command Options

- `--input` or `-i`: **Required** - Path to ItemsAdder pack (folder or ZIP file)
- `--output` or `-o`: **Optional** - Output directory (defaults to `./output`)
- `--pack-name`: **Optional** - Name for the output pack (defaults to `itemsadder-geyser-pack`)
- `--create-zip`: **Optional** - Create a ZIP file of the converted pack

## Examples

### Example 1: Convert a Pack Folder

```bash
php bin/convert -i /path/to/my-itemsadder-pack -o ./converted-pack
```

This will:
- Read the ItemsAdder pack from `/path/to/my-itemsadder-pack`
- Create output in `./converted-pack`
- Generate mappings and resource pack

### Example 2: Convert a ZIP File

```bash
php bin/convert -i /path/to/pack.zip -o ./output
```

The tool will automatically extract the ZIP, process it, and generate the output.

### Example 3: Create a ZIP of the Converted Pack

```bash
php bin/convert -i /path/to/pack -o ./output --create-zip
```

This creates both the unpacked version and a ZIP file for easy distribution.

### Example 4: Custom Pack Name

```bash
php bin/convert -i /path/to/pack -o ./output --pack-name=my-custom-items
```

The output pack will be named `my-custom-items` instead of the default.

### Example 5: Full Example with All Options

```bash
php bin/convert \
  --input=/path/to/itemsadder/pack.zip \
  --output=./geyser-packs \
  --pack-name=my-server-items \
  --create-zip
```

## Input Pack Structure

The converter looks for the following structure in your ItemsAdder pack:

```
itemsadder-pack/
├── items.yml                    # Item definitions (required)
├── resourcepack/                # Resource pack folder (optional)
│   ├── assets/
│   │   └── namespace/
│   │       ├── textures/
│   │       │   └── item/
│   │       └── models/
│   │           └── item/
└── ...
```

The converter will automatically search for:
- `items.yml` in common locations (root, `contents/`, `configs/`, or recursively)
- Resource pack folders (`resourcepack/`, `resource_pack/`, `rp/`, or folders containing `assets/`)

## Output Structure

After conversion, you'll get:

```
output/
├── mappings/
│   └── items.json              # Geyser custom mappings file
└── itemsadder-geyser-pack/     # Bedrock resource pack
    ├── manifest.json
    └── textures/
        └── items/
            └── ...
```

### Output Files Explained

1. **`mappings/items.json`** - This is the Geyser custom mappings file that maps Java Edition items to Bedrock Edition items
2. **Resource Pack Folder** - Contains the Bedrock Edition resource pack with textures and manifest

## Installing the Converted Pack

### Step 1: Install the Mappings File

1. Locate your Geyser server directory
2. Navigate to the `custom_mappings` folder (create it if it doesn't exist)
3. Copy the `items.json` file from `output/mappings/` to this folder

```
geyser-server/
└── custom_mappings/
    └── items.json          # Copy from output/mappings/
```

### Step 2: Install the Resource Pack

1. Navigate to Geyser's `packs` folder (create it if it doesn't exist)
2. Copy the resource pack folder (or ZIP if you used `--create-zip`) to this folder

```
geyser-server/
└── packs/
    └── itemsadder-geyser-pack/  # Copy from output/
        ├── manifest.json
        └── textures/
```

### Step 3: Restart Geyser

1. Restart your Minecraft server or reload Geyser
2. The custom items should now be available for Bedrock Edition players

## Troubleshooting

### Error: "Input path does not exist"

**Problem**: The path to your ItemsAdder pack is incorrect.

**Solution**: 
- Check that the path is correct
- Use absolute paths if relative paths don't work
- On Windows, use forward slashes or double backslashes: `C:/path/to/pack` or `C:\\path\\to\\pack`

### Error: "Failed to parse items.yml"

**Problem**: The `items.yml` file is missing or malformed.

**Solution**:
- Ensure your ItemsAdder pack has an `items.yml` file
- Check that the YAML syntax is correct
- The converter searches in common locations, but you can check if the file exists

### Warning: "No resource pack found"

**Problem**: The converter couldn't find a resource pack folder.

**Solution**:
- This is not a critical error - mappings will still be generated
- If you have textures, make sure they're in a folder named `resourcepack/`, `resource_pack/`, or `rp/`
- Or ensure there's an `assets/` folder in your pack structure

### Items not appearing in Bedrock Edition

**Problem**: Items don't show up for Bedrock players.

**Solutions**:
1. **Check Geyser version**: Make sure you're using a recent version of Geyser that supports custom items
2. **Verify file locations**: Ensure `items.json` is in `custom_mappings/` and the resource pack is in `packs/`
3. **Check server logs**: Look for errors related to custom items or resource packs
4. **Verify mappings format**: Open `items.json` and ensure it's valid JSON

### Textures not showing

**Problem**: Items appear but without textures.

**Solutions**:
1. **Check texture paths**: Verify textures are in `textures/items/` in the resource pack
2. **Verify texture format**: Ensure textures are PNG files
3. **Check manifest**: The `manifest.json` should be valid and include the textures module
4. **Resource pack loading**: Make sure the resource pack is loaded by Geyser (check server logs)

## Advanced Usage

### Multiple Packs

To convert multiple packs, run the converter multiple times with different output directories:

```bash
php bin/convert -i /path/to/pack1 -o ./output/pack1
php bin/convert -i /path/to/pack2 -o ./output/pack2
```

Then combine the `items.json` files if needed, or use separate mapping files.

### Custom Material Mapping

The converter automatically maps Java Edition materials to Bedrock Edition item IDs. If you need to customize this, you can modify the `getBedrockItemId()` method in `src/Generator/GeyserMappingsGenerator.php`.

### Custom Model Data

ItemsAdder items use custom model data to distinguish them. The converter:
- Uses the custom model data from `items.yml` if available
- Generates a unique custom model data value if not specified
- Ensures each item has a unique identifier

## Tips and Best Practices

1. **Backup your server**: Always backup your Geyser server before installing new packs
2. **Test in development**: Test converted packs on a development server first
3. **Check compatibility**: Ensure your Geyser version supports custom items
4. **Organize packs**: Keep converted packs organized in separate folders
5. **Version control**: Consider versioning your converted packs for easy rollback

## Getting Help

If you encounter issues:

1. Check the troubleshooting section above
2. Review the error messages - they often contain helpful information
3. Check Geyser documentation for custom items support
4. Verify your ItemsAdder pack structure is correct
5. Check PHP and Composer versions meet requirements

## Additional Resources

- [Geyser Documentation](https://geysermc.org/)
- [ItemsAdder Documentation](https://itemsadder.devs.beer/)
- [Geyser Custom Items Guide](https://geysermc.org/wiki/geyser-ese/custom-items/)

---

**Note**: This converter is designed to work with standard ItemsAdder packs. Custom configurations or advanced features may require manual adjustments to the generated files.

