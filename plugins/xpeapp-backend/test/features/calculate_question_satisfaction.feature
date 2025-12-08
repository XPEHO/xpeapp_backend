# language: fr
Fonctionnalité: Calcul de la satisfaction par question
  En tant que système d'analyse QVST
  Je veux calculer le taux de satisfaction pour chaque question
  Afin d'identifier les questions nécessitant une action

  Contexte:
    Étant donné que le seuil de satisfaction est de 75%
    Et que le score minimal de satisfaction est de 4

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
    Et le total de réponses devrait être de 35

  Scénario: Calcul de satisfaction pour une question nécessitant une action
    Étant donné une campagne avec les données suivantes:
      | question_id | question_text                             |
      | 2           | Les conditions de travail sont-elles bonnes?|
    Et les réponses suivantes pour la question 2:
      | value | numberAnswered |
      | 5     | 10             |
      | 4     | 15             |
      | 3     | 20             |
      | 2     | 10             |
      | 1     | 5              |
    Quand je calcule la satisfaction des questions
    Alors le taux de satisfaction de la question 2 devrait être de 41.67%
    Et la question 2 devrait nécessiter une action
    Et la question 2 devrait apparaître dans les questions nécessitant une action

  Scénario: Calcul de satisfaction pour une question au seuil limite (75%)
    Étant donné une campagne avec les données suivantes:
      | question_id | question_text                    |
      | 3           | Votre équipe est-elle efficace?  |
    Et les réponses suivantes pour la question 3:
      | value | numberAnswered |
      | 5     | 50             |
      | 4     | 25             |
      | 3     | 15             |
      | 2     | 10             |
    Quand je calcule la satisfaction des questions
    Alors le taux de satisfaction de la question 3 devrait être de 75%
    Et la question 3 ne devrait pas nécessiter d'action

  Scénario: Calcul de satisfaction juste en dessous du seuil (74.99%)
    Étant donné une campagne avec les données suivantes:
      | question_id | question_text                      |
      | 4           | La communication est-elle claire?  |
    Et les réponses suivantes pour la question 4:
      | value | numberAnswered |
      | 5     | 50             |
      | 4     | 24             |
      | 3     | 25             |
      | 2     | 1              |
    Quand je calcule la satisfaction des questions
    Alors le taux de satisfaction de la question 4 devrait être de 74%
    Et la question 4 devrait nécessiter une action

  Scénario: Calcul de satisfaction pour plusieurs questions
    Étant donné une campagne avec les données suivantes:
      | question_id | question_text                        |
      | 5           | Satisfaction générale?               |
      | 6           | Recommanderiez-vous l'entreprise?    |
      | 7           | Équilibre vie pro/perso acceptable?  |
    Et les réponses suivantes pour la question 5:
      | value | numberAnswered |
      | 5     | 30             |
      | 4     | 20             |
    Et les réponses suivantes pour la question 6:
      | value | numberAnswered |
      | 5     | 10             |
      | 4     | 10             |
      | 3     | 10             |
      | 2     | 10             |
      | 1     | 10             |
    Et les réponses suivantes pour la question 7:
      | value | numberAnswered |
      | 5     | 40             |
      | 4     | 40             |
      | 3     | 10             |
      | 2     | 5              |
      | 1     | 5              |
    Quand je calcule la satisfaction des questions
    Alors le nombre total de questions devrait être de 3
    Et le taux de satisfaction de la question 5 devrait être de 100%
    Et le taux de satisfaction de la question 6 devrait être de 40%
    Et le taux de satisfaction de la question 7 devrait être de 80%
    Et le nombre de questions nécessitant une action devrait être de 1
    Et la satisfaction totale devrait être de 220%

  Scénario: Gestion d'une question sans réponse
    Étant donné une campagne avec les données suivantes:
      | question_id | question_text                    |
      | 8           | Question sans réponse            |
    Et aucune réponse pour la question 8
    Quand je calcule la satisfaction des questions
    Alors le taux de satisfaction de la question 8 devrait être de 0%
    Et la question 8 devrait nécessiter une action
    Et le total de réponses devrait être de 0

  Scénario: Scores faibles uniquement (tous insatisfaits)
    Étant donné une campagne avec les données suivantes:
      | question_id | question_text                      |
      | 9           | Question avec scores très bas      |
    Et les réponses suivantes pour la question 9:
      | value | numberAnswered |
      | 3     | 10             |
      | 2     | 15             |
      | 1     | 25             |
    Quand je calcule la satisfaction des questions
    Alors le taux de satisfaction de la question 9 devrait être de 0%
    Et la question 9 devrait nécessiter une action
    Et le total de réponses devrait être de 50

  Scénario: Calcul avec précision décimale
    Étant donné une campagne avec les données suivantes:
      | question_id | question_text                      |
      | 10          | Test de précision décimale         |
    Et les réponses suivantes pour la question 10:
      | value | numberAnswered |
      | 5     | 2              |
      | 4     | 1              |
      | 3     | 2              |
      | 2     | 1              |
      | 1     | 1              |
    Quand je calcule la satisfaction des questions
    Alors le taux de satisfaction de la question 10 devrait être de 42.86%
    Et la question 10 devrait nécessiter une action

  Plan du Scénario: Vérification des différents seuils de satisfaction
    Étant donné une campagne avec les données suivantes:
      | question_id | question_text   |
      | <qid>       | <question_text> |
    Et <satisfied> réponses satisfaites sur <total> réponses
    Quand je calcule la satisfaction des questions
    Alors le taux de satisfaction de la question <qid> devrait être de <percentage>%
    Et la question <qid> <should_require> nécessiter une action

    Exemples:
      | qid | question_text      | satisfied | total | percentage | should_require     |
      | 11  | Test 0%            | 0         | 100   | 0          | devrait            |
      | 12  | Test 25%           | 25        | 100   | 25         | devrait            |
      | 13  | Test 50%           | 50        | 100   | 50         | devrait            |
      | 14  | Test 74%           | 74        | 100   | 74         | devrait            |
      | 15  | Test 75%           | 75        | 100   | 75         | ne devrait pas     |
      | 16  | Test 80%           | 80        | 100   | 80         | ne devrait pas     |
      | 17  | Test 90%           | 90        | 100   | 90         | ne devrait pas     |
      | 18  | Test 100%          | 100       | 100   | 100        | ne devrait pas     |

  Scénario: Vérification de la structure des données retournées
    Étant donné une campagne avec les données suivantes:
      | question_id | question_text              |
      | 19          | Test de structure          |
      | 20          | Autre question             |
    Et les réponses suivantes pour la question 19:
      | value | numberAnswered |
      | 5     | 30             |
      | 4     | 20             |
    Et les réponses suivantes pour la question 20:
      | value | numberAnswered |
      | 3     | 30             |
      | 2     | 20             |
    Quand je calcule la satisfaction des questions
    Alors le résultat devrait contenir la clé "questions_analysis"
    Et le résultat devrait contenir la clé "questions_requiring_action"
    Et le résultat devrait contenir la clé "total_satisfaction"
    Et "questions_analysis" devrait être un tableau de 2 éléments
    Et "questions_requiring_action" devrait être un tableau de 1 élément
    Et chaque question analysée devrait contenir les clés suivantes:
      | clé                      |
      | question_id              |
      | question_text            |
      | satisfaction_percentage  |
      | total_responses          |
      | requires_action          |
      | answers                  |
