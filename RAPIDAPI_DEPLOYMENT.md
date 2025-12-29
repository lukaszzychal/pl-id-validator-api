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

## Krok 6: Konfiguracja monetizacji (Pricing & Rate Limits)

### 6.1 Kompleksowy przewodnik konfiguracji planów

Poniżej znajdziesz **pełne rekomendacje** z wyjaśnieniem każdego pola i wartości do wpisania.

---

### 📋 **Wyjaśnienie wszystkich pól w edytorze planu**

#### **Plan Type (Typ planu)**

**Dostępne opcje:**
- **Monthly Subscription** (Miesięczna subskrypcja) - RECOMENDOWANE
  - Użytkownik płaci stałą miesięczną opłatę
  - Najprostszy model dla większości API
  - Użytkownik ma dostęp do wszystkich funkcji przez cały miesiąc
  
- **Pay per Use** (Płać za użycie)
  - Użytkownik płaci tylko za faktyczne użycie
  - Każde wywołanie API kosztuje
  - Dobre dla API z nieregularnym użyciem
  
- **Tiers** (Poziomy)
  - Różne poziomy cenowe z różnymi limitami
  - Bardziej skomplikowane w konfiguracji
  - Dobre dla enterprise

**Rekomendacja dla PL Validator API:** Wybierz **Monthly Subscription** - najprostsze i najbardziej przewidywalne dla użytkowników.

---

#### **Rate Limit (Limit szybkości)**

**Co to jest:**
- Maksymalna liczba requestów, które użytkownik może wykonać w określonym czasie
- Chroni przed nadmiernym użyciem w krótkim czasie
- Jest to **szybkość**, nie całkowita liczba requestów

**Przykład:**
- Rate Limit: `1,000 requests/hour`
- Użytkownik może wykonać maksymalnie 1,000 requestów w ciągu godziny
- Po 1 godzinie limit się resetuje
- To NIE wpływa na całkowity miesięczny limit (Quota Limit)

**Dostępne jednostki:**
- `/ minute` - na minutę
- `/ hour` - na godzinę (REKOMENDOWANE)
- `/ day` - na dzień

**Rekomendacja:** Ustaw w godzinach - łatwiej kontrolować i jest bardziej przyjazne dla użytkowników.

**Checkbox "Maximum Limit":**
- Zaznacz, jeśli chcesz włączyć rate limiting
- Jeśli nie zaznaczysz, użytkownik może wysyłać requesty bez limitu szybkości (tylko Quota Limit)

---

#### **Require approval (Wymaga zatwierdzenia)**

**Co to jest:**
- Jeśli zaznaczysz, użytkownik musi poczekać na Twoją akceptację przed subskrypcją planu
- Użyteczne dla drogich planów lub gdy chcesz kontrolować kto korzysta

**Kiedy używać:**
- Plan MEGA (drogie plany)
- Gdy chcesz sprawdzić użytkownika przed dostępem
- Enterprise plany

**Rekomendacja dla PL Validator API:** **NIE zaznaczaj** - pozwól użytkownikom subskrybować automatycznie.

---

#### **Recommended Plan (Rekomendowany plan)**

**Co to jest:**
- Plan oznaczony jako "Rekomendowany" będzie miał badge "Recommended" w Hub
- Tylko jeden plan może być rekomendowany na raz
- Pomaga użytkownikom wybrać najlepszy plan

**Kiedy używać:**
- Wybierz plan, który najlepiej pasuje dla większości użytkowników
- Zazwyczaj plan środkowy (PRO lub ULTRA)

**Rekomendacja dla PL Validator API:** Zaznacz dla **PRO planu** - dobry balans między ceną a limitami.

---

#### **Subscription Price (Cena subskrypcji)**

**Co to jest:**
- Miesięczna opłata za dostęp do planu
- Wprowadź kwotę w USD (bez znaku $, tylko cyfry)
- Przykład: `9.99` = $9.99/month

**Dla startu (Beta/Soft Launch):**
- Możesz ustawić wszystkie plany na `0.00` żeby zdobyć użytkowników
- Później możesz dodać ceny

**Dla pełnego launchu:**
- Ustaw odpowiednie ceny zgodnie z rekomendacjami poniżej

---

### 📊 **Wyjaśnienie pól w modalu "Requests"**

#### **Quota Type (Typ kwoty)**

