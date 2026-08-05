#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
PREPARE_SCRIPT="$SCRIPT_DIR/prepare-translation-catalogs.sh"
EXPECTED_LOCALES='es_ES,pt_BR,fr_FR,de_DE,ja_JP,ru_RU,it_IT,nl_NL,pl_PL,tr_TR,id_ID,zh_CN,ar_AR,sv_SE,ko_KR,vi_VN,fa_IR,cs_CZ,pt_PT,hu_HU,es_MX,da_DK,zh_TW,he_IL,th_TH,ro_RO,el_GR,hi_IN'
TMP_DIR=$(mktemp -d)
trap 'rm -rf "$TMP_DIR"' EXIT

make_pot() {
	local output="$1"
	local count="$2"
	local index

	{
		printf 'msgid ""\nmsgstr ""\n'
		for (( index = 1; index <= count; index++ )); do
			printf '\nmsgid "Source %s"\nmsgstr ""\n' "$index"
		done
	} > "$output"
}

assert_output() {
	local output_file="$1"
	local expected="$2"

	if ! grep -Fxq "$expected" "$output_file"; then
		echo "Missing expected output: $expected" >&2
		cat "$output_file" >&2
		exit 1
	fi
}

run_preflight() {
	local fixture="$1"
	local output_file="$fixture/output"

	GITHUB_OUTPUT="$output_file" bash "$PREPARE_SCRIPT" \
		"$fixture/languages" \
		75 \
		2100 \
		28 \
		"$EXPECTED_LOCALES" \
		>/dev/null
}

mkdir -p "$TMP_DIR/bootstrap/languages"
make_pot "$TMP_DIR/bootstrap/languages/example.pot" 2
run_preflight "$TMP_DIR/bootstrap"
assert_output "$TMP_DIR/bootstrap/output" 'catalog_count=0'
assert_output "$TMP_DIR/bootstrap/output" 'missing_catalogs=28'
assert_output "$TMP_DIR/bootstrap/output" 'total_missing=56'
assert_output "$TMP_DIR/bootstrap/output" 'max_missing=2'
assert_output "$TMP_DIR/bootstrap/output" 'needs_translation=true'
assert_output "$TMP_DIR/bootstrap/output" 'within_limits=true'

mkdir -p "$TMP_DIR/oversize/languages"
make_pot "$TMP_DIR/oversize/languages/example.pot" 76
run_preflight "$TMP_DIR/oversize"
assert_output "$TMP_DIR/oversize/output" 'total_missing=2128'
assert_output "$TMP_DIR/oversize/output" 'max_missing=76'
assert_output "$TMP_DIR/oversize/output" 'within_limits=false'

mkdir -p "$TMP_DIR/unexpected/languages"
make_pot "$TMP_DIR/unexpected/languages/example.pot" 1
msgen "$TMP_DIR/unexpected/languages/example.pot" \
	-o "$TMP_DIR/unexpected/languages/example-not_configured.po"
run_preflight "$TMP_DIR/unexpected"
assert_output "$TMP_DIR/unexpected/output" 'unexpected_catalogs=1'
assert_output "$TMP_DIR/unexpected/output" 'within_limits=false'

echo 'Translation preflight accounting verified.'
