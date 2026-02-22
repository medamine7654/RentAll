# Guide des Avis - RentAll

## 📝 Comment Laisser un Avis

### Conditions Requises

Pour pouvoir laisser un avis, **TOUTES** ces conditions doivent être remplies:

1. ✅ Vous devez avoir une réservation **confirmée** (statut = 'confirmee')
2. ✅ Le séjour doit être **terminé** (date de fin < aujourd'hui)
3. ✅ Vous ne devez **pas avoir déjà laissé** un avis pour cette réservation

### Où Trouver le Bouton "Laisser un Avis"

#### Option 1: Page "Mes Réservations"
1. Connectez-vous avec votre compte utilisateur
2. Allez dans **"Mes Réservations"** (navbar)
3. Cherchez une réservation avec:
   - Badge vert "Confirmée"
   - Badge jaune clignotant "Avis disponible"
4. Cliquez sur **"Laisser un avis"** (bouton jaune avec étoile)

#### Option 2: Page de Détail de la Réservation
1. Allez dans "Mes Réservations"
2. Cliquez sur **"Voir les détails"** d'une réservation terminée
3. Scrollez jusqu'à la section **"Votre avis"**
4. Cliquez sur **"Laisser un avis"** (bouton bleu avec étoile)

## 🧪 Tester la Fonctionnalité

### Créer une Réservation Passée (pour test)

Exécutez cette commande pour créer automatiquement une réservation passée:

```bash
php bin/console app:create-past-reservation
```

Cette commande va:
- Créer une réservation pour `user@test.com`
- Dates: il y a 1 mois (3 nuits)
- Statut: confirmée
- La réservation sera automatiquement "terminée" car dans le passé

### Vérifier qu'une Réservation est Terminée

Une réservation est considérée comme terminée si:
```php
$reservation->isTerminee() // retourne true
// Équivalent à: $reservation->getDateFin() < new \DateTime()
```

## 🎨 Interface Utilisateur

### Badges Visuels

Sur la page "Mes Réservations", vous verrez:

| Badge | Signification |
|-------|---------------|
| 🟢 Confirmée | Réservation active |
| 🟡 Avis disponible (clignotant) | Vous pouvez laisser un avis |
| ✅ Avis laissé | Vous avez déjà laissé un avis |
| 🔴 Annulée | Réservation annulée |

### Boutons d'Action

| Bouton | Icône | Couleur | Quand visible |
|--------|-------|---------|---------------|
| Laisser un avis | ⭐ | Jaune | Séjour terminé + pas d'avis |
| Modifier mon avis | ✏️ | Vert | Avis déjà laissé |
| Voir les détails | 👁️ | Bleu | Toujours |
| Annuler la réservation | ❌ | Rouge | Confirmée + >3 jours avant |

## 📋 Processus Complet

### 1. Faire une Réservation
```
User → Logement → Réserver → Formulaire → Confirmation
```

### 2. Attendre la Fin du Séjour
```
Date de fin < Aujourd'hui → Réservation devient "terminée"
```

### 3. Laisser un Avis
```
Mes Réservations → Laisser un avis → Formulaire (note + commentaire) → Publier
```

### 4. Modifier/Supprimer l'Avis
```
Mes Réservations → Modifier mon avis → Formulaire → Enregistrer
OU
Page Réservation → Supprimer (bouton rouge)
```

## 🔍 Vérifications de Sécurité

Le système vérifie automatiquement:

1. **Propriété**: Seul le locataire peut laisser un avis
2. **Statut**: La réservation doit être confirmée
3. **Date**: Le séjour doit être terminé
4. **Unicité**: Un seul avis par réservation
5. **Contenu**: Filtre de mots interdits activé

## 🐛 Dépannage

### "Je ne vois pas le bouton"

Vérifiez:
- [ ] Êtes-vous connecté avec le bon compte (celui qui a fait la réservation)?
- [ ] La réservation est-elle confirmée (badge vert)?
- [ ] La date de fin est-elle passée?
- [ ] Avez-vous déjà laissé un avis (badge vert "Avis laissé")?

### "Le bouton est grisé"

Cela signifie que le séjour n'est pas encore terminé. Attendez que la date de fin soit passée.

### "Erreur lors de la publication"

Vérifiez:
- [ ] Votre commentaire fait au moins 10 caractères
- [ ] Vous avez sélectionné une note (1-5 étoiles)
- [ ] Votre commentaire ne contient pas de mots inappropriés

## 📊 Statistiques des Avis

Les avis sont utilisés pour:
- ✅ Calculer la note moyenne du logement
- ✅ Afficher sur la page du logement
- ✅ Afficher sur les cartes de recherche
- ✅ Aider les futurs voyageurs à choisir

## 🎯 Exemple Complet

### Scénario: Jean veut laisser un avis

1. **Jean se connecte** avec `user@test.com`
2. **Jean va dans "Mes Réservations"**
3. **Jean voit sa réservation passée**:
   - Logement: "Appartement cosy"
   - Dates: 01/01/2024 - 04/01/2024
   - Badge: 🟢 Confirmée
   - Badge: 🟡 Avis disponible (clignotant)
4. **Jean clique sur "Laisser un avis"**
5. **Jean remplit le formulaire**:
   - Note: ⭐⭐⭐⭐⭐ (5/5)
   - Commentaire: "Excellent séjour, logement très propre et bien situé!"
6. **Jean clique sur "Publier l'avis"**
7. **Succès!** Message: "Merci pour votre avis !"
8. **L'avis apparaît**:
   - Sur la page du logement
   - Sur la page de la réservation
   - Badge change en: ✅ Avis laissé

## 🔗 Routes Importantes

| Route | URL | Description |
|-------|-----|-------------|
| app_reservation_index | /reservation | Liste des réservations |
| app_reservation_show | /reservation/{id} | Détail d'une réservation |
| app_avis_new | /avis/new/{id} | Créer un avis |
| app_avis_edit | /avis/{id}/edit | Modifier un avis |
| app_avis_delete | /avis/{id}/delete | Supprimer un avis |

## 💡 Conseils

1. **Pour tester rapidement**: Utilisez la commande `app:create-past-reservation`
2. **Pour voir tous les avis**: Allez sur la page d'un logement
3. **Pour modifier un avis**: Cliquez sur "Modifier mon avis" dans la liste des réservations
4. **Pour supprimer un avis**: Allez sur la page de détail de la réservation

## 📞 Support

Si vous ne voyez toujours pas le bouton après avoir vérifié toutes les conditions:
1. Vérifiez la console du navigateur (F12) pour les erreurs JavaScript
2. Vérifiez les logs Symfony: `var/log/dev.log`
3. Exécutez: `php bin/console debug:router | grep avis` pour vérifier les routes
