# GetCampaignAnalysis

## Objectif
La classe `GetCampaignAnalysis` construit une vue d'analyse complete d'une campagne QVST a partir des statistiques existantes et des reponses stockees.

Elle retourne :
- les stats globales de campagne (respondants, moyenne de satisfaction, besoin d'action)
- la satisfaction par question
- les questions qui necessitent une action
- la liste des collaborateurs anonymes a risque
- la distribution globale des scores

## Regles metier principales
- Une reponse est consideree comme **satisfaisante** si le score est `>= 4`.
- Une question est marquee **requires_action** si sa satisfaction est `< 75%`.
- Un collaborateur est **a risque** si sa satisfaction globale est `< 75%` et qu'il a au moins une reponse.
- Les questions inversees sont remappees avec la formule :
  - `valeur_normalisee = max + min - valeur`

## Flux general
1. L'API recoit l'identifiant de campagne.
2. Elle appelle `GetStatsOfCampaign::apiGetQvstStatsByCampaignId` pour recuperer les stats brutes.
3. Elle calcule la satisfaction par question.
4. Elle analyse les repondants a risque via les tables SQL de reponses.
5. Elle calcule la distribution globale des scores.
6. Elle assemble la reponse finale JSON.

## Diagramme de sequence (Mermaid)
```mermaid
sequenceDiagram
    autonumber
    actor Client
    participant API as GetCampaignAnalysis.apiGetCampaignAnalysis
    participant Log as xpeapp_log
    participant Stats as GetStatsOfCampaign.apiGetQvstStatsByCampaignId
    participant CalcQ as calculateQuestionSatisfaction
    participant Risk as analyzeEmployeesAtRisk
    participant Dist as calculateGlobalDistribution
    participant DB as wpdb

    Client->>API: GET /qvst/campaigns/{id}/analysis
    API->>API: Lire params et campaign_id

    alt campaign_id vide
        API->>Log: Log erreur "No parameters"
        API-->>Client: {}
    else campaign_id present
        API->>Stats: Requete interne /stats + id
        Stats-->>API: WP_REST_Response | WP_Error

        alt WP_Error
            API->>Log: Log erreur stats
            API-->>Client: {}
        else Reponse stats valide
            API->>CalcQ: calculateQuestionSatisfaction(stats_data)
            CalcQ-->>API: questions_analysis + total_satisfaction

            API->>Risk: analyzeEmployeesAtRisk(wpdb, campaign_id)
            Risk->>DB: SELECT reponses fermees + ouvertes
            DB-->>Risk: resultats SQL
            Risk-->>API: employees_data + at_risk_employees

            API->>Dist: calculateGlobalDistribution(questions_analysis)
            Dist-->>API: global_distribution_array

            API->>API: Calcul average_satisfaction + global_stats
            API-->>Client: payload analyse complet
        end
    end
```

## Diagramme de l'algorithme `calculateQuestionSatisfaction` (Mermaid)
```mermaid
flowchart TD
    A[Debut calculateQuestionSatisfaction] --> B[Initialiser questions_analysis, questions_requiring_action, total_satisfaction=0]
    B --> C{Boucle sur les questions}
    C -->|Oui| D[Lire le flag reversed]
    D --> E[Calculer min et max de l echelle]
    E --> F[Recuperer min_value et max_value]
    F --> G[Compter total et satisfaits]
    G --> H[Recuperer total_responses et satisfied_count]
    H --> I{total_responses > 0 ?}
    I -->|Oui| J[Calculer le pourcentage de satisfaction]
    I -->|Non| K[satisfaction_percentage = 0]
    J --> L[Construire question_data]
    K --> L
    L --> M[Ajouter question_data a questions_analysis]
    M --> N[Ajouter la satisfaction au total]
    N --> O{Satisfaction inferieure a 75}
    O -->|Oui| P[Ajouter question_data a questions_requiring_action]
    O -->|Non| Q[Question suivante]
    P --> Q
    Q --> C
    C -->|Non| R[Retourner questions_analysis, questions_requiring_action, total_satisfaction]
    R --> S[Fin]
```