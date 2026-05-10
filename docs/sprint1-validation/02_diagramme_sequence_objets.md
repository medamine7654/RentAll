# Diagramme de sequence objets - Fonctionnalite avancee Sprint 1

Fonctionnalite choisie: **Recherche avancee API logements**

```mermaid
sequenceDiagram
    actor U as Utilisateur
    participant F as Front (search_filters_controller.js)
    participant C as SearchController
    participant LR as LogementRepository
    participant AR as AvisRepository
    participant J as JsonResponse

    U->>F: Saisit filtres (q, location, prix, guests, category)
    F->>C: GET /api/search/logements?... (AJAX)
    C->>LR: createQueryBuilder() + filtres dynamiques
    LR-->>C: Liste logements disponibles

    loop pour chaque logement
        C->>AR: getAverageRatingForLogement(logement)
        C->>AR: findByLogement(logement)
        AR-->>C: moyenne + total avis
    end

    C->>J: Construire payload JSON (results, count, success)
    J-->>F: 200 OK + JSON
    F-->>U: Affichage resultats filtres en temps reel
```

## Objets et responsabilites
- `SearchController`: orchestration des filtres et formatage de la reponse API.
- `LogementRepository`: requete SQL/Doctrine dynamique sur disponibilite + criteres.
- `AvisRepository`: enrichissement qualite (note moyenne, total avis).
- `Front controller JS`: declenchement requete asynchrone et rendu UI.

## Valeur metier
- Recherche plus rapide.
- Resultats plus pertinents.
- Meilleure experience utilisateur grace au filtrage instantane.
