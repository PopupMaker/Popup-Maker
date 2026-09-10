#!/usr/bin/env bash

set -euo pipefail

LANGUAGES_DIR="${1:-languages}"
MAX_MISSING_PER_LANGUAGE="${2:-75}"
MAX_TOTAL_MISSING="${3:-2100}"
EXPECTED_LANGUAGE_COUNT="${4:-28}"
EXPECTED_LOCALES_CSV="${5:-}"

if [[ ! "$MAX_MISSING_PER_LANGUAGE" =~ ^[0-9]+$ \
	|| ! "$MAX_TOTAL_MISSING" =~ ^[0-9]+$ \
	|| ! "$EXPECTED_LANGUAGE_COUNT" =~ ^[1-9][0-9]*$ ]]; then
	echo "Translation limits and expected catalog count must be non-negative integers." >&2
	exit 1
fi

IFS=',' read -r -a EXPECTED_LOCALES <<< "$EXPECTED_LOCALES_CSV"

if [[ "${#EXPECTED_LOCALES[@]}" -ne "$EXPECTED_LANGUAGE_COUNT" ]]; then
	echo "Expected $EXPECTED_LANGUAGE_COUNT catalog locales, received ${#EXPECTED_LOCALES[@]}." >&2
	exit 1
fi

shopt -s nullglob
POT_FILES=( "$LANGUAGES_DIR"/*.pot )

if [[ "${#POT_FILES[@]}" -ne 1 ]]; then
	echo "Expected exactly one POT catalog in $LANGUAGES_DIR." >&2
	exit 1
fi

POT_FILE="${POT_FILES[0]}"
PLUGIN_SLUG=$(basename "$POT_FILE" .pot)
CATALOG_COUNT=0
MISSING_CATALOGS=0
UNEXPECTED_CATALOGS=0
TOTAL_MISSING=0
MAX_MISSING=0
SOURCE_STRING_COUNT=$(
	msgattrib --no-obsolete --no-wrap "$POT_FILE" \
		| awk '/^msgid / && $0 != "msgid \"\"" { count++ } END { print count + 0 }'
)

EXPECTED_CATALOGS=','

for locale in "${EXPECTED_LOCALES[@]}"; do
	if [[ -z "$locale" || "$EXPECTED_CATALOGS" == *",$locale,"* ]]; then
		echo "Target locales must be non-empty and unique." >&2
		exit 1
	fi

	EXPECTED_CATALOGS+="$locale,"
done

for locale in "${EXPECTED_LOCALES[@]}"; do
	po_file="$LANGUAGES_DIR/$PLUGIN_SLUG-$locale.po"

	if [[ ! -f "$po_file" ]]; then
		MISSING_CATALOGS=$(( MISSING_CATALOGS + 1 ))
		TOTAL_MISSING=$(( TOTAL_MISSING + SOURCE_STRING_COUNT ))

		if (( SOURCE_STRING_COUNT > MAX_MISSING )); then
			MAX_MISSING=$SOURCE_STRING_COUNT
		fi

		continue
	fi

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

while IFS= read -r po_file; do
	locale=${po_file#"$LANGUAGES_DIR/$PLUGIN_SLUG-"}
	locale=${locale%.po}

	if [[ "$EXPECTED_CATALOGS" != *",$locale,"* ]]; then
		UNEXPECTED_CATALOGS=$(( UNEXPECTED_CATALOGS + 1 ))
	fi
done < <(find "$LANGUAGES_DIR" -maxdepth 1 -name "$PLUGIN_SLUG-*.po" -print)

NEEDS_TRANSLATION=false
WITHIN_LIMITS=true

if (( TOTAL_MISSING > 0 )); then
	NEEDS_TRANSLATION=true
fi

if (( UNEXPECTED_CATALOGS > 0 \
	|| MAX_MISSING > MAX_MISSING_PER_LANGUAGE \
	|| TOTAL_MISSING > MAX_TOTAL_MISSING )); then
	WITHIN_LIMITS=false
fi

{
	echo "catalog_count=$CATALOG_COUNT"
	echo "missing_catalogs=$MISSING_CATALOGS"
	echo "unexpected_catalogs=$UNEXPECTED_CATALOGS"
	echo "source_strings=$SOURCE_STRING_COUNT"
	echo "total_missing=$TOTAL_MISSING"
	echo "max_missing=$MAX_MISSING"
	echo "needs_translation=$NEEDS_TRANSLATION"
	echo "within_limits=$WITHIN_LIMITS"
} | tee -a "${GITHUB_OUTPUT:-/dev/null}"
