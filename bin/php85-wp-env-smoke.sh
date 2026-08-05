#!/usr/bin/env bash

set -euo pipefail

config_path="${1:-.wp-env.php85-artifact.json}"
base_url="${2:-http://127.0.0.1:8895}"
smoke_tmp_dir="$(mktemp -d)"
popup_id=""

wp_env_run() {
	pnpm exec wp-env run cli --config="$config_path" "$@"
}

cleanup() {
	if [[ -n "$popup_id" ]]; then
		wp_env_run wp eval "wp_delete_post( ${popup_id}, true );" >/dev/null 2>&1 || true
	fi
	rm -rf "$smoke_tmp_dir"
}
trap cleanup EXIT

wp_env_run sh -lc 'rm -f /var/www/html/wp-content/debug.log'

runtime_output="$(wp_env_run wp eval-file wp-content/php85-smoke/runtime.php)"
printf '%s\n' "$runtime_output"
popup_id="$(printf '%s\n' "$runtime_output" | sed -n 's/^PHP85_SMOKE_POPUP_ID=\([0-9][0-9]*\)$/\1/p' | tail -n 1)"

if [[ -z "$popup_id" ]]; then
	echo 'Could not determine the runtime smoke popup ID.' >&2
	exit 1
fi

frontend_status="$(curl --silent --show-error --output "$smoke_tmp_dir/frontend.html" --write-out '%{http_code}' "$base_url/")"
[[ "200" == "$frontend_status" ]]
grep -q "popmake-${popup_id}" "$smoke_tmp_dir/frontend.html"

rest_status="$(curl --silent --show-error --output "$smoke_tmp_dir/rest.json" --write-out '%{http_code}' "$base_url/wp-json/")"
[[ "200" == "$rest_status" ]]
jq -e '.namespaces | index("pum/v1")' "$smoke_tmp_dir/rest.json" >/dev/null

ajax_status="$(curl --silent --show-error --output "$smoke_tmp_dir/ajax.json" --write-out '%{http_code}' \
	--data-urlencode 'action=pum_analytics' \
	--data-urlencode "pid=${popup_id}" \
	--data-urlencode 'event=open' \
	--data-urlencode 'method=json' \
	"$base_url/wp-admin/admin-ajax.php")"
[[ "200" == "$ajax_status" ]]

cron_status="$(curl --silent --show-error --output "$smoke_tmp_dir/cron.txt" --write-out '%{http_code}' \
	"$base_url/wp-cron.php?doing_wp_cron=php85-smoke")"
[[ "200" == "$cron_status" ]]

curl --silent --show-error --location \
	--cookie-jar "$smoke_tmp_dir/cookies.txt" \
	--data-urlencode 'log=admin' \
	--data-urlencode 'pwd=php85-runtime-smoke' \
	--data-urlencode 'wp-submit=Log In' \
	--data-urlencode "redirect_to=${base_url}/wp-admin/" \
	--output "$smoke_tmp_dir/login.html" \
	"$base_url/wp-login.php"

admin_status="$(curl --silent --show-error --location \
	--cookie "$smoke_tmp_dir/cookies.txt" \
	--output "$smoke_tmp_dir/admin.html" \
	--write-out '%{http_code}' \
	"$base_url/wp-admin/edit.php?post_type=popup")"
[[ "200" == "$admin_status" ]]
grep -q 'PHP 8.5 Runtime Smoke Popup Updated' "$smoke_tmp_dir/admin.html"

debug_log="$(wp_env_run sh -lc 'test ! -f /var/www/html/wp-content/debug.log || cat /var/www/html/wp-content/debug.log')"
printf '%s\n' "$debug_log"

if printf '%s\n' "$debug_log" | grep -E 'PHP (Deprecated|Warning|Notice|Fatal error|Parse error).*/wp-content/plugins/popup-maker[^/]*/'; then
	echo 'First-party PHP runtime diagnostics were written to debug.log.' >&2
	exit 1
fi

echo 'PHP 8.5 wp-env runtime smoke passed.'
