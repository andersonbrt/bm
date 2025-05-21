<?php

namespace App\Controllers;

use Exception;

class TresCPlusController
{
    private static function token()
    {
        return "UBmc4XtcrNOTh8gcAhQBSzi9ZesLjuY45Yrrvi0uK3glYgFRIuz642fSmFAF";
    }
    public static function call($payload, $list_id, $campaign_id)
    {
        $token = TresCPlusController::token();
        $endpoint = "https://3c.fluxoti.com/api/v1/campaigns/{$campaign_id}/lists/{$list_id}/mailing.json?api_token={$token}";

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }
    public static function removeList($payload, $campaign_id)
    {
        $token = TresCPlusController::token();
        $endpoint = "https://3c.fluxoti.com/api/v1/campaigns/{$campaign_id}/mailing/delete?api_token={$token}";
        return $endpoint;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
    public static function weight($list_id, $campaign_id)
    {
        $token = TresCPlusController::token();
        $endpoint = "https://3c.fluxoti.com/api/v1/campaigns/{$campaign_id}/lists/{$list_id}/updateWeight?api_token={$token}";
        $payload = [
            "weight" => 99
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $endpoint,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "PUT",
            CURLOPT_POSTFIELDS => json_encode($payload, true),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response, true);
    }
    public static function checkUser($json, $id_find)
    {
        foreach ($json["data"] as $item) {
            if ($item["id"] == $id_find) {
                return $item;
            }
        }
        return null;
    }
    public static function getUsers($url)
    {
        $response = file_get_contents($url);
        if ($response === FALSE) {
            throw new Exception("Erro ao obter dados da URL: $url");
        }
        return json_decode($response, true);
    }
    public static function getUserById($id_find)
    {
        $token = TresCPlusController::token();

        // URL get users
        $url_base = "https://3c.fluxoti.com/api/v1/users?api_token={$token}&per_page=25";
        // pega users na api do 3cplus
        $json = TresCPlusController::getUsers($url_base);

        // Buscar ID na página atual
        $item = TresCPlusController::checkUser($json, $id_find);
        if ($item !== null) {
            return $item;
        }

        while ($item == null && isset($json["meta"]["pagination"]["links"]["next"])) {

            $url = $url_base . "&page=" . $json["meta"]["pagination"]["current_page"] + 1;
            $json = TresCPlusController::getUsers($url);

            // Buscar ID na página atuals
            $item = TresCPlusController::checkUser($json, $id_find);
            if ($item !== null) {
                return $item;
            }
        }

        return null;
    }
}
