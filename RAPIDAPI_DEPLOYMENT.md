# Dokumentacja wdrożenia do RapidAPI

## Przegląd

Ten dokument opisuje krok po kroku proces wdrożenia PL Validator API do RapidAPI.

---

## Wymagania wstępne

1. ✅ API wdrożone i dostępne publicznie (np. Railway, Render, Fly.io)
2. ✅ Konto na [RapidAPI](https://rapidapi.com/) (darmowe lub płatne)
3. ✅ Plik `openapi.yaml` z definicją API
4. ✅ HTTPS endpoint (wymagany przez RapidAPI)

---

## Krok 1: Wdrożenie API

### 1.1 Wybór hostingu

**Rekomendowane platformy:**
- **Railway** (używane w tym projekcie) - bezpłatna wersja dostępna
- **Render** - bezpłatna wersja dostępna
- **Fly.io** - bezpłatna wersja dostępna
- **Heroku** - wymaga karty kredytowej

### 1.2 Wdrożenie na Railway

1. Zaloguj się na [Railway](https://railway.app/)
2. Kliknij **"New Project"**
3. Wybierz **"Deploy from GitHub repo"**
4. Wybierz repozytorium `pl-id-validator-api`
5. Railway automatycznie:
   - Wykryje Dockerfile
   - Zbuduje obraz
   - Wdroży aplikację
6. Po wdrożeniu, Railway wygeneruje publiczny URL np.:
   ```
   https://pl-id-validator-api-production.up.railway.app
   ```

### 1.3 Weryfikacja wdrożenia

Sprawdź czy API działa:

```bash
# Health check
curl https://pl-id-validator-api-production.up.railway.app/v1/health

# Oczekiwana odpowiedź:
# {"status":"ok"}
```

**Ważne:** Upewnij się, że API jest dostępne przez HTTPS (Railway automatycznie to zapewnia).

---

## Krok 2: Przygotowanie OpenAPI Spec

### 2.1 Aktualizacja openapi.yaml

Plik `openapi.yaml` już zawiera obie wersje serwerów (lokalną i produkcyjną):

```yaml
servers:
  - url: http://localhost:8080
    description: Local development server
  - url: https://pl-id-validator-api-production.up.railway.app
    description: Production server (Railway)
```

**Dla wdrożenia na Railway:**
- Jeśli Twój URL Railway jest inny, zaktualizuj drugi wpis w sekcji `servers`
- Dla lokalnego testowania użyj pierwszego wpisu (`http://localhost:8080`)

**Dla importu do RapidAPI:**
- RapidAPI automatycznie użyje odpowiedniego URL w zależności od wybranego środowiska
- Możesz też ustawić Base URL ręcznie w ustawieniach RapidAPI (patrz Krok 4.2)

**Uwaga:** Jeśli masz własny URL Railway, zaktualizuj wartość w drugim wpisie.

### 2.2 Weryfikacja OpenAPI Spec

Możesz zweryfikować specyfikację używając:
- [Swagger Editor](https://editor.swagger.io/)
- [Swagger Validator](https://validator.swagger.io/)

---

## Krok 3: Tworzenie API Project w RapidAPI Studio

**Ważne:** RapidAPI używa teraz **RapidAPI Studio** do zarządzania API. Tworzymy **API Project**, nie "App".

### 3.1 Logowanie i przejście do RapidAPI Studio

1. Zaloguj się na [RapidAPI](https://rapidapi.com/)
2. W górnym menu kliknij **"Studio"** (lub przejdź bezpośrednio do https://rapidapi.com/studio)
3. Zostaniesz przekierowany do RapidAPI Studio

### 3.2 Dodanie nowego API Project

1. Na stronie RapidAPI Studio, kliknij przycisk **"+ Add API Project"** (prawy górny róg lub w głównej sekcji)
2. Zostanie otwarty modal **"Add API Project"**

### 3.3 Wypełnienie formularza "Add API Project"

W modalu **"Add API Project"** wypełnij:

1. **Name:** (wymagane)
   - Wpisz: `PL-Validator-API` (możesz użyć myślników lub podkreśleń)
   - Przykład: `PL Validator API` lub `pl-validator-api`

2. **Description:**
   - Wpisz: `Validate and normalize Polish identifiers (NIP, REGON) and IBAN`
   - Opcjonalnie możesz dodać więcej szczegółów

3. **Category:**
   - Wybierz z dropdown: **"Business"** (lub odpowiednią kategorię)
   - Inne opcje: `Developer Tools`, `Finance`, etc.

4. **Team:**
   - Wybierz **"Personal"** (domyślnie) lub wybierz zespół jeśli masz

5. **Import data from:** ⚠️ **WAŻNE - TO JEST KLUCZOWE**
   - Wybierz **"OpenAPI"** (radio button)
   - **NIE wybieraj "Do not import"** - jeśli wybierzesz to, będziesz musiał dodawać endpointy ręcznie

### 3.4 Importowanie OpenAPI Spec

Po wybraniu **"OpenAPI"** w kroku 3.3, zobaczysz opcje importu:

**Opcja A: Upload pliku (Rekomendowane)**
1. Kliknij **"Upload File"** lub **"Choose File"**
2. Wybierz plik `openapi.yaml` z lokalnego komputera
3. Plik zostanie automatycznie zaimportowany

**Opcja B: Wklejenie URL**
1. Jeśli plik jest dostępny publicznie (np. na GitHub), wklej URL:
   ```
   https://raw.githubusercontent.com/twoj-username/pl-id-validator-api/main/openapi.yaml
   ```
   **Uwaga:** Zamień `twoj-username` na swoje GitHub username

**Opcja C: Wklejenie zawartości**
1. Skopiuj zawartość pliku `openapi.yaml`
2. Wklej do pola tekstowego (jeśli dostępne)
3. Kliknij **"Import"** lub **"Validate"**

### 3.5 Utworzenie projektu

1. Po wybraniu i zaimportowaniu OpenAPI spec, kliknij przycisk **"Add API Project"** (niebieski przycisk na dole)
2. RapidAPI utworzy projekt i automatycznie zaimportuje wszystkie endpointy z OpenAPI spec
3. Zostaniesz przekierowany do dashboardu projektu

### 3.6 Weryfikacja zaimportowanych endpointów

Po utworzeniu projektu, sprawdź czy wszystkie endpointy zostały zaimportowane:
- `GET /v1/health`
- `POST /v1/normalize`
- `POST /v1/validate/nip`
- `POST /v1/validate/regon`
- `POST /v1/validate/iban`

**Jeśli endpointy nie zostały zaimportowane:**
- Sprawdź czy plik OpenAPI jest poprawny (walidacja w Swagger Editor)
- Możesz dodać endpointy ręcznie później w zakładce **"Endpoints"**

---

## Krok 4: Konfiguracja endpointów

### 4.1 Weryfikacja endpointów

RapidAPI powinno automatycznie wykryć wszystkie endpointy z OpenAPI spec:
- `GET /v1/health`
- `POST /v1/normalize`
- `POST /v1/validate/nip`
- `POST /v1/validate/regon`
- `POST /v1/validate/iban`

### 4.2 Konfiguracja Base URL

**Gdzie znaleźć Base URL:**
1. W dashboardzie projektu API, przejdź do **"Settings"** lub **"Configuration"**
2. Znajdź sekcję **"Base URL"** lub **"API Base URL"**
3. Ustaw na URL Twojego wdrożenia Railway:
   ```
   https://pl-id-validator-api-production.up.railway.app
   ```
4. **Nie dodawaj** `/v1` na końcu - endpointy w OpenAPI już to zawierają

**Alternatywnie:**
- Jeśli importowałeś OpenAPI spec podczas tworzenia projektu, RapidAPI może automatycznie wykryć Base URL z sekcji `servers` w pliku
- W takim przypadku upewnij się, że w `openapi.yaml` masz prawidłowy URL Railway (powinien być już ustawiony)
- Jeśli Base URL został automatycznie wykryty, sprawdź czy jest poprawny

### 4.3 Konfiguracja autoryzacji

**W RapidAPI Studio/Projects:**
- Autoryzacja jest zazwyczaj konfigurowana na poziomie endpointów
- Domyślnie endpointy używają standardowej autoryzacji RapidAPI (subscription key)
- W ustawieniach projektu znajdź sekcję **"Authorization"** lub **"Security"** aby sprawdzić/zmienić ustawienia

**Dla publicznego API:**
- Użytkownicy będą musieli mieć RapidAPI subscription key do korzystania z API
- To pozwala na śledzenie użycia i zarządzanie dostępem
- Możesz skonfigurować różne poziomy dostępu (Free, Pro, Ultra) w ustawieniach projektu

---

## Krok 5: Testowanie w RapidAPI

### 5.1 Testowanie przez RapidAPI Console

1. Przejdź do zakładki **"Endpoints"**
2. Wybierz endpoint np. `GET /v1/health`
3. Kliknij **"Test Endpoint"**
4. Sprawdź odpowiedź

**Przykład testu NIP:**
- Endpoint: `POST /v1/validate/nip`
- Body:
  ```json
  {
    "value": "123-456-32-18"
  }
  ```
- Oczekiwana odpowiedź:
  ```json
  {
    "valid": true,
    "normalized": "1234563218"
  }
  ```

### 5.2 Testowanie wszystkich endpointów

Przetestuj każdy endpoint:
- [ ] `GET /v1/health` - zwraca `{"status":"ok"}`
- [ ] `POST /v1/normalize` - normalizuje wartości
- [ ] `POST /v1/validate/nip` - waliduje poprawnie i błędnie
- [ ] `POST /v1/validate/regon` - waliduje 9 i 14 cyfr
- [ ] `POST /v1/validate/iban` - waliduje IBAN

---

## Krok 6: Konfiguracja zaawansowana

### 6.1 Rate Limiting (opcjonalnie)

W ustawieniach API możesz skonfigurować:
- **Free Tier:** np. 1000 requestów/miesiąc
- **Pro Tier:** np. 10000 requestów/miesiąc
- **Ultra Tier:** np. 100000 requestów/miesiąc

**Uwaga:** RapidAPI zarządza rate limitingiem automatycznie.

### 6.2 Pricing (opcjonalnie)

Jeśli chcesz zarabiać na API:
1. Przejdź do sekcji **"Pricing"**
2. Ustaw ceny dla różnych tierów:
   - **Free:** $0 (ograniczona liczba requestów)
   - **Pro:** $9.99/miesiąc
   - **Ultra:** $49.99/miesiąc
3. Ustaw limity requestów dla każdego tieru

### 6.3 Dokumentacja

1. Przejdź do sekcji **"Documentation"**
2. RapidAPI automatycznie wygeneruje dokumentację z OpenAPI spec
3. Możesz dodać dodatkowe przykłady, opisy, itp.

---

## Krok 7: Publikacja API

### 7.1 Review przed publikacją

Przed opublikowaniem API, upewnij się że:
- [ ] Wszystkie endpointy działają poprawnie
- [ ] Dokumentacja jest kompletna
- [ ] Przykłady są poprawne
- [ ] API jest stabilne i dostępne
- [ ] Error handling działa poprawnie

### 7.2 Publikacja

1. Kliknij przycisk **"Publish API"** lub **"Make Public"**
2. RapidAPI może wymagać przeglądu (review) przed publikacją
3. Proces review może trwać od kilku godzin do kilku dni

### 7.3 Po publikacji

Po publikacji API będzie dostępne:
- W katalogu RapidAPI Marketplace
- Przez wyszukiwarkę RapidAPI
- Będzie można do niego subskrybować

---

## Krok 8: Monitoring i statystyki

### 8.1 Dashboard w RapidAPI

W sekcji **"Analytics"** możesz zobaczyć:
- Liczbę requestów dziennie/miesiąc
- Najczęściej używane endpointy
- Geolokalizację użytkowników
- Error rate

### 8.2 Logi Railway

Monitoruj logi aplikacji w Railway:
```bash
# Przez Railway dashboard
# Lub przez CLI:
railway logs
```

---

## Rozwiązywanie problemów

### Problem: API nie działa w RapidAPI

**Rozwiązanie:**
1. Sprawdź czy API działa bezpośrednio:
   ```bash
   curl https://twoj-url.up.railway.app/v1/health
   ```
2. Sprawdź logi w Railway
3. Upewnij się że Base URL jest poprawny (bez `/v1` na końcu)

### Problem: CORS errors

**Rozwiązanie:**
- API już ma CORS headers w `public/index.php`
- Jeśli nadal występują problemy, sprawdź czy headers są poprawnie ustawione

### Problem: Timeout errors

**Rozwiązanie:**
1. Sprawdź czy API odpowiada szybko (< 1s)
2. Railway może mieć limit czasu - sprawdź w ustawieniach
3. Rozważ użycie cache jeśli to możliwe

### Problem: OpenAPI spec nie importuje się

**Rozwiązanie:**
1. Zweryfikuj spec w [Swagger Validator](https://validator.swagger.io/)
2. Upewnij się że format jest OpenAPI 3.0
3. Sprawdź czy wszystkie wymagane pola są wypełnione

---

## Sprawdzenie listy kontrolnej

Przed publikacją API, upewnij się że:

### Konfiguracja
- [ ] API jest wdrożone i dostępne publicznie
- [ ] HTTPS działa poprawnie
- [ ] OpenAPI spec jest zaktualizowany z prawidłowym URL
- [ ] Base URL w RapidAPI jest poprawny

### Funkcjonalność
- [ ] Wszystkie endpointy działają poprawnie
- [ ] Testy przeszły w RapidAPI Console
- [ ] Error handling działa
- [ ] CORS headers są ustawione

### Dokumentacja
- [ ] Dokumentacja jest kompletna
- [ ] Przykłady są poprawne
- [ ] Opisy endpointów są jasne

### Publikacja
- [ ] API jest przetestowane
- [ ] Pricing jest skonfigurowany (jeśli dotyczy)
- [ ] Rate limiting jest ustawiony

---

## Przykładowe użycie po publikacji

Po publikacji, użytkownicy mogą używać API:

```bash
# Przez RapidAPI endpoint
curl -X POST "https://pl-validator-api.p.rapidapi.com/v1/validate/nip" \
  -H "X-RapidAPI-Key: YOUR_API_KEY" \
  -H "X-RapidAPI-Host: pl-validator-api.p.rapidapi.com" \
  -H "Content-Type: application/json" \
  -d '{"value":"123-456-32-18"}'
```

**Uwaga:** RapidAPI automatycznie zmienia endpointy na swoje proxy. Użytkownicy nie będą łączyć się bezpośrednio z Twoim serwerem.

---

## Wsparcie

W przypadku problemów:
1. Sprawdź dokumentację RapidAPI: https://docs.rapidapi.com/
2. Sprawdź logi w Railway
3. Skontaktuj się z supportem RapidAPI przez dashboard

---

## Aktualizacje API

Po opublikowaniu, aby zaktualizować API:

1. Wprowadź zmiany w kodzie
2. Wdróż nową wersję na Railway
3. Jeśli zmieniasz endpointy, zaktualizuj `openapi.yaml`
4. W RapidAPI, przejdź do **"Versions"** i utwórz nową wersję
5. Importuj zaktualizowany OpenAPI spec
6. Przetestuj nową wersję
7. Opublikuj nową wersję

---

## Linki pomocnicze

- [RapidAPI Documentation](https://docs.rapidapi.com/)
- [Railway Documentation](https://docs.railway.app/)
- [OpenAPI Specification](https://swagger.io/specification/)
- [Swagger Editor](https://editor.swagger.io/)

