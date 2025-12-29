# Logowanie w Railway - Wyjaśnienie

## Problem: Dlaczego w Railway widzisz tylko metadane z RapidAPI Gateway?

### Co widziałeś wcześniej:

Logi które widziałeś w Railway wyglądały tak:
```json
{
  "requestId": "t7eujipKR6aK_zdDw9P4nw",
  "timestamp": "2025-12-29T20:45:01.457028871Z",
  "method": "POST",
  "path": "/v1/validate/nip",
  "httpStatus": 200,
  "totalDuration": 458,
  "txBytes": 40,
  "rxBytes": 1302
}
```

**To są logi z RapidAPI Gateway**, nie z Twojej aplikacji!

### Dlaczego?

**RapidAPI działa jako proxy:**
1. Użytkownik → RapidAPI Gateway → Twoje API (Railway)
2. RapidAPI Gateway loguje metadane requestów
3. Railway pokazuje tylko podstawowe logi PHP built-in servera

**PHP built-in server** (który używasz) loguje tylko:
- Informacje o połączeniu (Accepted, Closing)
- Błędy PHP
- Nie loguje automatycznie requestów/responses z pełnym ciałem

## Rozwiązanie: Dodane logowanie w aplikacji

### Co zostało dodane:

Middleware logujący który zapisuje:
- **REQUEST:** metoda, path, IP klienta, raw body, parsed body
- **RESPONSE:** status code, czas wykonania, pełne body odpowiedzi

### Przykład logów które teraz widzisz w Railway:

```
[REQUEST] POST /v1/validate/nip | IP: :: | Body (raw): {"value":"123-456-32-18"} | Body (parsed): {"value":"123-456-32-18"}
[RESPONSE] POST /v1/validate/nip | Status: 200 | Duration: 3.82ms | Body: {"valid":true,"normalized":"1234563218"}
```

### Jak to działa:

1. **Middleware logujący** jest dodany PRZED BodyParsingMiddleware
2. Zapisuje raw body przed parsowaniem
3. Po wykonaniu requestu zapisuje pełną odpowiedź
4. Wszystko idzie przez `error_log()` → widoczne w Railway logs

### Gdzie znaleźć logi:

**WAŻNE: Railway ma dwa typy logów!**

1. **HTTP Logs** (zakładka w service details) - to są logi z Railway Gateway/proxy
   - Pokazują tylko metadane (requestId, duration, bytes, status)
   - **To NIE są logi z aplikacji!**

2. **Logs** (główna zakładka w service) - to są logi z aplikacji (stdout/stderr)
   - Tu są logi z `error_log()` - pełne requesty i responses
   - **To są logi z Twojej aplikacji!**

**Jak znaleźć pełne logi z aplikacji w Railway Dashboard:**

**Sposób 1: Przez główną zakładkę "Logs"**
1. W Railway Dashboard, kliknij na service "pl-id-validator-api"
2. U góry są zakładki: **"Architecture"**, **"Observability"**, **"Logs"**, **"Settings"**
3. Kliknij na **"Logs"** (główna zakładka, nie ta w service details!)
4. Tutaj zobaczysz pełne logi z aplikacji, w tym:
   ```
   [REQUEST] POST /v1/validate/nip | Source: RapidAPI | IP: :: | Body (raw): {"value":"..."} | Body (parsed): {"value":"..."}
   [RESPONSE] POST /v1/validate/nip | Status: 200 | Duration: 3.82ms | Body: {"valid":true,...}
   ```

**Sposób 2: Przez Deploy Logs**
1. Kliknij na service "pl-id-validator-api"
2. W panelu po prawej, zakładka **"Deploy Logs"**
3. Wybierz ostatni deployment
4. Kliknij **"View Logs"**

**Sposób 3: Przez Railway CLI**
```bash
# Wszystkie logi z aplikacji
railway logs

# Tylko ostatnie 100 linii
railway logs --tail 100

# Logi w czasie rzeczywistym (follow)
railway logs --follow
```

**Sposób 4: SSH do kontenera**
```bash
railway ssh
# W kontenerze:
tail -f /proc/1/fd/1  # stdout
tail -f /proc/1/fd/2  # stderr
```

### Co jest logowane:

**REQUEST:**
- Metoda HTTP (GET, POST, etc.)
- Path endpointu
- IP klienta (w tym X-Forwarded-For od RapidAPI)
- Raw body (JSON przed parsowaniem)
- Parsed body (po parsowaniu przez middleware)

**RESPONSE:**
- Status code HTTP
- Czas wykonania w milisekundach
- Pełne body odpowiedzi JSON

### Przykładowe logi dla różnych endpointów:

**Health check:**
```
[REQUEST] GET /v1/health | IP: :: | Body (raw): (empty) | Body (parsed): (empty)
[RESPONSE] GET /v1/health | Status: 200 | Duration: 0.85ms | Body: {"status":"ok"}
```

**Validate NIP:**
```
[REQUEST] POST /v1/validate/nip | IP: :: | Body (raw): {"value":"123-456-32-18"} | Body (parsed): {"value":"123-456-32-18"}
[RESPONSE] POST /v1/validate/nip | Status: 200 | Duration: 3.82ms | Body: {"valid":true,"normalized":"1234563218"}
```

**Validate IBAN:**
```
[REQUEST] POST /v1/validate/iban | IP: :: | Body (raw): {"value":"PL 10 1050 0099 7603 1234 5678 9123"} | Body (parsed): {"value":"PL 10 1050 0099 7603 1234 5678 9123"}
[RESPONSE] POST /v1/validate/iban | Status: 200 | Duration: 2.15ms | Body: {"valid":true,"country":"PL","normalized":"PL10105000997603123456789123"}
```

## Wyłączenie logowania (opcjonalnie)

Jeśli chcesz wyłączyć szczegółowe logowanie (np. w produkcji):

1. Usuń lub zakomentuj middleware logujący w `public/index.php`
2. Lub dodaj warunek sprawdzający zmienną środowiskową:
   ```php
   if (getenv('ENABLE_REQUEST_LOGGING') === 'true') {
       // middleware logujący
   }
   ```

**Domyślnie logowanie jest włączone** - pomaga w debugowaniu i monitorowaniu.

## Różnica między logami:

| Źródło | Co loguje | Gdzie widoczne w Railway |
|--------|-----------|-------------------------|
| **Railway Gateway (HTTP Logs)** | Metadane (requestId, duration, bytes, status) | Service Details → "HTTP Logs" tab |
| **RapidAPI Gateway** | Metadane (requestId, duration, bytes) | RapidAPI Dashboard / Analytics |
| **Aplikacja (Middleware)** | **Pełne requesty i responses** ✅ | **Service → "Logs" tab** (główna zakładka) |
| **PHP Server** | Podstawowe informacje (Accepted, Closing) | Service → "Logs" tab |

**WAŻNE:** 
- **HTTP Logs** (w service details) = logi z Railway Gateway (tylko metadane)
- **Logs** (główna zakładka) = logi z aplikacji (pełne requesty/responses z `error_log()`)

**Aby zobaczyć pełne logi z aplikacji:**
1. Kliknij na service
2. Kliknij na główną zakładkę **"Logs"** (na górze, obok "Observability")
3. **NIE** klikaj na "HTTP Logs" w service details panelu!

**Teraz widzisz wszystko!** 🎉

