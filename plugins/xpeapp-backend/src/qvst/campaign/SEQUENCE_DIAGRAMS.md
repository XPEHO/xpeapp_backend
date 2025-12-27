# Diagramme de Séquence - Analyse de Campagne QVST

## 1️⃣ Diagramme de Séquence Principal - Requête Complète

```mermaid
sequenceDiagram
    participant Client as Client REST
    participant API as WordPress REST API
    participant Handler as apiGetCampaignAnalysis()
    participant Logger as Logging System
    participant StatsAPI as api_get_qvst_stats_by_campaign_id()
    participant DB as Base de Données
    participant Analysis as Functions d'Analyse

    Client->>API: GET /xpeho/v1/qvst/campaigns/{id}:analysis
    API->>Handler: Appel avec WP_REST_Request
    
    Handler->>Logger: xpeapp_log_request(request)
    Logger-->>Handler: ✓ Logged
    
    Handler->>Handler: Extraction du campaign_id
    
    alt Paramètres Valides
        Handler->>StatsAPI: Appel api_get_qvst_stats_by_campaign_id()
        
        StatsAPI->>Logger: xpeapp_log_request()
        Logger-->>StatsAPI: ✓ Logged
        
        StatsAPI->>DB: SELECT * FROM qvst_campaign WHERE id=?
        DB-->>StatsAPI: Campaign Data
        
        StatsAPI->>DB: SELECT questions FROM qvst_questions<br/>INNER JOIN qvst_campaign_questions<br/>WHERE campaign_id=?
        DB-->>StatsAPI: Questions avec réponses
        
        StatsAPI->>DB: SELECT themes FROM qvst_theme<br/>WHERE campaign_id=?
        DB-->>StatsAPI: Themes de la campagne
        
        StatsAPI-->>Handler: WP_REST_Response(stats_data)
        
        Handler->>Analysis: calculateQuestionSatisfaction(stats_data)
        Analysis-->>Handler: question_results
        
        Handler->>Analysis: analyzeEmployeesAtRisk(wpdb, campaign_id)
        Analysis-->>Handler: employee_results
        
        Handler->>Analysis: calculateGlobalDistribution(questions_analysis)
        Analysis-->>Handler: global_distribution
        
        Handler->>Handler: Calcul métriques globales
        Handler->>Handler: Agrégation du rapport final
        
        Handler-->>API: Rapport complet (array)
        API-->>Client: JSON 200 OK
    else Paramètres Manquants
        Handler->>Logger: Error - No parameters
        Logger-->>Handler: ✓ Logged
        Handler-->>API: Tableau vide
        API-->>Client: JSON 200 avec array vide
    else Erreur Stats
        StatsAPI-->>Handler: WP_Error
        Handler->>Logger: Error - Stats error message
        Logger-->>Handler: ✓ Logged
        Handler-->>API: Tableau vide
        API-->>Client: JSON 200 avec array vide
    else Exception
        Handler->>Logger: Error - Exception message
        Logger-->>Handler: ✓ Logged
        Handler-->>API: Tableau vide
        API-->>Client: JSON 200 avec array vide
    end
```

---

## 2️⃣ Diagramme Détaillé - calculateQuestionSatisfaction()

