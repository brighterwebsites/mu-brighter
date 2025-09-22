#!/usr/bin/env bash
# Month-aware, quota-safe LiteSpeed queue kicker
# Usage: run-lsc-quotas-monthly.sh /abs/path/to/site

set -euo pipefail

SITE_PATH="${1:-}"
[[ -n "$SITE_PATH" && -d "$SITE_PATH" ]] || { echo "Invalid site path"; exit 1; }

PHP_BIN="/usr/local/lsws/lsphp83/bin/php"
WP="/usr/bin/wp"
PHP_OPTS="-d memory_limit=256M -d max_execution_time=300"

# OFFICIAL LIMITS — edit if your plan changes
DAILY_IMG=1000;  MONTH_IMG=10000
DAILY_PO=90;     MONTH_PO=2000          # Page Optimisation total (CCSS+UCSS+VPI combined)
DAILY_LQIP=45;   MONTH_LQIP=1000

# Split PO across CCSS/UCSS/VPI (must total 100)
W_CCSS=45; W_UCSS=35; W_VPI=20

# Derive site name and state paths
SITE_NAME="$(basename "$(dirname "$SITE_PATH")")"
STATE_DIR="/var/lib/lscq"
STATE_FILE="${STATE_DIR}/usage-${SITE_NAME}.json"
LOCK="/tmp/lscq-${SITE_NAME}.lock"
LOG="/var/log/wp-lscq-${SITE_NAME}.log"

# Init state if missing or month rolled
MONTH_NOW="$(date +%Y-%m)"
if [[ ! -f "$STATE_FILE" ]] || ! grep -q "\"month\":\"$MONTH_NOW\"" "$STATE_FILE" 2>/dev/null; then
  cat >"$STATE_FILE" <<EOF
{"month":"$MONTH_NOW","img":0,"po":0,"lqip":0}
EOF
fi

# Read current usage
IMG_USED=$("$PHP_BIN" -r "\$j=json_decode(file_get_contents('$STATE_FILE'),true); echo \$j['img'];")
PO_USED=$("$PHP_BIN" -r  "\$j=json_decode(file_get_contents('$STATE_FILE'),true); echo \$j['po'];")
LQIP_USED=$("$PHP_BIN" -r "\$j=json_decode(file_get_contents('$STATE_FILE'),true); echo \$j['lqip'];")

# Days remaining including today
DAY=$(date +%d)
DAYS_IN_MONTH=$(cal | awk 'NF {D=$NF} END{print D}')
DAYS_LEFT=$((DAYS_IN_MONTH - DAY + 1))

# Budget calculators with buffer (keep 2 days in hand)
buffer_days=2
safe_days=$(( DAYS_LEFT > buffer_days ? DAYS_LEFT - buffer_days : 1 ))

calc_budget () {
  local daily="$1" monthly="$2" used="$3"
  local remaining=$(( monthly - used ))
  if (( remaining <= 0 )); then echo 0; return; fi
  local per_day=$(( remaining / safe_days ))
  # At least 1 if we still have monthly left
  if (( per_day < 1 )); then per_day=1; fi
  # Never exceed daily cap
  if (( per_day > daily )); then per_day=$daily; fi
  echo $per_day
}

BUDGET_IMG=$(calc_budget "$DAILY_IMG" "$MONTH_IMG" "$IMG_USED")
BUDGET_PO=$(calc_budget "$DAILY_PO"  "$MONTH_PO"  "$PO_USED")
BUDGET_LQIP=$(calc_budget "$DAILY_LQIP" "$MONTH_LQIP" "$LQIP_USED")

# Split PO across tasks
CAP_CCSS=$(( BUDGET_PO * W_CCSS / 100 ))
CAP_UCSS=$(( BUDGET_PO * W_UCSS / 100 ))
CAP_VPI=$((  BUDGET_PO * W_VPI  / 100 ))
# Ensure at least 1 where there is any PO budget
if (( BUDGET_PO > 0 )); then
  (( CAP_CCSS<1 )) && CAP_CCSS=1
  (( CAP_UCSS<1 )) && CAP_UCSS=1
  (( CAP_VPI<1  )) && CAP_VPI=1
