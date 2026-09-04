#!/usr/bin/env bash
#
# Sync GitHub Actions secrets and variables from .env.secrets.
#
# Values between the Secrets and Variables section markers are sent to the
# corresponding GitHub repository settings without being printed or executed.
#
# Usage:
#   ./custom-tools/setup-repo-secrets.sh [owner/repo]
#
# When owner/repo is omitted, the repository is detected from the current
# checkout. GITHUB_REPO may also provide an explicit repository.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
ENV_FILE="$PROJECT_ROOT/.env.secrets"
REPO="${1:-${GITHUB_REPO:-}}"

if ! command -v gh >/dev/null 2>&1; then
	echo "❌ GitHub CLI (gh) is required."
	exit 1
fi

if ! gh auth status >/dev/null 2>&1; then
	echo "❌ GitHub CLI is not authenticated. Run: gh auth login"
	exit 1
fi

if [ ! -f "$ENV_FILE" ]; then
	echo "❌ Missing $ENV_FILE"
	echo "   Copy .env.secrets.example to .env.secrets and fill in its values."
	exit 1
fi

if [ -z "$REPO" ]; then
	REPO="$(git -C "$PROJECT_ROOT" remote get-url origin 2>/dev/null 		| sed -E 's#^(git@github.com:|https://github.com/)##; s#\.git$##')"
fi

if [ -z "$REPO" ]; then
	echo "❌ Could not determine the GitHub repository."
	exit 1
fi

strip_outer_quotes() {
	local value="$1"
	local first
	local last

	if [ "${#value}" -lt 2 ]; then
		printf '%s' "$value"
		return
	fi

	first="${value:0:1}"
	last="${value: -1}"

	if { [ "$first" = '"' ] && [ "$last" = '"' ]; } 		|| { [ "$first" = "'" ] && [ "$last" = "'" ]; }; then
		value="${value:1:${#value}-2}"
	fi

	printf '%s' "$value"
}

set_secret() {
	local key="$1"
	local value="$2"
	local filepath

	if [[ "$value" == @* ]]; then
		filepath="${value:1}"
		[[ "$filepath" = /* ]] || filepath="$PROJECT_ROOT/$filepath"

		if [ ! -f "$filepath" ]; then
			echo "❌ Secret $key references a missing file."
			exit 1
		fi

		if [[ "$filepath" == *.json ]]; then
			base64 < "$filepath" | tr -d '\n' | gh secret set "$key" --repo "$REPO"
		else
			gh secret set "$key" --repo "$REPO" < "$filepath"
		fi
	else
		printf '%s' "$value" | gh secret set "$key" --repo "$REPO"
	fi

	echo "  ✅ Secret: $key"
}

set_variable() {
	local key="$1"
	local value="$2"

	gh variable set "$key" --repo "$REPO" --body "$value"
	echo "  ✅ Variable: $key"
}

section=""
saw_secret_marker=false
saw_variable_marker=false
secret_count=0
variable_count=0

echo "🔧 Syncing GitHub Actions configuration to $REPO"

while IFS= read -r line || [ -n "$line" ]; do
	line="${line%$'\r'}"

	case "$line" in
		"# --- Secrets (encrypted, not visible in logs) ---")
			section="secret"
			saw_secret_marker=true
			continue
			;;
		"# --- Variables (visible in logs, non-sensitive) ---")
			section="variable"
			saw_variable_marker=true
			continue
			;;
	esac

	if [[ ! "$line" =~ ^([A-Za-z_][A-Za-z0-9_]*)=(.*)$ ]] || [ -z "$section" ]; then
		continue
	fi

	key="${BASH_REMATCH[1]}"
	value="$(strip_outer_quotes "${BASH_REMATCH[2]}")"

	if [ -z "$value" ]; then
		echo "  ⏭️  $key is empty; skipped."
		continue
	fi

	if [ "$section" = "secret" ]; then
		set_secret "$key" "$value"
		secret_count=$((secret_count + 1))
	else
		set_variable "$key" "$value"
		variable_count=$((variable_count + 1))
	fi
done < "$ENV_FILE"

if [ "$saw_secret_marker" != true ] || [ "$saw_variable_marker" != true ]; then
	echo "❌ .env.secrets must contain the standard Secrets and Variables markers."
	exit 1
fi

echo "✅ Synced $secret_count secret(s) and $variable_count variable(s)."
echo "   Verify at: https://github.com/$REPO/settings/secrets/actions"