**Dostępne opcje:**
- **Unlimited** - Nieograniczone (bez limitu)
  - Użytkownik może używać bez limitu
  - Tylko Rate Limit będzie działał
  
- **Monthly** - Miesięczne (REKOMENDOWANE)
  - Limit resetuje się co miesiąc
  - Najbardziej popularne
  
- **Daily** - Dzienne
  - Limit resetuje się codziennie
  - Mniej popularne, ale dobre dla bardzo aktywnych API

**Rekomendacja:** Wybierz **Monthly** - standardowe i łatwe do zrozumienia.

---

#### **Quota Limit (Limit kwoty)**

**Co to jest:**
- Całkowita liczba requestów dostępna w okresie (miesięcznym/dziennym)
- Przykład: `100000` = 100,000 requestów w miesiącu

**Jak obliczyć:**
- Pomyśl o typowym użytkowniku
- Przykład: Mały biznes może walidować 100 NIPów dziennie = 3,000/month
- Daj bezpieczny margines

**Wartość do wpisania:**
- Wpisz tylko liczbę, bez separatorów
- Przykład: `100000` (nie `100,000`)

---

#### **Limit Type (Typ limitu)**

**Dostępne opcje:**

**Soft Limit (Miękki limit) - RECOMENDOWANE**
- Użytkownik może przekroczyć limit
- Za dodatkowe użycie płaci "Overages"
- Użytkownik nie dostaje błędów, API dalej działa
- **Przykład:** Limit 10,000, użył 12,000 = zapłaci za 2,000 extra

**Hard Limit (Twardy limit)**
- Użytkownik NIE może przekroczyć limitu
- Po osiągnięciu limitu dostaje błąd 429 (Too Many Requests)
- API przestaje działać do końca okresu
- Użytkownik nie może używać nawet jeśli chce płacić

**Rekomendacja:** Wybierz **Soft Limit** - lepsze doświadczenie użytkownika, możliwość zarabiania na overages.

---

#### **Overages (Dopłaty za przekroczenie)**

**Co to jest:**
- Cena za każdy dodatkowy request po przekroczeniu Quota Limit
- Działa tylko z Soft Limit

**Jak obliczyć:**
- Jeśli chcesz $0.01 za 1,000 dodatkowych requestów:
  - $0.01 ÷ 1,000 = $0.00001 per request
- Jeśli chcesz $0.005 za 1,000 dodatkowych requestów:
  - $0.005 ÷ 1,000 = $0.000005 per request

**Wartość do wpisania:**
- Wpisz w formacie: `0.00001` (nie `$0.00001`)
- Dla bezpłatnych overages: `0`

**Przykład kalkulacji:**
```
Użytkownik na planie PRO:
- Quota: 100,000 requests/month
- Użył: 120,000 requests
- Extra: 20,000 requests
- Overages: 20,000 × $0.00001 = $0.20 dodatkowej opłaty
```

**Rekomendacja dla startu:** Ustaw `0` (bezpłatne overages) żeby zachęcić użytkowników.

---

### 🎯 **Rekomendowane konfiguracje dla PL Validator API**

### 6.1 Rekomendowane limity dla PL Validator API

Ponieważ to lekkie API walidacyjne (bez bazy danych, szybkie odpowiedzi), możesz ustawić wyższe limity:

#### **BASIC Plan (Free)**
**Plan Type:** Monthly Subscription  
**Subscription Price:** $0.00/month  
**Rate Limit:** 1,000 requests/hour  
**Quota Limit:** 10,000 requests/month  
**Limit Type:** Soft Limit (po przekroczeniu można nadal używać)  
**Overages:** $0.00 (bezpłatne przekroczenia, ale z limitem rate)

**Dlaczego 10,000?**
- Wystarczające do testów i małych projektów
- Około 330 requestów dziennie
- Zachęca do upgrade'u dla większego użycia

#### **PRO Plan**
**Plan Type:** Monthly Subscription  
**Subscription Price:** $9.99/month (lub $4.99 dla startu)  
**Rate Limit:** 5,000 requests/hour  
**Quota Limit:** 100,000 requests/month  
**Limit Type:** Soft Limit  
**Overages:** $0.01 per 1,000 extra requests (lub $0 dla początku)

**Dlaczego 100,000?**
- Dla małych biznesów i średnich projektów
- Około 3,300 requestów dziennie
- Dobra wartość dla płatnego planu

