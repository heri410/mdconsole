#!/bin/bash




trap "kill $NGROK_PID" EXIT

# Starte ngrok im Hintergrund für Port 8000
ngrok http 8000 > /dev/null &
NGROK_PID=$!

# Warte bis zu 30 Sekunden auf die ngrok-URL
for i in {1..30}; do
  NGROK_URL=$(curl -s http://localhost:4040/api/tunnels | jq -r '.tunnels[0].public_url // empty')
  if [[ $NGROK_URL == https://* ]]; then
    break
  fi
  sleep 1
done

if [ -z "$NGROK_URL" ]; then
  echo "ngrok URL konnte nicht ermittelt werden!"
  kill $NGROK_PID
  exit 1
fi

echo "ngrok läuft unter: $NGROK_URL"

# Trage die ngrok-URL als APP_URL in die .env ein
if grep -q '^APP_URL=' .env; then
  sed -i "s|^APP_URL=.*$|APP_URL=$NGROK_URL|" .env
else
  echo "APP_URL=$NGROK_URL" >> .env
fi

echo "Registriere Lexoffice Webhooks"
php artisan lexoffice:webhook delete
php artisan lexoffice:webhook create

echo "\nÖffne diese URL im Browser, um die Laravel-Seite zu sehen:"
echo "$NGROK_URL"

# Starte den Laravel Webserver
php artisan serve --host=0.0.0.0 --port=8000

# Beende ngrok, wenn das Skript/Webserver beendet wird
trap "kill $NGROK_PID" EXIT
