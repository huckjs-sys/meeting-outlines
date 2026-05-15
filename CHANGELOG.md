# Changelog — Meeting Outlines Plugin

## [1.0.2] — 2026-05-15

### Corrections de sécurité (suite à la review ChurchCRM PR #8848)

- **Validation des énumérés côté serveur** (`routes/routes.php`)
  - `status` (`draft` / `published`) validé avant tout write en BDD sur CREATE et UPDATE service
  - `type` validé contre la liste des types de réunions connus
  - `item_type` validé contre la liste des types d'éléments connus sur CREATE et UPDATE item

- **Validation de `bible_version`** (`src/MeetingOutlinesPlugin.php`)
  - La version biblique n'est sauvegardée en config que si elle figure dans `bible-versions.json`

- **SRI sur SortableJS CDN** (`views/edit.php`)
  - Ajout de `integrity="sha384-/jkFGhPVLS9HIUzX09xB5W3coE5q1X5NXZA/PuOAdOaRxUPczlZmKzYEq9QcJnW0"` et `crossorigin="anonymous"` sur la balise `<script>` jsDelivr

### Autres

- Nom d'auteur `Eoles Conseil` remplacé par `huckjs-sys` dans `plugin.json`, `README.md` et `locale/.../meeting-outlines.po`
- `README.md` : section SortableJS mise à jour avec le tag `<script>` complet (SRI) et explication du choix CDN+SRI
- Config git locale du repo : `user.name = huckjs-sys`, `user.email = huckjs@gmail.com`

### PR associées

| Repo | PR | Statut |
|------|----|--------|
| `huckjs-sys/meeting-outlines` | — | Commits pushés sur `main` |
| `ChurchCRM/CRM` | [#8928](https://github.com/ChurchCRM/CRM/pull/8928) | Ouverte — en attente de review sur branche `External` |

### SHA-256 du zip de release

```
494e6534861ea6a6a74435d0efcbdab7474bca72c54bb9d0d34e994e7f51a81f  meeting-outlines-1.0.2.zip
```

---

## [1.0.1] — 2026-04-28

- Version initiale soumise à ChurchCRM (PR #8848, fermée et approuvée directement sur `External`)
- Ajout de `SECURITY.md` (VDP)
- Correction d'affichage des badges Draft / Built-in en dark mode
- Textes bibliques LSG (66 livres) téléchargés et exclus du zip de release via `.gitattributes`

## [1.0.0] — 2026-04-24

- Version initiale : gestion des ordres de service, drag & drop, sélecteur biblique, notifications email, types personnalisables, vue impression
