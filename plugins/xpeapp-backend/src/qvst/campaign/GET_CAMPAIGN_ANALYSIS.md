# Documentation - Analyse de Campagne QVST

## 📋 Vue d'ensemble

Le fichier `get_campaign_analysis.php` fournit une analyse complète et détaillée des résultats d'une campagne de satisfaction (QVST). Il extrait et calcule plusieurs indicateurs clés pour évaluer la satisfaction des employés et identifier les domaines nécessitant une action.

**Endpoint REST:** `GET /xpeho/v1/qvst/campaigns/{id}:analysis`

---

## 🎯 Objectifs Principaux

1. **Analyser la satisfaction par question** : Calculer le pourcentage de satisfaction pour chaque question
2. **Identifier les employés à risque** : Détecter les employés ayant une satisfaction < 75%
3. **Calculer la distribution globale** : Analyser la répartition des réponses par score
4. **Fournir des métriques globales** : Calculer les statistiques d'ensemble de la campagne
5. **Identifier les axes d'amélioration** : Lister les questions et employés nécessitant une action

---

## 📊 Fonctions Principales

### 1. `calculateQuestionSatisfaction($stats_data)`

**Objectif:** Calculer le taux de satisfaction pour chaque question de la campagne.

**Paramètres:**
- `$stats_data` (array) : Données des statistiques de campagne

**Logique:**
- Itère sur chaque question et ses réponses
- Compte les réponses satisfaites (valeur ≥ 4)
- Calcule le pourcentage : `(réponses_satisfaites / total_réponses) × 100`
- Identifie les questions nécessitant une action (satisfaction < 75%)

**Retour:**
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
    'questions_requiring_action' => [...],  // Sous-ensemble avec requires_action = true
    'total_satisfaction' => float            // Somme de toutes les satisfactions
]
```

**Exemple de calcul:**
```
Question: "Êtes-vous satisfait?"
Réponses: 5 (score 5), 3 (score 4), 2 (score 2), 1 (score 1)
Total: 11 réponses
Satisfaites (≥4): 5 + 3 = 8 réponses
Satisfaction: (8/11) × 100 = 72.73%
Requires action: true (< 75%)
```

---

### 2. `analyzeEmployeesAtRisk($wpdb, $campaign_id)`

**Objectif:** Identifier les employés ayant une faible satisfaction et récupérer leurs commentaires.

**Paramètres:**
- `$wpdb` (wpdb) : Instance WordPress database
- `$campaign_id` (int) : ID de la campagne

**Requêtes SQL:**
1. **Récupère les réponses:** Joint les réponses de campagne avec les valeurs de scoring
2. **Récupère les commentaires:** Extrait les commentaires libres (open answers) associés

**Traitement:**
- Agrège les réponses par `answer_group_id` (employé)
- Calcule la satisfaction pour chaque employé
- Filtre ceux avec satisfaction < 75%
- Associe les commentaires libres

**Retour:**
```php
[
    'employees_data' => [
        'group_id' => [
            'total_responses' => int,
            'satisfied_count' => int,
            'open_answer' => string|null
        ],
        ...
    ],
    'at_risk_employees' => [
        [
            'anonymous_user_id' => int,
            'satisfaction_percentage' => float,
            'total_responses' => int,
            'open_answer' => string|null
        ],
        ...
    ]
]
```

**Exemple:**
```
Employé ID 42:
- Total réponses: 10
- Réponses satisfaites: 6
- Satisfaction: 60%
- Commentaire: "Les conditions de travail pourraient s'améliorer"
=> Inclus dans at_risk_employees (60% < 75%)
```

---

### 3. `calculateGlobalDistribution($questions_analysis)`

**Objectif:** Créer un histogramme de distribution des réponses par score.

**Paramètres:**
- `$questions_analysis` (array) : Résultats de `calculateQuestionSatisfaction()`

**Logique:**
- Parcourt toutes les questions et réponses
- Agrège les comptes par score (1-5)
- Trie par score décroissant

**Retour:**
```php
[
    ['score' => 5, 'count' => 150],
    ['score' => 4, 'count' => 120],
    ['score' => 3, 'count' => 45],
    ['score' => 2, 'count' => 30],
    ['score' => 1, 'count' => 15]
]
```

**Visualisation:**
```
Score 5: ████████████████ 150 réponses (37.5%)
Score 4: ████████████   120 réponses (30%)
Score 3: █████            45 réponses (11.25%)
Score 2: ███              30 réponses (7.5%)
Score 1: ██               15 réponses (3.75%)
         ─────────────────────────────────
         Total:          400 réponses
