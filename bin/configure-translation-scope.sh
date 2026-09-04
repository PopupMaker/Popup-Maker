#!/usr/bin/env bash

set -euo pipefail

LANGUAGES_DIR="${1:-languages}"
PLUGIN_SLUG="${2:-}"
CANONICAL_TARGETS_CSV="${3:-}"
CANONICAL_CATALOGS_CSV="${4:-}"
REQUESTED_TARGETS_CSV="${5:-}"

if [[ -z "$PLUGIN_SLUG" || -z "$CANONICAL_TARGETS_CSV" || -z "$CANONICAL_CATALOGS_CSV" ]]; then
	echo "Usage: $0 <languages-dir> <plugin-slug> <canonical-targets> <canonical-catalogs> [requested-targets]" >&2
	exit 1
fi

IFS=',' read -r -a CANONICAL_TARGETS <<< "$CANONICAL_TARGETS_CSV"
IFS=',' read -r -a CANONICAL_CATALOGS <<< "$CANONICAL_CATALOGS_CSV"

if [[ "${#CANONICAL_TARGETS[@]}" -ne "${#CANONICAL_CATALOGS[@]}" ]]; then
	echo "Canonical provider and catalog locale counts must match." >&2
	exit 1
fi

trim() {
	local value="$1"
	value="${value#"${value%%[![:space:]]*}"}"
	value="${value%"${value##*[![:space:]]}"}"
	printf '%s' "$value"
}

canonical_index_for_target() {
	local target="$1"
	local index

	for (( index = 0; index < ${#CANONICAL_TARGETS[@]}; index++ )); do
		if [[ "${CANONICAL_TARGETS[$index]}" == "$target" ]]; then
			printf '%s' "$index"
			return 0
		fi
	done

	return 1
}

canonical_index_for_catalog() {
	local catalog="$1"
	local index

	for (( index = 0; index < ${#CANONICAL_CATALOGS[@]}; index++ )); do
		if [[ "${CANONICAL_CATALOGS[$index]}" == "$catalog" ]]; then
			printf '%s' "$index"
			return 0
		fi
	done

	return 1
}

REQUESTED_SET=','

if [[ -n "$(trim "$REQUESTED_TARGETS_CSV")" ]]; then
	IFS=',' read -r -a REQUESTED_TARGETS <<< "$REQUESTED_TARGETS_CSV"

	for raw_target in "${REQUESTED_TARGETS[@]}"; do
		target=$(trim "$raw_target")

		if [[ -z "$target" ]]; then
			echo "Requested language entries must not be empty." >&2
			exit 1
		fi

		if index=$(canonical_index_for_target "$target"); then
			target=${CANONICAL_TARGETS[$index]}
		elif index=$(canonical_index_for_catalog "$target"); then
			target=${CANONICAL_TARGETS[$index]}
		else
			echo "Unsupported translation language: $target" >&2
			exit 1
		fi

		if [[ "$REQUESTED_SET" == *",$target,"* ]]; then
			echo "Requested translation languages must be unique: $target" >&2
			exit 1
		fi

		REQUESTED_SET+="$target,"
	done
fi

shopt -s nullglob
for po_file in "$LANGUAGES_DIR"/*.po; do
	filename=$(basename "$po_file")
	prefix="$PLUGIN_SLUG-"

	if [[ "$filename" != "$prefix"*.po ]]; then
		echo "Unexpected PO catalog: $po_file" >&2
		exit 1
	fi

	catalog=${filename#"$prefix"}
	catalog=${catalog%.po}

	if ! canonical_index_for_catalog "$catalog" >/dev/null; then
		echo "Unexpected PO catalog locale: $catalog" >&2
		exit 1
	fi
done

if [[ "$REQUESTED_SET" == ',' ]]; then
	ACTIVE_TARGETS_CSV="$CANONICAL_TARGETS_CSV"
	ACTIVE_CATALOGS_CSV="$CANONICAL_CATALOGS_CSV"
	ACTIVE_COUNT=${#CANONICAL_TARGETS[@]}
else
	ACTIVE_TARGETS_CSV=''
	ACTIVE_CATALOGS_CSV=''
	ACTIVE_COUNT=0

	for (( index = 0; index < ${#CANONICAL_TARGETS[@]}; index++ )); do
		target=${CANONICAL_TARGETS[$index]}
		catalog=${CANONICAL_CATALOGS[$index]}
		po_file="$LANGUAGES_DIR/$PLUGIN_SLUG-$catalog.po"

		if [[ -f "$po_file" || "$REQUESTED_SET" == *",$target,"* ]]; then
			if [[ -n "$ACTIVE_TARGETS_CSV" ]]; then
				ACTIVE_TARGETS_CSV+=','
				ACTIVE_CATALOGS_CSV+=','
			fi

			ACTIVE_TARGETS_CSV+="$target"
			ACTIVE_CATALOGS_CSV+="$catalog"
			ACTIVE_COUNT=$(( ACTIVE_COUNT + 1 ))
		fi
	done
fi

if [[ "$ACTIVE_COUNT" -eq 0 ]]; then
	echo "No translation languages were selected." >&2
	exit 1
fi

echo "target_languages=$ACTIVE_TARGETS_CSV"
echo "expected_catalog_locales=$ACTIVE_CATALOGS_CSV"
echo "expected_language_count=$ACTIVE_COUNT"
