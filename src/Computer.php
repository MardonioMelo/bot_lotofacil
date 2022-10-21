<?php

namespace Src;

use Src\Calculation;
use Phpml\Classification\SVC;
use Phpml\SupportVectorMachine\Kernel;
use Phpml\Classification\KNearestNeighbors;

class Computer
{

    public static $file_dataset = 'dataset.csv';

    /**
     * Gerar previsão de jogo de forma simplificada para testar os parâmetros
     * 
     * @param bool $test Modo de teste
     * @return void
     */
    public static function run($test = false): void
    {

        //self::exPredict($test, true, 200, 14);


        foreach ([100, 200] as $val) {
            self::exPredict($test, true, $val, 15);
            self::exPredict($test, true, $val, 19);
        }

        // $loops = 30;

        // $i = 1;
        // while ($i <= $loops) {
        //     self::exPredict($test, true, 30, $i);
        //     $i++;
        // }

        // $i = 1;
        // while ($i <= $loops) {
        //     self::exPredict($test, true, 100, $i);
        //     $i++;
        // }

        // $i = 1;
        // while ($i <= $loops) {
        //     self::exPredict($test, true, 200, $i);
        //     $i++;
        // }

        // $i = 1;
        // while ($i <= $loops) {
        //     self::exPredict($test, true, 300, $i);
        //     $i++;
        // }
    }

    /**
     * Gerar previsão de jogo
     *
     * @param bool $test Modo de teste
     * @param boolean $log Exibir logs
     * @param integer $ngames Número de jogos que deve avaliar
     * @param int $c_nx Quantidade de jogos que deve agrupar
     * @return array
     */
    public static function exPredict($test = false, $log = true, $ngames = 200, $c_nx = null): array
    {
        $cal = new Calculation();

        if ($test) {
            $dataset = $cal->getLastGames($ngames + 1);
            $game_test = end($dataset);
            unset($dataset[array_key_last($dataset)]);
            $game = self::predict($dataset, 0, $c_nx);

            $gameUnique = array_unique($game);
            $acertos = $cal->checkHits($game_test, $game);
            if ($log == true && count($acertos) >= 11) {
                echo Helper::title('Previsão de Números');
                echo "\nJogos: " . $ngames;
                echo "\nPrevisto: " . implode('-', $game);
                echo "\nCorreto: " . implode('-', $game_test);
                echo "\nDistintos: " . implode('-', $gameUnique) . ' (' . count($gameUnique) . ')';
                echo "\nAcertos: " . implode('-', $acertos) . ' (' . count($acertos) . ')';
                echo "\nc_nx: " . $c_nx;
                echo "\n";
            
                //echo "\nParam: $ngames - $c_nx";         
          
            }
        } else {
            $dataset = $cal->getLastGames($ngames);

            $game = self::predict($dataset, 0, $c_nx);

            $gameUnique = array_unique($game);
            if ($log == true) {
                echo Helper::title('Previsão de Números');
                echo "\nPrevisto: " . implode('-', $game);
                echo "\nDistintos: " . implode('-', $gameUnique) . ' (' . count($gameUnique) . ')';
                echo "\n";
            }
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
           // print_r('*');
        }

        return $game;
    }
}
