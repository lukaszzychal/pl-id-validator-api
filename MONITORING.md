# Monitoring i Hooks dla RapidAPI

## Przegląd

System monitorowania pozwala śledzić:
- **Liczbę requestów** z RapidAPI vs bezpośrednich
- **Statystyki per endpoint** i metoda HTTP
- **Statystyki per token** RapidAPI (hash dla prywatności)
- **Hooks/callbacki** dla requestów z RapidAPI

## Endpointy

### GET /v1/monitoring/stats

Zwraca statystyki requestów:

```json
{
  "total_requests": 10,
  "rapidapi_requests": 7,
  "direct_requests": 3,
  "rapidapi_percentage": 70.0,
  "direct_percentage": 30.0,
  "by_endpoint": {
    "/v1/validate/nip": 5,
    "/v1/validate/regon": 3,
    "/v1/validate/iban": 2
  },
  "by_method": {
    "POST": 10
  },
  "rapidapi_by_token": {
    "53136271": 4,
    "a796af03": 3
  },
  "uptime_seconds": 3600,
  "uptime_formatted": "1h 0m 0s",
  "requests_per_minute": 0.17
}
```

### POST /v1/monitoring/reset

Resetuje wszystkie statystyki:

```json
{
  "status": "ok",
  "message": "Statistics reset successfully"
}
```

## Wykrywanie requestów RapidAPI

Request jest identyfikowany jako pochodzący z RapidAPI jeśli:
1. Zawiera header `X-RapidAPI-Host` LUB
2. Zawiera header `X-RapidAPI-Key` LUB  
3. Zawiera header `X-RapidAPI-Proxy-Secret` który pasuje do `RAPIDAPI_PROXY_SECRET` (jeśli skonfigurowany)

## Konfiguracja

### Zmienne środowiskowe

- `RAPIDAPI_PROXY_SECRET` - opcjonalny secret do weryfikacji requestów z RapidAPI (jeśli ustawiony, wymagany dla każdego requestu RapidAPI)

### Przykład użycia

```bash
# Ustawienie secret w Railway
railway variables set RAPIDAPI_PROXY_SECRET=your-secret-here
```

## Hooks dla RapidAPI

Możesz dodać własne hooki/callbacki, które będą wywoływane dla każdego requestu z RapidAPI.

### Dodawanie hooków

W `public/index.php`:

```php
RequestMonitor::addRapidApiHook(function ($request, $response, $context) {
    // $context zawiera:
    // - 'token': X-RapidAPI-Key (raw)
    // - 'token_hash': hash tokena (8 znaków)
    // - 'duration': czas wykonania w ms
    // - 'method': metoda HTTP
    // - 'path': ścieżka endpointu
    
    // Przykład: wyślij webhook
    file_get_contents('https://your-webhook-url.com/api/rapidapi-event', [
        'http' => [
            'method' => 'POST',
            'content' => json_encode([
                'token_hash' => $context['token_hash'],
                'path' => $context['path'],
                'duration' => $context['duration']
            ])
        ]
    ]);
    
    // Przykład: zapisz do bazy danych
    // Database::logRequest($context);
    
    // Przykład: wyślij do analytics
    // Analytics::track('rapidapi_request', $context);
});
```

### Przykładowe użycia hooków

1. **Webhooks** - powiadomienia o requestach
2. **Analytics** - śledzenie użycia
3. **Rate limiting** - własne limity per token
4. **Logging** - szczegółowe logowanie
5. **Monitoring** - alerty przy nieprawidłowościach

## Przykłady

### Pobranie statystyk

```bash
curl http://localhost:8080/v1/monitoring/stats
```

### Reset statystyk

```bash
curl -X POST http://localhost:8080/v1/monitoring/reset
```

### Test requestów z RapidAPI

```bash
# Direct request
curl -X POST http://localhost:8080/v1/validate/nip \
  -H "Content-Type: application/json" \
  -d '{"value":"123-456-32-18"}'

# RapidAPI request (z headery)
curl -X POST http://localhost:8080/v1/validate/nip \
  -H "Content-Type: application/json" \
  -H "X-RapidAPI-Key: your-key" \
  -H "X-RapidAPI-Host: your-host" \
  -d '{"value":"123-456-32-18"}'
```

## Przechowywanie statystyk

Statystyki są zapisywane w pliku `/tmp/pl-validator-api-stats.json` w kontenerze.

**Uwaga:** Plik jest resetowany przy restarcie kontenera. W produkcji rozważ:
- Użycie bazy danych (Redis, PostgreSQL, etc.)
- Użycie shared volume
- Przesyłanie do zewnętrznego systemu monitoringu

## Implementacja

### RequestMonitor Class

Klasa `App\Monitoring\RequestMonitor` zawiera całą logikę monitorowania:

- `initialize()` - inicjalizacja systemu
- `isRapidApiRequest()` - wykrywanie requestów RapidAPI
- `getRapidApiToken()` - pobieranie tokena z requestu
- `recordRequest()` - zapisywanie requestu
- `addRapidApiHook()` - dodawanie hooków
- `triggerRapidApiHooks()` - wywoływanie hooków
- `getStats()` - pobieranie statystyk
- `resetStats()` - reset statystyk

## Logi

System loguje:
- `[REQUEST]` - każdy request z informacją o źródle (RapidAPI/Direct)
- `[RESPONSE]` - każda odpowiedź z czasem wykonania
- `[RAPIDAPI_HOOK]` - wywołania hooków (jeśli dodano hook logujący)

## Bezpieczeństwo

- Tokeny RapidAPI są przechowywane tylko jako hash (8 znaków MD5)
- Endpointy monitoring są dostępne publicznie (rozważ dodanie autoryzacji w produkcji)
- Statystyki mogą zawierać wrażliwe dane (endpointy, częstotliwość) - rozważ ograniczenie dostępu

