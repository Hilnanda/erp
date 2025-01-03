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
            "device_key" => env('LSENDERDEVICEKEY', '0b5b7662-0048-4fa0-87b2-89d26989ae2e'),
            "api_key" => env('LSENDERAPIKEY', 'aa8f92139b7c127d8296d14285972ebf5d92b10a'),
            "receiver" => $this->formatNumber($receiver),
            "message_type" => "text",
            "data" => array("message" => $message),
        );

        return $this->curlSend($body, env('LSENDERAPISENDTEXT', 'https://lsender.my.id/app/api/single/message'));
    }

    /**
     * Send whatsapp media
     *
     * @param  string  $receiver
     * @param  string  $media_url
     * @param  string  $type
     * @param  string  $caption
     * @return object  $response
     */
    public function sendWhatsappMedia($receiver, $media_url, $type, $caption)
    {
        if (!$receiver) {
            return (object) [
                'status' => false,
                'message' => 'Number is invalid!',
            ];
        }

        $body = array(
            "device_key" => env('LSENDERDEVICEKEY', '0b5b7662-0048-4fa0-87b2-89d26989ae2e'),
            "api_key" => env('LSENDERAPIKEY', 'aa8f92139b7c127d8296d14285972ebf5d92b10a'),
            "receiver" => $this->formatNumber($receiver),
            "message_type" => "media",
            "data" => [
                "url" => $media_url,
                "media_type" => $type,
                "caption" => $caption,
            ],
        );

        return $this->curlSend($body, env('LSENDERAPISENDMEDIA', 'https://lsender.my.id/app/api/single/message'));
    }

    /**
     * Curl send
     *
     * @param  Object  $body
     * @param  string  $url
     * @param  string  $method
     * @return object  $response
     */
    public function curlSend($body, $url, $method = "POST")
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Content-Type: application/json",
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return json_decode($err);
        } else {
            return json_decode($response);
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
        if (is_staging()) {
            return owner_mobile();
        }
        $number = preg_replace('/^(0|\+62)/', '62', $number);
        $number = preg_replace('/[^0-9]*/', '', $number);
        return $number;
    }
}