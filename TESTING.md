# Dokumentacja testów - PL Validator API

## Przegląd

API do walidacji i normalizacji polskich identyfikatorów (NIP, REGON) oraz IBAN.

## Testy automatyczne

### Uruchomienie testów

```bash
# Zainstaluj zależności (jeśli nie zainstalowane)
composer install

# Uruchom wszystkie testy
composer test

# Lub bezpośrednio
./vendor/bin/phpunit
```

### Pokrycie testami

Projekt zawiera **35 testów** pokrywających:
- ✅ Walidację NIP (poprawne i błędne przypadki)
- ✅ Walidację REGON (9 i 14 cyfr)
- ✅ Walidację IBAN (PL i inne kraje)
- ✅ Normalizację wartości
- ✅ Edge cases (puste stringi, błędne długości)
- ✅ Wszystkie endpointy API (integracja)

### Struktura testów

```
tests/
├── ValidatorTest.php           # Testy jednostkowe klasy Validator
└── ValidatorControllerTest.php # Testy integracyjne API endpoints
```

---

## Testy manualne - Instrukcja dla QA

### 1. Przygotowanie środowiska

#### Opcja A: Lokalnie (bez Dockera)
```bash
composer install
composer start
```
API będzie dostępne na: `http://localhost:8080`

#### Opcja B: Docker
```bash
docker compose up --build
```
API będzie dostępne na: `http://localhost:8080`

#### Opcja C: Railway (produkcja)
API dostępne na: `https://pl-id-validator-api-production.up.railway.app`

---

### 2. Endpoint: GET /

**Cel:** Sprawdzenie informacji o API

**Request:**
```bash
curl http://localhost:8080/
```

**Oczekiwana odpowiedź:**
```json
{
  "name": "PL Validator API",
  "version": "1.0.0",
  "description": "Validate and normalize Polish identifiers (NIP, REGON) and IBAN",
  "endpoints": {
    "GET  /v1/health": "Health check",
    "POST /v1/normalize": "Normalize input value",
    "POST /v1/validate/nip": "Validate NIP",
    "POST /v1/validate/regon": "Validate REGON",
    "POST /v1/validate/iban": "Validate IBAN"
  }
}
```

**Testy do wykonania:**
- [ ] Status code: 200
- [ ] Content-Type: application/json
- [ ] Wszystkie endpointy są wymienione

---

### 3. Endpoint: GET /v1/health

**Cel:** Health check - sprawdzenie czy API działa

**Request:**
```bash
curl http://localhost:8080/v1/health
```

**Oczekiwana odpowiedź:**
```json
{
  "status": "ok"
}
```

**Testy do wykonania:**
- [ ] Status code: 200
- [ ] Content-Type: application/json
- [ ] Status = "ok"

---

### 4. Endpoint: POST /v1/normalize

**Cel:** Normalizacja wartości (usunięcie separatorów, uppercase)

**Request (przykład 1):**
```bash
curl -X POST http://localhost:8080/v1/normalize \
  -H "Content-Type: application/json" \
  -d '{"value":"PL 10 1050 0099 7603 1234 5678 9123"}'
```

**Oczekiwana odpowiedź:**
```json
{
  "normalized": "PL10105000997603123456789123"
}
```

**Request (przykład 2 - znaki specjalne):**
```bash
curl -X POST http://localhost:8080/v1/normalize \
  -H "Content-Type: application/json" \
  -d '{"value":"A-B C_1.2,3"}'
```

**Oczekiwana odpowiedź:**
```json
{
  "normalized": "ABC123"
}
```

**Testy do wykonania:**
- [ ] Status code: 200
- [ ] Usuwanie spacji, myślników, podkreśleń
- [ ] Konwersja na uppercase
- [ ] Pusty string zwraca pusty string
- [ ] Brakujące pole "value" traktowane jako pusty string

---

### 5. Endpoint: POST /v1/validate/nip

**Cel:** Walidacja numeru NIP (10 cyfr z sumą kontrolną)

**Request (poprawny NIP):**
```bash
curl -X POST http://localhost:8080/v1/validate/nip \
  -H "Content-Type: application/json" \
  -d '{"value":"123-456-32-18"}'
```

**Oczekiwana odpowiedź:**
```json
{
  "valid": true,
  "normalized": "1234563218"
}
```

