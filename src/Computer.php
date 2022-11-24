<?php

namespace Src;

use Src\Calculation;
use Phpml\Classification\SVC;
use Phpml\SupportVectorMachine\Kernel;
use Phpml\Classification\KNearestNeighbors;

class Computer
{

    static $data = [];
    static $max = [60, 59, 57, 56, 52, 49, 47, 46, 45, 44];
    static $med = [43, 41, 40, 38, 37, 36, 33, 31, 29, 28];
    static $min = [25, 23];

    /**
     * Gerar previsão de jogo de forma simplificada para testar os parâmetros
     * 
     * @param bool $test Modo de teste
     * @return void
     */
    public static function run($test = false): void
    {

        // self::exPredict($test, 400, 28);

        // $nGames = 400;
        // foreach (array_merge(self::$max, self::$med, self::$min) as $val) {          
        //     self::exPredict($test, $nGames, $val, 0);
        // }

        // Gerar arquivo de treino
        // self::generateTraining(400, 10, 0);
        // self::generateTraining(400, 15, 0);
        // self::generateTraining(400, 20, 0);
        // self::generateTraining(400, 25, 0);
        // self::generateTraining(400, 30, 0);
        // self::generateTraining(400, 60, 0);
        // self::generateTraining(400, 400, 0);

        // self::generateTraining(400, 1000, 0);
        // Prever resultado
        $groups = self::searchGrouping(400, 0, 'training1000.json');
        print_r($groups);

        // foreach ($groups as $c_nx) {
        //     self::exPredict($test, 400, $c_nx);
        // }

        echo "\n Fim \n";
    }

    /**
     * Procurar pelo agrupamento correto
     *
     * @param int $ngames Número de jogos que deve avaliar
     * @param int $preOffset Remove últimos registros antes de aplicar o limite
     * @return array Array com os números de agrupamentos eleitos
     */
    public static function searchGrouping($ngames = 400, $preOffset = 0, $name_file = 'training.json'): array
    {
        $cal = new Calculation();
        $dataset = $cal->getLastGames($ngames, $preOffset);
        $result = [];

        $grouping = [];
        $training = json_decode(file_get_contents($name_file), true);
        foreach ($training['training'][$ngames] as $c_nx => $data) {

            foreach ($data as $contest => $dataGame) {
                $game = self::predict($dataset, 1, $c_nx);
                $gameUnique = array_unique($game);
                $acertos = $cal->checkHits($dataGame['game_test'], $gameUnique);
                $countAcertos = count($acertos);

                if ($countAcertos >= 11) {
                    $name = $countAcertos . '_acertos';
                    $result['total_acertos'][$c_nx] = (empty($result['total_acertos'][$c_nx]) ? 1 : $result['total_acertos'][$c_nx] + 1);
                    $result[$name][$c_nx] = (empty($result[$name][$c_nx]) ? 1 : $result[$name][$c_nx] + 1);
                    $grouping[] = $c_nx;
                }
            }
        }

        print_r($result);

        return array_unique($grouping);
    }

    

    /**
     * Gerar dados do trinamento previsão de jogo
     *
     * @param int $ngames Número de jogos que deve avaliar
     * @param int $preOffset Inicio do offset
     * @param int $limitOffSet Remove últimos registros antes de aplicar o limite
     * @return void
     */
    public static function generateTraining($ngames = 400, $preOffset = 10, $limitOffSet = 0): void
    {
        echo date("d/m/Y H:i:s") . " - Inicio da geração do treinamento de $preOffset jogos \n";
        $cal = new Calculation();
        $name_file = "training$preOffset.json";

        while ($preOffset >= $limitOffSet) {

            foreach (array_merge(self::$min, self::$med, self::$max) as $c_nx) {

                $dataset = $cal->getLastGames($ngames + 1, $preOffset);
                $game_test = end($dataset);
                $key_last_game_test = array_key_last($dataset);
                unset($dataset[$key_last_game_test]);
                $game = self::predict($dataset, 1, $c_nx);

                $gameUnique = array_unique($game);
                $acertos = $cal->checkHits($game_test, $gameUnique);

                if (count($acertos) >= 11) {
                    $concurso = $key_last_game_test + 1;
                    self::$data['training'][$ngames][$c_nx][$concurso]['contest'] = $concurso;
                    self::$data['training'][$ngames][$c_nx][$concurso]['game_test'] = $game_test;
                    self::$data['training'][$ngames][$c_nx][$concurso]['hits'] = count($acertos);
                    self::$data['training'][$ngames][$c_nx][$concurso]['correct_tens'] = $acertos;
                    self::$data['qtd_game_cnx'][$ngames][$c_nx] = count(self::$data['training'][$ngames][$c_nx]);
                    self::$data['unique'][$ngames][] = $c_nx;
                    self::$data['unique'][$ngames] = array_unique(self::$data['unique'][$ngames]);
                    arsort(self::$data['unique'][$ngames]);
                }
            }

            $preOffset--;
        }

        Helper::saveFile($name_file, json_encode(self::$data));
        self::$data = [];
        echo date("d/m/Y H:i:s") . " - Fim da geração do treinamento \n";
    }

