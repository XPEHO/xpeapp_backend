<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\Assert;

require_once __DIR__ . '/../../src/qvst/campaign/get_campaign_analysis.php';

/**
 * Contexte Behat pour tester la fonction calculateQuestionSatisfaction
 */
class CalculateQuestionSatisfactionContext implements Context
{
    /**
     * @var array Données de campagne simulées
     */
    private $statsData = [];

    /**
     * @var array Résultat du calcul de satisfaction
     */
    private $result = [];

    /**
     * @var int Seuil de satisfaction en pourcentage
     */
    private $satisfactionThreshold = 75;

    /**
     * @var int Score minimal considéré comme satisfait
     */
    private $minSatisfiedScore = 4;

    /**
     * @BeforeScenario
     */
    public function setUp()
    {
        $this->statsData = ['questions' => []];
        $this->result = [];
    }

    /**
     * @Given que le seuil de satisfaction est de :threshold%
     */
    public function queLSeuilDeSatisfactionEstDe($threshold)
    {
        $this->satisfactionThreshold = (int)$threshold;
    }

    /**
     * @Given que le score minimal de satisfaction est de :score
     */
    public function queLeScoreMinimalDeSatisfactionEstDe($score)
    {
        $this->minSatisfiedScore = (int)$score;
    }

    /**
     * @Given une campagne avec les données suivantes:
     */
    public function uneCampagneAvecLesDonneesSuivantes(TableNode $table)
    {
        foreach ($table->getHash() as $row) {
            $question = new stdClass();
            $question->question_id = (int)$row['question_id'];
            $question->question = $row['question_text'];
            $question->answers = [];
            
            $this->statsData['questions'][] = $question;
        }
    }

    /**
     * @Given les réponses suivantes pour la question :questionId:
     */
    public function lesReponsesSuivantesPourLaQuestion($questionId, TableNode $table)
    {
        $questionId = (int)$questionId;
        
        foreach ($this->statsData['questions'] as &$question) {
            if ($question->question_id === $questionId) {
                foreach ($table->getHash() as $row) {
                    $answer = new stdClass();
                    $answer->value = (int)$row['value'];
                    $answer->numberAnswered = (int)$row['numberAnswered'];
                    
                    $question->answers[] = $answer;
                }
                break;
            }
        }
    }

    /**
     * @Given aucune réponse pour la question :questionId
     */
    public function aucuneReponsePourLaQuestion($questionId)
    {
        // Les questions sans réponse ont déjà un tableau answers vide par défaut
        // Cette étape est donc principalement documentaire
    }

    /**
     * @Given :satisfied réponses satisfaites sur :total réponses
     */
    public function reponsesSatisfaitesSurReponses($satisfied, $total)
    {
        $satisfied = (int)$satisfied;
        $total = (int)$total;
        $unsatisfied = $total - $satisfied;
        
        // Récupère la dernière question ajoutée
        $lastIndex = count($this->statsData['questions']) - 1;
        $question = &$this->statsData['questions'][$lastIndex];
        
        // Ajoute les réponses satisfaites (score 5)
        if ($satisfied > 0) {
            $answer = new stdClass();
            $answer->value = 5;
            $answer->numberAnswered = $satisfied;
            $question->answers[] = $answer;
        }
        
        // Ajoute les réponses non satisfaites (score 3)
        if ($unsatisfied > 0) {
            $answer = new stdClass();
            $answer->value = 3;
            $answer->numberAnswered = $unsatisfied;
            $question->answers[] = $answer;
        }
    }

    /**
     * @When je calcule la satisfaction des questions
     */
    public function jeCalculeLaSatisfactionDesQuestions()
    {
        $this->result = calculateQuestionSatisfaction($this->statsData);
    }

    /**
     * @Then le taux de satisfaction de la question :questionId devrait être de :percentage%
     */
    public function leTauxDeSatisfactionDeLaQuestionDevraitEtreDe($questionId, $percentage)
    {
        $questionId = (int)$questionId;
        $expectedPercentage = (float)$percentage;
        
        $found = false;
        foreach ($this->result['questions_analysis'] as $question) {
            if ($question['question_id'] === $questionId) {
                Assert::assertEquals(
                    $expectedPercentage,
                    $question['satisfaction_percentage'],
                    "Le taux de satisfaction de la question {$questionId} devrait être {$expectedPercentage}% mais est {$question['satisfaction_percentage']}%"
                );
                $found = true;
                break;
            }
        }
        
        Assert::assertTrue($found, "Question {$questionId} non trouvée dans les résultats");
    }

