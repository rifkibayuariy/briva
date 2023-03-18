<?php

require('vendor/autoload.php');

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Exception\ClientException;

class BRIVirtualAccount
{
    public $institution_code = '0';
    public $briva_number = 0;

    private $client_id;
    private $client_secret;
    private $api_url;
    private $token;

    /**
     * @param string                                $client_id      Consumer key
     * @param string                                $client_secret  Consumer scret
     * @param string|UriInterface                   $api_url URI    API URL
     */
    public function __construct($client_id, $client_secret, $api_url)
    {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->api_url = $api_url;
        $this->refresh_token();
    }

    private function timestamp()
    {
        return gmdate("Y-m-d\TH:i:s.000\Z");
    }

    private function signature($path, $verb, $timestamp, $payload = NULL)
    {
        $payloads = "path=$path&verb=$verb&token=Bearer {$this->token}&timestamp=$timestamp&body=$payload";
        return base64_encode(hash_hmac("sha256", $payloads, $this->client_secret, TRUE));
    }

    private function request($data, $type = null)
    {
        $client = new Client();

        $verb = isset($data['verb']) ? $data['verb'] : "GET";
        $path = isset($data['path']) ? $data['path'] : "";
        $body = isset($data['body']) ? $data['body'] : "";

        $timestamp = $this->timestamp();
        $signature = $this->signature("/$path", $verb, $timestamp, $body);

        if ($type != 'refresh_token') {
            $headers = [
                "Authorization" => "Bearer {$this->token}",
                "BRI-Timestamp" => "{$timestamp}",
                "BRI-Signature" => "{$signature}"

            ];
        }

        if (isset($data['type'])) $headers['Content-Type'] = $data['type'];

        $url = $this->api_url . $path;
        $request = new Request($verb, $url, $headers, $body);

        try {
            $res = $client->sendAsync($request)->wait();
            $code = $res->getStatusCode();
            $origin = $res->getBody()->getContents();
            $response = (array) json_decode($origin);
        } catch (ClientException $e) {
            $code = $e->getResponse()->getStatusCode();
            $origin = $e->getResponse()->getBody()->getContents();
            $response = (array) json_decode($origin);
        }

        return [
            'code' => $code,
            'response' => $response,
            'origin' => $origin
        ];
    }

    public function refresh_token()
    {
        $response = $this->request([
            'verb' => 'POST',
            'path' => 'oauth/client_credential/accesstoken?grant_type=client_credentials',
            'body' => "client_id={$this->client_id}&client_secret={$this->client_secret}",
            'type' => 'application/x-www-form-urlencoded'
        ], 'refresh_token');

        $this->token = $response['response']['access_token'];
    }

    public function create($payload = [])
    {
        $body = [
            'institutionCode' => isset($payload['institutionCode']) ? $payload['institutionCode'] : "",
            'brivaNo' => isset($payload['brivaNo']) ? $payload['brivaNo'] : "",
            'custCode' => isset($payload['custCode']) ? $payload['custCode'] : "",
            'nama' => isset($payload['nama']) ? $payload['nama'] : "",
            'amount' => isset($payload['amount']) ? $payload['amount'] : "",
            'keterangan' => isset($payload['keterangan']) ? $payload['keterangan'] : "",
            'expiredDate' => isset($payload['expiredDate']) ? $payload['expiredDate'] : ""
        ];

        $response = [
            'verb' => 'POST',
            'path' => 'v1/briva',
            'type' => 'application/json',
            'body' => json_encode($body)
        ];

        return $response;
    }

    public function get_status($customer_code = "")
    {
        $response = $this->request([
            'path' => "v1/briva/status/{$this->institution_code}/{$this->briva_number}/$customer_code",
        ]);

        return $response;
    }

    public function get_report($from, $to)
    {
        $response = $this->request([
            'path' => "v1/briva/report/{$this->institution_code}/{$this->briva_number}/$from/$to",
        ]);

        return $response;
    }

    public function get($customer_code = "")
    {
        $response = $this->request([
            'path' => "v1/briva/{$this->institution_code}/{$this->briva_number}/$customer_code",
        ]);

        return $response;
    }

    public function update_status($payload = [])
    {
        $body = [
            'institutionCode' => isset($payload['institutionCode']) ? $payload['institutionCode'] : "",
            'brivaNo' => isset($payload['brivaNo']) ? $payload['brivaNo'] : "",
            'custCode' => isset($payload['custCode']) ? $payload['custCode'] : "",
            'statusBayar' => isset($payload['statusBayar']) ? $payload['statusBayar'] : ""
        ];

        $response = [
            'verb' => 'PUT',
            'path' => 'v1/briva/status',
            'type' => 'application/json',
            'body' => json_encode($body)
        ];

        return $response;
    }

    public function update($payload = [])
    {
        $body = [
            'institutionCode' => isset($payload['institutionCode']) ? $payload['institutionCode'] : "",
            'brivaNo' => isset($payload['brivaNo']) ? $payload['brivaNo'] : "",
            'custCode' => isset($payload['custCode']) ? $payload['custCode'] : "",
            'nama' => isset($input['nama']) ? $input['nama'] : "",
            'amount' => isset($input['amount']) ? $input['amount'] : "",
            'keterangan' => isset($input['keterangan']) ? $input['keterangan'] : "",
            'expiredDate' => isset($input['expiredDate']) ? $input['expiredDate'] : ""
        ];

        $response = [
            'verb' => 'PUT',
            'path' => 'v1/briva',
            'type' => 'application/json',
            'body' => json_encode($body)
        ];

        return $response;
    }

    public function delete($customer_code = "")
    {
        $result = $this->request([
            'path' => 'v1/briva',
            'verb' => 'DELETE',
            'type' => 'Text/plain',
            'body' => "institutionCode={$this->institution_code}&brivaNo={$this->briva_number}&custCode=$customer_code"
        ]);

        return $result;
    }
}