```mermaid
sequenceDiagram
    participant Caller as API Handler
    participant Func as calculateQuestionSatisfaction()
    participant Variables as Variables d'État

    Caller->>Func: stats_data
    
    Func->>Variables: questions_analysis = []
    Func->>Variables: questions_requiring_action = []
    Func->>Variables: total_satisfaction = 0

    loop Pour chaque question dans stats_data['questions']
        Func->>Func: total_responses = 0
        Func->>Func: satisfied_count = 0
        
        loop Pour chaque réponse dans question->answers
            Func->>Func: count = answer->numberAnswered
            Func->>Func: value = answer->value
            
            Func->>Func: total_responses += count
            
            alt value >= 4
                Func->>Func: satisfied_count += count
            else value < 4
                Note over Func: Aucune action
            end
        end
        
        Func->>Variables: satisfaction_percentage = <br/>(satisfied_count / total_responses) × 100
        
        Func->>Variables: question_data = {<br/>  question_id,<br/>  question_text,<br/>  satisfaction_percentage,<br/>  total_responses,<br/>  requires_action (< 75%),<br/>  answers<br/>}
        
        Func->>Variables: questions_analysis[] << question_data
        Func->>Variables: total_satisfaction += satisfaction_percentage
        
        alt requires_action == true
            Func->>Variables: questions_requiring_action[] << question_data
        end
    end
    
    Func->>Func: Prépare retour
    
    Func-->>Caller: {<br/>  questions_analysis,<br/>  questions_requiring_action,<br/>  total_satisfaction<br/>}
```

---

## 3️⃣ Diagramme Détaillé - analyzeEmployeesAtRisk()

```mermaid
sequenceDiagram
    participant Caller as API Handler
    participant Func as analyzeEmployeesAtRisk()
    participant WPDB as WordPress WPDB
    participant Result as Variables de Résultat

    Caller->>Func: wpdb, campaign_id
    
    Func->>Func: Initialise noms de tables
    
    Func->>WPDB: SELECT answer_group_id, value<br/>FROM qvst_campaign_answers<br/>INNER JOIN qvst_answers<br/>WHERE campaign_id = ?
    WPDB-->>Func: employee_answers[]
    
    Func->>WPDB: SELECT answer_group_id, text<br/>FROM qvst_open_answers<br/>WHERE answer_group_id IN (...)
    WPDB-->>Func: open_answers[]
    
    Func->>Result: employees_data = {}
    
    loop Pour chaque row dans employee_answers
        Func->>Func: group_id = row->answer_group_id
        Func->>Func: value = (int)row->answer_value
        
        alt group_id n'existe pas dans employees_data
            Func->>Result: employees_data[group_id] = {<br/>  total_responses: 0,<br/>  satisfied_count: 0,<br/>  open_answer: null<br/>}
        end
        
        Func->>Result: employees_data[group_id]['total_responses']++
        
        alt value >= 4
            Func->>Result: employees_data[group_id]['satisfied_count']++
        end
    end
    
    loop Pour chaque open_answer
        Func->>Result: employees_data[group_id]['open_answer'] = <br/>open_answer_text
    end
    
    Func->>Result: at_risk_employees = []
    
    loop Pour chaque employee dans employees_data
        Func->>Func: satisfaction = <br/>(satisfied_count / total_responses) × 100
        
        alt satisfaction < 75
            Func->>Result: at_risk_employees[] << {<br/>  anonymous_user_id,<br/>  satisfaction_percentage,<br/>  total_responses,<br/>  open_answer<br/>}
        end
    end
    
    Func-->>Caller: {<br/>  employees_data,<br/>  at_risk_employees<br/>}
```

---

## 4️⃣ Diagramme Détaillé - calculateGlobalDistribution()

```mermaid
sequenceDiagram
    participant Caller as API Handler
    participant Func as calculateGlobalDistribution()
    participant Agg as Agrégation

    Caller->>Func: questions_analysis
    
    Func->>Agg: global_distribution = {}
    
    loop Pour chaque question dans questions_analysis
        loop Pour chaque answer dans question['answers']
            Func->>Func: score = answer->value
            
            alt score n'existe pas dans global_distribution
                Func->>Agg: global_distribution[score] = 0
            end
            
            Func->>Agg: global_distribution[score] += <br/>answer->numberAnswered
        end
    end
    
    Func->>Func: Convertit en array
    
    Func->>Func: Trie par score DESCENDING (5→1)
    
    loop Résultats triés
        Func->>Agg: global_distribution_array[] << <br/>{score, count}
    end
    
    Func-->>Caller: global_distribution_array
```

---

## 5️⃣ Flux Complet d'Agrégation - apiGetCampaignAnalysis()

