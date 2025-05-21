<?php

namespace App\Controllers;

use DateTime;

class SellitController
{
    // Função para enviar uma requisição HTTP
    public function call($url, $method, $payload, $token)
    {
        // Inicializa o cURL
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);

        // Se houver dados, configura o corpo da requisição
        if ($payload !== null) :
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
        endif;
        if (str_contains($url, "/conta-usuario/v1/autenticar")) :
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        else :
            curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer ' . $token));
        endif;
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $resposta = curl_exec($curl);

        // Verifica se ocorreu algum erro
        if (curl_errno($curl)) {
            $erro = curl_error($curl);
            echo "Erro na requisição: $erro";
        }

        // Fecha a conexão cURL
        curl_close($curl);

        return json_decode($resposta, true);
    }
    private function token()
    {
        $auth = [
            "login" => "marcus.gomes.ext@cyrela.com.br",
            "consumerApi" => 3
        ];
        return $this->call("https://prod-sellit.cyrela.com.br/identity/default/conta-usuario/v1/autenticar", "POST", $auth, null)["token"]["access_token"];
    }
    public function getEmail($data)
    {
        $data["email"] = $data["email"] ?? $data["lead"]["email"] ?? null;

        return $this->call("https://prod-sellit.cyrela.com.br/leads/integracao/leads/verificar-existencia-lead/v1?Email={$data["email"]}&OrigemIntegracao=4", "GET", $data, $this->token($data));
    }
    public function getPhone($data)
    {
        $data["telefone"] = $data["telefone"] ?? $data["lead"]["phone"] ?? null;

        if (!empty($data["telefone"])) :
            return $this->call("https://prod-sellit.cyrela.com.br/leads/integracao/leads/verificar-existencia-lead/v1?Telefone={$data["telefone"]}&OrigemIntegracao=4", "GET", $data, $this->token($data));
        else :
            return ["success" => false];
        endif;
    }
    public function getCodigoLead($data)
    {
        $data["CodigoLead"] = $_GET["CodigoLead"] ?? null;
        return $this->call("https://prod-sellit.cyrela.com.br/leads/integracao/leads/consultar-lead/v1?CodigoLead={$data["CodigoLead"]}&OrigemIntegracao=4", "GET", $data, $this->token($data));
    }
    public function atualizar($data)
    {
        $payload = [
            "codigoLead" => $data["codigoLead"],
            "observacoes" => $data["observacoes_atualizada"],
            "origemIntegracao" => 4 // Canal identifcador WAVEMAKER

        ];
        if (isset($data["cpfProprietario"]) && !empty($data["cpfProprietario"])) :
            $payload["cpfProprietario"] = $data["cpfProprietario"];
        endif;

        return $this->call("https://prod-sellit.cyrela.com.br/leads/integracao/leads/alterar-lead/v1", "PUT", $payload, $this->token($data));
    }
    public function dateTime()
    {
        date_default_timezone_set('America/Sao_Paulo');
        $date_time = new DateTime();
        return $date_time->format('d/m/Y H:i:s');
    }
    public function adicionar($data)
    {
        $payload = [
            "nome" => $data["name"],
            "telefone" => $data["phone"],
            "cpfProprietario" => $data["cpf_corretor"],
            "email" => $data["email"],
            "observacoes" => $data["observacoes"],
            // "idTemperaturaLead" => "",
            // "idOrigemLead" => "",
            // "idCanalContato" => "",
            // "idEmpreendimento" => "",
            "origemIntegracao" => 4
        ];

        return $this->call("https://prod-sellit.cyrela.com.br/leads/integracao/leads/criar-lead/v1", "POST", $payload, $this->token($data));
    }
    public static function removeBreakLines($content)
    {
        // Verifica se a string contém quebras de linha
        if (preg_match('/\r\n|\r|\n/', $content)) {
            // Remove todas as quebras de linha
            $content = preg_replace('/\r\n|\r|\n/', '', $content);
        }
        return $content;
    }
    public static function removeEmojis($content)
    {
        // Regex pattern to match most emojis
        $emojiPattern = '/[\x{1F600}-\x{1F64F}|\x{1F300}-\x{1F5FF}|\x{1F680}-\x{1F6FF}|\x{1F700}-\x{1F77F}|\x{1F780}-\x{1F7FF}|\x{1F800}-\x{1F8FF}|\x{1F900}-\x{1F9FF}|\x{1FA00}-\x{1FA6F}|\x{1FA70}-\x{1FAFF}|\x{2600}-\x{26FF}|\x{2700}-\x{27BF}|\x{2300}-\x{23FF}|\x{2B50}|\x{2934}-\x{2935}|\x{2B06}|\x{2194}-\x{21AA}|\x{2B06}|\x{2934}-\x{2935}]/u';

        return preg_replace($emojiPattern, '', $content);
    }
}