```

---

### 4. `apiGetCampaignAnalysis(WP_REST_Request $request)` ⭐ Fonction Principale

**Objectif:** Endpoint REST qui orchestre toute l'analyse et retourne un rapport complet.

**Paramètres:**
- `$request` (WP_REST_Request) : Requête REST contenant `id` (campaign_id)

**Processus:**
1. Valide les paramètres
2. Appelle `api_get_qvst_stats_by_campaign_id()` pour récupérer les données brutes
3. Exécute les trois analyses parallèles :
   - Satisfaction par question
   - Employés à risque
   - Distribution globale
4. Calcule les métriques globales
5. Agrège tout dans un rapport structuré

**Gestion d'erreurs:**
- Capture les erreurs de requête stats
- Enregistre les exceptions avec logging
- Retourne un résultat vide en cas d'erreur

**Retour (Succès):**
```php
[
    'campaign_id' => int,
    'campaign_name' => string,
    'campaign_status' => string,
    'start_date' => string,
    'end_date' => string,
    'themes' => array,
    
    'global_stats' => [
        'total_respondents' => int,
        'total_questions' => int,
        'average_satisfaction' => float,        // Moyenne de satisfaction
        'requires_action' => bool,              // true si < 75%
        'at_risk_count' => int                  // Nombre d'employés à risque
    ],
    
    'global_distribution' => array,             // Distribution par score
    'questions_analysis' => array,              // Détail par question
    'questions_requiring_action' => array,      // Questions problématiques
    'at_risk_employees' => array                // Employés à risque
]
```

---

## 📈 Métriques Clés

| Métrique | Calcul | Seuil d'Alerte |
|----------|--------|-----------------|
| Satisfaction Question | (réponses ≥4) / total × 100 | < 75% |
| Satisfaction Employé | (réponses ≥4) / total × 100 | < 75% |
| Satisfaction Globale | Moyenne de toutes les questions | < 75% |
| Distribution | Histogramme des scores | - |

---

## 🔗 Dépendances

- **`get_stats_of_campaign.php`** : Fonction `api_get_qvst_stats_by_campaign_id()`
- **`campaign_themes_utils.php`** : Utilitaires de thèmes (appelé via get_stats_of_campaign.php)
- **`Xpeapp_Log_Level`** : Système de logging personnalisé
- **WordPress REST API** : Framework REST natif

---

## 📐 Architecture des Données

### Flux de Données

```
┌─────────────────────────────────────────────────────────┐
│ Requête: GET /xpeho/v1/qvst/campaigns/{id}:analysis    │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────┐
│  apiGetCampaignAnalysis()                               │
│  - Valide l'ID de campagne                              │
│  - Appelle api_get_qvst_stats_by_campaign_id()         │
└────────────────────┬────────────────────────────────────┘
                     │
                     ▼
        ┌────────────┴────────────┐
        │ Stats brutes retournées │
        └────────┬────────────────┘
                 │
        ┌────────┴────────────────────────────┐
        │                                     │
        ▼                                     ▼
  ┌─────────────────┐          ┌──────────────────────┐
  │ Questions       │          │ Réponses Employés    │
  │ + Réponses      │          │ + Commentaires       │
  └────────┬────────┘          └──────────┬───────────┘
           │                              │
    ┌──────┴─────────┬──────────┐        │
    │                │          │        │
    ▼                ▼          ▼        ▼
 calculateQuestionSatisfaction() analyzeEmployeesAtRisk()
         │                             │
         ▼                             ▼
 Question Satisfaction          Employees At Risk
         │                             │
         └──────────────┬──────────────┘
                        │
                        ▼
            calculateGlobalDistribution()
                        │
                        ▼
            Global Distribution
                        │
    ┌───────────────────┼───────────────────┐
    │                   │                   │
    ▼                   ▼                   ▼
