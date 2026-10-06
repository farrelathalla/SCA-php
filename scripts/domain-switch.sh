#!/bin/sh
# Which site answers on saiga-conservation.org: the old WordPress or the new site.
#
#   sh ~/domain-switch/switch.sh status      show the current state (changes nothing)
#   sh ~/domain-switch/switch.sh wordpress   the OLD WordPress site is public; the new site stays on staging.
#   sh ~/domain-switch/switch.sh newsite     GO LIVE: the NEW site is public on saiga-conservation.org
#
# Everything is reversible and nothing is deleted. The switch itself is one
# atomic rename of the public_html symlink, so there is no moment without a site.
#
#   public_html -> archive.saiga-conservation.org      (WordPress, never moved)
#   public_html -> staging.saiga-conservation.org/public  (the new site)
#
# Besides the symlink it flips, for the new site only:
#   app/config.local.php   site_url + noindex   (canonical URLs, sitemap, Google, analytics)
#   go-live.flag           lets public/.htaccess redirect staging./www. to the main domain
#
# The WordPress .htaccess starts with the block in scripts/wordpress.htaccess,
# which reads the same flag: without it, /admin on the main domain goes to
# staging. and archive. is switched off (redirects to the main domain).
#
# Server only, in /home3/saiga/domain-switch/. No secrets in this file.

set -eu

H=/home3/saiga
NEW=$H/staging.saiga-conservation.org
WP=$H/archive.saiga-conservation.org
LINK=$H/public_html
CONF=$NEW/app/config.local.php
FLAG=$NEW/go-live.flag
BACKUPS=$H/domain-switch/backups
MAIN=https://saiga-conservation.org
STAGING=https://staging.saiga-conservation.org

die() { echo "ABORT: $*" >&2; exit 1; }

state() {
  case "$(readlink "$LINK" 2>/dev/null || true)" in
    "$WP")          echo wordpress ;;
    "$NEW/public")  echo newsite ;;
    *)              echo unknown ;;
  esac
}

# "<http code> <title or note>" for a URL, following no redirects.
probe() {
  code=$(curl -s -o /tmp/switch-probe.$$ -w '%{http_code}' --max-time 20 "$1" || echo 000)
  note=$(grep -o -i '<title>[^<]*' /tmp/switch-probe.$$ 2>/dev/null | head -1 | sed 's/<[tT][iI][tT][lL][eE]>//' | cut -c1-70)
  loc=$(curl -s -o /dev/null -w '%{redirect_url}' --max-time 20 "$1" || true)
  rm -f /tmp/switch-probe.$$
  echo "$code ${loc:+-> $loc }${note:-}"
}

status() {
  echo "public_html -> $(readlink "$LINK")   [state: $(state)]"
  echo "go-live.flag: $([ -f "$FLAG" ] && echo present || echo absent)"
  echo "config: $(grep -E "'(noindex|site_url)'" "$CONF" | tr -s ' ' | tr '\n' ' ')"
  echo "main    $MAIN/    : $(probe "$MAIN/")"
  echo "staging $STAGING/ : $(probe "$STAGING/")"
  echo "archive https://archive.saiga-conservation.org/ : $(probe https://archive.saiga-conservation.org/)"
}

set_config() { # $1 = live|staging
  tmp=$(mktemp)
  # Drop any site_url line, then write the two settings for the wanted state.
  grep -v "'site_url'" "$CONF" > "$tmp"
  if [ "$1" = live ]; then
    sed -i "s/'noindex' => [a-z]*,/'noindex' => false,/" "$tmp"
    sed -i "/'noindex' =>/a\\    'site_url' => '$MAIN'," "$tmp"
  else
    sed -i "s/'noindex' => [a-z]*,/'noindex' => true,/" "$tmp"
  fi
  php -l "$tmp" >/dev/null || { rm -f "$tmp"; die "the new config.local.php would not parse; nothing changed"; }
  cat "$tmp" > "$CONF"   # keep the file's owner and permissions
  rm -f "$tmp"
}

point_to() { # atomic: build the new link beside the old one, then rename over it
  ln -s "$1" "$LINK.new"
  mv -T "$LINK.new" "$LINK"
}

backup() {
  d=$BACKUPS/$(date +%Y%m%d-%H%M%S)-before-$1
  mkdir -p "$d"
  cp -p "$CONF" "$d/config.local.php"
  cp -p "$WP/wp-config.php" "$d/wp-config.php"
  cp -p "$NEW/public/.htaccess" "$d/new-site.htaccess"
  readlink "$LINK" > "$d/public_html.link"
  echo "backup: $d"
}

preflight() {
  [ -d "$NEW/public" ] && [ -f "$NEW/public/index.php" ] || die "new site files not found in $NEW/public"
  [ -d "$WP" ] && [ -f "$WP/wp-config.php" ] && [ -f "$WP/index.php" ] || die "WordPress files not found in $WP"
  [ -L "$LINK" ] || die "$LINK is not a symlink (expected one); will not touch it"
  [ -f "$CONF" ] || die "$CONF is missing"
  grep -q "WP_HOME" "$WP/wp-config.php" || die "wp-config.php has no WP_HOME logic; run the one-time setup first"
}

case "${1:-status}" in
  status) status ;;

  wordpress)
    preflight
    [ "$(state)" = wordpress ] && { echo "Already on WordPress."; status; exit 0; }
    backup wordpress
    # 1. the new site stops presenting itself as the main domain ...
    set_config staging
    # 2. ... then the main domain goes to WordPress in one atomic step.
    rm -f "$FLAG"
    point_to "$WP"
    echo "Switched: saiga-conservation.org now shows the OLD WordPress site."
    status
    ;;

  newsite)
    preflight
    [ "$(state)" = newsite ] && { echo "Already live."; status; exit 0; }
    backup newsite
    set_config live
    touch "$FLAG"
    point_to "$NEW/public"
    echo "Switched: saiga-conservation.org now shows the NEW site (live)."
    status
    ;;

  *) die "usage: switch.sh status|wordpress|newsite" ;;
esac
