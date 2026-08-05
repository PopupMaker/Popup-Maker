#!/usr/bin/env bash

set -euo pipefail

LANGUAGES_DIR="${1:-languages}"
MAX_MISSING_PER_LANGUAGE="${2:-75}"
MAX_TOTAL_MISSING="${3:-2100}"
EXPECTED_LANGUAGE_COUNT="${4:-28}"

shopt -s nullglob
POT_FILES=( "$LANGUAGES_DIR"/*.pot )

if [[ "${#POT_FILES[@]}" -ne 1 ]]; then
	echo "Expected exactly one POT catalog in $LANGUAGES_DIR." >&2
	exit 1
fi

POT_FILE="${POT_FILES[0]}"
PLUGIN_SLUG=$(basename "$POT_FILE" .pot)
PO_FILES=( "$LANGUAGES_DIR/$PLUGIN_SLUG-"*.po )
CATALOG_COUNT=0
TOTAL_MISSING=0
MAX_MISSING=0

for po_file in "${PO_FILES[@]}"; do
	msgmerge \
		--quiet \
		--update \
		--backup=none \
		--no-fuzzy-matching \
		"$po_file" \
		"$POT_FILE" \
		>/dev/null

	untranslated=$(
		msgattrib --untranslated --no-obsolete --no-wrap "$po_file" \
			| awk '/^msgid / && $0 != "msgid \"\"" { count++ } END { print count + 0 }'
	)
	fuzzy=$(
		msgattrib --only-fuzzy --no-obsolete --no-wrap "$po_file" \
			| awk '/^msgid / && $0 != "msgid \"\"" { count++ } END { print count + 0 }'
	)
	missing=$(( untranslated + fuzzy ))
	TOTAL_MISSING=$(( TOTAL_MISSING + missing ))
	CATALOG_COUNT=$(( CATALOG_COUNT + 1 ))

	if (( missing > MAX_MISSING )); then
		MAX_MISSING=$missing
	fi
done

NEEDS_TRANSLATION=false
WITHIN_LIMITS=true

if (( TOTAL_MISSING > 0 )); then
	NEEDS_TRANSLATION=true
fi

if (( CATALOG_COUNT != EXPECTED_LANGUAGE_COUNT \
	|| MAX_MISSING > MAX_MISSING_PER_LANGUAGE \
	|| TOTAL_MISSING > MAX_TOTAL_MISSING )); then
	WITHIN_LIMITS=false
fi

{
	echo "catalog_count=$CATALOG_COUNT"
	echo "total_missing=$TOTAL_MISSING"
	echo "max_missing=$MAX_MISSING"
	echo "needs_translation=$NEEDS_TRANSLATION"
	echo "within_limits=$WITHIN_LIMITS"
} | tee -a "${GITHUB_OUTPUT:-/dev/null}"
