#!/usr/bin/env bash

set -euo pipefail

LANGUAGES_DIR="${1:-languages}"
PO_COUNT=0
shopt -s nullglob
POT_FILES=( "$LANGUAGES_DIR"/*.pot )
PO_FILES=( "$LANGUAGES_DIR"/*.po )

if [[ "${#POT_FILES[@]}" -eq 0 && "${#PO_FILES[@]}" -eq 0 ]]; then
	echo "No translation catalogs to verify."
	exit 0
fi

if [[ "${#POT_FILES[@]}" -ne 1 ]]; then
	echo "Expected exactly one POT catalog in $LANGUAGES_DIR." >&2
	exit 1
fi

POT_FILE="${POT_FILES[0]}"
PLUGIN_SLUG=$(basename "$POT_FILE" .pot)

for po_file in "${PO_FILES[@]}"; do
	if [[ "$po_file" != "$LANGUAGES_DIR/$PLUGIN_SLUG-"*.po ]]; then
		continue
	fi

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

if [[ "$PO_COUNT" -ne "$MO_COUNT" ]]; then
	echo "PO/MO catalog count mismatch: $PO_COUNT PO, $MO_COUNT MO." >&2
	exit 1
fi

find "$LANGUAGES_DIR" -maxdepth 1 -name '*.json' -print0 \
	| xargs -0 -n 1 jq empty

echo "Verified $PO_COUNT complete PO/MO catalogs and all JSON catalogs."
