#!/bin/bash
# =============================================================================
# Make POT Script
# =============================================================================
# Purpose: Generate translation template (.pot) file from source code
# Usage:   npm run makepot or ./bin/makepot.sh
# Requires: wp-cli (wp i18n make-pot)
# =============================================================================

cd "$(dirname "$0")/.." || exit 1

# =============================================================================
# Constants
# =============================================================================
TEXTDOMAIN="custom-plugin"
LANGUAGES_DIR="languages"
POT_FILE="$LANGUAGES_DIR/$TEXTDOMAIN.pot"

# =============================================================================
# Main Process
# =============================================================================
echo "Generating POT file for $TEXTDOMAIN..."

# Check if wp-cli is installed
if ! command -v wp &> /dev/null; then
  echo "Error: wp-cli is required but not installed."
  echo "Install via: curl -sO https://wp-cli.org/phar && chmod +x phar && mv phar /usr/local/bin/wp"
  exit 1
fi

# Create languages directory if it doesn't exist
if [ ! -d "$LANGUAGES_DIR" ]; then
  echo "Creating languages directory..."
  mkdir -p "$LANGUAGES_DIR"
fi

# Remove existing POT files
if ls "$LANGUAGES_DIR"/*.pot 1> /dev/null 2>&1; then
  echo "Removing existing POT files..."
  rm -f "$LANGUAGES_DIR"/*.pot
fi

# Generate POT file
echo "Generating POT file..."
wp i18n make-pot . "$POT_FILE" \
  --exclude=vendor,node_modules,.git,.vscode,bin,.husky,dist,build \
  --slug="$TEXTDOMAIN" \
  --domain="$TEXTDOMAIN" \
  --allow-root

# Verify POT file was created
if [ -f "$POT_FILE" ]; then
  echo "Generated: $POT_FILE"
else
  echo "Error: Failed to generate POT file"
  exit 1
fi

echo "Done!"
