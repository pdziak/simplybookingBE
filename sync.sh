#!/bin/bash

SOURCE="marszpozdrowie@s27.mydevil.net:~/domains/nordicwalkingworldleague.com/"
DEST="/volume1/marszpozdrowie/nordicwalkingworldleague.com"

# Wykonanie rsync
if rsync -az --delete -e ssh "$SOURCE" "$DEST"; then
  # Ustawienie uprawnień
  chown -R pdziak:users "$DEST"
  chmod -R u+rwX "$DEST"

  echo "✅ Backup zakończony sukcesem: $DEST"
else
  echo "❌ Błąd: rsync nie powiódł się dla $DEST"
  exit 1
fi