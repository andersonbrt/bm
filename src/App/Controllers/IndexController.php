<?php

namespace App\Controllers;

use App\Controllers\CustomerController;
use App\Controllers\SellitController;
use DateTime;

class BaseController
{
    // Armazena os dados da requisição
    protected $data = [];

    // Construtor para capturar dados da requisição
    public function __construct()
    {
        // Mescla $_POST e $_GET com $this->data apenas se não estiverem vazios
        if (!empty($_POST)) {
            $this->data = array_merge($this->data, $_POST);
        }

        if (!empty($_GET)) {
            $this->data = array_merge($this->data, $_GET);
        }

        // Remove a chave 'api_key' se existir
        unset($this->data['api_key']);


        // Captura dados de JSON (raw ou application/json)
        if ($this->isJsonRequest()) {
            $jsonData = $this->getDataFromJson();

            // Verifica se os dados JSON são válidos antes de mesclá-los
            if (is_array($jsonData)) {
                $this->data = array_merge($this->data, $jsonData);  // Adiciona os dados JSON
            } else {
                // Lida com o erro, por exemplo, você pode lançar uma exceção ou logar o erro
                // Exemplo: Definir dados como array vazio caso JSON seja inválido
                $this->data = array_merge($this->data, []);  // Adiciona um array vazio caso JSON seja inválido
                // Ou, caso queira um tratamento de erro:
                // $this->sendJsonResponse(false, "Dados JSON inválidos.");
            }
        }

        // Captura dados de formato `application/x-www-form-urlencoded`
        if ($this->isUrlEncoded()) {
            $this->data = array_merge($this->data, $_POST);  // Adiciona dados de formulário URL-encoded
        }

        // Captura dados de conteúdo raw (quando não for JSON, nem formulário)
        if ($this->isRawData()) {
            $rawData = $this->getDataFromRaw();
            $this->data = array_merge($this->data, $rawData);  // Adiciona dados raw
        }
    }

    // Verifica se a requisição tem conteúdo JSON
    private function isJsonRequest()
    {
        return strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false;
    }

    // Verifica se o conteúdo é application/x-www-form-urlencoded
    private function isUrlEncoded()
    {
        return strpos($_SERVER['CONTENT_TYPE'], 'application/x-www-form-urlencoded') !== false;
    }

    // Verifica se a requisição tem conteúdo raw (geralmente usado em APIs com corpo)
    private function isRawData()
    {
        return empty($_POST) && empty($_GET) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') === false;
    }

    // Recupera dados do corpo da requisição em JSON
    private function getDataFromJson()
    {
        $json = file_get_contents('php://input');
        // Decodifica o JSON, retornando null caso o JSON seja inválido
        return json_decode($json, true);
    }
    // Recupera dados do corpo da requisição raw
    private function getDataFromRaw()
    {
        return json_decode(file_get_contents('php://input'), true);
    }

    // Método para acessar os dados capturados
    public function getData()
    {
        return $this->data;
    }
}

