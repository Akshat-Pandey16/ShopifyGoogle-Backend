<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google_Client;
use App\Models\AuthToken;
use Google\Service\Sheets as GoogleSheets;
use App\Models\GoogleSpreadsheet;

class GoogleController extends Controller
{
    public function google_auth(Request $request)
    {
        $authCode = $request->input('code');
        $client = new Google_Client();
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));
        $client->setRedirectUri('postmessage');
        $client->addScope('https://www.googleapis.com/auth/spreadsheets');
        $client->setAccessType('offline');

        try {
            $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
            $user = $request->user();
            $existingToken = AuthToken::where('user_id', $user->id)->first();

            if ($existingToken) {
                $existingToken->update([
                    'google_access_token' => $accessToken['access_token'],
                    'google_refresh_token' => $accessToken['refresh_token'],
                ]);
            } else {
                AuthToken::create([
                    'user_id' => $user->id,
                    'google_access_token' => $accessToken['access_token'],
                    'google_refresh_token' => $accessToken['refresh_token'],
                ]);
            }

            return response()->json($accessToken);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to exchange authorization code for tokens', 'message' => $e->getMessage()], 400);
        }
    }

    public function getClient($userId)
    {
        $userTokens = AuthToken::where('user_id', $userId)->first();
        if (!$userTokens) {
            return response()->json(['error' => 'User tokens not found'], 404);
        }

        $accessToken = $userTokens->google_access_token;
        $refreshToken = $userTokens->google_refresh_token;
        $accessToken = $userTokens->shopify_token;
        $client = new Google_Client();
        $client->setAccessType('offline');
        $client->setAccessToken($accessToken);
        $client->setClientId(env('GOOGLE_CLIENT_ID'));
        $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));

        if ($client->isAccessTokenExpired()) {
            $client->refreshToken($refreshToken);
            $newAccessToken = $client->getAccessToken();
            $accessToken = $newAccessToken['access_token'];
            $userTokens->update(['google_access_token' => $accessToken]);
        }
        return $client;
    }

    public function createSheet(Request $request)
    {
        $sheetName = $request->name;
        $userId = $request->user()->id;
        $client = $this->getClient($userId);
        try {
            $sheetsService = new GoogleSheets($client);
            $spreadsheetTitle = $sheetName;
            $spreadsheet_exist = GoogleSpreadsheet::where('spreadsheet_name', $spreadsheetTitle)->first();
            if ($spreadsheet_exist) {
                try {
                    $spreadsheet = $sheetsService->spreadsheets->get($spreadsheet_exist->spreadsheet_id);
                    return response()->json(['error' => 'Spreadsheet already exists'], 400);
                } catch (\Exception $e) {
                    $spreadsheet = new GoogleSheets\Spreadsheet([
                        'properties' => [
                            'title' => $spreadsheetTitle,
                        ]
                    ]);
                    $spreadsheet = $sheetsService->spreadsheets->create($spreadsheet);
                    $spreadsheetId = $spreadsheet->spreadsheetId;
                    $spreadsheet_exist->update(['spreadsheet_id' => $spreadsheetId]);
                    return response()->json(['message' => 'Spreadsheet created', 'spreadsheet_id' => $spreadsheetId], 200);
                }
            } else {
                $spreadsheet = new GoogleSheets\Spreadsheet([
                    'properties' => [
                        'title' => $spreadsheetTitle,
                    ]
                ]);
                $spreadsheet = $sheetsService->spreadsheets->create($spreadsheet);
                $spreadsheetId = $spreadsheet->spreadsheetId;
                GoogleSpreadsheet::create([
                    'user_id' => $userId,
                    'spreadsheet_id' => $spreadsheetId,
                    'spreadsheet_name' => $sheetName,
                ]);
                return response()->json(['message' => 'Spreadsheet created', 'spreadsheet_id' => $spreadsheetId], 200);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create spreadsheet: ' . $e->getMessage()], 500);
        }
    }

    public function getSheets(Request $request)
    {
        $userId = $request->user()->id;
        $data = GoogleSpreadsheet::where('user_id', $userId)->get(['spreadsheet_id', 'spreadsheet_name']);
        return response()->json($data);
    }

    public function updateSheet(Request $request)
    {
        $userId = $request->user()->id;
        $client = $this->getClient($userId);
        $spreadsheetId = $request->spreadsheet_id;
        $data = $request->data;
        $choice = $request->choice;

        function replaceNullsWithString($array)
        {
            foreach ($array as &$row) {
                foreach ($row as &$value) {
                    if ($value === null) {
                        $value = "null";
                    }
                }
            }
            return $array;
        }

        $formattedData = [];

        $formattedData[] = ['id', 'title', 'image', 'vendor', 'variants_id', 'variants_title', 'variants_price', 'variants_inventory_id', 'variants_inventory_quantity'];

        foreach ($data as $product) {
            $productId = $product['id'];
            $productTitle = $product['title'];
            $productImage = $product['image'];
            $productVendor = $product['vendor'];

            foreach ($product['variants'] as $variant) {
                $variantId = $variant['id'];
                $variantTitle = $variant['title'];
                $variantPrice = floatval($variant['price']);
                $variantInventoryId = $variant['inventory_id'];
                $variantInventoryQuantity = $variant['inventory_quantity'];

                $formattedData[] = [
                    $productId, $productTitle, $productImage, $productVendor,
                    $variantId, $variantTitle, '$' . $variantPrice, $variantInventoryId, $variantInventoryQuantity
                ];
            }
        }
        $formattedData = replaceNullsWithString($formattedData);
        $values = $formattedData;
        try{
            $sheetsService = new GoogleSheets($client);
            $range = 'Sheet1';
            $body = new GoogleSheets\ValueRange(['values' => $values]);
            if($choice == '0')
                $result = $sheetsService->spreadsheets_values->update($spreadsheetId, $range, $body, ['valueInputOption' => 'RAW']);
            else
                $result = $sheetsService->spreadsheets_values->append($spreadsheetId, $range, $body, ['valueInputOption' => 'RAW']);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update spreadsheet: ' . $e->getMessage()], 500);
        }
    }
}