#### **ULTRA Plan**
**Plan Type:** Monthly Subscription  
**Subscription Price:** $29.99/month  
**Rate Limit:** 10,000 requests/hour  
**Quota Limit:** 500,000 requests/month  
**Limit Type:** Soft Limit  
**Overages:** $0.005 per 1,000 extra requests

**Dlaczego 500,000?**
- Dla średnich biznesów z większym ruchem
- Około 16,600 requestów dziennie
- Wysoka wartość dla profesjonalnego użycia

#### **MEGA Plan**
**Plan Type:** Monthly Subscription  
**Subscription Price:** $99.99/month  
**Rate Limit:** 20,000 requests/hour  
**Quota Limit:** 2,000,000 requests/month (lub Unlimited)  
**Limit Type:** Soft Limit  
**Overages:** $0.002 per 1,000 extra requests

**Dlaczego 2,000,000?**
- Dla dużych biznesów i enterprise
- Około 66,600 requestów dziennie
- Najwyższy tier z najlepszą wartością

### 6.2 Konfiguracja w RapidAPI Studio - Wartości do wpisania

#### **BASIC Plan (Free)**

**W głównym edytorze planu:**
- **Plan Type:** Monthly Subscription
- **Subscription Price:** `0.00`
- **Rate Limit:** `1,000 requests per hour`
- **Require approval:** ☐ (niezaznaczone)
- **Recommended Plan:** ☐ (niezaznaczone)

**W modalu "BASIC / Requests":**
- **Quota Type:** Monthly ✓
- **Quota Limit:** `10000` (10,000)
- **Limit Type:** Soft Limit ✓
- **Overages:** `0` ($0.00 per extra request)
- Kliknij **"Save Changes"**

---

#### **PRO Plan**

**W głównym edytorze planu:**
- **Plan Type:** Monthly Subscription
- **Subscription Price:** `0.00` (lub `9.99` dla pełnego launchu)
- **Rate Limit:** `5,000 requests per hour`
- **Require approval:** ☐ (niezaznaczone)
- **Recommended Plan:** ☑ (zaznacz - dobry plan dla większości użytkowników)

**W modalu "PRO / Requests":**
- **Quota Type:** Monthly ✓
- **Quota Limit:** `100000` (100,000)
- **Limit Type:** Soft Limit ✓
- **Overages:** `0` (lub `0.00001` dla $0.01 per 1,000 extra)
- Kliknij **"Save Changes"**

**Uwaga:** `0.00001` = $0.01 za 1,000 dodatkowych requestów

---

#### **ULTRA Plan**

**W głównym edytorze planu:**
- **Plan Type:** Monthly Subscription
- **Subscription Price:** `0.00` (lub `29.99` dla pełnego launchu)
- **Rate Limit:** `10,000 requests per hour`
- **Require approval:** ☐ (niezaznaczone)
- **Recommended Plan:** ☐ (niezaznaczone - PRO jest recommended)

**W modalu "ULTRA / Requests":**
- **Quota Type:** Monthly ✓
- **Quota Limit:** `500000` (500,000)
- **Limit Type:** Soft Limit ✓
- **Overages:** `0` (lub `0.000005` dla $0.005 per 1,000 extra)
- Kliknij **"Save Changes"**

**Uwaga:** `0.000005` = $0.005 za 1,000 dodatkowych requestów

---

#### **MEGA Plan**

**W głównym edytorze planu:**
- **Plan Type:** Monthly Subscription
- **Subscription Price:** `0.00` (lub `99.99` dla pełnego launchu)
- **Rate Limit:** `20,000 requests per hour`
- **Require approval:** ☐ (niezaznaczone)
- **Recommended Plan:** ☐ (niezaznaczone)

**W modalu "MEGA / Requests":**
- **Quota Type:** Monthly ✓
- **Quota Limit:** `2000000` (2,000,000) lub pozostaw puste dla Unlimited
- **Limit Type:** Soft Limit ✓
- **Overages:** `0` (lub `0.000002` dla $0.002 per 1,000 extra)
- Kliknij **"Save Changes"**

**Uwaga:** `0.000002` = $0.002 za 1,000 dodatkowych requestów

---

### 6.2a Quick Reference - Wartości do skopiowania

**BASIC:**
```
Quota Limit: 10000
Overages: 0
Rate Limit: 1,000 requests per hour
```

