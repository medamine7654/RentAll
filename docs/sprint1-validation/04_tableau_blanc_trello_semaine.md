# Tableau blanc (Trello) - Illustration detaillee sur 1 semaine

Board: **Sprint 1 - Semaine 1**

## Listes Trello
- A faire
- En cours
- Verification
- Termine

## Plan de la semaine (detail par jour)

| Jour | A faire | En cours | Verification | Termine |
|---|---|---|---|---|
| Lundi | Initialiser backlog sprint, preparer US-02 reservation, preparer US-05 API | Definition des criteres acceptance US-02 | Revue des routes Symfony | Setup projet valide |
| Mardi | Ecrire tests de validation reservation, preparer filtre capacite/date | Dev formulaire reservation + calcul montant | Controle chevauchement de dates | US-02 create reservation |
| Mercredi | Preparer US-04 avis CRUD, preparer filtre mots interdits | Dev creation avis + controles acces | Test reservation confirmee obligatoire | US-04 create avis |
| Jeudi | Preparer endpoints API recherche/autocomplete | Dev `/api/search/logements` + filtres dynamiques | Verification JSON + performance requetes | US-05 API recherche |
| Vendredi | Finaliser API autocomplete + docs sprint | Dev endpoint autocomplete + limite 5 | Recette complete sprint + demo | US-06 autocomplete + compte-rendu |

## Cartes Trello detaillees (exemple pret a copier)

### Carte 1 - US-02 Creation reservation
- Checklist:
  - champ date debut/date fin
  - verification date fin > date debut
  - verification capacite logement
  - verification chevauchement
  - calcul montant total
  - statut initial `en_attente`
- Definition of Done:
  - formulaire valide
  - tests manuels executes
  - message succes affiche

### Carte 2 - US-04 Avis CRUD
- Checklist:
  - creation avis par locataire
  - modification avis
  - suppression avis
  - protection acces utilisateur
  - filtre mots inappropries
- Definition of Done:
  - aucun doublon d avis pour la meme reservation
  - operation CRUD testee

### Carte 3 - US-05 API recherche avancee
- Checklist:
  - endpoint GET `/api/search/logements`
  - filtres q/location/minPrice/maxPrice/guests/category
  - resultat limite a 20
  - payload JSON `success/count/results`
  - ajout note moyenne + total avis
- Definition of Done:
  - reponse API stable
  - integration front validee

### Carte 4 - US-06 API autocomplete
- Checklist:
  - endpoint GET autocomplete
  - longueur min requete = 2
  - limite 5 propositions
  - retour id/titre/adresse/type
- Definition of Done:
  - autocompletion fonctionnelle
  - temps de reponse acceptable

## Trace quotidienne sur tableau blanc
- Chaque matin: deplacer cartes vers `En cours`.
- Apres verification fonctionnelle: deplacer vers `Verification`.
- Apres validation equipe: deplacer vers `Termine`.
- Mettre a jour SP restants chaque fin de journee.
