#!/bin/bash

# WhatsApp Template Sender - Build Script
# This script creates a distribution-ready ZIP file of the plugin

PLUGIN_NAME="whatsapp-template-sender"
VERSION=$(grep "Version:" whatsapp-template-sender.php | cut -d' ' -f3)
BUILD_DIR="build"
DIST_DIR="dist"

echo "🚀 Building ${PLUGIN_NAME} v${VERSION}..."

# Clean up previous builds
rm -rf $BUILD_DIR
rm -rf $DIST_DIR
mkdir -p $BUILD_DIR/$PLUGIN_NAME
mkdir -p $DIST_DIR

# Copy necessary files
echo "📁 Copying plugin files..."
cp whatsapp-template-sender.php $BUILD_DIR/$PLUGIN_NAME/
cp README.md $BUILD_DIR/$PLUGIN_NAME/
cp -r includes/ $BUILD_DIR/$PLUGIN_NAME/
cp -r assets/ $BUILD_DIR/$PLUGIN_NAME/

# Create the ZIP file
echo "📦 Creating ZIP archive..."
cd $BUILD_DIR
zip -r ../$DIST_DIR/${PLUGIN_NAME}-v${VERSION}.zip $PLUGIN_NAME/
cd ..

# Clean up build directory
rm -rf $BUILD_DIR

echo "✅ Build complete!"
echo "📦 Archive created: $DIST_DIR/${PLUGIN_NAME}-v${VERSION}.zip"
echo ""
echo "Files included:"
unzip -l $DIST_DIR/${PLUGIN_NAME}-v${VERSION}.zip