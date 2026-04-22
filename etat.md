# État du projet — Meeting Outlines

## Fonctionnalités du plugin

### Gestion des cultes (services)
- Créer, modifier, supprimer un culte
- Champs : date, titre, type, prédicateur, président, notes, statut
- 4 types de culte : Dimanche, Réunion de prière, Culte spécial, Autre
- Workflow Draft / Publié
- Vue liste avec tableau paginé (DataTables)

### Programme du culte (items)
- Ajouter, modifier, supprimer des éléments au programme
- 8 types d'éléments : Chant, Prière, Lecture biblique, Sermon, Offrande, Annonces, Communion, Autre
- Champs par élément : type, titre, description, durée (minutes), responsable
- Glisser-déposer pour réordonner les éléments (SortableJS)
- Compteur de durée totale en bas de liste

### Référence biblique
- Sélecteur en cascade : livre → chapitre → verset début → verset fin
- 66 livres (AT + NT), données embarquées hors-ligne (bible-structure.json)
- Visible uniquement pour les éléments de type "Lecture biblique"

### Vue impression
- Page autonome (sans header/footer CRM) optimisée @media print
- Tableau du programme complet avec numérotation
- En-tête : titre, date, prédicateur, type
- Bloc notes du culte en bas de page

### Réglages
- Lier un groupe ChurchCRM aux prédicateurs (alimente le dropdown)
- Lier un groupe ChurchCRM aux responsables (alimente les dropdowns)
- Choisir la version biblique par défaut parmi celles de bible-versions.json

### Intégration ChurchCRM
- Menu latéral ajouté dynamiquement ("Church Meetings")
- Accès restreint aux administrateurs (AdminRoleAuthMiddleware)
- Membres des groupes tirés depuis la base ChurchCRM (person_per)
- Migrations de schéma idempotentes au démarrage (compatibles MySQL 8)
- Internationalisation via gettext() — traductions françaises incluses