```mermaid
graph TD
    A["🔵 Début:<br/>GET Campaign Analysis"] --> B["Validation<br/>ID Campagne"]
    
    B -->|ID Manquant| C["❌ Log Error<br/>Retour: []"]
    B -->|ID Valide| D["📊 Fetch Stats<br/>api_get_qvst_stats_by_campaign_id"]
    
    D -->|Error| E["❌ Log Error<br/>Retour: []"]
    D -->|Success| F["✅ Stats Data"]
    
    F --> G["🔄 Calculs Parallèles"]
    
    G --> G1["Question Satisfaction<br/>calculateQuestionSatisfaction"]
    G --> G2["Employees at Risk<br/>analyzeEmployeesAtRisk"]
    G --> G3["Global Distribution<br/>calculateGlobalDistribution"]
    
    G1 --> H["Questions Analysis<br/>Questions Requiring Action<br/>Total Satisfaction"]
    G2 --> I["Employees Data<br/>At Risk Employees<br/>Open Answers"]
    G3 --> J["Distribution Array<br/>Scores Sorted"]
    
    H --> K["Métriques Globales"]
    I --> K
    J --> K
    
    K --> L["📈 Calculs:<br/>- Average Satisfaction<br/>- Requires Action<br/>- At Risk Count"]
    
    L --> M["📋 Agrégation Finale"]
    
    M --> N["Rapport Complet:<br/>- campaign_id<br/>- campaign_name<br/>- campaign_status<br/>- start_date<br/>- end_date<br/>- themes<br/>- global_stats<br/>- global_distribution<br/>- questions_analysis<br/>- questions_requiring_action<br/>- at_risk_employees"]
    
    N --> O["🟢 Retour<br/>JSON Response"]
    
    E --> O
    C --> O
```

---

## 6️⃣ Diagramme États - Question

```mermaid
stateDiagram-v2
    [*] --> Analyzed
    
    Analyzed --> HighSatisfaction: Satisfaction ≥ 75%
    Analyzed --> LowSatisfaction: Satisfaction < 75%
    
    HighSatisfaction --> Ready: Mark requires_action = false
    LowSatisfaction --> AtRisk: Mark requires_action = true
    LowSatisfaction --> ToAction: Add to questions_requiring_action
    
    Ready --> Output
    AtRisk --> Output
    ToAction --> Output
    
    Output --> [*]
```

---

## 7️⃣ Diagramme États - Employé

```mermaid
stateDiagram-v2
    [*] --> Evaluated
    
    Evaluated --> Healthy: Satisfaction ≥ 75%
    Evaluated --> AtRisk: Satisfaction < 75%
    
    Healthy --> Included: In employees_data only
    AtRisk --> Flagged: Add to at_risk_employees
    AtRisk --> WithComment: Attach open_answer if exists
    
    Included --> Output
    Flagged --> Output
    WithComment --> Output
    
    Output --> [*]
```

---

## 8️⃣ Flux de Données - Structure

```mermaid
graph LR
    A["Campaign ID"] --> B["Campaign Data"]
    B --> C["Questions<br/>+ Réponses"]
    B --> D["Themes"]
    C --> E["calculateQuestion<br/>Satisfaction"]
    E --> F["Questions Analysis"]
    E --> G["Global Distribution"]
    
    A --> H["Employee Answers"]
    H --> I["analyzeEmployees<br/>AtRisk"]
    I --> J["At Risk Employees"]
    
    A --> K["Open Answers"]
    K --> I
    
    F --> L["Rapport Final"]
    G --> L
    J --> L
    D --> L
    B --> L
    
    L --> M["JSON Response"]
```

---

## 9️⃣ Matrice de Dépendances

