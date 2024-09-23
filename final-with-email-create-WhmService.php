<?php

namespace App\Services;
use SimpleXMLElement;
use DOMDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhmService
{
    protected $host;
    protected $user;
    protected $apiToken;

    public function init()
    {
        $this->host = env('WHM_HOST');
        $this->user = env('WHM_USER');
        $this->apiToken = env('WHM_API_TOKEN');
    }

    public function connect($apiPath, $params = [])
    {
        $url = "https://" . $this->host . ":2087" . $apiPath;

        Log::info('WHM API Request', ['url' => $url, 'params' => $params]);

        if (!isset($params['api.version'])) {
            $params['api.version'] = '1';
        }

        $response = Http::withHeaders([
            'Authorization' => 'whm ' . $this->user . ':' . $this->apiToken,
        ])->post($url, $params);

        if ($response->failed()) {
            Log::error('WHM API Response Failed', ['response' => $response->body()]);
            return false;
        }

        Log::info('WHM API Response', ['response' => $response->json()]);
        return $response->json();
    }

    public function createAccount($data)
    {
        $params = [
            'api.version' => '1',
            'username' => $data['username'],
            'password' => $data['password'],
            'domain' => $data['domain'],
            'plan' => $data['plan'],
        ];

        Log::info('Creating cPanel Account', ['params' => $params]);

        return $this->connect('/json-api/createacct', $params);
    }

    public function createEmailAccount($cpanelUser,$domain, $emailUser, $password)
    {
        $params = [
            'api.version' => '2',
            'cpanel.user' => $cpanelUser,
            'cpanel.module' => 'Email',
            'cpanel.function' => 'add_pop',
            'domain' => $domain,
            'email' => $emailUser,
            'password' => $password,
            'quota' => '100',
            'skip_update_db' => '1',
        ];

        Log::info('Creating Email Account', ['params' => $params]);

        // Use uapi_cpanel function
        return $this->connect('/json-api/uapi_cpanel', $params);
    }

    /**
     * Generate a strong, secure password.
     * 
     * @param int $length Length of the password
     * @return string The generated password
     */
    public function generateStrongPassword($length = 16)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()_+-=[]{},.<>?/|';
        $password = '';

        // Generate a password that meets a higher strength requirement
        do {
            $password = '';
            for ($i = 0; $i < $length; $i++) {
                $password .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while ($this->calculatePasswordStrength($password) < 80); // Ensure password strength >= 80

        return $password;
    }

    /**
     * Calculate the strength of the password.
     * 
     * @param string $password
     * @return int Password strength score
     */
    protected function calculatePasswordStrength($password)
    {
        $strength = 0;

        // Check for length
        if (strlen($password) >= 12) {
            $strength += 20;
        }
        if (strlen($password) >= 16) {
            $strength += 20;
        }

        // Check for different character types
        if (preg_match('/[a-z]/', $password)) {
            $strength += 15;
        }
        if (preg_match('/[A-Z]/', $password)) {
            $strength += 15;
        }
        if (preg_match('/[0-9]/', $password)) {
            $strength += 15;
        }
        if (preg_match('/[!@#$%^&*()_+\-=\[\]{};":\\|,.<>\/?]/', $password)) {
            $strength += 20;
        }

        // Bonus points for length and variety
        if (strlen($password) > 20) {
            $strength += 10;
        }
        if (preg_match_all('/[a-zA-Z]/', $password) > 6 && preg_match_all('/[0-9!@#$%^&*]/', $password) > 4) {
            $strength += 10;
        }

        return $strength;
    }

    /**
     * Check if the password meets the cPanel strength requirements.
     * 
     * @param string $password
     * @return bool
     */
    public function checkPasswordStrength($password)
    {
        // Here you can use the same logic as in generateStrongPassword or adjust according to cPanel requirements
        return $this->calculatePasswordStrength($password) >= 65;
    }
}