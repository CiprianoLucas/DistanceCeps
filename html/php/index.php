<?php

/**
 * Classe com as funções para calcular as distâncias entre dois CEPs.
 */
class DistanciaCeps
{

    static $CEP_ABERTO_URL = "https://www.cepaberto.com/api/v3/cep?cep=";
    static $R = 6371;
    public $importacao = false;
    public $erro = false;
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Busca as consultas de um cep ou todos.
     *
     * @param string $cep1 - CEP primario de consulta
     * @param string $cep2 - CEP secundário de consulta
     * @return string - resultado da consulta em json
     */
    public function buscarDistanciasMySQL($cep1 = '', $cep2 = '')
    {
        $conn = self::connectMySQL();

        if ($cep1 == null && $cep2 == null) {
            $sql = "SELECT cep_inicio, cep_fim, distancia FROM distancias ORDER BY id DESC LIMIT 200";
        } else if ($cep2 == null) {
            $cep1 = self::tratarCep($cep1);
            $sql = "SELECT cep_inicio, cep_fim, distancia FROM distancias WHERE cep_inicio = '$cep1' ORDER BY id DESC LIMIT 200";
        } else if ($cep1 == null) {
            $cep2 = self::tratarCep($cep2);
            $sql = "SELECT cep_inicio, cep_fim, distancia FROM distancias WHERE cep_fim = '$cep2' ORDER BY id DESC LIMIT 200";
        } else {
            $cep1 = self::tratarCep($cep1);
            $cep2 = self::tratarCep($cep2);
            $sql = "SELECT cep_inicio, cep_fim, distancia FROM distancias WHERE cep_inicio = '$cep1' AND cep_fim = '$cep2' ORDER BY id DESC LIMIT 200";
        }

        $result = $conn->query($sql);

        $distancias = array();
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $cepInicio = $row['cep_inicio'];
                $cepFim = $row['cep_fim'];

                $cepInicio = str_pad(strval($cepInicio), 8, '0', STR_PAD_LEFT);
                $cepFim = str_pad(strval($cepFim), 8, '0', STR_PAD_LEFT);

                $cepInicioFormatado = substr($cepInicio, 0, 5) . '-' . substr($cepInicio, 5, 3);
                $cepFimFormatado = substr($cepFim, 0, 5) . '-' . substr($cepFim, 5, 3);

                $distancias[] = array(
                    'cep_inicio' => $cepInicioFormatado,
                    'cep_fim' => $cepFimFormatado,
                    'distancia' => $row['distancia']
                );
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
    public function tratarCep($cep)
    {

        $cep = preg_replace('/\D/', '', $cep);

        if (strlen($cep) == 7 || strlen($cep) == 8) {

            $cepInt = intval($cep);


            if ($cepInt >= 1001000 && $cepInt <= 99999999) {

                return str_pad($cep, 8, '0', STR_PAD_LEFT);
            }
        }

        self::gerarErro("CEP $cep formato inválido");

        return 0;
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

        if ($conn->connect_error) {
            http_response_code(500);
            echo json_encode(['error' => "Falha ao conectar com o banco de dados"]);
            exit;
        }


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
    public function salvarDistancia($cep1, $cep2, $distancia)
    {
        if (!$this->erro) {
            $cep1 = intval($cep1);
            $cep2 = intval($cep2);

            $conn = self::connectMySQL();

            $result = $conn->query("SELECT * FROM distancias WHERE cep_inicio = $cep1 AND cep_fim = $cep2");

            if ($result->num_rows > 0) {
                $update = true;
            } else {
                $update = false;
            }

            $stmt = $conn->prepare("INSERT INTO distancias (cep_inicio, cep_fim, distancia) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE distancia = VALUES(distancia)");
            $stmt->bind_param("iid", $cep2, $cep1, $distancia);
            $stmt->execute();
            $stmt->bind_param("iid", $cep1, $cep2, $distancia);
            $stmt->execute();

            if ($update) {
                self::salvarLog(2, "Atualizado: Distância entre $cep1 e $cep2: $distancia Km");
            } else {
                self::salvarLog(2, "Cadastrado: Distância entre $cep1 e $cep2: $distancia Km");
            }

            return $update;
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
    public function distanciaCeps($cep1, $cep2)
    {
        $cep1 = DistanciaCeps::tratarCep($cep1);
        $cep2 = DistanciaCeps::tratarCep($cep2);

        $coordCep1 = self::coletarCoordenadas($cep1);
        $coordCep2 = self::coletarCoordenadas($cep2);

        $distancia = self::distanciaCoordenadas($coordCep1, $coordCep2);

        $update = self::salvarDistancia($cep1, $cep2, $distancia);

        if ($update) {
            $response = [
                'success' => "Atualizado: Distância entre $cep1 e $cep2: $distancia Km",
            ];
        } else {
            $response = [
                'success' => "Cadastrado: Distância entre $cep1 e $cep2: $distancia Km",
            ];
        }

        return json_encode($response);
    }

    public function importarCeps()
    {
        $this->importacao = true;

        if ($_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {

            $caminho = 'caminho/para/salvar/o/arquivo.csv';

            move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho);


            $arquivo = fopen($caminho, 'r');

            while (($linha = fgetcsv($arquivo)) !== false) {

                $cep1 = $linha[0];
                $cep2 = $linha[1];

                self::distanciaCeps($cep1, $cep2);
            }

            fclose($arquivo);
        } else {
            self::gerarErro("Erro ao importar arquivo");
        }
    }

    /**
     * Coleta as coodenadas de um CEP na API CEP Aberto.
     *
     * @param string $cep - CEP a ser coletado
     * @return array latitude e longitude
     *
     */
    public function coletarCoordenadas($cep)
    {
        $cepAbertoToken = getenv('CEP_ABERTO_TOKEN');
        $url = self::$CEP_ABERTO_URL . $cep;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: Token token=$cepAbertoToken"));

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            self::gerarErro("Erro ao consultar CEP");
        }

        $data = json_decode($response, true);

        if (!isset($data['latitude']) || !isset($data['longitude'])) {
            self::gerarErro("CEP $cep não encontrada");
        }

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

        $distancia = round(self::$R * $c, 2);

        return $distancia;
    }

    /**
     * Salva um log no banco de dados.
     *
     * @param string $nivel - número do nível (0: TRACE, 1: DEBUG, 2: INFO, 3: WARNING, 4: ERROR)
     * @param string $mensagem - mensagem do log
     * @param array $contexto - contexto do log
     */
    protected function salvarLog($nivel, $mensagem, $contexto = null)
    {
        $contexto = $contexto ?? $this->data;

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
        }
        ;

        $contexto_json = json_encode($contexto);

        $conn = self::connectMySQL();

        $stmt = $conn->prepare("INSERT INTO logs (level, message, context) VALUES (?, ?, ?)");

        $stmt->bind_param("sss", $nivel_str, $mensagem, $contexto_json);

        $stmt->execute();

        $stmt->close();
        $conn->close();
    }
    /**
     * Salva um log de erro no banco de dados e retorna um json de erro.
     *
     * @param string $mensagem - mensagem de erro
     *
     */
    public function gerarErro($mensagem)
    {
        http_response_code(400);
        if (!$this->importacao) {
            echo json_encode(['error' => "$mensagem"]);
        }
        self::salvarLog(4, $mensagem);
        self::toExit();
    }

    /**
     * Busca os logs no banco de dados.
     *
     * @param string - json logs
     *
     */

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

    /**
     * Impede que o código continue.
     *
     * @param string - json logs
     *
     */

    protected function toExit()
    {
        if (!$this->importacao) {
            exit;
        } else {
            $this->erro = true;
        }
    }
}

error_reporting(0);

// Configurações
header("Access-Control-Allow-Origin: *");
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    // Adiciona os cabeçalhos necessários para o CORS
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type");
    header("Access-Control-Max-Age: 86400"); // Cache por 1 dia
    exit(0); // Encerra a execução sem retornar conteúdo
}


$str_required = file_get_contents('php://input');

//Verifica se é uma impostação de CEPS
if (strpos($str_required, 'importarCeps') !== false) {

    $partes = preg_replace('/[\r\n\s\t.]/', " ", $str_required);
    $partes = explode(" ", $partes);
    $comecar = false;
    $error = false;
    foreach ($partes as $parte) {

        if (strpos($parte, 'cep1;cep2') !== false) {
            $comecar = true;
            continue;
        }

        if (!$comecar || $parte == "") {
            continue;
        }

        if ((strpos($parte, '--') !== false)) {
            break;
        }

        $linha = explode(";", $parte);
        $data = [
            "funcao" => "importarCeps",
            "cep1" => $linha[0],
            "cep2" => $linha[1]
        ];

        try {
            $request = new DistanciaCeps($data);
            $request->importacao = true;
            $request->distanciaCeps($linha[0], $linha[1]);

            if ($request->erro) {
                $error = true;
            }

        } catch (Exception) {

            $error = true;
            continue;
        }
    }
    if ($error) {
        echo json_encode(['error' => 'Alguns ceps não foram importados, verifique o arquivo e os logs e tente novamente'], true);
    } else {
        echo json_encode(['success' => 'Todos os CEPs fora cadastrados com sucesso!'], true);
    }

    //Valida se é outra função
} else {

    $data = json_decode($str_required, true);

    $request = new DistanciaCeps($data);

    $funcao = $data['funcao'];

    switch ($funcao) {

        case 'buscarDistancias':

            echo $request->buscarDistanciasMySQL($data['cep1'], $data['cep2']);

            break;

        case 'salvarDistancia':

            echo $request->distanciaCeps($data['cep1'], $data['cep2']);
            break;


        case 'logs':

            echo $request->buscarLogsMySQL();

            break;

        default:
            echo json_encode(['error' => 'funcao não encontrada']);
    }
}
