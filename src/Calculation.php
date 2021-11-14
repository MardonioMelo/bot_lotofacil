<?php

namespace Src;

class Calculation
{

    private $dataset;
    private $file_dataset;    

    /**
     * Construção
     * 
     */
    public function __construct()
    {
        $this->file_dataset = "./dataset.json";

        if (!file_exists($this->file_dataset)) {
            $this->updateDataset();
        }
        $this->setDataset();
    }

    /**
     * Verificar se um jogo é inedito
     * Nuca saiu uma jogo repetido de 15 certos
     *
     * @param string $jogo_input
     * @return void
     */
    public function unprecedented(string $jogo_input): void
    {
        $jogos = [];

        foreach ($this->dataset as $value) {
            $jogos[] = implode("-", $value);
        }

        $result = in_array($jogo_input, $jogos) ? "\nJá saiu.\n" : "\nNunca saiu.\n";

        print_r($result);
    }

    /**
     * Verificar o valor máximo e minimo de cada bola
     *
     * @param array $last_games
     * @return array
     */
    public function chackMaxMin(array $last_games): array
    {
        $data = [];

        foreach ($last_games as $jogo) {
            foreach ($jogo as $key => $dezena) {
                $data['Bola' . ($key + 1)][] = $dezena;
            }
        }

        $max_min = [];
        foreach ($data as $key => $item) {
            $max_min[$key]['min'] = min($item);
            $max_min[$key]['max'] = max($item);
        }
        unset($data);

        return  $max_min;
    }

    /**
     * Contar a frequencia de cada número
     *
     * @param array $last_games
     * @return array
     */
    public function countFrequency(array $last_games): array
    {
        for ($i = 1; $i <= 25; $i++) {
            $frequency[$i] = 0;
        }

        foreach ($last_games as $line) {

            foreach ($line as $item) {
                $frequency[(int)$item] += 1;
            }
        }

        $print = [];

        arsort($frequency);
        foreach ($frequency as $key => $item) {
            $print[$key] = $item;
        }

        return $print;
    }

    /**
     * Quantidade de numeros imperes e pares no jogo
     *
     * @param array $jogo
     * @return array
     */
    public function qtdImparPar(array $jogo): array
    {
        $qtd['par'] = 0;
        $qtd['impar'] = 0;
        foreach ($jogo as $item) {
            is_int((int)$item / 2) ? $qtd['par'] += 1 : $qtd['impar'] += 1;
        }

        return $qtd;
    }

    /**
     *  Números mais atrasados
     *
     * @param array $last_games
     * @return array
     */
    public function laterNumbers(array $last_games): array
    {
        for ($i = 1; $i <= 25; $i++) {
            $dezena['count'][$i] = 0;
            $dezena['stop'][$i] = false;
        }

        krsort($last_games);

        foreach ($last_games as $line) {
            foreach ($dezena['stop'] as $key => $item) {

                if (in_array(str_pad($key, 2, '0', STR_PAD_LEFT), $line)) {
                    $dezena['stop'][(int)$key] = true;
                } else if ($dezena['stop'][(int)$key] == false) {
                    $dezena['count'][(int)$key] += 1;
                }
            }
        }
        arsort($dezena['count']);

        return array_filter($dezena['count']);
    }

    /**
     * Soma das dezenas 166-220
     *
     * @return void
     */
    public function sumDezene($jogo)
    {
        return array_sum($jogo);
    }

    /**
     * Quadro com os 25 resultados
     *
     * @return void
     */
    public function withThe25()
    {
        $print = "\nQuatro com os 25 resultados:\n";

        for ($i = 1; $i <= 25; $i++) {
            $table_ref[$i] = $i;
        }

        foreach ($this->dataset as $line) {

            $str = "";
            foreach ($table_ref as $item_ref) {
                // if (in_array($item_ref, $line)) {
                //     $num = str_pad($item_ref, 2, '0', STR_PAD_LEFT);
                //     $str .= " | {$num}";
                // } else {
                //     $str .= " | --";
                // }

                if (in_array($item_ref, $line)) {
                    $num = str_pad($item_ref, 2, '0', STR_PAD_LEFT);
                    $str .= " 0 ";
                } else {
                    $str .= "   ";
                }
            }

            $print .= "\n {$str}";
        }

        $save_file = fopen("./dataset_img.txt", "w+");
        fwrite($save_file, $print);
        fclose($save_file);

        print_r("\nOs dados foram salvos no arquivo dataset_img.csv\n");
    }

    /**
     * Atualizar base de dados
     *
     * @return void
     */
    public function updateDataset()
    {
        print_r("\nConsultando dados da API...");

        $last = file_get_contents('https://loterias-api-gutotech.herokuapp.com/api/v0/lotofacil');
        $json = json_decode($last);

        print_r("\nSalvando os dados consultados...");

        $save_file = fopen($this->file_dataset, "w+");
        fwrite($save_file, $last);
        fclose($save_file);

        print_r("\nBase de dados atualizada!\n");
    }

    /**
     * Carregar historico de jogos na memória
     *
     * @return void
     */
    public function setDataset()
    {
        $json = json_decode(file_get_contents($this->file_dataset), true);
        $this->dataset = array_map(function ($arr) {
            return array_map(function ($v) {
                return (int) $v;
            }, $arr);
        }, array_column($json, 'dezenas'));
    }

    /**
     * Obter uma fração dos ultimos concursos
     *
     * @param integer $qtd
     * @return array
     */
    public function getLastGames(int $limit): array
    {
        return array_slice($this->dataset, count($this->dataset) - $limit, $limit, true);
    }    
}
