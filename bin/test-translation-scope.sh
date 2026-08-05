#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
SCOPE_SCRIPT="$SCRIPT_DIR/configure-translation-scope.sh"
TARGETS='es_ES,pt_BR,fr_FR,de_DE,ja,ru_RU,it_IT,nl_NL,pl_PL,tr_TR,id_ID,zh_CN,ar,sv_SE,ko_KR,vi,fa_IR,cs_CZ,pt_PT,hu_HU,es_MX,da_DK,zh_TW,he_IL,th,ro_RO,el,hi_IN'
CATALOGS='es_ES,pt_BR,fr_FR,de_DE,ja_JP,ru_RU,it_IT,nl_NL,pl_PL,tr_TR,id_ID,zh_CN,ar_AR,sv_SE,ko_KR,vi_VN,fa_IR,cs_CZ,pt_PT,hu_HU,es_MX,da_DK,zh_TW,he_IL,th_TH,ro_RO,el_GR,hi_IN'
TMP_DIR=$(mktemp -d)
trap 'rm -rf "$TMP_DIR"' EXIT

mkdir -p "$TMP_DIR/languages"

full_output=$(bash "$SCOPE_SCRIPT" "$TMP_DIR/languages" example "$TARGETS" "$CATALOGS" '')
grep -Fxq "target_languages=$TARGETS" <<< "$full_output"
grep -Fxq "expected_catalog_locales=$CATALOGS" <<< "$full_output"
grep -Fxq 'expected_language_count=28' <<< "$full_output"

touch "$TMP_DIR/languages/example-ja_JP.po"
partial_output=$(bash "$SCOPE_SCRIPT" "$TMP_DIR/languages" example "$TARGETS" "$CATALOGS" ' ar, vi ')
grep -Fxq 'target_languages=ja,ar,vi' <<< "$partial_output"
grep -Fxq 'expected_catalog_locales=ja_JP,ar_AR,vi_VN' <<< "$partial_output"
grep -Fxq 'expected_language_count=3' <<< "$partial_output"

if bash "$SCOPE_SCRIPT" "$TMP_DIR/languages" example "$TARGETS" "$CATALOGS" 'not_a_locale' >/dev/null 2>&1; then
	echo "Unsupported languages must be rejected." >&2
	exit 1
fi

echo 'Translation scope selection verified.'
