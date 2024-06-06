<?php

/**
 * Classe com as funções para calcular as distâncias entre dois CEPs.
 */
class DistanciaCeps
{

    static $CEP_ABERTO_URL = "https://www.cepaberto.com/api/v3/cep?cep=";
    static $R = 6371;

    /**
     * Salva no banco de dados a distância entre dois CEPs. Salvará duas vezes, invertendo o lado
     *
     * @param integer $cep1 - CEP 1
     * @param integer $cep2 - CEP 2
     * @param float $distancia - Distância entre os dois CEPs
     * @return boolean - true se salvou com sucesso
     */
    public static function salvarDistancia($cep1, $cep2, $distancia)
    {
        return false;
    }

    /**
     * Coleta as coodenadas de um CEP na API CEP Aberto.
     *
     * @param integer $cep1 - CEP a ser coletado
     * @return array latitude e longitude
     *
     */
    public static function coletarCoordenadas($cep1)
    {
        $cepAbertoToken = getenv('CEP_ABERTO_TOKEN');
        $url = self::$CEP_ABERTO_URL . $cep1;

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Token token=$cepAbertoToken"));

        $response = curl_exec($curl);
        $data = json_decode($response, true);

        $lat = $data['latitude'];
        $lon = $data['longitude'];
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

        $distance = (float)number_format(self::$R * $c, 2);

        return $distance;
    }
}

$envFile = __DIR__ . '/.env';
$lat1 = -22.9068;
$lon1 = -43.1729;
$lat2 = 37.7749;
$lon2 = -122.4194;
$coordenadas1 = array($lat1, $lon1);
$coordenadas2 = array($lat2, $lon2);
$cep1 = "01001000";
$cep2 = "89053195";
// $distance = DistanciaCeps::distanciaCoordenadas($coordenadas1, $coordenadas2);
$coordenadas = DistanciaCeps::coletarCoordenadas($cep1);
print_r($coordenadas);;

// echo "A distância entre os pontos é de " . $distance . " km"