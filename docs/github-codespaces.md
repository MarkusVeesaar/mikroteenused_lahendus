# GitHub Codespaces

See juhend aitab käivitada mikroteenuste lahenduse GitHub Codespaces keskkonnas.

## Eeldused

- Repo on GitHubis (Codespaces loob devcontaineri `.devcontainer/devcontainer.json` põhjal).
- Codespace kasutab Docker-in-Docker feature’it, et `docker compose` töötaks.

## Sammud

1. Ava repo GitHubis → **Code** → **Codespaces** → **Create codespace on main** (või valitud haru).
2. Oota, kuni devcontainer valmib. `postCreateCommand` kopeerib `.env.example` → `.env`, kui `.env` puudub.
3. Terminalis repo juures:

   ```bash
   docker compose up --build -d
   ```

4. Esimene build võib võtta mitu minutit (3 PHP teenust + 3 MySQL andmebaasi).
5. Ava rakendus:
   - **Ports** vahekaardil klõpsa **8080** (loans-service) → **Open in Browser**, või
   - terminalis: `curl -s http://localhost:8080/health`

## Kontrollnimekiri

```bash
curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/health   # oodatav 200
curl -s -o /dev/null -w "%{http_code}" http://localhost:8081/health   # oodatav 200
curl -s -o /dev/null -w "%{http_code}" http://localhost:8082/health   # oodatav 200

docker compose ps   # 6 konteinerit
```

Sisselogimine (token test):

```bash
curl -s -X POST http://localhost:8080/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"student@kool.ee","password":"student123"}'
```

Notifications (sisemine võti `.env` failist `INTERNAL_API_KEY`):

```bash
# Ilma võtmeta — oodatav 401
curl -s -o /dev/null -w "%{http_code}" http://localhost:8082/notifications

# Võtmega — oodatav 200
curl -s -o /dev/null -w "%{http_code}" http://localhost:8082/notifications \
  -H "X-Internal-Api-Key: $(grep INTERNAL_API_KEY .env | cut -d= -f2)"
```

Testkontod on kirjeldatud repo juure README failis.

## Portide edastamine

Codespaces edastab localhost pordid automaatselt. Brauseris avatud URL kasutab GitHubi HTTPS proxyt; terminalis `curl localhost:8080` töötab otse Codespace’i sees.

## Tõrkeotsing

| Probleem | Lahendus |
|----------|----------|
| `Cannot connect to the Docker daemon` | Oota devcontaineri täielikku käivitumist; taaskäivita Codespace vajadusel. |
| `.env` puudub | `cp .env.example .env` |
| Port 8080 ei vasta | `docker compose ps`; oota MySQL healthcheck’i; vaata `docker compose logs loans-service` |
| Sisselogimisel "Sisemine viga" / DB timeout | Käivita `sudo sysctl -w net.bridge.bridge-nf-call-iptables=0` ja `sudo iptables-legacy -P FORWARD ACCEPT` (Codespaces devcontaineri võrgusilla pakettide lubamiseks). |
| Build ebaõnnestub | `docker compose build --no-cache` ja uuesti `up -d` |

## Peatamine

```bash
docker compose down
```
