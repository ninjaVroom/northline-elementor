#!/usr/bin/env bash
# Booking smoke test: valid booking, same slot again (refused), unoffered slot (refused), double submit (same ref),
# slot shown as taken afterwards. Prints the booking ref; delete the record afterwards with tools/wp.sh post delete <id> --force.
set -u
BASE="${1:-http://northline.local}"
H=$(curl -s "$BASE/book/")
N=$(echo "$H" | grep -o 'name="nl_nonce" value="[^"]*"' | cut -d'"' -f4)
SLOT=$(echo "$H" | grep -o 'name="slot" value="[^"]*" required >' | head -1 | cut -d'"' -f4)
T="bk-$(date +%s)"
post() { curl -s -o /dev/null -w "%{redirect_url}" -X POST "$BASE/wp-admin/admin-post.php" \
	--data-urlencode action=nl_booking --data-urlencode "nl_nonce=$N" --data-urlencode "token=$1" \
	--data-urlencode "return=$BASE/book/" --data-urlencode "slot=$2" --data-urlencode name="Alex Test" \
	--data-urlencode email=alex@company.example --data-urlencode company="Test Plant Co" --data-urlencode consent=1; }
fail=0
expect() { case "$2" in *"$3"*) echo "pass  $1";; *) echo "FAIL  $1: $2"; fail=1;; esac; }
[ -n "$SLOT" ] || { echo "FAIL  no open slot found"; exit 1; }
first=$(post "$T" "$SLOT")
expect "valid booking ($SLOT)" "$first" "booked=NL-"
expect "double submit"        "$(post "$T" "$SLOT")" "${first#*booked=}"
expect "slot already taken"   "$(post "$T-2" "$SLOT")" "nl_error=slot"
expect "unoffered slot"       "$(post "$T-3" '2020-01-01 09:00')" "nl_error=slot"
curl -s "$BASE/book/" | grep -q "value=\"$SLOT\" required[^>]*disabled" && echo "pass  slot now shown as taken" || { echo "FAIL  slot not disabled"; fail=1; }
ref=${first#*booked=}; echo "ref: ${ref%%#*}"
exit $fail