// Exemplo de Controller que herda a classe BaseController
class IndexController extends BaseController
{
    public function handleRequest()
    {
        // Função para enviar a resposta como JSON
        function sendJsonResponse($status, $message)
        {
            echo json_encode([
                'status' => $status,
                'message' => $message
            ]);
            exit;
        }

        // Verifica os parâmetros 'page', 'per_page', 'filter_key', 'filter_value', 'filter_operator'
        $missing_params = [];

        if (empty($_GET['api_key'])) {
            $missing_params[] = "'api_key'";
        } else {
            $auth = new CustomerController();
            $api_key = $auth->ApiKey($_GET['api_key']);
        }
        // Se houver parâmetros faltando, gera a mensagem e envia a resposta
        if (!empty($missing_params)) {
            $missing_params_str = implode(", ", $missing_params);
            sendJsonResponse(false, "$missing_params_str é obrigatório!");
            exit;
        }

        if ($api_key === false) {
            sendJsonResponse(false, "'api_key' não autorizada!");
            exit;
        }

        // Pega todos dados ddo body da requisicao
        return $this->getData();
    }
    public function dateTime()
    {
        date_default_timezone_set('America/Sao_Paulo');
        $date_time = new DateTime();
        return $date_time->format('d/m/Y H:i:s');
    }
    public function formatHandle($data, $source)
    {
        if ($source == '3cplus') {
            $params['campaign_id'] = (string)$data['call-history-was-created']['callHistory']['campaign']['id'];
            $params['agente_id'] = (string)$data['call-history-was-created']['callHistory']['agent']['id'];
            $params['campaign_name'] = $data['call-history-was-created']['callHistory']['campaign']['name'];
            $params['list_name'] = $data['call-history-was-created']['callHistory']['list']['name'];
            $params['qualification_id'] = $data['call-history-was-created']['callHistory']['qualification']['id'];
            $params['qualification_name'] = strtolower($data['call-history-was-created']['callHistory']['qualification']['name']);
            $params['phone'] = $data['call-history-was-created']['callHistory']['number'];
            $params['email'] = $data['call-history-was-created']['callHistory']['mailing_data']['data']['email'] ?? "{$params['phone']}@naotememail.com";

            $params['name'] = null;
            $leadData = [
                $data["nome"] ?? null,
                $data["call-history-was-created"]["callHistory"]["mailing_data"]["data"]["NOME"] ?? null,
                $data["call-history-was-created"]["callHistory"]["mailing_data"]["data"]["nome"] ?? null,
                $data["call-history-was-created"]["callHistory"]["mailing_data"]["data"]["name"] ?? null,
                $data["call-history-was-created"]["callHistory"]["mailing_data"]["data"]["cliente_nome"] ?? null,
                $data["call-history-was-created"]["callHistory"]["mailing_data"]["data"]["nome_cliente"] ?? null,
                $data["call-history-was-created"]["callHistory"]["mailing_data"]["data"]["nome_negocio"] ?? null,
                $data["call-history-was-created"]["callHistory"]["mailing_data"]["identifier"] ?? null,
                $data["lead"]["name"] ?? null
            ];

            // Encontra o primeiro valor não nulo
            foreach ($leadData as $name) {
                if (!empty($name)) {
                    $params["name"] = $name;
                    break;
                }
            }
        }
        if ($source == 'rdstation') {

            $params['app_conversion'] = $_GET['app_conversion'] ?? null;
            $params['tags'] = $data['leads'][0]['tags'] ?? [];
            $params['name'] = $data['leads'][0]['name'] ?? null;
            $params['email'] = $data['leads'][0]['email'] ?? null;
            $params['produto'] = $data['leads'][0]['last_conversion']['content']['identificador'] ?? null;
            $params['origem'] = $data['leads'][0]['last_conversion']['conversion_origin']['source'] ?? null;
            $params['veiculo_captacao'] = $data['leads'][0]['custom_fields']['VeiculoCaptacao'] ?? null;
            $params['agente_id'] = $data['leads'][0]['custom_fields']['agente_id'] ?? null;
            //$params['codigo_lead'] = $data['']
            $params['data_hora'] = $data['leads'][0]['last_conversion']['created_at'] ?? null;
            $params['phone'] = null;
            $leadData = [
                $data["leads"][0]["mobile_phone"] ?? null,
                $data["leads"][0]["personal_phone"] ?? null
            ];

            // Encontra o primeiro valor não nulo
            foreach ($leadData as $phone) {
                if (!empty($phone)) {
                    $params["phone"] = $phone;
                    break;
                }
            }
        }

        return $params;
    }
    public function veiculosCaptacao()
    {
        return [
            'Anúncio Internet Corretor',
            'api-vista-cadastro-lead',
            'Captei',
            'Cônjuge',
            'Feirão do Imóvel',
            'Indicação',
            'Já é cliente',
            'Panfleto',
            'Parcerias',
            'Presencial - Loja',
            'Prospecção Ativa',
            'Rádio',
            'Reativado',
            'Telefone - Loja',
            'Trabalho próprio corretor',
            'Tráfego Direto'
        ];
    }
    public function webhooks()
    {
        // Define que o retorno será sempre em JSON
        header('Content-Type: application/json');

        // Coleta dados de requisição autorizada
        $data = $this->handleRequest();

        // Formata para dados vindo do RD Station
        $data = $this->formatHandle($data, 'rdstation');

        // pega lista de veiculos de captacao backlist
        $veiculosBlacklist = $this->veiculosCaptacao();

        // Blacklist 'cf_veiculo_captacao'
        if (isset($data['veiculo_captacao']) && !empty($data['veiculo_captacao'])) {

            // Verifica por regra de contém
            if (in_array($data['veiculo_captacao'], $veiculosBlacklist) || str_contains('Placa', $data['veiculo_captacao']) || str_contains('Plantão', $data['veiculo_captacao'])) {

                sendJsonResponse(400, "'VeiculoCaptacao' não permitido");
                exit;
            }
        }

        // Verifica e formata o telefone
        if (empty($data['phone'])) {

            // Mescla os dados e cria arrays pai para 'statusPhone'
            $data = array_merge($data, [
                'statusPhone' => "'telefone' é obrigatório!"
            ]);

            // Grava log em arquivo.json
            $this->logJson($data);

            // Retorna resposta
            sendJsonResponse(200, $data['statusPhone']);
            exit;
        }

        // Formata telefone
        $data['phone'] = CustomerController::formatarTelefone($data['phone']);
        //$data['phone'] = substr($data['phone'], 2);

        // Verifica se telefone formatado é valido
        if ($data['phone'] === null) {

            // Mescla os dados e cria arrays pai para 'statusPhone'
            $data = array_merge($data, [
                'statusPhone' => "'telefone' é inválido ou não existe!"
            ]);

            // Grava log em arquivo.json
            $this->logJson($data);

            // Retorna resposta
            sendJsonResponse(200, $data['statusPhone']);
            exit;
        }

        // Identifica se possuir tag #prime
        // Verificando se "prime" está presente em "tags"
        if (in_array("prime", $data['tags'])) {
            // Define lista e campanha PRIME
            $list_id = 2097551; // Leads: PRIME
            $campaign_id = 156961; // Campanha: PRIME
            $campaign_name = "prime";
        } else {
            // Define lista e campanha EQUIPE
            $list_id = 2101158; // Leads: EQUIPE
            $campaign_id = 157813; // Campanha: EQUIPE
            $campaign_name = "equipe";
        }

        $getCliente = $this->getCliente($data);

        if (isset($getCliente['Status']) && ($getCliente['Status'] == 'Comprou na imobiliária' || $getCliente['Status'] == 'Ativo' || $getCliente['Status'] == 'Proprietário')) {

            $data['tags'] = ["vistacrm - notifica corretor"];
            $data['conversion'] = "vistacrm - notifica corretor";

            // Criar payload RD Station
            $payloadRdStation = RdStationController::builderPayload($data);

            // Enviar para Webhook workflows para leads adicionados ao 3cplus / notificar corretor
            echo $this->sendToWebhookLeadsCadencia($payloadRdStation);
            exit;
        }

        // Prepara o payload para inclusão de lead na lista do 3CPLUS
        $payload = [
            [
                'phone' => $data['phone'],
                'identifier' => $data['phone'],
                'data' => [
                    'name' => $data['name'],
                    'produto' => $data['produto'],
                    'origem' => $data['origem'],
                    'data' => $data['data_hora'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'list_id' => (string)$list_id,
                    'campaign_id' => (string)$campaign_id,
                ]
            ]
        ];

        // Adiciona 'origem' apenas se não for null
        if ($data['origem'] !== null) {
            $payload[0]['data']['origem'] = $data['origem'];
        }

        // Faz envio para a lista da campanha
        $add_list = TresCPlusController::call($payload, (string)$list_id, (string)$campaign_id); // adição na lista

        // Se não houver resposta, atribui um status de sucesso padrão
        if (!$add_list) {
            $add_list = ["status" => 200, "message" => "Adicionado a lista"];

            // Define tags
            $data['tags'] = ["3cplus - adicionado a campanha - {$campaign_name}"];

            // Define conversion
            $data['conversion'] = "3cplus - adicionado a campanha - {$campaign_name}";

            // Define campaign_id no $data
            $data['campaign_id'] = (string)$campaign_id;

            // Criar payload RD Station
            $payloadRdStation = RdStationController::builderPayload($data);

            // Enviar para Webhook workflows para leads adicionados ao 3cplus / notificar corretor
            $this->sendToWebhookLeadsCadencia($payloadRdStation);
        }

        // Altera o peso da lista para 99
        $active_list = TresCPlusController::weight($list_id, $campaign_id); // ativação da lista

        // Mescla os dados e cria arrays com respectivos pais
        $data = array_merge($data, [
            '3cplus' => [
                'payload' => $payload,
                'add_list' => $add_list,
                'active_list' => $active_list
            ]
        ]);

        // Grava log em arquivo.json
        $this->logJson($data);

        // Envia resposta
        sendJsonResponse(200, $data);
    }
    public function leads_campanha_base()
    {
        // Define que o retorno será sempre em JSON
        header('Content-Type: application/json');

        // Coleta dados de requisição autorizada
        $data = $this->handleRequest();

        // Formata para dados vindo do RD Station
        $data = $this->formatHandle($data, 'rdstation');

        // Formata telefone
        $data['phone'] = CustomerController::formatarTelefone($data['phone']);

        // Define lista e campanha PRIME
        $list_id = 2192323; // Leads: PRIME
        $campaign_id = 150069; // Campanha: PRIME
        $campaign_name = "campanha base";
        // Prepara o payload para inclusão de lead na lista do 3CPLUS
        $payload = [
            [
                'phone' => $data['phone'],
                'identifier' => $data['phone'],
                'data' => [
                    'name' => $data['name'],
                    'produto' => $data['produto'],
                    'origem' => $data['origem'],
                    'data' => $data['data_hora'],
                    'phone' => $data['phone'],
                    'email' => $data['email'],
                    'list_id' => (string)$list_id,
                    'campaign_id' => (string)$campaign_id,
                ]
            ]
        ];

        // Adiciona 'origem' apenas se não for null
        if ($data['origem'] !== null) {
            $payload[0]['data']['origem'] = $data['origem'];
        }

        // Faz envio para a lista da campanha
        $add_list = TresCPlusController::call($payload, (string)$list_id, (string)$campaign_id); // adição na lista

        // Se não houver resposta, atribui um status de sucesso padrão
        if (!$add_list) {
            $add_list = ["status" => 200, "message" => "Adicionado a lista"];

            // Define tags
            $data['tags'] = ["3cplus - adicionado a campanha - {$campaign_name}"];

            // Define conversion
            $data['conversion'] = "3cplus - adicionado a campanha - {$campaign_name}";

            // Define campaign_id no $data
            $data['campaign_id'] = (string)$campaign_id;

            // Criar payload RD Station
            $payloadRdStation = RdStationController::builderPayload($data);

            // Enviar para Webhook workflows para leads adicionados ao 3cplus / notificar corretor
            //$this->sendToWebhookLeadsCadencia($payloadRdStation);
        }

        // Altera o peso da lista para 99
        $active_list = TresCPlusController::weight($list_id, $campaign_id); // ativação da lista

        // Mescla os dados e cria arrays com respectivos pais
        $data = array_merge($data, [
            '3cplus' => [
                'payload' => $payload,
                'add_list' => $add_list,
                'active_list' => $active_list
            ]
        ]);

        // Grava log em arquivo.json
        $this->logJson($data);

        // Envia resposta
        sendJsonResponse(200, $data);
    }
    public function identificarHorario(): string
    {
        // Definir o fuso horário para São Paulo (Brasil)
        date_default_timezone_set('America/Sao_Paulo');

        // Obter a hora atual (em formato 24 horas)
        $horaAtual = date('H:i:s');

        // Definir os períodos com base nas regras fornecidas
        $manhaInicio = '08:00:00'; // Começa às 08:00:00
        $manhaFim = '12:00:00';    // Vai até às 12:00:00
        $tardeInicio = '12:00:01';  // Começa às 12:00:01
        $tardeFim = '18:00:00';     // Vai até às 18:00:01
        $noiteInicio = '18:00:01';  // Começa às 18:00:01
        $noiteFim = '20:00:00';     // Vai até às 20:00:00

        // Identificar o período baseado na hora atual
        if ($horaAtual >= $manhaInicio && $horaAtual <= $manhaFim) {
            $periodo = "adicionado-3cplus-manha";
        } elseif ($horaAtual >= $tardeInicio && $horaAtual <= $tardeFim) {
            $periodo = "adicionado-3cplus-tarde";
        } elseif ($horaAtual >= $noiteInicio && $horaAtual <= $noiteFim) {
            $periodo = "adicionado-3cplus-noite";
        } else {
            $periodo = "adicionado-3clus-fora-expediente";
        }
        return $periodo;
    }
    public function oportunidade()
    {
        // Define que o retorno será sempre em JSON
        header('Content-Type: application/json');

        // Coleta dados de requisição autorizada
        $data = $this->handleRequest();

        // Formata para dados vindo do RD Station
        $data = $this->formatHandle($data, 'rdstation');

        // Verifica e formata o telefone
        if (empty($data['phone'])) {

            // Mescla os dados e cria arrays pai para 'statusPhone'
            $data = array_merge($data, [
                'statusPhone' => "'telefone' é obrigatório!"
            ]);

            // Grava log em arquivo.json
            $this->logJson($data);

            // Retorna resposta
            sendJsonResponse(200, $data['statusPhone']);
            exit;
        }

        // Formata telefone
        $data['phone'] = CustomerController::formatarTelefone($data['phone']);
        //$data['phone'] = substr($data['phone'], 2);

        // Verifica se telefone formatado é valido
        if ($data['phone'] === null) {

            // Mescla os dados e cria arrays pai para 'statusPhone'
            $data = array_merge($data, [
                'statusPhone' => "'telefone' é inválido ou não existe!"
            ]);

            // Grava log em arquivo.json
            $this->logJson($data);

            // Retorna resposta
            sendJsonResponse(200, $data['statusPhone']);
            exit;
        }

        // if ($data['agente_id'] === null) {

        //     $data = array_merge($data, [
        //         'agenteIdStatus' => "'agente_id' não existe!"
        //     ]);

        //     // Grava log em arquivo.json
        //     $this->logJson($data);

        //     // Retorna resposta
        //     sendJsonResponse(200, $data['agenteIdStatus']);
        //     exit;
        // }


        $sendLeadVistaCrm = $this->sendLeadVistaCrm($data);

        // Grava log em arquivo.json
        $this->logJson($data);

        // Envia resposta
        sendJsonResponse(200, $sendLeadVistaCrm);
    }
    public function getConnections(array $data, int $campaign_id): array
    {
        // Obtém o JSON das campanhas
        $getJson = file_get_contents('https://1v1connect.com/api/clients/vivaz/house/admin/campanhas.json');
        if (!$getJson) {
            return [
                'status' => false,
                'message' => 'Erro ao obter o JSON.'
            ];
        }

        // Decodifica o JSON para um array associativo
        $campanhas = json_decode($getJson, true);
        if (!isset($campanhas['campanhas'])) {
            return [
                'status' => false,
                'message' => 'Formato inválido de JSON.'
            ];
        }

        // Verifica se o campo cp_id está definido
        if (!isset($campaign_id)) {
            return [
                'status' => false,
                'message' => 'O campo cp_id não foi fornecido.'
            ];
        }

        // Variável para armazenar o Flow ID encontrado
        $data['getConnections'] = null;

        // Procura dentro das campanhas pelo velip_id correspondente ao cp_id
        foreach ($campanhas['campanhas'] as $item) {

            if (isset($item['name']) && $item['name'] == $campaign_id) {
                $data['getConnections'] = $item;
                break; // Encerra o loop ao encontrar o valor
            }
        }

        return $data;
    }
    public function allowedsQualifications(array $array, $key)
    {
        foreach ($array['qualificacoes'] as $item) {

            if (isset($item) && !empty($item) && $item == $key) {
                return true;
            }
        }

        return false;
    }
    public function qualifications()
    {
        // Define que o retorno será sempre em JSON
        header('Content-Type: application/json');

        // Coleta dados de requisição autorizada
        $data = $this->handleRequest();
        $data_log = $data;

        // Formata Handler
        $data = $this->formatHandle($data, '3cplus');

        if ($data['qualification_name'] != null) {

            // Grava log em arquivo.json
            $this->logJson($data_log);

            // Define tags
            $data['tags'] = ["qualificacao 3cplus - {$data['qualification_name']}"];

            // Define conversion
            $data['conversion'] = "qualificacao 3cplus - {$data['qualification_name']}";

            // Criar payload RD Station
            $payloadRdStation = RdStationController::builderPayload($data);

            // Enviar para Webhook workflows para leads adicionados ao 3cplus / notificar corretor
            echo $this->sendToWebhookLeadsQualificados($payloadRdStation);
            exit;
        }

        sendJsonResponse(200, 'campanha nao permitida');
    }
    public function test()
    {
        // Define que o retorno será sempre em JSON
        header('Content-Type: application/json');

        // Coleta dados de requisição autorizada
        $data = $this->handleRequest();

        // Grava log em arquivo.json
        $this->logJsonTest($data);
    }
    public function periodosRdconversas(): array
    {
        return // Controle de dados
            [
                'Manhã' => 'rdconversas - ligar de manha',
                'Tarde' => 'rdconversas - ligar a tarde',
                'Noite' => 'rdconversas - ligar a noite',
                '8 às 9 horas' => 'rdconversas - ligar de 8 as 9 horas',
                '10 horas' => 'rdconversas - ligar as 10 horas',
                '11 horas' => 'rdconversas - ligar as 11 horas',
                '12 horas' => 'rdconversas - ligar as 12 horas',
                '13 horas' => 'rdconversas - ligar as 13 horas',
                '14 horas' => 'rdconversas - ligar as 14 horas',
                '15 horas' => 'rdconversas - ligar as 15 horas',
                '16 horas' => 'rdconversas - ligar as 16 horas',
                '17 horas' => 'rdconversas - ligar as 17 horas',
                '18 às 19 horas' => 'rdconversas - ligar de 18 as 19 horas',


            ];
    }

    public function tagsAndConversion(string $action, array $data): array
    {
        if ($action === 'horarios') {
            // pega mapeamento de horarios
            $horarios = $this->periodosRdconversas();

            // Verifica se o periodo existe no array
            if (array_key_exists($data['preferencia_horario'], $horarios)) {
                $data["tags"] = [$horarios[$data['preferencia_horario']]];
                $data["conversion"] = $horarios[$data['preferencia_horario']];
            }
        }

        if ($action === 'rdconversas - oportunidade') {
            // Define tags
            $data['tags'] = ["rdconversas - prosseguiu via whatsapp"];

            // Define conversion
            $data['conversion'] = "rdconversas - prosseguiu via whatsapp";
        }
        return $data;
    }
    public function callbackRdConversas()
    {
        // Define que o retorno será sempre em JSON
        header('Content-Type: application/json');

        // Coleta dados de requisição autorizada
        $data = $this->handleRequest();

        ## 'Verifica Periodo'
        if (isset($data['preferencia_horario']) && !empty($data['preferencia_horario'])) {

            // Enviar para webhook do workflows horarios
            echo $this->sendToWebhookLeadDB($data);
            exit;
        }

        ## Verifica 'Finalidade do imovel'
        if (isset($data['finalidade_imovel']) && !empty($data['finalidade_imovel'])) {

            // define tags e conversao de acordo com qualificação RD Conversas
            $data = $this->tagsAndConversion('rdconversas - oportunidade', $data);

            // Criar payload RD Station
            $payloadRdStation = RdStationController::builderPayload($data);

            // Enviar para webhook do workflows
            echo $this->sendToWebhookLeadsQualificados($payloadRdStation);
            exit;
        }

        ## Verifica 'status_lead'
        if (isset($data['status_lead']) && ($data['status_lead'] === 'qualificado')) {

            // define tags e conversao de acordo com qualificação RD Conversas
            $data = $this->tagsAndConversion('rdconversas - oportunidade', $data);

            // Criar payload RD Station
            $payloadRdStation = RdStationController::builderPayload($data);

            // Enviar para webhook do workflows
            echo $this->sendToWebhookLeadsQualificados($payloadRdStation);
            exit;
        }

        return true;
    }
    public function builder($data, $format)
    {
        $params = [];

        if ($format === 'success') {
            $params = [
                'name' => $data['3cplus']['name'],
                'email' => $data['3cplus']['email'],
                'phone' => $data['3cplus']['phone'],
                'origin' => $data['3cplus']['origin'],
                'campaign_id' => $data['3cplus']['campaign_id'],
                'campaign_name' => $data['3cplus']['campaign_name'],
                'list_name' => $data['3cplus']['list_name'],
                'observacoes' => $data['3cplus']['observacoes'],
                'cpf_corretor' => $data['3cplus']['cpf_corretor'],
                'name_corretor' => $data['adicionarSellit']['data']['nomeCorretor'],
                'codigo_lead' => $data['adicionarSellit']['data']['codigoLead'],
                'etapa_funil' => $data['adicionarSellit']['data']['etapaFunil']
            ];
        }
        return $params;
    }
    public function logJson($data)
    {
        // Define o fuso horário para o Brasil
        date_default_timezone_set('America/Sao_Paulo');

        // Obtém a data e hora atual no formato brasileiro
        $dateTime = date('d/m/Y H:i:s');

        // Adiciona a data e hora ao array de dados
        $data['data_hora'] = $dateTime;

        // Caminho para o arquivo JSON onde os dados serão armazenados
        $filePath = 'dados.json';

        // Verifica se o arquivo já existe
        if (file_exists($filePath)) {
            // Lê os dados atuais do arquivo
            $currentData = json_decode(file_get_contents($filePath), true);
            // Se o arquivo estiver vazio, inicializa com um array vazio
            if (!is_array($currentData)) {
                $currentData = [];
            }
        } else {
            // Se o arquivo não existir, inicializa como um array vazio
            $currentData = [];
        }

        // Adiciona o novo registro ao array
        $currentData[] = $data;

        // Grava os dados de volta no arquivo JSON sem escapar as barras
        file_put_contents($filePath, json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    public function logJsonTest($data)
    {
        // Define o fuso horário para o Brasil
        date_default_timezone_set('America/Sao_Paulo');

        // Obtém a data e hora atual no formato brasileiro
        $dateTime = date('d/m/Y H:i:s');

        // Adiciona a data e hora ao array de dados
        $data['data_hora'] = $dateTime;

        // Caminho para o arquivo JSON onde os dados serão armazenados
        $filePath = 'dados-test.json';

        // Verifica se o arquivo já existe
        if (file_exists($filePath)) {
            // Lê os dados atuais do arquivo
            $currentData = json_decode(file_get_contents($filePath), true);
            // Se o arquivo estiver vazio, inicializa com um array vazio
            if (!is_array($currentData)) {
                $currentData = [];
            }
        } else {
            // Se o arquivo não existir, inicializa como um array vazio
            $currentData = [];
        }

        // Adiciona o novo registro ao array
        $currentData[] = $data;

        // Grava os dados de volta no arquivo JSON sem escapar as barras
        file_put_contents($filePath, json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    public function sendToWebhook($item)
    {
        // Inicializa a comunicação cURL
        $ch = curl_init("https://workflows.1v1connect.com/webhook-test/53286477-60e9-4c0f-8312-da3ee9971c06");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($item));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        // Executa o cURL e captura a resposta
        $response = curl_exec($ch);

        // Verifica se ocorreu algum erro
        if (curl_errno($ch)) {
            echo 'Erro cURL: ' . curl_error($ch);
        }

        curl_close($ch);
        return $response;
    }
    public function sendToWebhookLeadDB($item)
    {
        // Inicializa a comunicação cURL
        $ch = curl_init("https://n8n.waveconnection.com.br/webhook/aa9e19b8-c69d-441a-87b7-b3d5cb6cdf58");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($item));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        // Executa o cURL e captura a resposta
        $response = curl_exec($ch);

        // Verifica se ocorreu algum erro
        if (curl_errno($ch)) {
            echo 'Erro cURL: ' . curl_error($ch);
        }

        curl_close($ch);
        return $response;
    }
    public function sendToWebhookLeadsCadencia($item)
    {
        // Inicializa a comunicação cURL
        $ch = curl_init("https://n8n.waveconnection.com.br/webhook/614f4064-c44c-48ff-a163-224559fa3d0a");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($item));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        // Executa o cURL e captura a resposta
        $response = curl_exec($ch);

        // Verifica se ocorreu algum erro
        if (curl_errno($ch)) {
            echo 'Erro cURL: ' . curl_error($ch);
        }

        curl_close($ch);
        return $response;
    }
    public function sendToWebhookLeadsQualificados($item)
    {
        // Inicializa a comunicação cURL
        $ch = curl_init("https://n8n.waveconnection.com.br/webhook/633bcf41-85c2-4054-a22c-8f06d48d6329");

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($item));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);

        // Executa o cURL e captura a resposta
        $response = curl_exec($ch);

        // Verifica se ocorreu algum erro
        if (curl_errno($ch)) {
            echo 'Erro cURL: ' . curl_error($ch);
        }

        curl_close($ch);
        return $response;
    }
    public function getRequest($url, $method, $payload = [])
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Accept: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);

        if ($response === FALSE) {
            // Lidar com erro
            return [];
        } else {
            // Processar resposta
            return json_decode($response, true);
        }
    }

    public function getcliente($data)
    {
        // Monta pesquisa de filtro baseado no last_id
        $pesquisa = [
            "fields" => [
                "DataCadastro",
                "Celular",
                "FoneResidencial",
                "EmailResidencial",
                "FonePrincipal",
                "FoneComercial",
                "EmailComercial",
                "Codigo",
                "Status",
                "Nome"
            ],
            "order" => ["Codigo" => "asc"],
            "filter" => ["EmailResidencial" => $data['email']],
            //"filter" => ["Status" => "Comprou na imobiliária"], // Ativo, Inativo, Comprou na imobiliária, Não receber mais Ligações e Proprietário
            "paginacao" => ["pagina" => 1, "quantidade" => 50]

        ];

        $key = json_encode($pesquisa);
        $urlEncodeKey = urlencode($key);

        $baseUrl = "https://cli21423-rest.vistahost.com.br/clientes/listar?key=06fd20dabb96eb04a2c761a9ee8feff8&showtotal=1&pesquisa={$urlEncodeKey}";

        $return = $this->getRequest($baseUrl, 'GET');

        // Verificar se há clientes no retorno
        if (isset($return) && is_array($return)) {
            // Iterar sobre todos os elementos, que são os clientes
            foreach ($return as $key => $cliente) {
                // Verifique se o item não é a parte de total ou outras informações
                if (is_array($cliente) && isset($cliente['Codigo'])) {
                    return $cliente;
                }
            }
        }

        return [];
    }
    public function sendLeadVistaCrm($data)
    {
        $payload = [
            "cadastro" => [
                "lead" => [
                    "nome" => $data["name"],
                    "phone" => $data["phone"],
                    "email" => $data["email"],
                    "mensagem" => "Oportunidade - {$data["app_conversion"]}"
                ]

            ]
        ];

        if (isset($data['app_conversion']) && $data['app_conversion'] == 'rdconversas') {
            $payload['cadastro']['lead']['veiculo'] = 'oportunidade whatsapp';
        }

        if (isset($data['app_conversion']) && $data['app_conversion'] == '3cplus') {
            $payload['cadastro']['lead']['veiculo'] = $data["agente_id"];
        }

        if (isset($data['app_conversion']) && $data['app_conversion'] == 'prime') {
            $payload['cadastro']['lead']['veiculo'] = $data['app_conversion'];
        }

        $baseUrl = "https://cli21423-rest.vistahost.com.br/lead?key=06fd20dabb96eb04a2c761a9ee8feff8";

        $response = $this->getRequest($baseUrl, 'POST', $payload);


        return $response;
    }
}
