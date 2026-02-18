# CZ Article API

**CZ Article API** espone un endpoint REST pubblico per ottenere il contenuto di un singolo post tramite slug, in formato JSON.

---

## Funzionalita principali

- Endpoint pubblico `GET` basato su slug.
- Risposta normalizzata con autore, titolo, sottotitolo, contenuto HTML e volume principale.
- Supporto ACF per `sottotitolo` con fallback su `post_meta`.
- Integrazione con `cz-volume` per recuperare il titolo del volume principale.

---

## Requisiti

- WordPress 6.0+
- PHP 7.4+

---

## Installazione

1. Copia la cartella `cz-article-api` in `wp-content/plugins/`.
2. Attiva il plugin da **Plugin > Plugin installati**.
3. Usa l'endpoint REST dal tuo client (browser, app, servizi esterni).

---

## Endpoint REST

Namespace: `cz-article-api/v1`

- `GET /post/{slug}`

Esempio:

`/wp-json/cz-article-api/v1/post/il-mio-slug`

---

## Response

```json
{
  "author": "Nome Autore",
  "title": "Titolo Post",
  "subtitle": "Sottotitolo opzionale",
  "content": "<p>Contenuto HTML del post...</p>",
  "volume": "Titolo Volume Principale"
}
```

Note:

- `subtitle` puo essere `null` se non presente.
- `volume` puo essere `null` se il post non e associato a un volume.

---

## Errori

- `400` slug non valido
- `404` post non trovato