Questions        Employees              Distribution
Analysis         At Risk                (Histogramme)
    │                   │                   │
    └───────────────────┼───────────────────┘
                        │
                        ▼
         ┌──────────────────────────┐
         │ Rapport Complet          │
         │ - Campaign Info          │
         │ - Global Stats           │
         │ - Questions Analysis     │
         │ - At Risk Employees      │
         │ - Distribution           │
         └──────────────────────────┘
                        │
                        ▼
         ┌──────────────────────────┐
         │  Réponse JSON au Client  │
         └──────────────────────────┘
```

---

## 🚨 Cas d'Usage et Exemples

### Cas 1: Campagne Bien Notée
```json
{
  "campaign_id": 1,
  "global_stats": {
    "total_respondents": 50,
    "average_satisfaction": 82.5,
    "requires_action": false,
    "at_risk_count": 2
  },
  "questions_analysis": [
    {
      "question_id": 1,
      "question_text": "Êtes-vous heureux?",
      "satisfaction_percentage": 85.0,
      "requires_action": false
    }
  ]
}
```
**Action:** Aucune action requise. Maintenir les bonnes pratiques.

---

### Cas 2: Campagne Problématique
```json
{
  "campaign_id": 2,
  "global_stats": {
    "total_respondents": 100,
    "average_satisfaction": 68.0,
    "requires_action": true,
    "at_risk_count": 35
  },
  "questions_requiring_action": [
    {
      "question_id": 5,
      "question_text": "Conditions de travail adéquates?",
      "satisfaction_percentage": 52.3,
      "requires_action": true
    }
  ],
  "at_risk_employees": [
    {
      "anonymous_user_id": 42,
      "satisfaction_percentage": 40.0,
      "open_answer": "Nous avons besoin d'améliorer l'équipement"
    }
  ]
}
```
**Action:** Priorité d'amélioration sur les conditions de travail.

---

## 🔍 Points d'Attention

1. **Anonymisation:** Les employés sont identifiés par `answer_group_id`, pas par ID personnel
2. **Seuil de 75%:** Défini en dur dans le code - peut être un candidat pour la configuration
3. **Commentaires libres:** Peuvent contenir du texte brut potentiellement sensible
4. **Performances:** Les requêtes SQL pourraient nécessiter des indexes sur:
   - `qvst_campaign_answers.campaign_id`
   - `qvst_campaign_answers.answer_group_id`
   - `qvst_open_answers.answer_group_id`

---

## 🔧 Maintenance et Évolution

### Points Possibles d'Amélioration

1. **Extraire les constantes:**
   ```php
   define('SATISFACTION_THRESHOLD', 75); // Actuellement en dur
   define('SATISFIED_SCORE_MIN', 4);      // Actuellement en dur
   ```

2. **Cacher les résultats:**
   ```php
   // Les analyses pourraient être cachées pour les grandes campagnes
   $cache_key = "campaign_analysis_{$campaign_id}";
   $cached = wp_cache_get($cache_key);
   if ($cached) return $cached;
   ```

3. **Pagination pour les employés à risque:**
   ```php
   // Supporter la pagination pour les grandes populations
   if (count($at_risk_employees) > 1000) {
       // Implémenter la pagination
   }
   ```

---

## ✅ Tests Recommandés

- [ ] Campagne sans réponses
- [ ] Campagne avec un seul répondant
- [ ] Campagne avec commentaires vides/nulls
- [ ] Campagne avec des scores extrêmes (tous 1 ou tous 5)
- [ ] Campagne avec des réponses partielles
- [ ] Charge: 10000+ répondants

