# ⚽ Football Manager (PHP + MySQL)

Aplikacja do zarządzania drużyną piłkarską. Wersja PHP z bazą MySQL, działa na XAMPP.

## Uruchomienie (XAMPP)
1. Zainstaluj XAMPP i w panelu uruchom **Apache** oraz **MySQL**.
2. Skopiuj folder `football-manager-php` do `C:\xampp\htdocs\` (Linux: `/opt/lampp/htdocs/`).
3. Otwórz w przeglądarce: http://localhost/football-manager-php/
4. Baza `football_manager` i tabele tworzą się automatycznie przy pierwszym wejściu.

Domyślne dane MySQL w XAMPP (użytkownik `root`, bez hasła) są ustawione w `db.php`.

## Struktura
- `index.php` – strony (statystyki, zawodnicy, mecze, tabela) i obsługa formularzy
- `db.php` – połączenie z bazą i tworzenie tabel
- `style.css` – wygląd

## Autor
Franek Bryndza
