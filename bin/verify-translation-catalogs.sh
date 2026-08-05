#!/usr/bin/env bash

set -euo pipefail

LANGUAGES_DIR="${1:-languages}"
EXPECTED_LANGUAGE_COUNT="${2:-28}"
EXPECTED_LOCALES_CSV="${3:-}"
PO_COUNT=0

if [[ ! "$EXPECTED_LANGUAGE_COUNT" =~ ^[1-9][0-9]*$ ]]; then
	echo "Expected catalog count must be a positive integer." >&2
	exit 1
fi

IFS=',' read -r -a EXPECTED_LOCALES <<< "$EXPECTED_LOCALES_CSV"

if [[ "${#EXPECTED_LOCALES[@]}" -ne "$EXPECTED_LANGUAGE_COUNT" ]]; then
	echo "Expected $EXPECTED_LANGUAGE_COUNT catalog locales, received ${#EXPECTED_LOCALES[@]}." >&2
	exit 1
fi
shopt -s nullglob
POT_FILES=( "$LANGUAGES_DIR"/*.pot )
DISCOVERED_PO_COUNT=$(find "$LANGUAGES_DIR" -maxdepth 1 -name '*.po' -print 2>/dev/null | wc -l | tr -d ' ')

if [[ "${#POT_FILES[@]}" -eq 0 && "$DISCOVERED_PO_COUNT" -eq 0 ]]; then
	echo "No translation catalogs to verify."
	exit 0
fi

if [[ "${#POT_FILES[@]}" -ne 1 ]]; then
	echo "Expected exactly one POT catalog in $LANGUAGES_DIR." >&2
	exit 1
fi

POT_FILE="${POT_FILES[0]}"
PLUGIN_SLUG=$(basename "$POT_FILE" .pot)

EXPECTED_CATALOGS=','

for locale in "${EXPECTED_LOCALES[@]}"; do
	if [[ -z "$locale" || "$EXPECTED_CATALOGS" == *",$locale,"* ]]; then
		echo "Target locales must be non-empty and unique." >&2
		exit 1
	fi

	EXPECTED_CATALOGS+="$locale,"
	po_file="$LANGUAGES_DIR/$PLUGIN_SLUG-$locale.po"

	if [[ ! -f "$po_file" ]]; then
		echo "Missing expected PO catalog: $po_file" >&2
		exit 1
	fi
done

while IFS= read -r po_file; do
	locale=${po_file#"$LANGUAGES_DIR/$PLUGIN_SLUG-"}
	locale=${locale%.po}

	if [[ "$EXPECTED_CATALOGS" != *",$locale,"* ]]; then
		echo "Unexpected PO catalog: $po_file" >&2
		exit 1
	fi
done < <(find "$LANGUAGES_DIR" -maxdepth 1 -name "$PLUGIN_SLUG-*.po" -print)

for locale in "${EXPECTED_LOCALES[@]}"; do
	po_file="$LANGUAGES_DIR/$PLUGIN_SLUG-$locale.po"

	mo_file="${po_file%.po}.mo"

	msgcmp "$po_file" "$POT_FILE" >/dev/null
	msgfmt --check-format -o /dev/null "$po_file"

	untranslated=$(
		msgattrib --untranslated --no-obsolete --no-wrap "$po_file" \
			| awk '/^msgid / && $0 != "msgid \"\"" { count++ } END { print count + 0 }'
	)
	fuzzy=$(
		msgattrib --only-fuzzy --no-obsolete --no-wrap "$po_file" \
			| awk '/^msgid / && $0 != "msgid \"\"" { count++ } END { print count + 0 }'
	)

	if [[ "$untranslated" -ne 0 || "$fuzzy" -ne 0 ]]; then
		echo "Incomplete PO catalog: $po_file ($untranslated untranslated, $fuzzy fuzzy)." >&2
		exit 1
	fi

	if [[ ! -f "$mo_file" ]]; then
		echo "Missing MO catalog: $mo_file" >&2
		exit 1
	fi

	msgunfmt "$mo_file" >/dev/null
	PO_COUNT=$(( PO_COUNT + 1 ))
done

MO_COUNT=$(find "$LANGUAGES_DIR" -maxdepth 1 -name "$PLUGIN_SLUG-*.mo" | wc -l | tr -d ' ')

if [[ "$PO_COUNT" -ne "$EXPECTED_LANGUAGE_COUNT" || "$MO_COUNT" -ne "$EXPECTED_LANGUAGE_COUNT" ]]; then
	echo "Expected $EXPECTED_LANGUAGE_COUNT PO/MO catalogs, found $PO_COUNT PO and $MO_COUNT MO." >&2
	exit 1
fi

if [[ "$PO_COUNT" -ne "$MO_COUNT" ]]; then
	echo "PO/MO catalog count mismatch: $PO_COUNT PO, $MO_COUNT MO." >&2
	exit 1
fi

find "$LANGUAGES_DIR" -maxdepth 1 -name '*.json' -print0 \
	| xargs -0 -n 1 jq empty

echo "Verified $PO_COUNT complete PO/MO catalogs and all JSON catalogs."
