<?php

/**
 * Classe com as funções para calcular as distâncias entre dois CEPs.
 */
class DistanciaCeps
{

    static $CEP_ABERTO_URL = "https://www.cepaberto.com/api/v3/cep?cep=";
    static $R = 6371;

    /**
     * Faz alguns tratamentos no CEP para retornar um CEP válido.
     *
     * @param string $cep - CEP em seu formato de entrada
     * @return string - CEP só com números ou false se não for válido
     */
    public static function tratarCep($cep)
    {

        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) == 7 || strlen($cep) == 8) {

            $cepInt = intval($cep);

            // Verifica se o CEP está no range permitido
            if ($cepInt >= 1001000 && $cepInt <= 99999999) {
                // Completa com zeros à esquerda se necessário
                return str_pad($cep, 8, '0', STR_PAD_LEFT);
            }
        }

        return false;

    }

    /**
     * Salva no banco de dados a distância entre dois CEPs. Salvará duas vezes, invertendo o lado
     *
     * @param string $cep1 - CEP 1
     * @param string $cep2 - CEP 2
     * @param float $distancia - Distância entre os dois CEPs
     * @return boolean - true se salvou com sucesso
     */
    public static function salvarDistancia($cep1, $cep2, $distancia)
    {

        $servername = getenv('DB_SERVERNAME');
        $username = getenv('DB_USERNAME');
        $password = getenv('DB_PASSWORD');
        $dbname = getenv('DB_NAME');

        $cep1 = intval($cep1);
        $cep2 = intval($cep2);

        $conn = new mysqli($servername, $username, $password, $dbname);

        if ($conn->connect_error) {
            die("Falha na conexão: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("INSERT INTO distancias (cep_inicio, cep_fim, distancia) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE distancia = VALUES(distancia)");

        $stmt->bind_param("iid", $cep1, $cep2, $distancia);

        $stmt->execute();

        return false;
    }

    /**
     * Coleta as coodenadas de um CEP na API CEP Aberto.
     *
     * @param string $cep - CEP a ser coletado
     * @return array latitude e longitude
     *
     */
    public static function coletarCoordenadas($cep)
    {
        $cepAbertoToken = getenv('CEP_ABERTO_TOKEN');
        $url = self::$CEP_ABERTO_URL . $cep;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Token token=$cepAbertoToken"));

        $response = curl_exec($curl);
        $data = json_decode($response, true);

        $lat = $data['latitude'];
        $lon = $data['longitude'];

        print_r($lat);
        sleep(1);

        return array($lat, $lon);
    }

    /**
     * Calcula a distância as coodenadas.
     *
     * @param array $coordenadas1 - Coordenadas 1 (lat, lon)
     * @param array $coordenadas2 - Coordenadas 2 (lat, lon)
     * @return float distância entre as coordenadas
     *
     */
    public static function distanciaCoordenadas($coordenadas1, $coordenadas2)
    {

        $lat1 = deg2rad($coordenadas1[0]);
        $lon1 = deg2rad($coordenadas1[1]);
        $lat2 = deg2rad($coordenadas2[0]);
        $lon2 = deg2rad($coordenadas2[1]);

        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;

        $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlon / 2) * sin($dlon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $distance = (float) number_format(self::$R * $c, 2);

        return $distance;
    }
}

$cep1 = "01001-000";
$cep2 = "89053-195";
$cep1 = DistanciaCeps::tratarCep($cep1);
$cep2 = DistanciaCeps::tratarCep($cep2);
$coordenadas1 = DistanciaCeps::coletarCoordenadas($cep1);
$coordenadas2 = DistanciaCeps::coletarCoordenadas($cep2);
$distancia = DistanciaCeps::distanciaCoordenadas($coordenadas1, $coordenadas2);
$deuBoa = DistanciaCeps::salvarDistancia($cep1, $cep2, $distancia);

echo $deuBoa;