**PRO:**
```
Quota Limit: 100000
Overages: 0 (lub 0.00001)
Rate Limit: 5,000 requests per hour
```

**ULTRA:**
```
Quota Limit: 500000
Overages: 0 (lub 0.000005)
Rate Limit: 10,000 requests per hour
```

**MEGA:**
```
Quota Limit: 2000000
Overages: 0 (lub 0.000002)
Rate Limit: 20,000 requests per hour
```

---

### 📋 **Kompletna tabela konfiguracji wszystkich planów**

| Plan | Subscription Price | Rate Limit | Quota Limit | Overages | Recommended | Features |
|------|-------------------|------------|-------------|----------|-------------|----------|
| **BASIC** | $0.00 | 1,000/hour | 10,000/month | $0.00 | ❌ | Brak |
| **PRO** | $0.00* | 5,000/hour | 100,000/month | $0.00* | ✅ TAK | Email Support |
| **ULTRA** | $0.00* | 10,000/hour | 500,000/month | $0.00* | ❌ | Email + Priority |
| **MEGA** | $0.00* | 20,000/hour | 2,000,000/month | $0.00* | ❌ | Wszystkie |

*Dla startu ustaw $0.00, później zmień na: PRO=$9.99, ULTRA=$29.99, MEGA=$99.99

---

### 🎯 **Finalne wartości do wpisania - Quick Copy**

#### **BASIC Plan:**

**Główny edytor:**
```
Plan Type: Monthly Subscription ✓
Subscription Price: 0.00
Rate Limit: ✓ Maximum Limit: 1000 / hour
Require approval: ☐ (niezaznaczone)
Recommended Plan: ☐ (niezaznaczone)
```

**Modal "BASIC / Requests":**
```
Quota Type: Monthly ✓
Quota Limit: 10000
Limit Type: Soft Limit ✓
Overages: 0
```

**Features:** Brak

---

#### **PRO Plan:**

**Główny edytor:**
```
Plan Type: Monthly Subscription ✓
Subscription Price: 0.00 (lub 9.99)
Rate Limit: ✓ Maximum Limit: 5000 / hour
Require approval: ☐ (niezaznaczone)
Recommended Plan: ☑ (ZAZNACZONE - to jest rekomendowany plan)
```

**Modal "PRO / Requests":**
```
Quota Type: Monthly ✓
Quota Limit: 100000
Limit Type: Soft Limit ✓
Overages: 0 (lub 0.00001 dla $0.01/1k extra)
```

**Features:**
- ✅ Email Support

---

#### **ULTRA Plan:**

**Główny edytor:**
```
Plan Type: Monthly Subscription ✓
Subscription Price: 0.00 (lub 29.99)
Rate Limit: ✓ Maximum Limit: 10000 / hour
Require approval: ☐ (niezaznaczone)
Recommended Plan: ☐ (niezaznaczone - PRO jest recommended)
```

**Modal "ULTRA / Requests":**
```
Quota Type: Monthly ✓
Quota Limit: 500000
Limit Type: Soft Limit ✓
Overages: 0 (lub 0.000005 dla $0.005/1k extra)
```

**Features:**
- ✅ Email Support
- ✅ Priority Response

---

#### **MEGA Plan:**

**Główny edytor:**
```
Plan Type: Monthly Subscription ✓
Subscription Price: 0.00 (lub 99.99)
Rate Limit: ✓ Maximum Limit: 20000 / hour
Require approval: ☐ (lub ☑ jeśli chcesz kontrolować)
Recommended Plan: ☐ (niezaznaczone)
```

**Modal "MEGA / Requests":**
```
Quota Type: Monthly ✓
Quota Limit: 2000000 (lub pozostaw puste dla Unlimited)
Limit Type: Soft Limit ✓
Overages: 0 (lub 0.000002 dla $0.002/1k extra)
```

**Features:**
- ✅ Email Support
- ✅ Priority Response
- ✅ 99.9% Uptime SLA
- ✅ Custom Integration Help

---

### 💡 **Strategia cenowa - Dla startu vs Pełny launch**

#### **Wersja Beta/Soft Launch (Start):**
- Wszystkie plany za **$0.00**
- Overages na **$0.00**
- Cel: Zbierać użytkowników i feedback
- Po 1-3 miesiącach dodaj ceny

