#!/usr/bin/env bash
#
# Sicherung und Wiederherstellung der Entwicklungsdatenbank.
#
#   scripts/dev-db-snapshot.sh save [name]     Snapshot anlegen (Standard: Zeitstempel)
#   scripts/dev-db-snapshot.sh restore <name>  Snapshot wieder einspielen
#   scripts/dev-db-snapshot.sh list            Vorhandene Snapshots anzeigen
#
# Snapshots liegen unter storage/backups/ (nicht im Git).
# Aus Sicherheitsgründen nur mit APP_ENV=local und nie im Produktionsverzeichnis.

set -euo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKUP_DIR="$PROJECT_DIR/storage/backups"
ENV_FILE="$PROJECT_DIR/.env"

# --- Sicherheitsprüfungen -------------------------------------------------

if [[ "$PROJECT_DIR" == /var/www/* ]]; then
    echo "Abbruch: Skript darf nicht im Produktionsverzeichnis laufen ($PROJECT_DIR)." >&2
    exit 1
fi

if [[ ! -f "$ENV_FILE" ]]; then
    echo "Abbruch: $ENV_FILE nicht gefunden." >&2
    exit 1
fi

# Wert aus der .env lesen (ohne Anführungszeichen, ohne die Datei auszuführen).
env_value() {
    grep -E "^$1=" "$ENV_FILE" | tail -n1 | cut -d= -f2- | sed -e 's/^["'\'']//' -e 's/["'\'']$//'
}

APP_ENV="$(env_value APP_ENV)"
DB_HOST="$(env_value DB_HOST)"
DB_PORT="$(env_value DB_PORT)"
DB_DATABASE="$(env_value DB_DATABASE)"
DB_USERNAME="$(env_value DB_USERNAME)"
DB_PASSWORD="$(env_value DB_PASSWORD)"

if [[ "$APP_ENV" != "local" ]]; then
    echo "Abbruch: APP_ENV ist „$APP_ENV“, erlaubt ist nur „local“." >&2
    exit 1
fi

# Zugangsdaten über eine temporäre Optionsdatei übergeben,
# damit das Passwort nicht in der Prozessliste auftaucht.
MYSQL_CNF="$(mktemp)"
trap 'rm -f "$MYSQL_CNF"' EXIT
chmod 600 "$MYSQL_CNF"
cat > "$MYSQL_CNF" <<EOF
[client]
host=${DB_HOST:-127.0.0.1}
port=${DB_PORT:-3306}
user=$DB_USERNAME
password=$DB_PASSWORD
EOF

mkdir -p "$BACKUP_DIR"

# --- Befehle --------------------------------------------------------------

cmd_save() {
    local name="${1:-$(date +%Y-%m-%d_%H%M%S)}"
    local file="$BACKUP_DIR/${DB_DATABASE}_${name}.sql.gz"

    echo "Sichere $DB_DATABASE nach $file …"
    mysqldump --defaults-extra-file="$MYSQL_CNF" \
        --single-transaction --routines --triggers --no-tablespaces \
        "$DB_DATABASE" | gzip > "$file"
    echo "Fertig ($(du -h "$file" | cut -f1))."
}

cmd_restore() {
    local name="${1:-}"
    if [[ -z "$name" ]]; then
        echo "Bitte Snapshot angeben. Vorhandene Snapshots:" >&2
        cmd_list >&2
        exit 1
    fi

    # Sowohl kompletter Dateiname als auch Kurzname werden akzeptiert.
    local file="$BACKUP_DIR/$name"
    [[ -f "$file" ]] || file="$BACKUP_DIR/${DB_DATABASE}_${name}.sql.gz"
    if [[ ! -f "$file" ]]; then
        echo "Snapshot nicht gefunden: $name" >&2
        exit 1
    fi

    read -r -p "Datenbank „$DB_DATABASE“ mit $(basename "$file") überschreiben? [j/N] " answer
    if [[ "$answer" != "j" && "$answer" != "J" ]]; then
        echo "Abgebrochen."
        exit 0
    fi

    # Vorher automatisch den aktuellen Stand sichern.
    cmd_save "vor-restore_$(date +%Y-%m-%d_%H%M%S)"

    echo "Spiele $(basename "$file") ein …"
    gunzip -c "$file" | mysql --defaults-extra-file="$MYSQL_CNF" "$DB_DATABASE"
    echo "Wiederherstellung abgeschlossen."
}

cmd_list() {
    ls -1t "$BACKUP_DIR"/*.sql.gz 2>/dev/null | xargs -r -n1 basename || true
}

case "${1:-}" in
    save)    cmd_save "${2:-}" ;;
    restore) cmd_restore "${2:-}" ;;
    list)    cmd_list ;;
    *)
        sed -n '3,9p' "$0" | sed 's/^# \{0,1\}//'
        exit 1
        ;;
esac
