<?php

namespace App\Addons;

trait LSenderAddon
{
    /**
     * Send whatsapp text message
     *
     * @param  string  $receiver
     * @param  string  $message
     * @return object  $response
     */
    public function sendWhatsappText($receiver, $message)
    {
        if (!$receiver) {
            return (object) [
                'status' => false,
                'message' => 'Number is invalid!',
            ];
        }

        $body = array(
            "api_key" => env('LSENDERAPIKEY', 'aa8f92139b7c127d8296d14285972ebf5d92b10a'),
            "receiver" => $this->formatNumber($receiver),
            "data" => array("message" => $message),
        );

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://lsender.my.id/api/send-message",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => [
                "Accept: */*",
                "Content-Type: application/json",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
    }

    /**
     * Format number
     *
     * @param  string  $number
     * @return string  $number
     */
    public function formatNumber($number)
    {
        $number = preg_replace('/^(0|\+62)/', '62', $number);
        $number = preg_replace('/[^0-9]*/', '', $number);
        return $number;
    }
}