    /**
     * Gerar previsão de jogo
     *
     * @param bool $test Modo de teste
     * @param integer $ngames Número de jogos que deve avaliar
     * @param int $c_nx Agrupamento - Quantidade de jogos que deve agrupar
     * @param int $preOffset Remove últimos registros antes de aplicar o limite
     * @return array
     */
    public static function exPredict($test = false, $ngames = 400, $c_nx = null, $preOffset = 0): array
    {
        $cal = new Calculation();

        if ($test) {
            $dataset = $cal->getLastGames($ngames + 1, $preOffset);
            $game_test = end($dataset);
            $key_last_game_test = array_key_last($dataset);
            unset($dataset[$key_last_game_test]);
            $game = self::predict($dataset, 1, $c_nx);

            $gameUnique = array_unique($game);
            $acertos = $cal->checkHits($game_test, $gameUnique);
            $concurso = $key_last_game_test + 1;

            if (count($acertos) >= 11) {
                echo Helper::title('Previsão de Números')
                    . "\nConcurso: " . $concurso
                    . "\nJogos: " . $ngames
                    . "\nPrevisto: " . implode('-', $game)
                    . "\nCorreto: " . implode('-', $game_test)
                    . "\nDistintos: " . implode('-', $gameUnique) . ' (' . count($gameUnique) . ')'
                    . "\nAcertos: " . implode('-', $acertos) . ' (' . count($acertos) . ')'
                    . "\nSaiu no Anterior: " . count(array_intersect(end($dataset), $game_test))
                    . "\nc_nx: " . $c_nx
                    . "\n";
            }
        } else {
            $dataset = $cal->getLastGames($ngames);
            $game = self::predict($dataset, 1, $c_nx);
            $gameUnique = array_unique($game);

           // $acertos = $cal->checkHits([2,3,4,7,8,10,11,12,13,19,20,21,22,23,24], $gameUnique); // 2666
          //  if (count($acertos) >= 11) {
                echo Helper::title('Previsão de Números')
                    . "\nPrevisto: " . implode('-', $game)
                    . "\nDistintos: " . implode('-', $gameUnique) . ' (' . count($gameUnique) . ')'
              //      . "\nAcertos: " . implode('-', $acertos) . ' (' . count($acertos) . ')'
                    . "\nc_nx: " . $c_nx
                    . "\n";
            // }
        }

        return $gameUnique;
    }

    /**
     * Treinar e prever jogo
     * @param array $dataset dados para treino
     * @param int $cla Escolha do classificador, 
     * 0 = KNearestNeighbors, 1 = SVC(Kernel::LINEAR), 2 = new SVC(Kernel::RBF), 3 = new SVC(Kernel::SIGMOID
     * @param int $c_nx Quantidade de jogos que deve agrupar
     * @return array
     */
    public static function predict($dataset, $cla = 0, $c_nx = null): array
    {
        $c_nx_d = [3, 10, 3, 8];
        $c_nx = (!$c_nx ? $c_nx_d[$cla] : $c_nx);
        $nx = [];
        $i = 0;
        $samples = [];
        foreach ($dataset as $balls) {
            if (isset($nx[0]) && count($nx[0]) == $c_nx) {

                foreach ($balls as $key => $ball) {
                    $samples[$key][] = $nx[$key];
                    $labels[$key][] = $ball;
                }
                $i++;
                $nx = [];
            }

            //Agrupar 3 e nomear o próximo número
            foreach ($balls as $key => $ball) {
                $nx[$key][] = $ball;
            }
        }

        if ($cla == 0) {
            $classifier = new KNearestNeighbors(1);
        } elseif ($cla == 1) {
            $classifier = new SVC(Kernel::LINEAR, 1, 3, 6);
        } elseif ($cla == 2) {
            $classifier = new SVC(Kernel::RBF, 1, 3, 6);
        } else {
            $classifier = new SVC(Kernel::SIGMOID, 1, 3, 6);
        }

        $game = [];
        foreach ($samples as $key => $sample) {
            $classifier->train($sample, $labels[$key]);
            $game[$key] = $classifier->predict(end($sample));
        }

        return $game;
    }
}