#### **Pełny Launch (Production):**
- **BASIC:** $0.00 (zawsze darmowy)
- **PRO:** $9.99/month
- **ULTRA:** $29.99/month
- **MEGA:** $99.99/month
- Overages: ustaw według rekomendacji powyżej

### 6.2b Krok po kroku - Konfiguracja

1. Przejdź do zakładki **"Monetize"** w swoim projekcie API
2. Przejdź do sub-tab **"Public Plans"**
3. Dla każdego planu (BASIC, PRO, ULTRA, MEGA):
   - Kliknij przycisk **"Edit"** na karcie planu
   - Wypełnij ustawienia z sekcji powyżej (6.2)
   - Dla **Requests** (Object):
     - Kliknij przycisk **"Quota"** lub **"+"** przy obiekcie "Requests"
     - Użyj wartości z tabeli powyżej
   - Kliknij **"Save Changes"** w modalu Requests
   - Kliknij **"Save Changes"** w głównym edytorze planu

### 6.3 Wyjaśnienie obiektów: Requests vs Bandwidth Platform Fee

#### **Requests (Liczba żądań)**

**Co to jest:**
- **Requests** = liczba wywołań do endpointów API
- Każde wywołanie endpointu = 1 request
- Przykład: Wywołanie `/v1/validate/nip` = 1 request

**Jak działa:**
- Ustawiasz limit np. **10,000 requests/month**
- Użytkownik może wykonać maksymalnie 10,000 wywołań w miesiącu
- Po przekroczeniu limitu:
  - **Soft Limit:** użytkownik może dalej używać, ale płaci overages (np. $0.01 per 1,000 extra)
  - **Hard Limit:** użytkownik dostaje błąd 429 (Too Many Requests) i nie może dalej używać

**Dla PL Validator API:**
- To główny sposób mierzenia użycia
- Każda walidacja NIP/REGON/IBAN = 1 request
- Ważne dla ograniczenia użycia i zarabiania

**Przykład:**
```
Użytkownik na planie PRO (100,000 requests/month):
- Dnia 1-15: używa 50,000 requests
- Dnia 16-20: używa kolejne 40,000 requests  
- Dnia 21-30: używa kolejne 20,000 requests
- RAZEM: 110,000 requests
- Z quoty: 100,000 requests (bezpłatne)
- Overages: 10,000 requests × $0.01/1,000 = $0.10 dodatkowej opłaty
```

---

#### **Bandwidth Platform Fee (Opłata za przepustowość)**

**Co to jest:**
- **Bandwidth** = ilość danych przesyłanych między klientem a API
- Mierzone w megabajtach (MB) lub gigabajtach (GB)
- Każda odpowiedź API zawiera dane - to kosztuje przepustowość

**Jak działa:**
- RapidAPI mierzy ilość danych przesyłanych przez ich platformę
- 1 jednostka = 1 MB danych
- Po przekroczeniu limitu, naliczane są overages

**Dla PL Validator API:**
- Odpowiedzi są małe (kilkaset bajtów JSON)
- Przykład odpowiedzi: `{"valid":true,"normalized":"1234563218"}` = ~50 bajtów
- 1 MB = ~20,000 takich odpowiedzi
- **Dlatego:** Dla tego API, bandwidth zwykle nie jest problemem

**Przykład kalkulacji:**
```
Request do API:
- Request body: ~30 bajtów JSON
- Response: ~50 bajtów JSON
- RAZEM: ~80 bajtów = 0.00008 MB

Aby zużyć 1 MB:
- Potrzebne: 1 MB / 0.00008 MB = 12,500 requests

Aby zużyć 10 GB (10,240 MB):
- Potrzebne: 10,240 MB / 0.00008 MB = 128,000,000 requests
```

**Domyślne ustawienia:**
- RapidAPI często ustawia domyślne limity bandwidth
- Dla free planów: zazwyczaj 10 GB/month
- Dla płatnych: wyższe limity lub unlimited

**Czy zmieniać ustawienia?**
- **Zazwyczaj NIE** - pozostaw domyślne ustawienia RapidAPI
- Bandwidth dla małych odpowiedzi JSON nie jest problemem
- Skup się na limitach **Requests**, nie Bandwidth

---

#### **Rapid-free-plans-hard-limit**

**Co to jest:**
- Specjalny obiekt tylko dla darmowych planów (BASIC)
- Niewidoczny dla użytkowników końcowych
- Służy do ustawienia twardego limitu dla free planów

