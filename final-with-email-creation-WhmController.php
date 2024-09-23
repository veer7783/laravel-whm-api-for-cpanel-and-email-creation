<?php


namespace App\Http\Controllers;

use App\Services\WhmService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhmController extends Controller
{
    protected function generateRandomPassword($length = 12)
    {
        $whmService = new WhmService();
        $whmService->init();

        do {
            $password = $whmService->generateStrongPassword($length);
        } while (!$whmService->checkPasswordStrength($password));

        return $password;
    }

   public function createCpanelAccount(Request $request): JsonResponse
{
    $whmService = new WhmService();
    $whmService->init();

    Log::info('Incoming Request', ['query' => $request->query()]);

    $validated = $request->validate([
        'domains' => 'required|array',
        'domains.*.domain' => 'required|string',
        'domains.*.username' => 'required|string',
        'domains.*.plan' => 'required|string',
        'domains.*.emails' => 'required|array',
        'domains.*.emails.*.username' => 'required|string',
    ]);

    $results = [];
    foreach ($validated['domains'] as $domainData) {
        $cpanelPassword = $this->generateRandomPassword(12);

        $createAccountResponse = $whmService->createAccount([
            'username' => $domainData['username'],
            'password' => $cpanelPassword,
            'domain' => $domainData['domain'],
            'plan' => $domainData['plan'],
        ]);

        if (!$createAccountResponse || !isset($createAccountResponse['metadata']['result']) || $createAccountResponse['metadata']['result'] !== 1) {
            $results[$domainData['domain']] = [
                'status' => 'failed',
                'message' => 'Failed to create cPanel account.',
                'response' => $createAccountResponse,
            ];
            continue;
        }

        Log::info('Created cPanel Account', ['response' => $createAccountResponse]);

        $results[$domainData['domain']] = [
            'status' => 'success',
            'message' => 'Account created successfully.',
            'response' => $createAccountResponse,
            'emails' => [] // Initialize the emails array
        ];

        foreach ($domainData['emails'] as $emailData) {
            $emailPassword = $whmService->generateStrongPassword(12);

            $emailResponse = $whmService->createEmailAccount(
                $domainData['username'],
                $domainData['domain'],
                $emailData['username'],
                $emailPassword
            );

            if (!$emailResponse || !isset($emailResponse['metadata']['result']) || $emailResponse['metadata']['result'] !== 1) {
                $results[$domainData['domain']]['emails'][] = [
                    'email' => $emailData['username'] . '@' . $domainData['domain'],
                    'status' => 'failed',
                    'response' => $emailResponse,
                ];
                Log::error('Failed to create email account', [
                    'email' => $emailData['username'] . '@' . $domainData['domain'],
                    'response' => $emailResponse,
                ]);
            } else {
                $results[$domainData['domain']]['emails'][] = [
                    'email' => $emailData['username'] . '@' . $domainData['domain'],
                    'status' => 'success',
                    'password' => $emailPassword, // Handle securely
                ];
                Log::info('Created Email Account', [
                    'email' => $emailData['username'] . '@' . $domainData['domain'],
                    'password' => $emailPassword // Handle securely
                ]);
            }
        }
    }

    return response()->json($results);
}
}