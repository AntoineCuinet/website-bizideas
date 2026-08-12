# 💡 Biz-Ideas

Voir le site en ligne : [https://bizideas.acuinet.fr](https://bizideas.acuinet.fr)

BizIdeas est une application web moderne, légère et performante, développée avec **Symfony 8**, **MySQL** et **Sass (SCSS)**. Elle permet d'organiser, de noter et de classer collaborativement des idées de business.

L'application est conçue en approche **mobile-first** pour offrir une expérience fluide et responsive sur tous les appareils, avec une charte graphique intégrant un mode sombre natif.

[![img_contact](./screenshot.png)](https://bizideas.acuinet.fr/#gh-light-mode-only)
[![img_contact](./screenshot-dark.png)](https://bizideas.acuinet.fr/#gh-dark-mode-only)

---

## Fonctionnalités principales

1. **Page d'accueil & Dashboard personnalisé** :
   - **Non connecté** : Présentation du projet et bouton de connexion.
   - **Connecté** : Vue d'ensemble de toutes les idées (les siennes et celles des collaborateurs) avec un système de classement dynamique par rapport à la note globale.
   - **Filtrage et tri** : Possibilité de trier par meilleure note globale, date d'ajout (dernier ajout), par auteur (mes idées en premier, ou celles des autres en premier) ou par statut.
   - **Export** : Possibilité de télécharger la liste des idées triée au format CSV, Markdown ou PDF.

2. **Détails & Aperçu rapide (Popup)** :
   - Clic sur une idée pour ouvrir une boîte de dialogue modale affichant la description, les badges de statut, la note globale et une comparaison détaillée critère par critère des notes attribuées par le créateur et les collaborateurs.

3. **Ajout et modification d'idées** :
   - Formulaire accessible depuis un bouton flottant en bas à droite de l'écran.
   - Sélection du modèle de revenus (Paiement unique, Récurrent ou les deux), du public ciblé (B2B, B2C ou les deux) et saisie de l'auto-évaluation.
   - **Visualisation de la note en temps réel** : Un script Stimulus calcule instantanément la note estimée au fur et à mesure que les critères sont cochés, selon les préférences de l'utilisateur.

4. **Évaluation des idées des collaborateurs** :
   - Évaluation sur 5 étoiles de chaque idée créée par l'autre utilisateur selon les 9 critères.
   - Prévisualisation interactive de la note avant validation.

5. **Gestion du compte & Préférences de pondération** :
   - Modification de l'adresse email et du mot de passe.
   - **Importance personnalisée des critères** : Chaque utilisateur peut définir l'importance de chaque critère de notation (*Faible*, *Moyenne* ou *Élevée*). Ces préférences modifient le calcul de la note globale et le classement des idées sur sa propre page d'accueil.

6. **Notifications par email** :
   - Envoi automatique d'un email aux collaborateurs lors de l'ajout d'une nouvelle idée, contenant un lien direct pour y accéder et l'évaluer.

---

## Logique de calcul des notes

Pour garantir l'équité et respecter la personnalisation, les notes sont calculées ainsi :

1. **Pondération des critères** :
   - *Faible* = Coefficient 1
   - *Moyenne* = Coefficient 2
   - *Élevée* = Coefficient 3
2. **Note individuelle** :
   - La note attribuée par un utilisateur à une idée est la moyenne pondérée de ses scores sur les 9 critères, en fonction de ses propres coefficients d'importance.
3. **Note globale** :
   - La note globale affichée est la moyenne arithmétique simple des notes individuelles attribuées par chaque utilisateur ayant évalué l'idée (50% pour le créateur, 50% pour le collaborateur). Si un seul utilisateur a évalué l'idée pour l'instant, sa note individuelle constitue la note globale temporaire.

---

## Architecture SCSS & Design

Le frontend est conçu entièrement en Sass (sans Bootstrap ni Tailwind) et structuré de manière modulaire :

- `_variables.scss` : Définition des variables CSS pour les thèmes clair et sombre (couleurs HSL harmonieuses, espacements, ombres, transitions).
- `_base.scss` : Réinitialisation CSS standard et animations légères (fondus).
- `_components.scss` : Composants réutilisables (boutons, formulaires, cartes d'idées, modales, badges).
- `_layouts.scss` : Structures de mise en page globale et d'accueil responsive (mobile-first).

---

## Sécurité

- Les mots de passe en base de données sont sécurisés à l'aide d'un algorithme de hachage fort auto-sélectionné par Symfony.
- Protection contre les attaques CSRF activée sur le formulaire de connexion et sur toutes les actions sensibles (soumissions, suppression d'idées).
- Variables sensibles configurables dans `.env.local` pour éviter d'exposer des clés ou identifiants sensibles en production.

---

## Installation et démarrage en local

1. **Cloner le projet** et installer les dépendances :

   ```bash
   composer install
   ```

2. **Configurer l'environnement local** :
   Créez un fichier `.env.local` et modifiez la configuration de la base de données :

   ```env
   DATABASE_URL="mysql://username:password@127.0.0.1:3306/db?serverVersion=8.0.32&charset=utf8mb4"
   MAILER_DSN="smtp://localhost:1025"
   ```

3. **Créer la base de données et mettre à jour le schéma** :

   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:schema:update --force
   ```

4. **Créer les comptes d'utilisateurs** :

   ```bash
   php bin/console app:create-user mail@gmail.com MotDePasse
   ```

5. **Compiler les fichiers Sass** (à laisser tourner dans un terminal séparé) :

   ```bash
   php bin/console sass:build --watch
   ```

6. **Lancer le serveur de développement Symfony** :

   ```bash
   symfony serve
   ```
