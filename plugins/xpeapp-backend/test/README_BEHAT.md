# Tests Behat - calculateQuestionSatisfaction

## 📋 Vue d'ensemble

Cette suite de tests Behat valide le comportement de la fonction `calculateQuestionSatisfaction()` qui analyse les réponses aux questions d'une campagne QVST et détermine les niveaux de satisfaction.

## 🎯 Objectifs des Tests

- ✅ Vérifier le calcul correct des pourcentages de satisfaction
- ✅ Valider l'identification des questions nécessitant une action (< 75%)
- ✅ Tester les cas limites et edge cases
- ✅ Garantir la structure des données retournées
- ✅ Vérifier la précision des calculs décimaux

## 📁 Structure des Fichiers

```
test/
├── behat.yml                                    # Configuration Behat
├── features/
│   ├── calculate_question_satisfaction.feature  # Scénarios de test (Gherkin)
│   └── bootstrap/
│       └── CalculateQuestionSatisfactionContext.php  # Contexte d'exécution
```

## 🚀 Installation

### 1. Installer les dépendances

```bash
cd /work/XPEHO/github/xpeapp_backend/plugins/xpeapp-backend
composer install
```

### 2. Vérifier l'installation de Behat

```bash
vendor/bin/behat --version
```

## 🧪 Exécution des Tests

### Tous les tests
```bash
composer test
# ou
vendor/bin/behat --config test/behat.yml
```

### Format détaillé (pretty)
```bash
composer test:pretty
# ou
vendor/bin/behat --config test/behat.yml --format=pretty
```

### Suite spécifique
```bash
composer test:suite
# ou
vendor/bin/behat --config test/behat.yml --suite=calculate_satisfaction
```

### Scénario spécifique
```bash
vendor/bin/behat --config test/behat.yml features/calculate_question_satisfaction.feature:10
# Où :10 est le numéro de ligne du scénario
```

### Mode dry-run (sans exécution)
```bash
vendor/bin/behat --config test/behat.yml --dry-run
```

## 📊 Scénarios de Test Couverts

### 1. **Satisfaction à 100%** ✅
- Toutes les réponses sont ≥ 4
- Pas d'action requise
- `requires_action = false`

### 2. **Satisfaction faible (< 75%)** 🔴
- Majorité de réponses < 4
- Action requise
- Ajoutée à `questions_requiring_action`

### 3. **Seuil limite (75%)** ⚠️
- Exactement 75% de satisfaction
- Pas d'action requise (≥ 75%)

### 4. **Seuil juste en dessous (74%)** 🔴
- 74% de satisfaction
- Action requise (< 75%)

### 5. **Plusieurs questions** 📊
- Calcul sur 3+ questions
- Vérification de `total_satisfaction`
- Comptage des questions nécessitant une action

### 6. **Question sans réponse** 📭
- `total_responses = 0`
- `satisfaction_percentage = 0%`
- Action requise

### 7. **Scores très faibles** 📉
- Uniquement scores 1, 2, 3
- `satisfaction_percentage = 0%`

### 8. **Précision décimale** 🔢
- Vérification arrondis à 2 décimales
- Exemple: 42.86%, 72.73%

### 9. **Plan de scénario** 📋
- Tests paramétrés de 0% à 100%
- Vérification du seuil à chaque palier

### 10. **Structure de données** 🏗️
- Validation des clés retournées
- Vérification des types de données

## 🔍 Exemples de Scénarios

### Scénario Simple

```gherkin
Scénario: Calcul de satisfaction pour une question avec toutes les réponses satisfaites
  Étant donné une campagne avec les données suivantes:
    | question_id | question_text                      |
    | 1           | Êtes-vous satisfait de votre poste?|
  Et les réponses suivantes pour la question 1:
    | value | numberAnswered |
    | 5     | 20             |
    | 4     | 15             |
  Quand je calcule la satisfaction des questions
  Alors le taux de satisfaction de la question 1 devrait être de 100%
  Et la question 1 ne devrait pas nécessiter d'action
```

### Plan de Scénario (Tests Paramétrés)

```gherkin
Plan du Scénario: Vérification des différents seuils de satisfaction
  Étant donné une campagne avec les données suivantes:
    | question_id | question_text   |
    | <qid>       | <question_text> |
  Et <satisfied> réponses satisfaites sur <total> réponses
  Quand je calcule la satisfaction des questions
  Alors le taux de satisfaction de la question <qid> devrait être de <percentage>%

  Exemples:
    | qid | question_text | satisfied | total | percentage |
    | 11  | Test 0%       | 0         | 100   | 0          |
    | 15  | Test 75%      | 75        | 100   | 75         |
    | 18  | Test 100%     | 100       | 100   | 100        |
```

