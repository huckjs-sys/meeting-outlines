# Conventions de développement

## Workflow GitHub pour toute correction ou amélioration

Pour chaque correction (bug, sécurité, refactoring…) :

1. **Issue** — Ouvrir une issue GitHub décrivant le problème :
   - contexte et fichier(s) concerné(s)
   - impact
   - correction attendue

2. **Branche** — Développer la correction sur une branche dédiée.

3. **PR** — Ouvrir une Pull Request qui :
   - référence l'issue avec `Closes #N`
   - résume les changements effectués
   - inclut un plan de test pour validation avant merge

Ne jamais pousser une correction directement sur `main` sans passer par ce flux.
