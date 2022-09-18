<?php

namespace Src;

use LDAP\Result;
use Src\Calculation;

class Game
{

    private $cal;
    private $test;
    private $qtd_analysis;
    private $end_game_test;
    private $dataAnalysis;

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
     * Gerar mais de um jogo
     * @param integer $n_games Números de jogos
     * @param integer $qtd_analysis Qtd a ser analisado
     * @return void
     */
    public function generateGameMulti(int $n_games = 10, $qtd_analysis = 10)
    {
        for ($i = 0; $i < $n_games; $i++) {
            $jogo = $this->generateGame($qtd_analysis);
            $analysis = $this->analysis($jogo);
            echo $analysis['log'];
        }
    }

    /**
     * Gerar Jogo
     *
     * @param integer $qtd_analysis Qtd a ser analisado
     * @return array
     */
    private function generateGame($qtd_analysis = 10): array
    {
        $jogo = [];
        $this->qtd_analysis = $qtd_analysis;
        $this->cal->trainingMargin($qtd_analysis, $this->test);
        $margins = $this->cal->getTraining([], 1, "Analise de $qtd_analysis números:");

        // Selecionar os últimos concursos 
        $last_games = $this->cal->getLastGames($qtd_analysis);
        if ($this->test) {
            $this->end_game_test = end($last_games); //Jogo a ser previsto
            array_pop($last_games);
            Helper::showTraining($margins);
        }

        // Variáveis auxiliares para analise
        $all_games = $this->cal->allPreviousGames($this->test);
        $wordlist = $this->cal->getWordlist();
        $wordlist_count = count($wordlist); //3268760
        $laterNumbers = $this->cal->laterNumbers($last_games);
        $countFrequency = $this->cal->countFrequency($last_games);
        $getDataSetString = $this->cal->getDataSetString();
        $endGame = end($last_games);

        echo Helper::title('Logs');
        echo date("d/m/Y H:i:s") . " - Inicio da busca na Wordlist... \n";
        $end = 1;
        $end_wordlist = 1000000;
        $log_n_verify = 100000;
        $log_n_verify_range = 100000;
        $key = 1;
        for ($i = 0; $i < $end;) {
            // $end_wordlist++;
            // $key = mt_rand(1, $wordlist_count);
            $end_wordlist += (100 + mt_rand(1, 100));
            $key = $end_wordlist;
            $check_game = explode(' ', $wordlist[$key]);
            $checkAnalysis = $this->checkAnalysis($check_game, $all_games, $laterNumbers, $countFrequency, $endGame, $getDataSetString, $margins);

            if ($this->test) {
                //  $countHits = count($this->cal->checkHits($this->end_game_test, $check_game));
                // if($countHits == 14){
                //     echo date("d/m/Y H:i:s") . " - Acertos: $countHits | Análise: $checkAnalysis | Loop: $end_wordlist \n";
                // }
            }

            if ($end_wordlist == $log_n_verify) {
                echo date("d/m/Y H:i:s") . " - $end_wordlist jogos verificados. \n";
                $log_n_verify += $log_n_verify_range;
            }

            if ($checkAnalysis == 0) {
                echo date("d/m/Y H:i:s") . " - ID do jogo: $key \n";
                $jogo = $check_game;
                $i++;
            }

            if ($end_wordlist >= $wordlist_count) {
                echo date("d/m/Y H:i:s") . " - Nenhum número da Wordlist corresponde ao esperado! \n";
                $i++;
            }
        }
        echo date("d/m/Y H:i:s") . " - Fim da busca na Wordlist. \n";

        //Exibir resultado da analise para o jogo proposto
        if (!empty($this->dataAnalysis)) Helper::showTraining($this->dataAnalysis, 2);

        return $jogo;
    }

