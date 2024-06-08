<?php

/**
 * Classe com as funções para calcular as distâncias entre dois CEPs.
 */
class DistanciaCeps
{

    static $CEP_ABERTO_URL = "https://www.cepaberto.com/api/v3/cep?cep=";
    static $R = 6371;

    /**
     * Busca as consultas de um cep ou todos.
     *
     * @param string $cep1 - CEP primario de consulta
     * @param string $cep2 - CEP secundário de consulta
     * @return string - resultado da consulta em json
     */
    public static function buscarDistanciasMySQL($cep1 = '', $cep2 = '')
    {
        $conn = self::connectMySQL();

        if ($cep1 == null && $cep2 == null) {
            $sql = "SELECT cep_inicio, cep_fim, distancia FROM distancias";
        } else if ($cep2 == null) {
            $sql = "SELECT cep_inicio, cep_fim, distancia FROM distancias WHERE cep_inicio = '$cep1'";
        } else if ($cep1 == null) {
            $sql = "SELECT cep_inicio, cep_fim, distancia FROM distancias WHERE cep_fim = '$cep2'";
        } else {
            $sql = "SELECT cep_inicio, cep_fim, distancia FROM distancias WHERE cep_inicio = '$cep1' AND cep_fim = '$cep2'";
        }

        $result = $conn->query($sql);

        $distancias = array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $distancias[] = $row;
            }
        }

        $conn->close();

        $distanciaJson = json_encode($distancias);

        return $distanciaJson;
    }

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
     * Faz uma conexão com o banco de dados.
     *
     * @return mysqli - conexão com o banco de dados
     */
    protected static function connectMySQL()
    {
        $servername = getenv('DB_SERVERNAME');
        $username = getenv('DB_USERNAME');
        $password = getenv('DB_PASSWORD');
        $dbname = getenv('DB_NAME');

        $conn = new mysqli($servername, $username, $password, $dbname);
        $sql = "SET time_zone = 'America/Sao_Paulo'";
        $conn->query($sql);

        return $conn;
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

        $cep1 = intval($cep1);
        $cep2 = intval($cep2);

        $conn = self::connectMySQL();

        if ($conn->connect_error) {
            die("Falha na conexão: " . $conn->connect_error);
        }

        $stmt = $conn->prepare("INSERT INTO distancias (cep_inicio, cep_fim, distancia) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE distancia = VALUES(distancia)");
        $stmt->bind_param("iid", $cep1, $cep2, $distancia);
        $stmt->execute();
        $stmt->bind_param("iid", $cep2, $cep1, $distancia);
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

    /**
     * Faz alguns tratamentos no CEP para retornar um CEP válido.
     *
     * @param string $nivel - número do nível (0: TRACE, 1: DEBUG, 2: INFO, 3: WARNING, 4: ERROR)
     * @param string $mensagem - CEP em seu formato de entrada
     * @param array $contexto - CEP em seu formato de entrada
     */
    public static function salvarLog($nivel, $mensagem, $contexto)
    {
        switch ($nivel) {
            case 0:
                $nivel_str = "TRACE";
                break;
            case 1:
                $nivel_str = "DEBUG";
                break;
            case 2:
                $nivel_str = "INFO";
                break;
            case 3:
                $nivel_str = "WARNING";
                break;
            case 4:
                $nivel_str = "ERROR";
                break;
            default:
                $nivel_str = "UNKNOWN";
                break;
        };

        $contexto_json = json_encode($contexto);

        $conn = self::connectMySQL();

        $stmt = $conn->prepare("INSERT INTO logs (level, message, context) VALUES (?, ?, ?)");

        $stmt->bind_param("sss", $nivel_str, $mensagem, $contexto_json);

        $stmt->execute();

        $stmt->close();
        $conn->close();
    }

    public static function buscarLogsMySQL()
    {
        $conn = self::connectMySQL();
        $sql = "SELECT * FROM logs ORDER BY id DESC LIMIT 50";
        $result = $conn->query($sql);
        $logs = array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
        }
        $conn->close();
        $logsJson = json_encode($logs);
        return $logsJson;
    }
}

$cep1 = "01002-000";
$cep2 = "89053-195";
$cep1 = DistanciaCeps::tratarCep($cep1);
$cep2 = DistanciaCeps::tratarCep($cep2);
$coordenadas1 = DistanciaCeps::coletarCoordenadas($cep1);
$coordenadas2 = DistanciaCeps::coletarCoordenadas($cep2);
$distancia = DistanciaCeps::distanciaCoordenadas($coordenadas1, $coordenadas2);

DistanciaCeps::salvarDistancia($cep1, $cep2, $distancia);
DistanciaCeps::salvarLog(2, "Distância entre os CEPs salva com sucesso", array("cep1" => $cep1, "cep2" => $cep2, "distancia" => $distancia));

header('Content-Type: application/json');

echo json_encode(DistanciaCeps::buscarDistanciasMySQL());
