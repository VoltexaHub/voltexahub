#!/bin/bash
# warm-pool.sh — maintain N pre-provisioned forum instances ready to assign
# Run via cron: */5 * * * * /opt/voltexahub/app/voltexahub/docker/warm-pool.sh
set -e

POOL_SIZE="${WARM_POOL_SIZE:-2}"
BASE_DIR="/opt/voltexahub"
POOL_DIR="${BASE_DIR}/pool"
POOL_STATE="${POOL_DIR}/state.json"

mkdir -p "${POOL_DIR}"

# Wordlist for readable single-word subdomains
WORDS=(
  amber apex arc ash azure beacon blade blaze bloom bolt
  brook burst cedar chrome cliff cinder cobalt comet coral
  crest crisp crown dawn delta drift dusk echo ember falcon
  flare flint flux forge frost gale gem glow grove haze
  horizon hue jade jet karma kindle lark lava lumen lunar
  lynx maple mesa mint mist nova opal orbit pearl pine
  pixel prism pulse quartz ray reef ridge rune sage scout
  shale shard sierra silk silver slate solar spark spruce
  stark steel stone storm surge swift terra tide timber
  titan topaz trace trail tundra vale vapor vault veil
  verge vesper vine volt wave willow zenith zephyr zinc
)

# Pick a random unused word
pick_subdomain() {
  local used=()
  if [ -f "${POOL_STATE}" ]; then
    mapfile -t used < <(python3 -c "import json,sys; d=json.load(open('${POOL_STATE}')); print('\n'.join(d.get('used',[])+d.get('active',[])+d.get('warm',[]))) " 2>/dev/null || true)
  fi

  for word in $(printf "%s\n" "${WORDS[@]}" | shuf); do
    local taken=false
    for u in "${used[@]}"; do
      [ "$u" = "$word" ] && taken=true && break
    done
    if ! $taken; then
      echo "$word"
      return
    fi
  done
  # Fallback: word + random suffix
  echo "${WORDS[$RANDOM % ${#WORDS[@]}]}-$(openssl rand -hex 2)"
}

# Count warm (unassigned) instances
warm_count() {
  if [ ! -f "${POOL_STATE}" ]; then echo 0; return; fi
  python3 -c "import json; d=json.load(open('${POOL_STATE}')); print(len(d.get('warm',[])))" 2>/dev/null || echo 0
}

# Add instance to state
register_warm() {
  local subdomain="$1"
  python3 - <<PYEOF
import json, os
path = "${POOL_STATE}"
d = json.load(open(path)) if os.path.exists(path) else {"warm":[],"used":[],"active":[]}
d.setdefault("warm",[]).append("${subdomain}")
json.dump(d, open(path,"w"), indent=2)
PYEOF
}

# Main loop
CURRENT=$(warm_count)
NEEDED=$((POOL_SIZE - CURRENT))

if [ "$NEEDED" -le 0 ]; then
  echo "[warm-pool] Pool healthy (${CURRENT}/${POOL_SIZE} warm instances)"
  exit 0
fi

echo "[warm-pool] Need ${NEEDED} more warm instance(s) (have ${CURRENT}/${POOL_SIZE})"

for i in $(seq 1 "$NEEDED"); do
  SUBDOMAIN=$(pick_subdomain)
  echo "[warm-pool] Provisioning warm instance: ${SUBDOMAIN}"
  "${BASE_DIR}/app/voltexahub/docker/provision.sh" "${SUBDOMAIN}" 2>&1 | sed "s/^/  [${SUBDOMAIN}] /"
  register_warm "${SUBDOMAIN}"
  echo "[warm-pool] ✅ ${SUBDOMAIN}.voltexahub.com is warm and ready"
done