fi

# IMG caps apply to both req and pull, split roughly in half
CAP_IMG_REQ=$(( BUDGET_IMG / 2 ))
CAP_IMG_PULL=$(( BUDGET_IMG - CAP_IMG_REQ ))

# Lock to prevent overlap
exec 9>"$LOCK"
if ! flock -n 9; then exit 0; fi

has_event () {
  local hook="$1"
  "$PHP_BIN" $PHP_OPTS "$WP" --path="$SITE_PATH" --allow-root cron event list \
    --fields=hook --format=csv 2>/dev/null | tail -n +2 | grep -q "^${hook}$"
}


run_n () {
  local n="$1" hook="$2" count=0
  if ! has_event "$hook"; then
    echo "$(date -Is) $hook not scheduled, skipping" >> "$LOG"
    echo 0
    return 0
  fi
  while (( count < n )); do
    if ! "$PHP_BIN" $PHP_OPTS "$WP" --path="$SITE_PATH" --allow-root cron event run "$hook" --quiet 2>/dev/null ; then
      break
    fi
    ((count++))
    sleep 2
  done
  echo "$(date -Is) $hook processed $count items" >> "$LOG"
  echo "$count"
}

{
  echo "========== $(date -Is) start $SITE_NAME ($SITE_PATH) =========="
  echo "Budgets: PO=$BUDGET_PO (CCSS=$CAP_CCSS, UCSS=$CAP_UCSS, VPI=$CAP_VPI)  LQIP=$BUDGET_LQIP  IMG=$BUDGET_IMG (req=$CAP_IMG_REQ pull=$CAP_IMG_PULL)"

  # Nudge stale metadata a touch
  "$PHP_BIN" $PHP_OPTS "$WP" --path="$SITE_PATH" --allow-root eval "do_action('litespeed_clean_by_kind','ccss');" || true
  "$PHP_BIN" $PHP_OPTS "$WP" --path="$SITE_PATH" --allow-root eval "do_action('litespeed_clean_by_kind','ucss');" || true

  # Run within caps
  DID_CCSS=$(run_n "$CAP_CCSS"   litespeed_task_ccss)
  DID_UCSS=$(run_n "$CAP_UCSS"   litespeed_task_ucss)
  DID_VPI=$(run_n "$CAP_VPI"     litespeed_task_vpi)
  DID_LQIP=$(run_n "$BUDGET_LQIP" litespeed_task_lqip)
  DID_IMG1=$(run_n "$CAP_IMG_REQ"  litespeed_task_imgoptm_req)
  DID_IMG2=$(run_n "$CAP_IMG_PULL" litespeed_task_imgoptm_pull)
  run_n 3 litespeed_task_crawler >/dev/null

  # Update state
NEW_PO=$(( PO_USED + DID_CCSS + DID_UCSS + DID_VPI ))
NEW_LQIP=$(( LQIP_USED + DID_LQIP ))
NEW_IMG=$(( IMG_USED + DID_IMG1 + DID_IMG2 ))

"$PHP_BIN" -r '
  $f = $argv[1];
  $m = $argv[2];
  $img = (int)$argv[3];
  $po = (int)$argv[4];
  $lqip = (int)$argv[5];
  file_put_contents($f, json_encode([
    "month" => $m,
    "img"   => $img,
    "po"    => $po,
    "lqip"  => $lqip
  ], JSON_UNESCAPED_SLASHES));
' "$STATE_FILE" "$MONTH_NOW" "$NEW_IMG" "$NEW_PO" "$NEW_LQIP"


  # Final sweep
  "$PHP_BIN" $PHP_OPTS "$WP" --path="$SITE_PATH" --allow-root cron event run --due-now --quiet || true

  echo "Usage now: IMG=$NEW_IMG/$MONTH_IMG  PO=$NEW_PO/$MONTH_PO  LQIP=$NEW_LQIP/$MONTH_LQIP"
  echo "========== $(date -Is) end $SITE_NAME =========="
} >> "$LOG" 2>&1
