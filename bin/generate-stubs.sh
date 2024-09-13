#!/usr/bin/env bash

HEADER=$'/**\n * Generated stub declarations for Popup Maker.\n * @see https://wppopupmaker.com/\n * @see https://github.com/code-atlantic/wp-plugin-stubs\n */'

FILE="./bin/stubs/popup-maker.php"
# FILE="../popup-maker-pro/bin/stubs/popup-maker.stub"

set -e

if [ ! -f "$FILE" ]; then
    echo "File $FILE does not exist."
    exit 1
fi

echo "Generating stubs for Popup Maker..."

# Exclude globals.
"generate-stubs" \
    --include-inaccessible-class-nodes \
    --force \
    # --finder=bin/generate-stubs.php \
    --header="$HEADER" \
    --out="$FILE"
    ./classes/ ./inc/ ./popup-maker.php ./uninstall.php
