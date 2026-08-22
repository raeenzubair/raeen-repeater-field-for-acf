## 2026-08-16 - [Centralize Data Sanitization before DB Insertion]
**Vulnerability:** Inline custom sanitization within the `Rest_API.php` bypassed the central field-aware rules found in `Sanitizer()->prepare_for_database()`.
**Learning:** Data inserted to the database via API requests bypassed strict and context-aware central policies setup for security and data integrity.
**Prevention:** Always delegate incoming API row data to the `\Raeen_Repeater\Helpers\Sanitizer` class during REST API actions to centralize control over field type sanitizations before insertion.