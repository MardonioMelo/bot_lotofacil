<?php

namespace Src;

use LDAP\Result;
use Src\Calculation;

class Game
{

    private $cal;
    private $test;
    private $qtd_analysis;

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
        $last_games = $this->cal->getLastGames($qtd_analysis + ($this->test ? 1 : 0));
        if ($this->test) array_pop($last_games);

        // Variáveis auxiliares para analise
        $wordlist = $this->cal->getWordlist();
        $wordlist_count = count($wordlist);
        $last_games = $this->cal->getLastGames($this->qtd_analysis);
        $laterNumbers = $this->cal->laterNumbers($last_games);
        $countFrequency = $this->cal->countFrequency($last_games);
        $endGame = end($last_games);
        $getDataSetString = $this->cal->getDataSetString();

        echo date("d/m/Y H:i:s") . " - Inicio da busca na Wordlist... \n";
        $end = 1;
        $end_wordlist = 0;
        for ($i = 0; $i < $end;) {
            $key = rand(1, $wordlist_count);
            $check_game = explode(' ', $wordlist[$key]);
            $checkAnalysis = $this->checkAnalysis($check_game, $last_games, $laterNumbers, $countFrequency, $endGame, $getDataSetString);
            $countHits = count($this->cal->checkHits($endGame, $check_game));
        
           // echo date("d/m/Y H:i:s") . " - Acertos: $countHits | Análise: $checkAnalysis \n";       
            if ($checkAnalysis == 0) {
                echo date("d/m/Y H:i:s") . " - ID do jogo: $key \n";
                $jogo = $check_game;
                echo date("d/m/Y H:i:s") . " - Acertos: $countHits \n";
                $i++;
            }

            $end_wordlist++;
            if($end_wordlist == $wordlist_count){
                echo date("d/m/Y H:i:s") . " - Nenhum número da Wordlist corresponde ao esperado! \n";
                $i++;
            }
        }
        echo date("d/m/Y H:i:s") . " - Fim da busca na Wordlist. \n";

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
        if ($this->cal->unprecedented($getDataSetString, $jogo, $this->test) > 0) $result = 1;
        # As dezenas devem estar dentro do máximo e mínimo para cada posição  
        if (count($this->cal->checkMaxMinGame($last_games, $jogo)) > 0) $result = 2;
        # A soma das dezenas devem estar entre 166 e 220   
        $sumDezene = $this->cal->sumDezene($jogo);
        if ($sumDezene < 166 || $sumDezene > 220) $result = 3;
        # O jogo deve ter em media 7 dezenas impares
        $qtdImparPar = $this->cal->qtdImparPar($jogo);
        if ($qtdImparPar['par'] < 5 || $qtdImparPar['par'] > 9) $result = 4;
        # O jogo deve ter em media 8 dezenas pares
        if ($qtdImparPar['impar'] < 6 || $qtdImparPar['impar'] > 10) $result = 5;

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
        if (count($checkPreviousExist['yes_prim_exist']) >= 3) $result = 8;
        # Deve ter no mínimo 2 números primos que não saiu no ultimo concurso
        if (count($checkPreviousExist['not_prim_exist']) >= 2) $result = 9;
        # Deve ter de 5 a 7 números primos
        $prim_exist = count($checkPreviousExist['prim_exist']);
        if ($prim_exist < 5 || $prim_exist > 7) $result = 10;
        # Se tiver o número 1, deve ter ao menos o número 2, 3 ou 4.
        if ($this->cal->checkParent($jogo, 1, [2, 3, 4]) == false) $result = 11;
        # Se tiver o número 15, deve ter ao menos o número 22, 23, 24 ou 25.
        if ($this->cal->checkParent($jogo, 15, [22, 23, 24, 25]) == false) $result = 12;
        # Deve ter ao menos uma das 4 dezenas mais atrasadas
        $laterNumbers = array_keys($laterNumbers);
        if (
            !in_array($laterNumbers[0], $jogo)
            && !in_array($laterNumbers[1], $jogo)
            && !in_array($laterNumbers[2], $jogo)
            && !in_array($laterNumbers[3], $jogo)
            && !in_array($laterNumbers[4], $jogo)
        ) $result = 13;
        # Deve ter aos menos uma das 6 dezenas que mais são sorteadas
        $countFrequency = array_keys($countFrequency);
        if (
            !in_array($countFrequency[0], $jogo)
            && !in_array($countFrequency[1], $jogo)
            && !in_array($countFrequency[2], $jogo)
            && !in_array($countFrequency[3], $jogo)
            && !in_array($countFrequency[4], $jogo)
            && !in_array($countFrequency[5], $jogo)
        ) $result = 14;

        # Não pode ter mais que 8 dezenas seguidas 
        // Falta verificar  

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
            $endGame = end($last_games);
            $checkHits = $this->cal->checkHits($endGame, $jogo);
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
