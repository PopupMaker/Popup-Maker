#!/usr/bin/env bash

HEADER=$'/**\n * Generated stub declarations for Popup Maker.\n * @see https://wppopupmaker.com/\n * @see https://github.com/code-atlantic/wp-plugin-stubs\n */'

# Default output file if not specified
DEFAULT_FILE="./popup-maker.stub"

echo "Generating stubs for Popup Maker..."

# Check for -y flag (non-interactive)
AUTO_INSTALL=false
FILTERED_ARGS=()
OUT_FILE="$DEFAULT_FILE"

for arg in "$@"; do
    if [[ $arg == "-y" || $arg == "--yes" ]]; then
        AUTO_INSTALL=true
    elif [[ $arg == --out=* ]]; then
        OUT_FILE="${arg#*=}"
        # Create directory if it doesn't exist
        OUT_DIR=$(dirname "$OUT_FILE")
        if [[ ! -d "$OUT_DIR" ]]; then
            echo "Creating directory: $OUT_DIR"
            mkdir -p "$OUT_DIR"
        fi
        FILTERED_ARGS+=("$arg")
    else
        FILTERED_ARGS+=("$arg")
    fi
done

# Function to check for global generate-stubs installation
check_global_generator() {
    # Get the global composer bin directory
    local GLOBAL_BIN_DIR
    GLOBAL_BIN_DIR=$(composer global config bin-dir --absolute 2>/dev/null | tail -1)

    if [[ -n "$GLOBAL_BIN_DIR" && -x "$GLOBAL_BIN_DIR/generate-stubs" ]]; then
        # Found in global composer bin directory
        echo "$GLOBAL_BIN_DIR/generate-stubs"
        return 0
    fi

    # Check if it's in the system PATH (but not our vendor/bin)
    local GENERATOR_PATH
    GENERATOR_PATH=$(which generate-stubs 2>/dev/null)

    if [[ -n "$GENERATOR_PATH" && "$GENERATOR_PATH" != *"$(pwd)/vendor/bin"* ]]; then
        echo "$GENERATOR_PATH"
        return 0
    fi

    return 1
}

# Function to install generate-stubs globally
install_global_generator() {
    echo "📦 Installing php-stubs/generator globally..."

    if composer global require php-stubs/generator; then
        echo "✅ Successfully installed php-stubs/generator globally!"
        return 0
    else
        echo "❌ Failed to install php-stubs/generator globally"
        echo "Please run manually: composer global require php-stubs/generator"
        return 1
    fi
}

# Check if generate-stubs is available globally
GLOBAL_GENERATOR=$(check_global_generator)
if [[ $? -eq 0 && -n "$GLOBAL_GENERATOR" ]]; then
    echo "✅ Using globally installed generate-stubs: $GLOBAL_GENERATOR"
else
    echo "⚠️  Global generate-stubs not found"
    echo ""
    echo "📋 ISSUE:"
    echo "   The vendor/bin version conflicts with our project's autoloader"
    echo "   (QueryMonitor integration classes cause fatal errors)"
    echo ""

    if [[ $AUTO_INSTALL == true ]]; then
        echo "🔧 Auto-installing globally (non-interactive mode)..."
        if ! install_global_generator; then
            exit 1
        fi
        # Re-check for the global generator after installation
        GLOBAL_GENERATOR=$(check_global_generator)
        if [[ $? -ne 0 || -z "$GLOBAL_GENERATOR" ]]; then
            echo "❌ Failed to find generate-stubs after installation"
            exit 1
        fi
    else
        echo "🔧 SOLUTION:"
        echo "   Install php-stubs/generator globally"
        echo ""
        read -p "   Install now? [Y/n]: " -n 1 -r
        echo

        if [[ $REPLY =~ ^[Yy]$ ]] || [[ -z $REPLY ]]; then
            if ! install_global_generator; then
                exit 1
            fi
            # Re-check for the global generator after installation
            GLOBAL_GENERATOR=$(check_global_generator)
            if [[ $? -ne 0 || -z "$GLOBAL_GENERATOR" ]]; then
                echo "❌ Failed to find generate-stubs after installation"
                exit 1
            fi
        else
            echo "Installation cancelled. Run manually:"
            echo "composer global require php-stubs/generator"
            exit 1
        fi
    fi

    echo ""
fi

echo "Using custom finder: ./bin/generate-stubs.php"

# Generate stubs using the detected global installation with custom finder
"$GLOBAL_GENERATOR" \
    --header="$HEADER" \
    --out="$OUT_FILE" \
    --force \
    --include-inaccessible-class-nodes \
    --stats \
    --finder=./bin/generate-stubs.php

if [[ $? -eq 0 ]]; then
    echo "✅ Stub generation completed successfully!"
    echo "📁 Output: $OUT_FILE"
else
    echo "❌ Stub generation failed"
    exit 1
fi
