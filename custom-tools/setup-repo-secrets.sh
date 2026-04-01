#!/usr/bin/env bash
#
# Setup GitHub repo secrets and variables for CI/CD.
#
# Usage:
#   1. Copy .env.secrets.example to .env.secrets
#   2. Fill in the values (quote URLs!)
#   3. Run: ./custom-tools/setup-repo-secrets.sh owner/repo

set -eo pipefail

if [ -z "${1:-}" ]; then
    echo "Usage: $0 <owner/repo>"
    echo "  e.g. $0 PopupMaker/Popup-Maker"
    exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="${SCRIPT_DIR}/../.env.secrets"
REPO="$1"

if [ ! -f "$ENV_FILE" ]; then
    echo "❌ Missing .env.secrets file. Copy from .env.secrets.example:"
    echo "   cp .env.secrets.example .env.secrets"
    exit 1
fi

# Source the env file.
set -a
# shellcheck source=/dev/null
source "$ENV_FILE"
set +a

echo "🔧 Syncing secrets and variables to ${REPO}"
echo ""

# Helper: set a secret (supports @filepath for JSON/binary).
set_secret() {
    local key="$1"
    local val="${!key:-}"

    if [ -z "$val" ]; then
        echo "  ⏭️  Secret: $key (empty, skipped)"
        return
    fi

    if [[ "$val" == @* ]]; then
        local filepath="${val:1}"
        if [ -f "$filepath" ]; then
            # Base64-encode JSON files (required by some actions like GDrive).
            if [[ "$filepath" == *.json ]]; then
                base64 < "$filepath" | gh secret set "$key" --repo "$REPO"
            else
                gh secret set "$key" --repo "$REPO" < "$filepath"
            fi
        else
            echo "  ❌ Secret: $key (file not found: $filepath)"
            return
        fi
    else
        printf '%s' "$val" | gh secret set "$key" --repo "$REPO"
    fi
    echo "  ✅ Secret: $key"
}

# Helper: set a variable.
set_variable() {
    local key="$1"
    local val="${!key:-}"

    if [ -z "$val" ]; then
        echo "  ⏭️  Variable: $key (empty, skipped)"
        return
    fi

    gh variable set "$key" --repo "$REPO" --body "$val" 2>/dev/null \
        || gh variable set "$key" --repo "$REPO" --body "$val"
    echo "  ✅ Variable: $key"
}

# --- Secrets ---
echo "Secrets:"
set_secret SVN_USERNAME
set_secret SVN_PASSWORD
set_secret SLACK_WEBHOOK
set_secret SLACK_WEBHOOK_DEV
set_secret SLACK_WEBHOOK_SUPPORT
set_secret EDD_WEBHOOK_TOKEN
set_secret GOOGLE_DRIVE_CREDENTIALS

echo ""

# --- Variables ---
echo "Variables:"
set_variable EDD_PRODUCT_ID
set_variable EDD_WEBHOOK_URL
set_variable GOOGLE_DRIVE_FOLDER_ID
set_variable GOOGLE_DRIVE_BETA_FOLDER_ID

echo ""
echo "✅ Done. Verify at: https://github.com/${REPO}/settings/secrets/actions"