## 📐 Contexte de Test (CalculateQuestionSatisfactionContext)

### Méthodes Principales

#### Initialisation
```php
@BeforeScenario - setUp()
```
Réinitialise les données avant chaque scénario

#### Étapes "Given"
```php
@Given que le seuil de satisfaction est de :threshold%
@Given une campagne avec les données suivantes:
@Given les réponses suivantes pour la question :questionId:
@Given :satisfied réponses satisfaites sur :total réponses
```

#### Étapes "When"
```php
@When je calcule la satisfaction des questions
```
Exécute `calculateQuestionSatisfaction()`

#### Étapes "Then"
```php
@Then le taux de satisfaction de la question :questionId devrait être de :percentage%
@Then la question :questionId devrait nécessiter une action
@Then le nombre de questions nécessitant une action devrait être de :count
@Then la satisfaction totale devrait être de :total%
```

## 🧮 Logique de Calcul Testée

### Formule de Satisfaction
```php
satisfaction = (réponses_satisfaites / total_réponses) × 100
```

### Critères
- **Réponse satisfaite** : `value >= 4`
- **Seuil d'action** : `satisfaction < 75%`
- **Arrondi** : 2 décimales

### Exemple de Calcul
```
Question: "Êtes-vous heureux?"
Réponses:
  - Score 5: 50 votes
  - Score 4: 25 votes
  - Score 3: 15 votes
  - Score 2: 10 votes

Total: 100 réponses
Satisfaites (≥4): 50 + 25 = 75
Satisfaction: (75/100) × 100 = 75.00%
Requires action: false (75% >= 75%)
```

## 📊 Données de Sortie Attendues

### Structure Retournée
```php
[
    'questions_analysis' => [
        [
            'question_id' => int,
            'question_text' => string,
            'satisfaction_percentage' => float,
            'total_responses' => int,
            'requires_action' => bool,
            'answers' => array
        ],
        ...
    ],
    'questions_requiring_action' => [...],  // Sous-ensemble
    'total_satisfaction' => float           // Somme des %
]
```

## 🔧 Maintenance

### Ajouter un Nouveau Scénario

1. Éditer `features/calculate_question_satisfaction.feature`
2. Ajouter un scénario Gherkin
3. Exécuter les tests

```bash
vendor/bin/behat --config test/behat.yml --append-snippets
```

### Ajouter une Nouvelle Étape

Si Behat ne reconnaît pas une étape, il proposera automatiquement le code PHP à ajouter dans le contexte.

### Déboguer un Test

```bash
# Mode verbeux
vendor/bin/behat --config test/behat.yml -v

# Afficher les définitions d'étapes
vendor/bin/behat --config test/behat.yml --definitions

# Lister les scénarios
vendor/bin/behat --config test/behat.yml --story-syntax
```

## ✅ Checklist de Qualité

- [x] Tests de calculs corrects (100%, 75%, 0%)
- [x] Tests de seuils limites (74.99%, 75.00%)
- [x] Tests de précision décimale
- [x] Tests sans réponses
- [x] Tests avec plusieurs questions
- [x] Tests de structure de données
- [x] Tests paramétrés (0-100%)
- [x] Validation des clés retournées
- [x] Gestion des edge cases

## 🎓 Ressources

- [Documentation Behat](https://docs.behat.org/)
- [Syntaxe Gherkin](https://cucumber.io/docs/gherkin/)
- [PHPUnit Assertions](https://phpunit.readthedocs.io/en/latest/assertions.html)

## 📈 Couverture de Tests

Ces tests couvrent:
- ✅ Calculs arithmétiques
- ✅ Logique conditionnelle (seuils)
- ✅ Itérations (boucles)
- ✅ Agrégations
- ✅ Edge cases
- ✅ Validation de données

**Couverture estimée: ~95%** de la fonction `calculateQuestionSatisfaction()`

## 🐛 Cas Non Couverts (À Considérer)

- ⚠️ Données corrompues (types invalides)
- ⚠️ Valeurs négatives
- ⚠️ Nombres très grands (overflow)
- ⚠️ Caractères spéciaux dans les textes

## 📝 Notes

- Les tests utilisent des objets `stdClass` pour simuler les données WordPress
- PHPUnit est utilisé pour les assertions
- Les scénarios sont en français pour correspondre au contexte métier
- Chaque scénario est indépendant grâce au `@BeforeScenario`
