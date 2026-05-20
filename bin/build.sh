#!/bin/bash
# =============================================================================
# Build Script
# =============================================================================
# Purpose: Build the WordPress plugin/theme zip file for distribution
# Usage:   npm run zip or ./bin/build.sh
# =============================================================================

cd "$(dirname "$0")/.." || exit 1

# =============================================================================
# Constants
# =============================================================================
DIST_DIR="dist"
BUILD_DIR=".build"
PLUGIN_NAME="custom-plugin"

# =============================================================================
# Main Build Process
# =============================================================================
echo "Building $PLUGIN_NAME..."

if ! command -v rsync &> /dev/null; then
  echo "Error: rsync is required but not installed."
  exit 1
fi

# Check if zip is available
if ! command -v zip &> /dev/null; then
  echo "Error: zip is required but not installed."
  exit 1
fi

# Clean previous builds if they exist
if [ -d "$DIST_DIR" ] || [ -d "$BUILD_DIR" ]; then
  echo "Cleaning previous builds..."
  rm -rf "$DIST_DIR" "$BUILD_DIR"
fi

# Create dist directory
mkdir -p "$DIST_DIR"

# Create build directory
mkdir -p "$BUILD_DIR"

# Copy files to build directory
echo "Copying files..."
rsync -av --exclude='node_modules' \
      --exclude='.*' \
      --exclude='*-lock.*' \
      --exclude='*.lock' \
      --exclude='*.lock*' \
      --exclude='*.log' \
      --exclude='*.md' \
      --exclude='*.tsbuildinfo' \
      --exclude='*config*.json' \
      --exclude='/*config*' \
      --exclude='/bin' \
      --exclude='/build' \
      --exclude='/dist' \
      --exclude='composer.json' \
      --exclude='gulpfile*' \
      --exclude='package.json' \
      --exclude='phpcs.xml' \
      --exclude='phpcs*.xml' \
      --exclude='vendor' \
      . "$BUILD_DIR/$PLUGIN_NAME"

if [ ! -d "$BUILD_DIR/$PLUGIN_NAME" ]; then
  echo "Error: Failed to create build directory"
  exit 1
fi

# Remove dev files from build
echo "Cleaning dev files..."
rm -rf "$BUILD_DIR/$PLUGIN_NAME/node_modules" 2>/dev/null || true
rm -rf "$BUILD_DIR/$PLUGIN_NAME/vendor" 2>/dev/null || true
rm -rf "$BUILD_DIR/$PLUGIN_NAME/tests" 2>/dev/null || true
rm -rf "$BUILD_DIR/$PLUGIN_NAME/bin" 2>/dev/null || true
rm -rf "$BUILD_DIR/$PLUGIN_NAME/build" 2>/dev/null || true
rm -rf "$BUILD_DIR/$PLUGIN_NAME/dist" 2>/dev/null || true
rm -rf "$BUILD_DIR/$PLUGIN_NAME/.build" 2>/dev/null || true
rm -rf "$BUILD_DIR/$PLUGIN_NAME/.git" 2>/dev/null || true
rm -rf "$BUILD_DIR/$PLUGIN_NAME/.tmp" 2>/dev/null || true

# Create zip file
echo "Creating archive..."
cd "$BUILD_DIR"
zip -r "../$DIST_DIR/$PLUGIN_NAME.zip" "$PLUGIN_NAME"
cd ..

# Verify zip was created
if [ -f "$DIST_DIR/$PLUGIN_NAME.zip" ]; then
  ZIP_SIZE=$(stat -f%z "$DIST_DIR/$PLUGIN_NAME.zip" 2>/dev/null || stat -c%s "$DIST_DIR/$PLUGIN_NAME.zip" 2>/dev/null)
  if [ "$ZIP_SIZE" -gt 0 ]; then
    echo "Build complete: $DIST_DIR/$PLUGIN_NAME.zip"
  else
    echo "Error: Created zip file is empty"
    exit 1
  fi
else
  echo "Error: Failed to create zip file"
  exit 1
fi

# Clean up build directory
rm -rf "$BUILD_DIR"

echo "Done!"
