# PL Validator API

Validate and normalize Polish business identifiers and international bank account numbers.

## Features

✅ **NIP Validation** - Validate Polish tax identification numbers (10 digits with checksum)  
✅ **REGON Validation** - Validate Polish business registry numbers (9 or 14 digits)  
✅ **IBAN Validation** - Validate International Bank Account Numbers (MOD 97-10 algorithm, works for all countries including Poland)  
✅ **Normalization** - Clean and normalize input values automatically  

## Quick Start

### Validate NIP (Polish Tax ID)

```bash
curl --request POST \
  --url https://pl-validator-api.p.rapidapi.com/v1/validate/nip \
  --header 'X-RapidAPI-Key: YOUR_API_KEY' \
  --header 'X-RapidAPI-Host: pl-validator-api.p.rapidapi.com' \
  --header 'Content-Type: application/json' \
  --data '{
    "value": "123-456-32-18"
  }'
```

**Response:**
```json
{
  "valid": true,
  "normalized": "1234563218"
}
```

### Validate REGON (9 digits)

```bash
curl --request POST \
  --url https://pl-validator-api.p.rapidapi.com/v1/validate/regon \
  --header 'X-RapidAPI-Key: YOUR_API_KEY' \
  --header 'Content-Type: application/json' \
  --data '{
    "value": "590096454"
  }'
```

**Response:**
```json
{
  "valid": true,
  "type": "9",
  "normalized": "590096454"
}
```

### Validate IBAN

```bash
curl --request POST \
  --url https://pl-validator-api.p.rapidapi.com/v1/validate/iban \
  --header 'X-RapidAPI-Key: YOUR_API_KEY' \
  --header 'Content-Type: application/json' \
  --data '{
    "value": "PL 10 1050 0099 7603 1234 5678 9123"
  }'
```

**Response:**
```json
{
  "valid": true,
  "country": "PL",
  "normalized": "PL10105000997603123456789123"
}
```

## Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/v1/health` | Health check endpoint |
| `POST` | `/v1/normalize` | Normalize input value (remove separators, uppercase) |
| `POST` | `/v1/validate/nip` | Validate Polish NIP |
| `POST` | `/v1/validate/regon` | Validate Polish REGON (9 or 14 digits) |
| `POST` | `/v1/validate/iban` | Validate IBAN (any country) |

## Request Format

All POST requests use JSON format:

```json
{
  "value": "string to validate/normalize"
}
```

## Response Format

### Validation Endpoints

```json
{
  "valid": true|false,
  "normalized": "cleaned value",
  // Additional fields depending on endpoint:
  "type": "9" | "14" | null,  // For REGON
  "country": "PL" | "DE" | ... | null  // For IBAN
}
```

### Normalize Endpoint

```json
{
  "normalized": "UPPERCASE_WITHOUT_SEPARATORS"
}
```

## Examples

### Valid NIP Examples

- `123-456-32-18`
- `1234563218`
- `526 104 08 28`

### Valid REGON Examples

**9 digits:**
- `590096454`

**14 digits:**
- `59009645400002`

### Valid IBAN Examples

- `PL 10 1050 0099 7603 1234 5678 9123` (Poland)
- `DE89 3704 0044 0532 0130 00` (Germany)
- `GB82 WEST 1234 5698 7654 32` (United Kingdom)

## Error Handling

Invalid inputs return `valid: false` with normalized value:

```json
{
  "valid": false,
  "normalized": "1234563219"
}
```

API always returns HTTP 200 status code. Check the `valid` field in response to determine if validation passed.

## Use Cases

- 📋 Form validation in web applications
- 🔍 Data cleaning and normalization
- 📊 Business data verification
- 🏦 Banking and financial applications
- 📝 CRM and ERP systems integration

## Technical Details

- **Algorithm:** Checksum validation for NIP and REGON
- **IBAN:** MOD 97-10 algorithm (ISO 13616 standard)
- **Normalization:** Removes all non-alphanumeric characters, converts to uppercase
- **Response Time:** < 100ms average

## Support

For issues or questions, please contact the API provider through RapidAPI support.

## License

This API is provided as-is. Use at your own risk.

---

**Made with ❤️ for Polish developers and businesses**

