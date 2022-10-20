<?php

namespace Src;

use Src\Calculation;
use Phpml\Classification\SVC;
use Phpml\Math\Distance\Minkowski;
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
        // $dataset = new CsvDataset('dataset.csv', 15, true);
        // $samples = $dataset->getSamples();
        // $labels = $dataset->getTargets();
        self::exPredict($test);
    }

    /**
     * Gerar previsão de jogo
     *
     * @param bool $test Modo de teste
     * @param boolean $log Exibir logs
     * @param integer $ngames Número de jogos
     * @return array
     */
    public static function exPredict($test = false, $log = true, $ngames = 2600): array
    {
        $cal = new Calculation();

        echo Helper::title('Previsão de Números');
        if ($test) {
            $dataset = $cal->getLastGames($ngames + 1);
            $game_test = end($dataset);
            unset($dataset[array_key_last($dataset)]);

            // $game = array_merge(self::predict($dataset, 1), self::predict($dataset, 3));
           $game = self::predict($dataset, 1);

            $gameUnique = array_unique($game);
            if ($log == true) {
                $acertos = $cal->checkHits($game_test, $game);
                echo "\nJogos: " . $ngames;
                echo "\nPrevisto: " . implode('-', $game);
                echo "\nCorreto: " . implode('-', $game_test);
                echo "\nDistintos: " . implode('-', $gameUnique) . ' (' . count($gameUnique) . ')';
                echo "\nAcertos: " . implode('-', $acertos) . ' (' . count($acertos) . ')';
                echo "\n";
            }
        } else {
            $dataset = $cal->getLastGames($ngames);
            // $game = array_merge(self::predict($dataset, 1), self::predict($dataset, 3));
            $game = self::predict($dataset, 1);
            $gameUnique = array_unique($game);
            if ($log == true) {
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
     * @return array
     */
    public static function predict($dataset, $cla = 0): array
    {
        $c_nx = [3, 15, 3, 8];
        $nx = [];
        $i = 0;
        $samples = [];
        foreach ($dataset as $balls) {
            if (isset($nx[0]) && count($nx[0]) == $c_nx[$cla]) {

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
            $classifier = new KNearestNeighbors(15);
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
            print_r('*');
        }

        return $game;
    }
}
