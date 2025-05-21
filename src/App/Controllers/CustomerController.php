<?php

namespace App\Controllers;

class CustomerController
{
    public function ApiKey($api_key)
    {

        if ($api_key == "SAlwKT0uMAeAIKbiJYKdSbZTW3hMtAZbovqJw0sySDVbxOi8B4GeBX8fhXMYfmqv") {
            return true;
        }
        return false;
    }
    // Função para formatar o telefone
    public static function formatarTelefone($telefone)
    {
        // Remover caracteres não numéricos
        $telefone = preg_replace('/\D/', '', $telefone);

        // Verificar o tamanho do telefone
        $tamanho = strlen($telefone);

        // Verificar a quantidade de dígitos e aplicar a formatação conforme necessário
        switch ($tamanho) {
                // case 8: // 8 dígitos (sem DDD, DDI e sem nono dígito)
                //     $telefone = "55119" . $telefone;
                //     break;
                // case 9: // 9 dígitos (sem DDD, DDI e com nono dígito)
                //     $telefone = "5511" . $telefone;
                //     break;
            case 10: // 10 dígitos (sem DDI e sem nono dígito)
                $ddd = substr($telefone, 0, 2);
                $resto = substr($telefone, 2);

                if ($ddd < 28) {
                    $telefone = "55" . $ddd . "9" . $resto; // Adiciona o DDI e o nono dígito
                } else {
                    $telefone = "55" . "9" . $telefone; // Apenas adiciona o DDI
                }
                break;

            case 11: // 11 dígitos (sem DDI e com nono dígito)
                $ddd = substr($telefone, 0, 2);
                $resto = substr($telefone, 3); // Pula o nono dígito

                if ($ddd < 28) {
                    $telefone = "55" . $telefone; // Adiciona o DDI e mantém o nono dígito
                } else {
                    $telefone = "55" . $ddd . "9" .  $resto; // Adiciona o DDI e remove o nono dígito
                }
                break;

            case 12: // 12 dígitos (com DDI e sem nono dígito)
                $ddi = substr($telefone, 0, 2);
                $ddd = substr($telefone, 2, 2);
                $resto = substr($telefone, 4);

                if ($ddi === "55" && ($ddd < 28 || $ddd >= 28)) {
                    $telefone = $ddi . $ddd . "9" . $resto; // Adiciona o nono dígito
                }

                break;

            case 13: // 13 dígitos (com DDI e com nono dígito)
                $ddi = substr($telefone, 0, 2);
                $ddd = substr($telefone, 2, 2);
                $resto = substr($telefone, 5); // Pula o nono dígito

                if ($ddi === "55" && $ddd >= 28) {
                    $telefone = $ddi . $ddd . "9" . $resto; // Remove o nono dígito
                }
                break;
            default:
                $telefone = null;
                break;
        }

        return $telefone;
    }
}