**Request (błędny NIP - zła suma kontrolna):**
```bash
curl -X POST http://localhost:8080/v1/validate/nip \
  -H "Content-Type: application/json" \
  -d '{"value":"123-456-32-19"}'
```

**Oczekiwana odpowiedź:**
```json
{
  "valid": false,
  "normalized": "1234563219"
}
```

**Request (za krótki):**
```bash
curl -X POST http://localhost:8080/v1/validate/nip \
  -H "Content-Type: application/json" \
  -d '{"value":"123456789"}'
```

**Oczekiwana odpowiedź:**
```json
{
  "valid": false,
  "normalized": "123456789"
}
```

**Testy do wykonania:**
- [ ] Poprawny NIP: valid = true
- [ ] Błędna suma kontrolna: valid = false
- [ ] Za krótki (< 10 cyfr): valid = false
- [ ] Za długi (> 10 cyfr): valid = false
- [ ] Pusty string: valid = false
- [ ] Normalizacja usuwa separatory
- [ ] NIP tylko cyframi (bez separatorów) działa poprawnie

**Przykładowe poprawne NIP-y do testów:**
- `1234563218`
- `123-456-32-18`
- `526 104 08 28`
- `774-00-00-234`

---

### 6. Endpoint: POST /v1/validate/regon

**Cel:** Walidacja numeru REGON (9 lub 14 cyfr)

**Request (poprawny REGON 9-cyfrowy):**
```bash
curl -X POST http://localhost:8080/v1/validate/regon \
  -H "Content-Type: application/json" \
  -d '{"value":"590096454"}'
```

**Oczekiwana odpowiedź:**
```json
{
  "valid": true,
  "type": "9",
  "normalized": "590096454"
}
```

**Request (poprawny REGON 14-cyfrowy):**
```bash
curl -X POST http://localhost:8080/v1/validate/regon \
  -H "Content-Type: application/json" \
  -d '{"value":"59009645400002"}'
```

**Oczekiwana odpowiedź:**
```json
{
  "valid": true,
  "type": "14",
  "normalized": "59009645400002"
}
```

**Request (błędny REGON):**
```bash
curl -X POST http://localhost:8080/v1/validate/regon \
  -H "Content-Type: application/json" \
  -d '{"value":"590096455"}'
```

**Oczekiwana odpowiedź:**
```json
{
  "valid": false,
  "type": null,
  "normalized": "590096455"
}
```

**Testy do wykonania:**
- [ ] Poprawny REGON 9-cyfrowy: valid = true, type = "9"
- [ ] Poprawny REGON 14-cyfrowy: valid = true, type = "14"
- [ ] Błędna suma kontrolna: valid = false
- [ ] Za krótki (< 9 cyfr): valid = false
- [ ] Za długi (> 14 cyfr): valid = false
- [ ] 10-13 cyfr (nieprawidłowa długość): valid = false
- [ ] Pusty string: valid = false
- [ ] Normalizacja usuwa separatory

---

### 7. Endpoint: POST /v1/validate/iban

**Cel:** Walidacja numeru IBAN (dowolny kraj, algorytm MOD 97-10)

**Request (poprawny IBAN PL):**
```bash
curl -X POST http://localhost:8080/v1/validate/iban \
  -H "Content-Type: application/json" \
  -d '{"value":"PL 10 1050 0099 7603 1234 5678 9123"}'
```

**Oczekiwana odpowiedź:**
```json
{
  "valid": true,
  "country": "PL",
  "normalized": "PL10105000997603123456789123"
}
```

**Request (poprawny IBAN DE - Niemcy):**
```bash
curl -X POST http://localhost:8080/v1/validate/iban \
  -H "Content-Type: application/json" \
  -d '{"value":"DE89 3704 0044 0532 0130 00"}'
```

**Oczekiwana odpowiedź:**
```json
{
  "valid": true,
  "country": "DE",
  "normalized": "DE89370400440532013000"
}
```

**Request (błędny IBAN):**
```bash
curl -X POST http://localhost:8080/v1/validate/iban \
  -H "Content-Type: application/json" \
  -d '{"value":"PL00105000997603123456789123"}'
```

**Oczekiwana odpowiedź:**
```json
{
  "valid": false,
  "country": "PL",
  "normalized": "PL00105000997603123456789123"
}
```