**Jak działa:**
- Użytkownik widzi tylko limit "Requests" (np. 10,000/month)
- Ale może być też ukryty hard limit (np. 500,000/month)
- Po przekroczeniu hard limit, użytkownik dostaje błąd - nawet jeśli ma Soft Limit na Requests

**Dla PL Validator API:**
- Zazwyczaj pozostaw domyślne ustawienia RapidAPI
- Służy jako zabezpieczenie przed nadużyciem free planów

---

### 6.5 Features (Funkcje) - Opcjonalne

**Co to są Features:**
- Dodatkowe funkcje/benefity które możesz przypisać do planów
- Pomagają różnicować plany i zachęcać do upgrade'u
- Widoczne dla użytkowników w Hub

**Jak dodać Feature:**
1. Kliknij **"+ Add Feature"** w sekcji Features
2. Wypełnij:
   - **Name:** Nazwa funkcji (np. "Priority Support")
   - **Description:** Opis (opcjonalnie)
   - **Associated Endpoints:** Wybierz endpointy jeśli funkcja dotyczy tylko niektórych (opcjonalnie)

**Rekomendowane Features dla PL Validator API:**

#### **Dla planu BASIC (Free):**
- ❌ Brak dodatkowych features (to jest free plan)

#### **Dla planu PRO:**
- ✅ **Email Support**
  - Name: `Email Support`
  - Description: `Get email support for API usage questions`
  - Associated Endpoints: (pozostaw puste - wszystkie endpointy)

#### **Dla planu ULTRA:**
- ✅ **Email Support** (jak w PRO)
- ✅ **Priority Response**
  - Name: `Priority Response`
  - Description: `Faster response times for support requests`
  - Associated Endpoints: (pozostaw puste)

#### **Dla planu MEGA:**
- ✅ **Email Support**
- ✅ **Priority Response**
- ✅ **SLA Guarantee**
  - Name: `99.9% Uptime SLA`
  - Description: `Guaranteed 99.9% uptime with SLA`
  - Associated Endpoints: (pozostaw puste)
- ✅ **Custom Integration Help**
  - Name: `Custom Integration Help`
  - Description: `Assistance with custom integration requirements`
  - Associated Endpoints: (pozostaw puste)

**Strategia Features:**
- Więcej features = wyższy plan = wyższa cena
- Features powinny być wartościowe dla użytkowników
- Nie przesadzaj - zbyt dużo może przytłoczyć

---

### 6.6 Podsumowanie rekomendacji dla PL Validator API

**Co skonfigurować:**
1. ✅ **Requests** - główny sposób mierzenia użycia, SKONFIGURUJ
2. ⚠️ **Bandwidth Platform Fee** - pozostaw domyślne (RapidAPI)
3. ⚠️ **Rapid-free-plans-hard-limit** - pozostaw domyślne (RapidAPI)
4. ✅ **Features** - dodaj dla płatnych planów (opcjonalnie, ale zalecane)

**Dlaczego Requests jest ważniejsze:**
- Dla API z małymi odpowiedziami JSON, Requests jest głównym limitem
- Bandwidth rzadko będzie przekroczone (10 GB = ~128 milionów requestów)
- Skup się na limitach Requests i overages

**Podsumowanie:**
- **Requests** = "Ile razy można wywołać API" (główne ograniczenie)
- **Bandwidth** = "Ile danych można przesłać" (rzadko problem dla JSON API)
- **Platform Fee** = opłata RapidAPI (20% od płatności użytkowników) - automatyczna

### 6.4 Features (Opcjonalnie)

Możesz dodać features które różnicują plany:
- **Priority Support** - dla PRO i wyżej
- **Email Support** - dla ULTRA i wyżej
- **SLA Guarantee** - dla MEGA

### 6.5 Strategia cenowa

**Dla startu (wersja Beta/Soft Launch):**
- Wszystkie plany za $0.00 żeby zdobyć użytkowników
- Ustaw limity zgodnie z rekomendacjami
- Po zebraniu feedbacku i użytkowników, możesz zwiększyć ceny

**Dla pełnego launchu:**
- BASIC: $0.00 (zawsze darmowy dla przyciągnięcia użytkowników)
- PRO: $9.99/month
- ULTRA: $29.99/month  
- MEGA: $99.99/month

**Uwaga:** RapidAPI zarządza rate limitingiem i billingiem automatycznie.

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

