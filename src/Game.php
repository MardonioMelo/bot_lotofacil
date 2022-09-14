<?php

namespace Src;

use LDAP\Result;
use Src\Calculation;

class Game
{

    private $cal;
    private $test;
    private $qtd_analysis;
    private $training;

    public function __construct()
    {
        $this->cal = new Calculation();
        $this->test = false;
    }

    /**
     * Play para gerar jogo
     *  
     * @return void
     */
    public function play(): void
    {
        $jogo = $this->generateGame();
        $analysis = $this->analysis($jogo);
        echo $analysis['log'];
    }

    /**
     * Gerar Jogo
     *
     * @param integer $qtd_analysis Qtd a ser analisado
     * @return array
     */
    private function generateGame($qtd_analysis = 20): array
    {
        $jogo = [];
        $this->qtd_analysis = $qtd_analysis;

        // Selecionar os últimos concursos 
        $last_games = $this->cal->getLastGames($qtd_analysis);
        if ($this->test) {
            $endGameTest = end($last_games);
            array_pop($last_games);
        }

        // Variáveis auxiliares para analise
        $wordlist = $this->cal->getWordlist();
        $wordlist_count = count($wordlist);
        $laterNumbers = $this->cal->laterNumbers($last_games);
        $countFrequency = $this->cal->countFrequency($last_games);
        $getDataSetString = $this->cal->getDataSetString();
        $endGame = end($last_games);

        echo date("d/m/Y H:i:s") . " - Inicio da busca na Wordlist... \n";
        $end = 1;
        $end_wordlist = 0;
        for ($i = 0; $i < $end;) {
            $end_wordlist++;
            $key = rand(1, $wordlist_count);
            $check_game = explode(' ', $wordlist[$key]);
            $checkAnalysis = $this->checkAnalysis($check_game, $last_games, $laterNumbers, $countFrequency, $endGame, $getDataSetString);

            if ($this->test) {
                $countHits = count($this->cal->checkHits($endGameTest, $check_game));
                $this->training['countHits'][] = $countHits;
                $this->training['checkAnalysis'][] = $checkAnalysis;

                // echo date("d/m/Y H:i:s") . " - Acertos: $countHits | Análise: $checkAnalysis \n";
                if ($checkAnalysis == 0 && $countHits == 13) {
                    echo date("d/m/Y H:i:s") . " - ID do jogo: $key \n";
                    $jogo = $check_game;
                    $i++;
                }
            } else {
                if ($checkAnalysis == 0) {
                    echo date("d/m/Y H:i:s") . " - ID do jogo: $key \n";
                    $jogo = $check_game;
                    $i++;
                }
            }

            if ($end_wordlist == $wordlist_count) {
                echo date("d/m/Y H:i:s") . " - Nenhum número da Wordlist corresponde ao esperado! \n";
                $i++;
            }
        }
        echo date("d/m/Y H:i:s") . " - Fim da busca na Wordlist. \n";

        //Verificar treinamento
        if ($this->test) $this->cal->checkTraining($this->training);


        return $jogo;
    }

