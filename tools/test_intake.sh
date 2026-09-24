#!/usr/bin/env bash
# Intake smoke test against the local site: invalid email, valid submit, idempotent repeat, honeypot, forged nonce.
# Usage: tools/test_intake.sh [base_url]   (creates one private nl_inquiry record; delete it afterwards)
set -u
BASE="${1:-http://northline.local}"
H=$(curl -s "$BASE/contact/")
N=$(echo "$H" | grep -o 'name="nl_nonce" value="[^"]*"' | cut -d'"' -f4)
T="test-$(date +%s)"
post() { curl -s -o /dev/null -w "%{redirect_url}" -X POST "$BASE/wp-admin/admin-post.php" \
	--data-urlencode action=nl_inquiry --data-urlencode "nl_nonce=$2" --data-urlencode "token=$1" \
	--data-urlencode "return=$BASE/contact/" --data-urlencode "interest=SMED Changeover Reduction" \
	--data-urlencode "email=$3" --data-urlencode company="Test Plant Co" --data-urlencode role="Plant manager" \
	--data-urlencode plants=3 --data-urlencode consent=1 ${4:+--data-urlencode "$4"}; }
fail=0
expect() { case "$2" in *"$3"*) echo "pass  $1";; *) echo "FAIL  $1: $2"; fail=1;; esac; }
expect "invalid email"  "$(post "$T" "$N" 'alex@')" "nl_error=email"
first=$(post "$T" "$N" 'alex@company.example')
expect "valid submit"   "$first" "inquiry=NL-"
expect "idempotent"     "$(post "$T" "$N" 'alex@company.example')" "${first#*inquiry=}"
expect "honeypot"       "$(post hp "$N" 'bot@x.example' 'website=spam')" "inquiry=NL-$(date +%Y)-0"
expect "forged nonce"   "$(post t2 bad 'a@b.example')" "nl_error=nonce"
exit $fail