    /**
     * @Then la question :questionId ne devrait pas nécessiter d'action
     */
    public function laQuestionNeDevraitPasNecessiterDAction($questionId)
    {
        $questionId = (int)$questionId;
        
        foreach ($this->result['questions_analysis'] as $question) {
            if ($question['question_id'] === $questionId) {
                Assert::assertFalse(
                    $question['requires_action'],
                    "La question {$questionId} ne devrait pas nécessiter d'action"
                );
                return;
            }
        }
        
        Assert::fail("Question {$questionId} non trouvée");
    }

    /**
     * @Then la question :questionId devrait nécessiter une action
     */
    public function laQuestionDevraitNecessiterUneAction($questionId)
    {
        $questionId = (int)$questionId;
        
        foreach ($this->result['questions_analysis'] as $question) {
            if ($question['question_id'] === $questionId) {
                Assert::assertTrue(
                    $question['requires_action'],
                    "La question {$questionId} devrait nécessiter une action"
                );
                return;
            }
        }
        
        Assert::fail("Question {$questionId} non trouvée");
    }

    /**
     * @Then le total de réponses devrait être de :total
     */
    public function leTotalDeReponsesDevraitEtreDe($total)
    {
        $expectedTotal = (int)$total;
        
        // Vérifie sur la première question (dans les scénarios simples)
        Assert::assertEquals(
            $expectedTotal,
            $this->result['questions_analysis'][0]['total_responses'],
            "Le total de réponses devrait être {$expectedTotal}"
        );
    }

    /**
     * @Then la question :questionId devrait apparaître dans les questions nécessitant une action
     */
    public function laQuestionDevraitApparaitreDansLesQuestionsNecessitantUneAction($questionId)
    {
        $questionId = (int)$questionId;
        
        $found = false;
        foreach ($this->result['questions_requiring_action'] as $question) {
            if ($question['question_id'] === $questionId) {
                $found = true;
                break;
            }
        }
        
        Assert::assertTrue(
            $found,
            "La question {$questionId} devrait apparaître dans questions_requiring_action"
        );
    }

    /**
     * @Then le nombre total de questions devrait être de :count
     */
    public function leNombreTotalDeQuestionsDevraitEtreDe($count)
    {
        $expectedCount = (int)$count;
        
        Assert::assertCount(
            $expectedCount,
            $this->result['questions_analysis'],
            "Le nombre total de questions devrait être {$expectedCount}"
        );
    }

    /**
     * @Then le nombre de questions nécessitant une action devrait être de :count
     */
    public function leNombreDeQuestionsNecessitantUneActionDevraitEtreDe($count)
    {
        $expectedCount = (int)$count;
        
        Assert::assertCount(
            $expectedCount,
            $this->result['questions_requiring_action'],
            "Le nombre de questions nécessitant une action devrait être {$expectedCount}"
        );
    }

    /**
     * @Then la satisfaction totale devrait être de :total%
     */
    public function laSatisfactionTotaleDevraitEtreDe($total)
    {
        $expectedTotal = (float)$total;
        
        Assert::assertEquals(
            $expectedTotal,
            $this->result['total_satisfaction'],
            "La satisfaction totale devrait être {$expectedTotal}%"
        );
    }

    /**
     * @Then le résultat devrait contenir la clé :key
     */
    public function leResultatDevraitContenirLaCle($key)
    {
        Assert::assertArrayHasKey(
            $key,
            $this->result,
            "Le résultat devrait contenir la clé '{$key}'"
        );
    }

    /**
     * @Then :key devrait être un tableau de :count éléments
     */
    public function devraitEtreUnTableauDeElements($key, $count)
    {
        $expectedCount = (int)$count;
        
        Assert::assertIsArray($this->result[$key], "'{$key}' devrait être un tableau");
        Assert::assertCount(
            $expectedCount,
            $this->result[$key],
            "'{$key}' devrait contenir {$expectedCount} éléments"
        );
    }

    /**
     * @Then chaque question analysée devrait contenir les clés suivantes:
     */
    public function chaqueQuestionAnalyseeDevraitContenirLesClesSuivantes(TableNode $table)
    {
        $expectedKeys = array_column($table->getHash(), 'clé');
        
        foreach ($this->result['questions_analysis'] as $index => $question) {
            foreach ($expectedKeys as $key) {
                Assert::assertArrayHasKey(
                    $key,
                    $question,
                    "La question {$index} devrait contenir la clé '{$key}'"
                );
            }
        }
    }
}
