# Image Resizer Plugin

## Overview

The Image Resizer plugin is designed to generate multiple sizes of images in the WordPress Media Library. This plugin allows you to specify custom image sizes and automatically generates those sizes for each image in the Media Library. The generated sizes are based on the defined dimensions, ensuring that your images are available in various sizes to suit different use cases on your website.

## Features

- **Custom Image Sizes**: Define custom image sizes in the plugin settings.
- **Automatic Resizing**: Automatically generates and resizes images to the specified dimensions.
- **Image Quality Control**: Control the image quality for resized images.
- **Extension Support**: Specify allowed file extensions for image resizing.
- **Replace Existing Sizes**: Choose whether to replace existing image sizes when regenerating.
- **Bulk Resize**: Option to bulk resize existing images in the Media Library.

## Installation

1. **Download the Plugin**: Download the Image Resizer plugin from
 ```bash
 git clone https://github.com/AponAhmed/image-resizer.git
```

3. **Install the Plugin**: Upload the plugin to your WordPress site and activate it.

## Usage

1. **Configure Settings**: Go to the plugin settings in the WordPress admin area and configure your desired image sizes, quality, and other options.

2. **Automatic Resizing**: Once configured, the plugin will automatically generate and resize images according to the specified dimensions.

3. **Manual Resizing**: You can manually trigger image resizing by using the provided AJAX actions or the bulk resize option in the plugin settings.

4. **View Resized Images**: Check the Media Library to see the newly generated image sizes for each uploaded image.

## AJAX Actions

- **`wp_ajax_buildImageSize`**: Triggers the image size building process.
- **`wp_ajax_clean_size_build_history`**: Cleans the build history, allowing regeneration of all image sizes.

## Notes

- Ensure that the server has the necessary GD library installed for image processing.
- Backup your site before performing bulk resize operations to avoid data loss.

## Credits

This plugin is developed and maintained by [APON](mailto:apon2041@gmail.com.com). If you have any questions or issues, please [contact us](mailto:apon2041@gmail.com.com).

Feel free to contribute to the development of this plugin on [GitHub](https://github.com/AponAhmed/image-resizer).

**Version:** 2.5.2

