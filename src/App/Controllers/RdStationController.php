<?php

namespace App\Controllers;

class RdStationController extends IndexController
{
    public static function conversion($payload)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.rd.services/platform/conversions?api_key=31d5981a0d3862118b947e4a5323cbc6",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
        if (!$response) {
            $response = json_encode([]);
        }

        return $response;
    }
    public static function builderPayload($data)
    {
        $payload = [
            "event_type" => "CONVERSION",
            "event_family" => "CDP",
            "payload" => [
                "legal_bases" => [
                    [
                        "category" => "communications",
                        "type" => "consent",
                        "status" => "granted"
                    ]
                ],
                "conversion_identifier" => $data["conversion"],
                "email" => $data["email"],
                "mobile_phone" => $data["phone"],
                "name" => $data["name"]
            ]
        ];

        $fields = [
            'tags' => 'tags',
            'finalidade_imovel' => 'cf_finalidade_imovel',
            'preferencia_horario' => 'cf_preferencia_horario',
            'bairro' => 'cf_qual_bairro_voce_tem_interesse',
            'qtd_quartos' => 'cf_qtd_quartos',
            'fase_imovel' => 'cf_fase_imovel',
            'valor_imovel' => 'cf_valor_imovel',
            'campaign_id' => 'cf_campaign_id',
            'agente_id' => 'cf_agente_id'
        ];

        foreach ($fields as $dataKey => $payloadKey) {
            if (!empty($data[$dataKey])) {
                $payload['payload'][$payloadKey] = $data[$dataKey];
            }
        }

        return $payload;
    }
    public static function fields()
    {
        return [
            'finalidade_imovel',
            'preferencia_horario',
            'preferencia_bairro',
            'fase_imovel',
            'bairro',
            'qtd_quartos'
        ];
    }
}
