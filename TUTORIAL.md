# Tutorial: Tworzenie API w PHP z Slim Framework

## Spis treści

1. [Wprowadzenie](#wprowadzenie)
2. [Struktura projektu](#struktura-projektu)
3. [Routing i kontrolery](#routing-i-kontrolery)
4. [Middleware](#middleware)
5. [Autoryzacja](#autoryzacja)
6. [Praktyczne przykłady](#praktyczne-przykłady)

---

## Wprowadzenie

Ten tutorial pokazuje, jak zbudować REST API w PHP używając Slim Framework. Omówimy kluczowe koncepty: routing, middleware, autoryzację i best practices.

### Wymagania

- PHP 8.2+
- Composer
- Podstawowa znajomość PHP i HTTP

---

## Struktura projektu

```
pl-id-validator-api/
├── public/
│   └── index.php          # Entry point aplikacji
├── src/
│   ├── Controllers/       # Kontrolery (logika endpointów)
│   ├── Auth/             # Logika autoryzacji
│   ├── Monitoring/       # Monitoring requestów
│   └── Validator.php     # Logika biznesowa
├── tests/                # Testy automatyczne
├── composer.json         # Zależności
└── docker-compose.yml    # Docker setup
```

### Entry Point (`public/index.php`)

Główny plik aplikacji, gdzie konfigurujemy:
- Middleware
- Routing
- Inicjalizację

```php
<?php
require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

// Middleware, routing, etc.

$app->run();
```

---

## Routing i kontrolery

### Podstawowy routing

```php
// GET endpoint
$app->get('/v1/health', function ($request, $response) {
    $response->getBody()->write(json_encode(['status' => 'ok']));
    return $response;
});

// POST endpoint
$app->post('/v1/validate/nip', [$controller, 'nip']);
```

### Kontrolery

Kontrolery to klasy, które obsługują logikę endpointów:

```php
namespace App\Controllers;

final class ValidatorController
{
    public function nip(Request $request, Response $response): Response
    {
        $data = (array) ($request->getParsedBody() ?? []);
        $value = (string) ($data['value'] ?? '');
        
        // Logika walidacji
        $result = $this->validator->validateNip($value);
        
        $response->getBody()->write(json_encode($result));
        return $response;
    }
}
```

**Dlaczego kontrolery?**
- Separacja odpowiedzialności
- Łatwiejsze testowanie
- Możliwość reużycia logiki

---

## Middleware

### Czym jest Middleware?

Middleware to funkcje, które wykonują się **przed** lub **po** obsłudze requestu. Pozwalają na:
- Logowanie requestów
- Autoryzację
- Parsowanie body
- Dodawanie nagłówków CORS
- Monitorowanie wydajności

### Pipeline requestu w Slim

```
Request → Middleware 1 → Middleware 2 → ... → Route Handler → Response
                          ↑                              ↓
                          └──────── Response ───────────┘
```

**Ważne:** Middleware wykonuje się w **odwrotnej kolejności** podczas zwracania Response (LIFO - Last In, First Out).

### Przykład: Middleware do logowania

```php
$app->add(function ($request, $handler) {
    // PRZED obsługą requestu
    $startTime = microtime(true);
    $method = $request->getMethod();
    $path = $request->getUri()->getPath();
    
    error_log(sprintf("[REQUEST] %s %s", $method, $path));
    
    // Przekazujemy request dalej
    $response = $handler->handle($request);
    
    // PO obsłużeniu requestu
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    error_log(sprintf("[RESPONSE] Status: %d | Duration: %.2fms", 
        $response->getStatusCode(), $duration));
    
    return $response;
});
```

### Kolejność Middleware - przykład z naszego projektu

W `public/index.php`:

```php
// 1. Middleware logujący (DODANY PIERWSZY)
$app->add(function ($request, $handler) use ($app) {
    // Loguje request PRZED parsowaniem body
    $rawBody = $request->getBody()->getContents();
    $request->getBody()->rewind(); // Reset stream
    
    $response = $handler->handle($request);
    
    // Loguje response PO obsłużeniu
    return $response;
});

// 2. Body Parsing Middleware (DODANY DRUGI)
$app->addBodyParsingMiddleware();
```

**Dlaczego ta kolejność?**

1. **Logging middleware** musi przeczytać **surowy body** przed parsowaniem
2. **Body Parsing Middleware** parsuje body do `$request->getParsedBody()`
3. Kontroler otrzymuje już sparsowany JSON w `getParsedBody()`

**Wykonanie:**
```
Request → Logging (czyta raw body) → Body Parser (parsuje JSON) → Controller → Response
```

### Praktyczny przykład: Middleware CORS

```php
$app->add(function ($request, $handler) {
    $response = $handler->handle($request);
    
    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
});
```

### Obsługa OPTIONS (CORS Preflight)

```php
$app->add(function ($request, $handler) use ($app) {
    // Obsługujemy OPTIONS przed routingiem
    if ($request->getMethod() === 'OPTIONS') {
        $response = $app->getResponseFactory()->createResponse();
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->withStatus(204); // No Content
    }
    
    return $handler->handle($request);
});
```

### Middleware z kontekstem (closure)

Możesz przekazać zmienne do middleware używając `use`:

```php
$app->add(function ($request, $handler) use ($app) {
    // Masz dostęp do $app wewnątrz middleware
    if ($request->getMethod() === 'OPTIONS') {
        $response = $app->getResponseFactory()->createResponse();
        return $response->withStatus(204);
    }
    return $handler->handle($request);
});
```

### Wbudowane Middleware w Slim

Slim dostarcza kilka gotowych middleware:

```php
// Parsowanie JSON body
$app->addBodyParsingMiddleware();

// Error handling
$app->addErrorMiddleware(true, true, true);

// Routing (domyślnie dodawany automatycznie)
$app->addRoutingMiddleware();
```

### Przykład: Middleware do monitorowania

```php
$app->add(function ($request, $handler) {
    $method = $request->getMethod();
    $path = $request->getUri()->getPath();
    
    // Rejestrujemy request przed obsługą
    RequestMonitor::recordRequest($method, $path);
    
    $response = $handler->handle($request);
    
    // Możemy dodać dodatkowe nagłówki do response
    return $response->withHeader('X-Request-ID', uniqid());
});
```

---

## Autoryzacja

### Czym jest autoryzacja?

Autoryzacja to proces **weryfikacji**, czy użytkownik ma prawo do wykonania danej akcji. W kontekście API oznacza sprawdzenie, czy request zawiera poprawne dane uwierzytelniające.

### Metody autoryzacji

#### 1. API Key (Header)

Klient wysyła klucz w nagłówku HTTP:

```bash
curl -H "X-API-Key: your-secret-key" http://api.example.com/protected
```

#### 2. API Key (Query Parameter)

Klient wysyła klucz jako parametr URL:

```bash
curl "http://api.example.com/protected?api_key=your-secret-key"
```

#### 3. Basic Authentication

HTTP Basic Auth (RFC 7617):

```bash
curl -u username:password http://api.example.com/protected
```

W nagłówku: `Authorization: Basic base64(username:password)`

### Implementacja: Klasa SimpleAuth

Stwórzmy klasę do obsługi autoryzacji:

```php
namespace App\Auth;

final class SimpleAuth
{
    private static ?string $adminApiKey = null;
    private static ?string $adminUsername = null;
    private static ?string $adminPassword = null;

    public static function initialize(): void
    {
        // Wczytujemy dane z zmiennych środowiskowych
        self::$adminApiKey = getenv('MONITORING_API_KEY') ?: null;
        self::$adminUsername = getenv('MONITORING_USERNAME') ?: null;
        self::$adminPassword = getenv('MONITORING_PASSWORD') ?: null;
    }

    public static function isAuthenticated(ServerRequestInterface $request): bool
    {
        // Tryb deweloperski - jeśli brak konfiguracji, pozwól na dostęp
        if (self::$adminApiKey === null && self::$adminUsername === null) {
            return true;
        }

        // Sprawdź API Key (nagłówek)
        if (self::$adminApiKey !== null) {
            $apiKey = $request->getHeaderLine('X-API-Key');
            
            // Lub jako query parameter
            if (empty($apiKey)) {
                $apiKey = $request->getQueryParams()['api_key'] ?? null;
            }
            
            if ($apiKey === self::$adminApiKey) {
                return true;
            }
        }

        // Sprawdź Basic Auth
        if (self::$adminUsername !== null && self::$adminPassword !== null) {
            $authHeader = $request->getHeaderLine('Authorization');
            
            if (preg_match('/Basic\s+(.*)$/i', $authHeader, $matches)) {
                $decoded = base64_decode($matches[1], true);
                if ($decoded !== false) {
                    [$username, $password] = explode(':', $decoded, 2) + ['', ''];
                    if ($username === self::$adminUsername && 
                        $password === self::$adminPassword) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
```

### Użycie w kontrolerze

```php
namespace App\Controllers;

use App\Auth\SimpleAuth;

final class MonitoringController
{
    public function statsFiltered(Request $request, Response $response): Response
    {
        // Sprawdź autoryzację
        if (!SimpleAuth::isAuthenticated($request)) {
            $response->getBody()->write(json_encode([
                'error' => 'Unauthorized',
                'message' => 'Authentication required',
            ]));
            return $response->withStatus(401)
                ->withHeader('WWW-Authenticate', 'Basic realm="Monitoring"');
        }

        // Jeśli autoryzacja OK, zwróć dane
        $stats = RequestMonitor::getStats();
        $response->getBody()->write(json_encode($stats));
        return $response;
    }
}
```

### Middleware autoryzacyjny (alternatywa)

Zamiast sprawdzać w każdym kontrolerze, możesz stworzyć middleware:

```php
$app->add(function ($request, $handler) {
    $path = $request->getUri()->getPath();
    
    // Sprawdź czy endpoint wymaga autoryzacji
    $protectedPaths = ['/v1/monitoring/stats/filtered', '/v1/monitoring/reset'];
    
    if (in_array($path, $protectedPaths)) {
        if (!SimpleAuth::isAuthenticated($request)) {
            $response = new \Slim\Psr7\Response();
            $response->getBody()->write(json_encode([
                'error' => 'Unauthorized'
            ]));
            return $response->withStatus(401);
        }
    }
    
    return $handler->handle($request);
});
```

**Zalety middleware:**
- Centralna logika autoryzacji
- Nie duplikujemy kodu w kontrolerach
- Łatwiejsza zmiana strategii autoryzacji

**Zalety sprawdzania w kontrolerze:**
- Większa elastyczność per endpoint
- Możliwość różnych poziomów autoryzacji

### Konfiguracja przez zmienne środowiskowe

**Docker Compose:**
```yaml
services:
  api:
    environment:
      MONITORING_API_KEY: your-secret-key-123
      MONITORING_USERNAME: admin
      MONITORING_PASSWORD: secure-password
```

**Railway:**
```bash
railway variables set MONITORING_API_KEY=your-secret-key-123
```

**Local (.env file):**
```bash
export MONITORING_API_KEY=your-secret-key-123
```

### Bezpieczeństwo - Best Practices

1. **Nigdy nie commituj kluczy do repo**
   - Używaj `.gitignore` dla `.env`
   - Używaj zmiennych środowiskowych

2. **Używaj silnych kluczy**
   ```bash
   # Generuj losowy klucz
   openssl rand -hex 32
   ```

3. **HTTPS w produkcji**
   - API Key w nagłówkach jest bezpieczny tylko przez HTTPS

4. **Rate limiting**
   - Ogranicz liczbę requestów z jednego klucza

5. **Rotacja kluczy**
   - Regularnie zmieniaj klucze

### Przykład: Walidacja tokena JWT (zaawansowane)

Jeśli chcesz użyć JWT:

```php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

public static function validateJWT(ServerRequestInterface $request): bool
{
    $authHeader = $request->getHeaderLine('Authorization');
    
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        return false;
    }
    
    try {
        $token = $matches[1];
        $decoded = JWT::decode($token, new Key(getenv('JWT_SECRET'), 'HS256'));
        return true;
    } catch (\Exception $e) {
        return false;
    }
}
```

---

## Praktyczne przykłady

### Przykład 1: Pełny middleware z logowaniem i CORS

```php
$app->add(function ($request, $handler) use ($app) {
    $startTime = microtime(true);
    $method = $request->getMethod();
    $path = $request->getUri()->getPath();
    
    // Obsługa OPTIONS
    if ($method === 'OPTIONS') {
        $response = $app->getResponseFactory()->createResponse();
        return $response
            ->withHeader('Access-Control-Allow-Origin', '*')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withStatus(204);
    }
    
    // Logowanie requestu
    error_log(sprintf("[%s] %s %s", date('Y-m-d H:i:s'), $method, $path));
    
    // Obsłuż request
    $response = $handler->handle($request);
    
    // Dodaj CORS headers
    $response = $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    
    // Logowanie response
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    error_log(sprintf("[RESPONSE] %s %s | Status: %d | Duration: %.2fms", 
        $method, $path, $response->getStatusCode(), $duration));
    
    return $response;
});
```

### Przykład 2: Middleware z autoryzacją dla wybranych endpointów

```php
$app->add(function ($request, $handler) {
    $path = $request->getUri()->getPath();
    
    // Lista chronionych endpointów
    $protectedPaths = [
        '/v1/monitoring/stats/filtered',
        '/v1/monitoring/reset',
        '/v1/admin/users',
    ];
    
    // Sprawdź czy path jest chroniony
    foreach ($protectedPaths as $protectedPath) {
        if (strpos($path, $protectedPath) === 0) {
            if (!SimpleAuth::isAuthenticated($request)) {
                $response = new \Slim\Psr7\Response();
                $response->getBody()->write(json_encode([
                    'error' => 'Unauthorized',
                    'message' => 'This endpoint requires authentication',
                ]));
                return $response->withStatus(401);
            }
            break;
        }
    }
    
    return $handler->handle($request);
});
```

### Przykład 3: Middleware z rate limiting

```php
$app->add(function ($request, $handler) {
    $clientIp = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
    
    // Prosty rate limiter (w pamięci)
    static $requests = [];
    $key = $clientIp;
    $now = time();
    
    // Wyczyść stare wpisy (stare niż 60 sekund)
    $requests = array_filter($requests, function($timestamp) use ($now) {
        return ($now - $timestamp) < 60;
    });
    
    // Policz requesty w ostatniej minucie
    $recentRequests = array_filter($requests, function($timestamp) use ($now) {
        return ($now - $timestamp) < 60;
    });
    
    if (count($recentRequests) >= 100) { // Limit: 100 req/min
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write(json_encode([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded'
        ]));
        return $response->withStatus(429)
            ->withHeader('Retry-After', '60');
    }
    
    // Dodaj obecny request
    $requests[] = $now;
    
    return $handler->handle($request);
});
```

### Przykład 4: Testowanie autoryzacji

```php
// Test bez autoryzacji
$request = $app->createRequest('GET', '/v1/monitoring/stats/filtered');
$response = $app->handle($request);
assert($response->getStatusCode() === 401);

// Test z API Key
$request = $app->createRequest('GET', '/v1/monitoring/stats/filtered')
    ->withHeader('X-API-Key', 'valid-key');
$response = $app->handle($request);
assert($response->getStatusCode() === 200);

// Test z Basic Auth
$credentials = base64_encode('admin:password');
$request = $app->createRequest('GET', '/v1/monitoring/stats/filtered')
    ->withHeader('Authorization', 'Basic ' . $credentials);
$response = $app->handle($request);
assert($response->getStatusCode() === 200);
```

---

## Podsumowanie

### Middleware - kluczowe punkty

1. **Middleware wykonuje się przed i po obsłudze requestu**
2. **Kolejność dodawania jest ważna** - ostatni dodany wykonuje się najpierw podczas requestu, ale ostatni podczas response
3. **Można modyfikować request i response**
4. **Można przerwać pipeline** zwracając response wcześniej

### Autoryzacja - kluczowe punkty

1. **Nigdy nie przechowuj kluczy w kodzie** - używaj zmiennych środowiskowych
2. **Używaj HTTPS w produkcji**
3. **Różne metody autoryzacji** - API Key, Basic Auth, JWT
4. **Sprawdzaj autoryzację w middleware lub kontrolerze**
5. **Zwracaj właściwe kody HTTP** (401 Unauthorized)

### Następne kroki

- Przeczytaj dokumentację Slim Framework: https://www.slimframework.com/docs/
- Eksperymentuj z różnymi middleware
- Implementuj rate limiting
- Dodaj obsługę JWT tokenów
- Przeczytaj o PSR-15 (HTTP Server Middleware): https://www.php-fig.org/psr/psr-15/

---

**Powodzenia w budowaniu API! 🚀**

