# Sprint 1 - Validation (Compte-rendu complet)

## 1) Objectif du Sprint 1
Livrer une base fonctionnelle de la plateforme Smart Rental avec:
- flux reservation locataire
- CRUD des avis apres reservation confirmee
- fonctionnalites avancees API (recherche dynamique et suggestions)
- notifications et suivi admin

## 2) Sprint Backlog detaille

| ID | User Story | Type | Priorite | Estimation (SP) | Statut |
|---|---|---|---|---:|---|
| US-01 | En tant que locataire, je peux consulter la liste de mes reservations. | CRUD (Read) | Haute | 3 | Termine |
| US-02 | En tant que locataire, je peux creer une reservation sur un logement disponible. | CRUD (Create) | Haute | 8 | Termine |
| US-03 | En tant que locataire, je peux annuler ma reservation selon les regles. | CRUD (Update) | Moyenne | 3 | Termine |
| US-04 | En tant que locataire, je peux publier/modifier/supprimer un avis lie a ma reservation confirmee. | CRUD (Create/Update/Delete) | Haute | 8 | Termine |
| US-05 | En tant qu utilisateur, je peux faire une recherche avancee de logements via API avec filtres. | Avance (API) | Haute | 5 | Termine |
| US-06 | En tant qu utilisateur, je recois des suggestions API pendant la saisie de recherche. | Avance (API) | Moyenne | 3 | Termine |
| US-07 | En tant qu admin, je recois une notification temps reel lors d une nouvelle reservation. | Avance (Service/API temps reel) | Moyenne | 5 | Termine |
| US-08 | En tant que locataire/hote, je peux telecharger une reservation en PDF. | Avance (Service) | Basse | 5 | Termine |

Total Sprint: **40 SP**

## 3) Critere d acceptation et execution (compte-rendu)

### US-04 (CRUD Avis) - Detail attendu a l oral
- Contexte: avis autorise uniquement pour une reservation confirmee.
- Regles appliquees:
  - utilisateur connecte obligatoire
  - seul le locataire proprietaire de la reservation peut publier/modifier/supprimer
  - 1 seul avis par reservation
  - filtre de mots interdits avant sauvegarde
- Resultat: operation CRUD complete (Create, Update, Delete) + controle d acces + validation metier.

### US-05 (API Recherche avancee) - Detail attendu a l oral
- Endpoint: `GET /api/search/logements`
- Filtres pris en charge:
  - `q`, `location`, `minPrice`, `maxPrice`, `guests`, `category`
- Regles appliquees:
  - uniquement logements disponibles
  - limite de resultat (20)
  - enrichissement reponse avec note moyenne et nombre d avis
- Resultat: API JSON exploitable par l interface front pour une recherche rapide et precise.

## 4) Taches techniques par User Story

| US | Taches techniques executees |
|---|---|
| US-02 | Form reservation, validations dates/capacite, verification chevauchement, calcul montant total, statut `en_attente` |
| US-04 | Form avis, controle proprietaire, controle statut reservation, filtre mots interdits, persist/flush, suppression securisee CSRF |
| US-05 | QueryBuilder dynamique, filtres combinables, normalisation reponse JSON, lien vers page detail |
| US-06 | Endpoint suggestions, longueur minimale requete, limite 5 suggestions |
| US-07 | Service notification declenche apres creation reservation |
| US-08 | Generation PDF avec template Twig dedie |

## 5) Affectation pour l explication orale (2 stories par etudiant)

Exemple de repartition (a adapter a votre equipe):

| Etudiant | Story CRUD (1) | Story avancee API (1) |
|---|---|---|
| Etudiant A | US-04 Avis CRUD | US-05 API recherche logements |
| Etudiant B | US-02 Creation reservation | US-06 API suggestions |
| Etudiant C | US-03 Annulation reservation | US-07 Notification temps reel |
| Etudiant D | US-01 Liste reservations | US-08 Export PDF |

## 6) Script oral court (pret a dire)

### Story CRUD (US-04)
"Notre story CRUD concerne les avis. Un locataire peut ajouter, modifier et supprimer son avis. On controle l acces, le statut de reservation confirmee, et on filtre les mots inappropries avant enregistrement."

### Story avancee API (US-05)
"Notre story API avancee expose un endpoint de recherche filtree des logements. La requete combine texte, prix, capacite et categorie. La reponse JSON retourne aussi les notes et le nombre d avis, ce qui alimente une recherche dynamique cote front."
