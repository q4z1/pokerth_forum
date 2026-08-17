#!/bin/bash
# Baut stylesheet.css aus stylesheet.imports.css zusammen.
#
# Warum: die Vorlage bindet 25 Dateien per @import ein. Der Browser kann eine
# @import-Kette nicht parallelisieren – er muss stylesheet.css laden, parsen,
# dann die erste Datei laden, parsen, und so weiter. Der Server spricht nur
# HTTP/1.1, damit summiert sich das auf viele Sekunden, in denen die Seite ohne
# responsive.css dasteht: die Navbar bleibt zweizeilig (128px) und klappt erst
# beim Eintreffen auf 70px zusammen. Genau dieser Sprung war der grösste
# CLS-Posten. Inline ist alles in einer Antwort da.
#
# Voraussetzung, hier geprüft: die Dateien unter responsive/ enthalten kein
# einziges url() – sonst würden ihre relativen Pfade beim Hochziehen brechen.
# Die Dateien auf gleicher Ebene behalten ihre Pfade ohnehin.
#
# Nach einem Style-Update erneut ausführen.

set -euo pipefail
cd "$(dirname "$0")"

SRC=stylesheet.imports.css
OUT=stylesheet.css

if [ ! -f "$SRC" ]; then
    echo "FEHLER: $SRC fehlt. Beim ersten Lauf: cp stylesheet.css $SRC" >&2
    exit 1
fi

{
    echo "/* Automatisch erzeugt von build-stylesheet.sh – nicht von Hand bearbeiten."
    echo "   Quelle: $SRC. Änderungen dort oder in den Einzeldateien vornehmen. */"
    echo

    # Kopfkommentar der Vorlage übernehmen, @import-Zeilen auflösen.
    while IFS= read -r line; do
        if [[ "$line" =~ @import[[:space:]]+url\(\"([^\"?]+) ]]; then
            f="${BASH_REMATCH[1]}"
            if [ -f "$f" ]; then
                echo "/* ===== $f ===== */"
                cat "$f"
                echo
            else
                echo "WARNUNG: $f nicht gefunden, @import bleibt stehen" >&2
                echo "$line"
            fi
        else
            echo "$line"
        fi
    done < "$SRC"
} > "$OUT.tmp"

mv "$OUT.tmp" "$OUT"
echo "$OUT gebaut: $(wc -c < "$OUT") Bytes aus $(grep -c '@import' "$SRC") Dateien"
