# iageyser/ItemsAdder Geyser Converter

A PHP CLI tool to convert Minecraft Java Edition ItemsAdder packs to Geyser-compatible resource packs and custom mappings.

## Disclosure

Some code has been generated using Generative AI, However all code errors and bugs have been hand/human fixed.

## Features

- ✅ Convert ItemsAdder items.yml to Geyser custom mappings
- ✅ Convert Java Edition resource packs to Bedrock Edition format
- ✅ Extract and process ItemsAdder packs (ZIP or folder)
- ✅ Generate Bedrock resource pack manifest
- ✅ Create ZIP files for easy distribution

## Installation

1. Clone this repository:
   ```bash
   git clone <repository-url>
   cd iageyser-rewrite
   ```

2. Install dependencies using Composer:
   ```bash
   composer install
   ```

## Usage

### Basic Usage

Convert an ItemsAdder pack to Geyser format:

```bash
php bin/convert --input=/path/to/itemsadder/pack --output=/path/to/output
```

### Options

- `--input` or `-i`: Path to ItemsAdder pack (folder or ZIP file) - **Required**
- `--output` or `-o`: Output directory for converted pack (default: `./output`)
- `--pack-name`: Name for the output pack (default: `itemsadder-geyser-pack`)
- `--create-zip`: Create a ZIP file of the converted pack

### Examples

```bash
# Convert a pack folder
php bin/convert -i /path/to/itemsadder/pack -o ./output

# Convert a ZIP file and create output ZIP
php bin/convert -i /path/to/pack.zip -o ./output --create-zip

# Custom pack name
php bin/convert -i /path/to/pack -o ./output --pack-name=my-custom-pack
```

### Output Structure

After conversion, you'll get:

```
output/
├── mappings/
│   └── items.json          # Geyser custom mappings file
└── itemsadder-geyser-pack/  # Bedrock resource pack
    ├── manifest.json
    └── textures/
        └── items/
            └── ...
```

### Next Steps

1. Place the `items.json` file in Geyser's `custom_mappings` folder
2. Place the resource pack folder (or ZIP) in Geyser's `packs` folder
3. Restart your server or reload Geyser

## Current Status

### Supported Features

- ✅ Basic 2D items conversion
- ✅ Items.yml parsing
- ✅ Texture conversion
- ✅ Geyser custom mappings generation
- ✅ Resource pack conversion (basic)

### Planned Features

- 🔄 Custom block models
- 🔄 Font images
- 🔄 Emojis
- 🔄 Custom UIs
- 🔄 Custom item models (3D)
- 🔄 Custom HUDs
- 🔄 Custom Furniture

## How It Works

1. **Parser**: Reads ItemsAdder pack structure (items.yml, resourcepack folder)
2. **Generator**: Creates Geyser custom mappings (items.json) with proper item IDs and properties
3. **Converter**: Converts Java Edition resource pack to Bedrock Edition format
4. **Output**: Generates both mappings file and Bedrock resource pack

## Development

### Project Structure

```
.
├── bin/
│   └── convert              # CLI entry point
├── src/
│   ├── Command/             # CLI commands
│   ├── Parser/              # ItemsAdder pack parser
│   ├── Generator/           # Geyser mappings generator
│   └── Converter/           # Resource pack converter
├── composer.json
└── README.md
```

## License

GNU General Public License v3.0

## Contributing

Contributions are welcome! Please feel free to submit a pull request.

---

# the end goal
Support items, blocks, custom block models, font images, emojis, custom uis, custom item models, custom HUDs and custom Furnature

# What can I do with this plugins source code?
You can use this for:

    Commercial use ( Use in public servers, idk if credit is needed or not, its just a license off of choosealicense.com )
    Distribution ( Give others downloads to this plugin or any other forks )
    Modification ( Modify the plugin, whether it contributing to the main repo or to a fork )
    Patent use ( I dont know this one... )
    Private use ( Modify this code and use it for you're own server, e.g. customizing the plugin and using the customized code for you're server, without having to release it to the public, which means you're own private fork, that can't be shared online without source code )

You must:

    
    Disclose the source code of said fork, e.g. you're fork MUST be open source
    License and copyright notice, e.g. you're fork MUST include a link to this project
    Same license, e.g. you MUST use the same license we use ( GNU General Public License v3.0 )
    State changes, e.g. you MUST say what you changed
