# Prism Studio - Ghost Block
A PocketMine-MP plugin that adds ghost and invisible blocks to your server, perfect for creating visual illusions and secret constructions.

## Features

- **Ghost Block**: A transparent block that automatically displays the adjacent block, creating stunning visual illusions
- **Invisible Block**: A completely transparent block with collision, ideal for hidden mechanisms and secret builds
- **Custom Resource Pack**: Includes textures and models for both block types
- **Performance Optimized**: Uses AsyncIterator for better performance when available

## Requirements

- PocketMine-MP 5.0.0 or higher
- **Customies** plugin (required dependency)
- **AsyncIterator** virion (soft dependency - recommended for better performance)

## Installation

1. Download the plugin files
2. Place the plugin folder in your `plugins/` directory
3. Install **Customies** plugin (required)
4. Install **AsyncIterator** virion (recommended)
5. Restart your server

## Usage

### Ghost Block
- Place a ghost block next to any other block
- The ghost block will automatically display the texture of the adjacent block
- Perfect for creating floating block effects and visual illusions

### Invisible Block
- Place invisible blocks where you want hidden collision
- Players can walk through them but they provide solid collision
- Ideal for secret passages and hidden mechanisms

## Configuration
No configuration file is needed. The plugin works out of the box with default settings.

## Dependencies

- **Customies**: Required for custom block registration
- **AsyncIterator**: Soft dependency for performance optimization

## License
This project is licensed under the [GNU General Public License v3.0](LICENSE) – see the LICENSE file for details.