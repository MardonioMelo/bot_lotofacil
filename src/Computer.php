<?php

namespace Src;

use Src\Calculation;
use Phpml\Dataset\CsvDataset;
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
            $game = self::predict($dataset, true);
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
            $game = self::predict($dataset);
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
     * @return array
     */
    public static function predict($dataset): array
    {
        $nx = [];
        $i = 0;
        $samples = [];
        foreach ($dataset as $balls) {
            if (isset($nx[0]) && count($nx[0]) == 6) {

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

        $game = [];
        $classifier = new KNearestNeighbors(7);
        foreach ($samples as $key => $sample) {
            $classifier->train($sample, $labels[$key]);
            $game[$key] = $classifier->predict(end($sample));
        }

        return $game;
    }
}
