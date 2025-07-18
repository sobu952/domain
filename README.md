# Domain Monitor

System monitorowania wygasających domen z analizą AI i powiadomieniami.

## Funkcjonalności

- **Automatyczne pobieranie domen**: Codzienne pobieranie listy wygasających domen z dns.pl
- **Analiza AI**: Integracja z Gemini API do analizy domen według kategorii
- **System powiadomień**: Przypomnienia o nadchodzących rejestracjach
- **Panel administracyjny**: Zarządzanie kategoriami, użytkownikami i konfiguracją
- **Ulubione domeny**: Możliwość dodawania domen do ulubionych
- **Responsywny design**: Nowoczesny interfejs oparty na Bootstrap

## Wymagania

- PHP 7.4 lub nowszy
- MySQL 5.7 lub nowszy
- Composer
- Dostęp do cron jobs
- Klucz API Gemini

## Instalacja

1. **Pobierz pliki** na swój hosting
2. **Zainstaluj zależności**:
   ```bash
   composer install
   ```
3. **Utwórz bazę danych** MySQL
4. **Uruchom instalator** - przejdź do `install.php` w przeglądarce
5. **Skonfiguruj cron jobs** zgodnie z instrukcjami z instalatora

## Konfiguracja Cron Jobs

Dodaj następujące zadania do crontab:

```bash
# Codzienne pobieranie domen (9:05)
5 9 * * * /usr/bin/php /ścieżka/do/projektu/cron/fetch_domains.php

# Wysyłanie przypomnień (8:00)
0 8 * * * /usr/bin/php /ścieżka/do/projektu/cron/send_reminders.php

# Czyszczenie starych danych (2:00, raz w tygodniu)
0 2 * * 0 /usr/bin/php /ścieżka/do/projektu/cron/cleanup.php
```

## Konfiguracja

Główna konfiguracja znajduje się w pliku `config/config.php`. Możesz również zarządzać ustawieniami przez panel administracyjny.

### Gemini API

1. Uzyskaj klucz API z [Google AI Studio](https://aistudio.google.com/)
2. Wprowadź klucz w konfiguracji systemu
3. Skonfiguruj prompty dla kategorii w panelu administracyjnym

### Email

System obsługuje wysyłanie emaili przez SMTP. Skonfiguruj:
- Host SMTP
- Port (zazwyczaj 587 dla TLS)
- Dane logowania
- Szyfrowanie

## Struktura projektu

```
/
├── config/           # Konfiguracja systemu
├── includes/         # Wspólne funkcje i komponenty
├── auth/            # Autoryzacja (logowanie/wylogowanie)
├── admin/           # Panel administracyjny
├── domains/         # Zarządzanie domenami
├── cron/            # Zadania cron
├── ajax/            # Endpointy AJAX
├── assets/          # CSS, JS, obrazy
├── install/         # Pliki instalacyjne
└── logs/            # Logi systemu
```

## Bezpieczeństwo

- Wszystkie hasła są hashowane
- Ochrona przed SQL injection
- Walidacja danych wejściowych
- Sesje zabezpieczone
- Logi aktywności

## Wsparcie

W przypadku problemów:
1. Sprawdź logi w katalogu `/logs/`
2. Upewnij się, że cron jobs działają poprawnie
3. Sprawdź konfigurację bazy danych i API

## Licencja

Ten projekt jest własnością prywatną. Wszystkie prawa zastrzeżone.