    /**
     * Verificar
     *
     * @param array $jogo Jogo a ser analisado
     * @param array $last_games Array com os jogos anteriores
     * @param array $laterNumbers Números mais atrasados nos últimos sorteios
     * @param array $countFrequency Os números mais sorteados nos últimos concursos 
     * @param array $endGame Último jogo que saiu
     * @param array $getDataSetString Array obtido com o método $this->cal->getDataSetString()
     * @return int
     */
    public function checkAnalysis($jogo, $last_games, $laterNumbers, $countFrequency, $endGame, $getDataSetString): int
    {
        $result = 0;
        # ------------------------------------------------->
        # Avaliações básicas para considerar o jogo valido
        # ------------------------------------------------->
        # O jogo deve ser inédito
        $unprecedented = $this->cal->unprecedented($getDataSetString, $jogo, $this->test);
        if ($unprecedented > 0) $result = 1;
        # As dezenas devem estar dentro do máximo e mínimo para cada posição  
        $checkMaxMinGame = count($this->cal->checkMaxMinGame($last_games, $jogo));
        if ($checkMaxMinGame > 0) $result = 2;
        # A soma das dezenas devem estar entre 166 e 220   
        $sumDezene = $this->cal->sumDezene($jogo);
        if ($sumDezene < 166 || $sumDezene > 220) $result = 3;
        # O jogo deve ter em media 7 dezenas impares
        $qtdImparPar = $this->cal->qtdImparPar($jogo);
        $qtdPar = $qtdImparPar['par'];
        if ($qtdPar < 6 || $qtdPar > 7) $result = 4;
        # O jogo deve ter em media 8 dezenas pares
        $qtdImpar = $qtdImparPar['impar'];
        if ($qtdImpar < 8 || $qtdImpar > 9) $result = 5;
        # ------------------------------------------------->
        # Considerar algumas avaliações como critério de desempate 
        # Considerar outras avaliações com pesos diferentes conforme grau de importância e q mais se aproxime do objetivo
        # ------------------------------------------------->
        $checkPreviousExist = $this->cal->checkPreviousExist($endGame, $jogo);
        # Deve ter de 7 a 10 dezenas que saiu no concurso anterior
        $yes_15_exist = count($checkPreviousExist['yes_15_exist']);
        if ($yes_15_exist < 7 || $yes_15_exist > 10) $result = 6;
        # Deve ter de 5 a 7 dezenas das 10 que não saiu no concurso anterior
        $not_10_exist = count($checkPreviousExist['not_10_exist']);
        if ($not_10_exist < 5 || $not_10_exist > 7) $result = 7;
        # Deve ter no mínimo 3 números primos que saiu no último concurso
        $yes_prim_exist = count($checkPreviousExist['yes_prim_exist']);
        if ($yes_prim_exist >= 3) $result = 8;
        # Deve ter no mínimo 2 números primos que não saiu no ultimo concurso
        $not_prim_exist = count($checkPreviousExist['not_prim_exist']);
        if ($not_prim_exist >= 2) $result = 9;
        # Deve ter de 5 a 7 números primos
        $prim_exist = count($checkPreviousExist['prim_exist']);
        $result = ($prim_exist < 5 && $prim_exist > 7 ? 10 : 0);
        # Se tiver o número 1, deve ter ao menos o número 2, 3 ou 4.
        $checkParent1 = $this->cal->checkParent($jogo, 1, [2, 3, 4]);
        if ($checkParent1 == false) $result = 11;
        # Se tiver o número 15, deve ter ao menos o número 22, 23, 24 ou 25.
        $checkParent15 = $this->cal->checkParent($jogo, 15, [22, 23, 24, 25]);
        if ($checkParent15 == false) $result = 12;
        # Deve ter ao menos uma das 4 dezenas mais atrasadas
        $checkLaterNumInGame = count($this->cal->checkLaterNumInGame($laterNumbers, $jogo, 4));
        if ($checkLaterNumInGame == 0) $result = 13;
        # Deve ter aos menos uma das 6 dezenas que mais são sorteadas
        $checkFrequencyInGame = count($this->cal->checkFrequencyInGame($countFrequency, $jogo, 6));
        if ($checkFrequencyInGame == 0) $result = 14;
        # ------------------------------------------------->
        # Guardar resultado da avaliação como treino
        # ------------------------------------------------->
        $this->training['unprecedented'][] = $unprecedented;
        $this->training['checkMaxMinGame'][] = $checkMaxMinGame;
        $this->training['sumDezene'][] = $sumDezene;
        $this->training['qtdPar'][] = $qtdPar;
        $this->training['qtdImpar'][] = $qtdImpar;
        $this->training['yes_15_exist'][] = $yes_15_exist;
        $this->training['not_10_exist'][] = $not_10_exist;
        $this->training['not_prim_exist'][] = $not_prim_exist;
        $this->training['prim_exist'][] = $prim_exist;
        $this->training['checkParent1'][] = $checkParent1;
        $this->training['checkParent15'][] = $checkParent15;
        $this->training['checkLaterNumInGame'][] = $checkLaterNumInGame;
        $this->training['checkFrequencyInGame'][] = $checkFrequencyInGame;

        return $result;
    }

    /**
     * Realizar analise do jogo
     *
     * @param string Jogo gerado com o método $this->generateGame()
     * @return array
     */
    public function analysis($jogo): array
    {
        $result['log'] = '';
        if ($this->test) {
            $last_games = $this->cal->getLastGames($this->qtd_analysis);
            $inedito = $this->cal->unprecedented($this->cal->getDataSetString(), $jogo);
            $checkMaxMin = $this->cal->checkMaxMinGame($last_games, $jogo);
            $endGameTest = end($last_games);
            $checkHits = $this->cal->checkHits($endGameTest, $jogo);
            $result['log'] .= "\n\n------------------------------------> \n";
            $result['log'] .= " Análise para Teste\n";
            $result['log'] .= "------------------------------------> \n";
            $result['log'] .= 'Inédito  : ' . ($inedito == 0 ? 'Sim' : "Não ($inedito)") . "\n";
            $result['log'] .= 'Impar    : ' . $this->cal->qtdImparPar($jogo)['impar'] . " (8)\n";
            $result['log'] .= 'Par      : ' . $this->cal->qtdImparPar($jogo)['par'] . " (7)\n";
            $result['log'] .= 'Soma     : ' . $this->cal->sumDezene($jogo) . " (166 - 220)\n";
            $result['log'] .= 'Máx.|Mín.: ' . (empty($checkMaxMin) ? 'OK!' : implode(', ', $checkMaxMin)) . "\n";
            $result['log'] .= 'Total    : ' . count($jogo) . " (15)\n";
            $result['log'] .= 'Previsto : ' . implode("-", $jogo) . "\n";
            $result['log'] .= 'Correto  : ' . implode("-", end($last_games)) . ' (' . (array_key_last($last_games) + 1) . ")\n";
            $result['log'] .= 'Acertos  : ' . implode("-", $checkHits) . ' (' . count($checkHits) . ")\n";
        } else {
            $result['log'] .= "\n\n------------------\n Análise para Jogar \n------------------ \n";
            $result['log'] .= 'Impar   : ' . $this->cal->qtdImparPar($jogo)['impar'] . " (8)\n";
            $result['log'] .= 'Par     : ' . $this->cal->qtdImparPar($jogo)['par'] . " (7)\n";
            $result['log'] .= 'Soma    : ' . $this->cal->sumDezene($jogo) . " (166 - 220)\n";
            $result['log'] .= 'Total   : ' . count($jogo) . " (15)\n";
            $result['log'] .= 'Previsto: ' . implode("-", $jogo) . "\n";
        }
        return $result;
    }

    /**
     * Definir modo de teste.
     * A analise será realizada com os jogos antes do ultimo jogo para prever o ultimo jogo que já saiu.
     * 
     * @return void
     */
    public function setModeTest()
    {
        $this->test = true;
    }
}