```mermaid
graph TB
    subgraph Input["🔵 Entrées"]
        I1["WP_REST_Request"]
        I2["Global wpdb"]
        I3["Campaign ID"]
    end
    
    subgraph External["🟠 Appels Externes"]
        E1["api_get_qvst_stats_by_campaign_id"]
        E2["Logging System"]
    end
    
    subgraph Analyis["🟢 Fonctions d'Analyse"]
        A1["calculateQuestionSatisfaction"]
        A2["analyzeEmployeesAtRisk"]
        A3["calculateGlobalDistribution"]
    end
    
    subgraph Output["🔴 Sortie"]
        O1["Rapport JSON"]
    end
    
    I1 --> E1
    I2 --> A2
    I3 --> E1
    I3 --> A2
    
    E1 --> A1
    E1 --> A3
    
    A1 --> A3
    
    E1 --> O1
    A1 --> O1
    A2 --> O1
    A3 --> O1
    
    E2 -.->|Logging| E1
    E2 -.->|Logging| A1
    E2 -.->|Logging| A2
```

---

## 🔟 Timeline d'Exécution - Performance

```mermaid
timeline
    title Chronologie Estimée d'Exécution (Campagne: 100 questions, 500 répondants)
    
    section Initialisation
        Validation : 1-2ms : a1
        Setup : 1ms : a2
    
    section Fetch Stats
        DB Query Campaigns : 5ms : b1
        DB Query Questions : 10ms : b2
        DB Query Themes : 2ms : b3
        Serialization : 2ms : b4
    
    section Analyse Parallèle
        Question Satisfaction (100Q × 5A) : 15ms : c1
        Employees at Risk (500E) : 20ms : c2
        Global Distribution : 8ms : c3
    
    section Agrégation
        Calcul Métriques : 2ms : d1
        Composition Réponse : 3ms : d2
    
    section Total
        Temps Total : 81ms : total
```

---

## 1️⃣1️⃣ Cas d'Erreur - Flow Diagramme

```mermaid
graph TD
    Start["🔴 Requête Reçue"] --> Check1{"Campaign ID<br/>Valide?"}
    
    Check1 -->|Non| Error1["❌ Error:<br/>No parameters"]
    Error1 --> Log1["📝 Log Error"]
    Log1 --> Return1["Return: []"]
    
    Check1 -->|Oui| Stats["📊 Fetch Stats"]
    
    Stats --> Check2{"Stats Réussis?"}
    
    Check2 -->|WP_Error| Error2["❌ Error:<br/>Stats failed"]
    Error2 --> Log2["📝 Log Error Message"]
    Log2 --> Return2["Return: []"]
    
    Check2 -->|Succès| Analysis["🔄 Analyse"]
    
    Analysis --> Check3{"Exception<br/>Levée?"}
    
    Check3 -->|Oui| Error3["❌ Exception Caught"]
    Error3 --> Log3["📝 Log Exception"]
    Log3 --> Return3["Return: []"]
    
    Check3 -->|Non| Success["✅ Analyse Complète"]
    Success --> Return4["Return: Rapport"]
    
    Return1 --> End["🟢 Fin"]
    Return2 --> End
    Return3 --> End
    Return4 --> End
```

---

## 1️⃣2️⃣ Exemple Visuel - Satisfaction Calculation

```
Question: "Êtes-vous satisfait de votre environnement?"
─────────────────────────────────────────────────────

Réponses Reçues:
┌─────┬──────────┬───────────┐
│Score│Description │Nombre   │
├─────┼──────────┼───────────┤
│  5  │ Très bien  │ 45 votes │
│  4  │ Bien       │ 35 votes │
│  3  │ Moyen      │ 15 votes │ ─────┐
│  2  │ Mauvais    │ 10 votes │      │ Pas de satisfaction
│  1  │ Très mal   │  5 votes │ ─────┘
└─────┴──────────┴───────────┘

Calcul:
──────
Total réponses       = 45 + 35 + 15 + 10 + 5 = 110
Satisfaites (≥4)     = 45 + 35 = 80
Pourcentage          = (80 / 110) × 100 = 72.73%

Résultat:
─────────
Satisfaction:        72.73%
Requires Action:     TRUE (< 75%)

Statut:  🟠 ALERTE - Action requise
```