    /**
     * Verificar
     *
     * @param array $game Jogo a ser analisado
     * @param array $all_games Todos os jogos que já saiu
     * @param array $laterNumbers Números mais atrasados nos últimos sorteios
     * @param array $countFrequency Os números mais sorteados nos últimos concursos 
     * @param array $endGame Último jogo que saiu
     * @param array $getDataSetString Array obtido com o método $this->cal->getDataSetString()  
     * @param array $margins Array com as margens de especificação obtidas com $this->cal->getTraining();
     * @return int
     */
    public function checkAnalysis($game, $all_games, $laterNumbers, $countFrequency, $endGame, $getDataSetString, $margins): int
    {
        // # O jogo deve ser inédito
        $unprecedented = $this->cal->unprecedented($getDataSetString, $game, $this->test);
        if ($unprecedented > 0) return 2;

        # As dezenas devem estar dentro do máximo e mínimo para cada posição  
        $checkMaxMinGame = count($this->cal->checkMaxMinGame($all_games, $game)); //aqui deve avaliar todos os jogos desde o inicio
        if ($checkMaxMinGame > 0) return 3;

        # A soma das dezenas devem estar entre 166 e 220   
        $sumDezene = $this->cal->sumDezene($game);
        if ($sumDezene < $margins['sumDezene']['min'] || $sumDezene > $margins['sumDezene']['max']) return 4;

        # O jogo deve ter em media 7 dezenas impares
        $qtdImparPar = $this->cal->qtdImparPar($game);
        $qtdPar = $qtdImparPar['par'];
        if ($qtdPar < $margins['qtdPar']['min'] || $qtdPar > $margins['qtdPar']['max']) return 5;

        # O jogo deve ter em media 8 dezenas pares
        $qtdImpar = $qtdImparPar['impar'];
        if ($qtdImpar < $margins['qtdImpar']['min'] || $qtdImpar > $margins['qtdImpar']['max']) return 6;

        $checkPreviousExist = $this->cal->checkPreviousExist($endGame, $game);
        # Deve ter de 7 a 10 dezenas que saiu no concurso anterior
        $yes_15_exist = count($checkPreviousExist['yes_15_exist']);
        if ($yes_15_exist < $margins['yes_15_exist']['min'] || $yes_15_exist > $margins['yes_15_exist']['max']) return 7;

        # Deve ter de 5 a 7 dezenas das 10 que não saiu no concurso anterior
        $not_10_exist = count($checkPreviousExist['not_10_exist']);
        if ($not_10_exist < $margins['not_10_exist']['min'] || $not_10_exist > $margins['not_10_exist']['max']) return 8;

        # Deve ter no mínimo 3 números primos que saiu no último concurso
        $yes_prim_exist = count($checkPreviousExist['yes_prim_exist']);
        if ($yes_prim_exist < $margins['yes_prim_exist']['min'] || $yes_prim_exist > $margins['yes_prim_exist']['max']) return 9;

        # Deve ter no mínimo 2 números primos que não saiu no ultimo concurso
        $not_prim_exist = count($checkPreviousExist['not_prim_exist']);
        if ($not_prim_exist < $margins['not_prim_exist']['min'] || $not_prim_exist > $margins['not_prim_exist']['max']) return 10;

        # Deve ter de 5 a 7 números primos
        $prim_exist = count($checkPreviousExist['prim_exist']);
        if ($prim_exist < $margins['prim_exist']['min'] || $prim_exist > $margins['prim_exist']['max']) return 11;

        // # Deve ter de 5 a 7 números primos
        $prime_20 = count($checkPreviousExist['prime_20']);
        if ($prime_20 == 14) return 12;

        # Se tiver o número 1, deve ter ao menos o número 2, 3 ou 4.
        $checkParent1 = $this->cal->checkParent($game, 1, [2, 3, 4]);
        if ($checkParent1 == false) return 13;

        # Se tiver o número 15, deve ter ao menos o número 22, 23, 24 ou 25.
        $checkParent15 = $this->cal->checkParent($game, 15, [22, 23, 24, 25]);
        if ($checkParent15 == false) return 14;

        # Deve ter ao menos uma das 4 dezenas mais atrasadas
        $checkLaterNumInGame = count($this->cal->checkLaterNumInGame($laterNumbers, $game));
        if ($checkLaterNumInGame < $margins['checkLaterNumInGame']['min'] || $checkLaterNumInGame > $margins['checkLaterNumInGame']['max']) return 15;

        # Deve ter ao menos uma das 6 dezenas que mais são sorteadas
        $checkFrequencyInGame = count($this->cal->checkFrequencyInGame($countFrequency, $game));
        if ($checkFrequencyInGame < $margins['checkFrequencyInGame']['min'] || $checkFrequencyInGame > $margins['checkFrequencyInGame']['max']) return 16;

        // // Guardar resultado da análise     
        $this->dataAnalysis['sumDezene']['analysis'] = $sumDezene;
        $this->dataAnalysis['qtdPar']['analysis'] = $qtdPar;
        $this->dataAnalysis['qtdImpar']['analysis'] = $qtdImpar;
        $this->dataAnalysis['yes_15_exist']['analysis'] = $yes_15_exist;
        $this->dataAnalysis['not_10_exist']['analysis'] = $not_10_exist;
        $this->dataAnalysis['yes_prim_exist']['analysis'] = $yes_prim_exist;
        $this->dataAnalysis['not_prim_exist']['analysis'] = $not_prim_exist;
        $this->dataAnalysis['prim_exist']['analysis'] = $prim_exist;
        $this->dataAnalysis['prime_20']['analysis'] = $prime_20;
        $this->dataAnalysis['checkLaterNumInGame']['analysis'] = $checkLaterNumInGame;
        $this->dataAnalysis['checkFrequencyInGame']['analysis'] = $checkFrequencyInGame;

        return 0;
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
            $end_game_test = end($last_games);
            $checkHits = $this->cal->checkHits($end_game_test, $jogo);
            $result['log'] = Helper::title('Análise para Teste');
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
            $result['log'] = Helper::title('Análise para Jogar');
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
