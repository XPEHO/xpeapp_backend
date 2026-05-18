# CalculateQuestionSatisfaction — Explication simple

## Objectif
Expliquer de façon concise et chiffrée le calcul de "satisfaction" implémenté dans `calculateQuestionSatisfaction` et ses fonctions utilitaires (`getMinMaxAnswerValues`, `getSatisfactionCounts`).

## Entrées
- Pour chaque question : une liste d'objets `answers` où chaque élément contient `value` (score) et `numberAnswered` (nombre de réponses pour ce score).
- Le champ `reversed_question` indique s'il faut normaliser les valeurs.

## Étapes principales
1. Pour chaque question :
   - Déterminer l'échelle (min, max) via `getMinMaxAnswerValues(answers)`.
   - Calculer le total de réponses `T` et le nombre de réponses satisfaites `S` via `getSatisfactionCounts(answers, is_reversed, min, max)`.
   - Si `T > 0`, calculer le pourcentage de satisfaction :

$$
\text{satisfaction\_percentage} = \mathrm{round}\left(\frac{S}{T} \times 100, 2\right)
$$

   - Sinon, `satisfaction_percentage = 0`.
   - Une question est marquée `requires_action` si `satisfaction_percentage < 75`.
   - Ajouter `satisfaction_percentage` au cumul `total_satisfaction`.

2. Retourner pour la campagne :
   - `questions_analysis` (détail par question),
   - `questions_requiring_action`,
   - `total_satisfaction` (somme des pourcentages par question).

## Règles métier (rappel)
- Score considéré comme "satisfaisant" si sa valeur (après normalisation) est $\ge 4$.
- Question nécessitant action si son pourcentage de satisfaction est $< 75$.
- Normalisation pour questions inversées :

$$
v_{norm} = v_{max} + v_{min} - v
$$

## Détail des calculs
- Calcul des bornes :
  - `min = min(answer.value)`
  - `max = max(answer.value)`

- Compte des réponses :
  - Total de réponses :

$$
T = \sum_{i} numberAnswered_i
$$

  - Réponses satisfaites (après normalisation si nécessaire) :

$$
S = \sum_{i\;:\;value_i^{(norm)} \ge 4} numberAnswered_i
$$

- Pourcentage par question :

$$
\text{satisfaction\_percentage} = \begin{cases}
\mathrm{round}\left(\dfrac{S}{T}\times 100,2\right) & \text{si } T>0 \\[6pt]
0 & \text{sinon}
\end{cases}
$$

- Moyenne des satisfactions sur toutes les questions (si $N$ questions) :

$$
\text{average\_satisfaction} = \begin{cases}
\mathrm{round}\left(\dfrac{\sum_{i=1}^{N} satisfaction\_percentage_i}{N},2\right) & \text{si } N>0 \\[6pt]
0 & \text{sinon}
\end{cases}
$$

## Exemple chiffré simple
Question (non inversée) avec les réponses :

- value = 1, numberAnswered = 5
- value = 2, numberAnswered = 10
- value = 4, numberAnswered = 20
- value = 5, numberAnswered = 15

Calcul :
- $T = 5 + 10 + 20 + 15 = 50$
- Réponses satisfaites (value >= 4) : $S = 20 + 15 = 35$
- Pourcentage : $\dfrac{35}{50} \times 100 = 70.00\%$
- `requires_action` = $70.00 < 75$ → Oui

## Exemple avec question inversée
Même distribution mais la question est marquée `reversed_question = true` et l'échelle est de 1 à 5.
- Normaliser chaque valeur : $v_{norm} = 5 + 1 - v = 6 - v$.
  - value 1 → 5 (satisfait)
  - value 2 → 4 (satisfait)
  - value 4 → 2 (non satisfait)
  - value 5 → 1 (non satisfait)

- Recalcul des satisfaits : nombreAnswered pour normalized value >= 4 → value 1 (norm 5) 5 réponses + value 2 (norm 4) 10 réponses = 15
- T reste 50, S = 15 → satisfaction = 30.00% → `requires_action` = Oui

---

Fichier généré automatiquement pour documenter les calculs de `calculateQuestionSatisfaction`.