**Testy do wykonania:**
- [ ] Poprawny IBAN PL: valid = true, country = "PL"
- [ ] Poprawny IBAN innego kraju (DE, GB, etc.): valid = true
- [ ] Błędna suma kontrolna: valid = false
- [ ] Za krótki (< 15 znaków): valid = false
- [ ] Błędny kod kraju: valid = false (jeśli nie zaczyna się od 2 liter)
- [ ] Pusty string: valid = false, country = null
- [ ] Normalizacja usuwa separatory i konwertuje na uppercase

---

## Testy negatywne (error handling)

### Brakujące pole "value"

**Request:**
```bash
curl -X POST http://localhost:8080/v1/validate/nip \
  -H "Content-Type: application/json" \
  -d '{}'
```

**Oczekiwana odpowiedź:**
```json
{
  "valid": false,
  "normalized": ""
}
```

### Nieprawidłowy Content-Type

**Request:**
```bash
curl -X POST http://localhost:8080/v1/validate/nip \
  -H "Content-Type: text/plain" \
  -d '{"value":"1234563218"}'
```

**Oczekiwana odpowiedź:**
- API powinno zwrócić odpowiedź (może być pusta lub z wartościami domyślnymi)
- Status code: 200 (Slim parsuje body jeśli Content-Type pozwala)

### Nieprawidłowy endpoint

**Request:**
```bash
curl http://localhost:8080/v1/nonexistent
```

**Oczekiwana odpowiedź:**
- Status code: 404
- Treść: błąd Slim Framework

---

## Testy wydajności

### Sprawdzenie czasu odpowiedzi

```bash
# Test pojedynczego requesta
time curl -X POST http://localhost:8080/v1/validate/nip \
  -H "Content-Type: application/json" \
  -d '{"value":"123-456-32-18"}'

# Oczekiwany czas: < 100ms
```

### Test obciążeniowy (opcjonalnie)

```bash
# Wymaga zainstalowanego Apache Bench (ab)
ab -n 100 -c 10 -p request.json -T application/json \
  http://localhost:8080/v1/validate/nip
```

---

## Checklist testów QA

### Funkcjonalność
- [ ] Wszystkie endpointy działają poprawnie
- [ ] Walidacja NIP działa dla poprawnych i błędnych wartości
- [ ] Walidacja REGON działa dla 9 i 14 cyfr
- [ ] Walidacja IBAN działa dla różnych krajów
- [ ] Normalizacja działa poprawnie
- [ ] Edge cases są obsługiwane (puste, za krótkie, za długie)

### Walidacja danych
- [ ] Sumy kontrolne są weryfikowane poprawnie
- [ ] Błędne wartości są odrzucane
- [ ] Normalizacja usuwa separatory

### Format odpowiedzi
- [ ] Wszystkie odpowiedzi są w formacie JSON
- [ ] Content-Type: application/json
- [ ] Status codes są poprawne (200 dla sukcesu, 404 dla nieistniejących endpointów)

### Error handling
- [ ] Brakujące pola są obsługiwane gracefully
- [ ] Błędne żądania nie powodują crashy
- [ ] Komunikaty błędów są czytelne

### Integracja
- [ ] API działa w Dockerze
- [ ] API działa w Railway (produkcja)
- [ ] CORS headers są ustawione poprawnie

---

## Raportowanie błędów

W przypadku znalezienia błędów, proszę podać:

1. **Endpoint:** `/v1/validate/nip`
2. **Request:**
   ```json
   {"value": "..."}
   ```
3. **Oczekiwana odpowiedź:**
4. **Rzeczywista odpowiedź:**
5. **Status code:**
6. **Środowisko:** (Lokalne/Docker/Railway)
7. **Kroki reprodukcji:**

---

## Narzędzia pomocnicze

### Testy przez przeglądarkę (Postman/Insomnia)
Importuj poniższe przykłady do Postman/Insomnia:

**Collection JSON:**
```json
{
  "info": {
    "name": "PL Validator API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "item": [
    {
      "name": "Health Check",
      "request": {
        "method": "GET",
        "url": "{{baseUrl}}/v1/health"
      }
    },
    {
      "name": "Validate NIP",
      "request": {
        "method": "POST",
        "url": "{{baseUrl}}/v1/validate/nip",
        "header": [{"key": "Content-Type", "value": "application/json"}],
        "body": {
          "mode": "raw",
          "raw": "{\"value\": \"123-456-32-18\"}"
        }
      }
    }
  ]
}
```

---

## Kontakt

W przypadku pytań dotyczących testów, proszę skontaktować się z zespołem deweloperskim.

