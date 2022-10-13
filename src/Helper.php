<?php

namespace Src;

class Helper
{
    /**
     * Titulo para logos
     *
     * @param string $desc Titulo do log
     * @return string
     */
    public static function title(string $desc): string
    {
        $title = "\n-------------------------------------------> \n";
        $title .= " $desc\n";
        $title .= "-------------------------------------------> \n";
        return $title;
    }

    /**
     *  Verificar treinamento
     *
     * @param array $training array gerada com método $this->trainingMargin()
     * @param int $modo Modo de exibição dos dados
     * @param string $title Titulo
     * @return void
     */
    public static function showTraining(array $training = [], int $modo = 1, $title = "Margens")
    {
        if ($modo == 1) {
            echo "\n" . str_pad($title, 32, '-', STR_PAD_RIGHT) . "|";
            echo "\nAnálise-------------|Mín|Máx|Méd \n";
            foreach ($training as $name => $arrTrain) {
                echo str_pad($name, 20, '-', STR_PAD_RIGHT);
                echo "|";
                echo str_pad($arrTrain['min'], 3, ' ', STR_PAD_LEFT);
                echo "|";
                echo str_pad($arrTrain['max'], 3, ' ', STR_PAD_LEFT);
                echo "|";
                echo str_pad($arrTrain['med'], 3, ' ', STR_PAD_LEFT);
                echo "\n";
            }
            echo "--------------------------------| \n";
        } elseif ($modo == 2) {
            echo "\n" . str_pad($title, 26, '-', STR_PAD_RIGHT) . "|";
            echo "\nAnálise-------------|Total \n";
            foreach ($training as $name => $arrTrain) {
                echo str_pad($name, 20, '-', STR_PAD_RIGHT);
                echo "|";
                echo str_pad($arrTrain['analysis'], 3, ' ', STR_PAD_LEFT);
                echo "\n";
            }
            echo "--------------------------| \n";
        } elseif ($modo == 3) {
            echo "\n" . str_pad($title, 36, '-', STR_PAD_RIGHT) . "|";
            echo "\nAnálise-------------|Val|Mín|Máx|Méd \n";
            foreach ($training as $name => $arrTrain) {
                echo str_pad($name, 20, '-', STR_PAD_RIGHT);
                echo "|";
                echo str_pad($arrTrain['analysis'] ?? '-', 3, ' ', STR_PAD_LEFT);
                echo "|";
                echo str_pad($arrTrain['min'] ?? '-', 3, ' ', STR_PAD_LEFT);
                echo "|";
                echo str_pad($arrTrain['max'] ?? '-', 3, ' ', STR_PAD_LEFT);
                echo "|";
                echo str_pad($arrTrain['med'] ?? '-', 3, ' ', STR_PAD_LEFT);
                echo "\n";
            }
            echo "------------------------------------| \n";
        }
    }

    /**
     * Salvar dados em um arquivo
     *
     * @param string $file Nome do arquivo com caminho e extensão
     * @param string $content Conteúdo do arquivo
     * @param string $modo Modo de escrita
     * @return void
     */
    public static function saveFile($file, $content, $modo = "w+"): void
    {
        $save_file = fopen($file, $modo);
        fwrite($save_file, $content);
        fclose($save_file);
    }

    /**
     * Limpar terminal
     *
     * @return void
     */
    public static function clearTerminal()
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            popen('cls', 'w');
        } else {
            popen('clear', 'w');
        }
    }

    /**
     * log Inicio
     *
     * @return void
     */
    public static function logInicio()
    {
        echo "\nInício --- " . date("H:i:s") . "\n";
    }

    /**
     * log Inicio
     *
     * @return void
     */
    public static function logFim()
    {
        echo "\n\nFim --- " . date("H:i:s") . "\n";
    }

    /**
     * Função para perguntar
     *
     * @return string
     */
    public static function inputResp()
    {
        $handle = fopen("php://stdin", "r");
        do {
            $line = fgets($handle);
        } while ($line == '');
        fclose($handle);
        return self::removerQuebraLinha($line);
    }

    /**
     * Remover quebra de linha
     *
     * @param string $str
     * @return string
     */
    public static function removerQuebraLinha($str)
    {
        $str = str_replace("\n", "", $str);
        $str = str_replace("\r", "", $str);
        $str = preg_replace('/\s/', ' ', $str);
        return $str;
    }
}
