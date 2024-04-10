<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\AuthToken;

class ShopifyController extends Controller
{
    public function shopify_auth(Request $request)
    {
        $code = $request->input('code');
        $url = "https://akshat16pandey.myshopify.com/admin/oauth/access_token";

        $clientId = env('SHOPIFY_API_KEY');
        $clientSecret = env('SHOPIFY_API_SECRET');

        $data = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code
        ];

        $response = Http::post($url, $data);

        $responseData = $response->json();

        if (isset($responseData['access_token'])) {
            $accessToken = $responseData['access_token'];
            $user = $request->user();
            $existingToken = AuthToken::where('user_id', $user->id)->first();

            if ($existingToken) {
                $existingToken->update(['shopify_token' => $accessToken]);
            } else {
                AuthToken::create([
                    'user_id' => $user->id,
                    'shopify_token' => $accessToken,
                ]);
            }

            return response()->json($accessToken);
        } else {
            return response()->json(['error' => 'Failed to obtain access token'], 400);
        }
    }

    public function shopify_products(Request $request)
    {
        $user = $request->user();
        $token = AuthToken::where('user_id', $user->id)->first();
        $accessToken = $token->shopify_token;

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Accept' => 'application/json',
            ])->get('https://akshat16pandey.myshopify.com/admin/api/2024-04/products.json');

            $products = $response->json()['products'];
            $formattedProducts = [];
            foreach ($products as $product) {
                $formattedVariants = [];
                foreach ($product['variants'] as $variant) {
                    $inventoryId = $variant['inventory_item_id'] ?? null;
                    $inventoryQuantity = $variant['inventory_quantity'] ?? 0;

                    $formattedVariant = [
                        'id' => $variant['id'],
                        'title' => $variant['title'],
                        'price' => $variant['price'],
                        'inventory_id' => $inventoryId,
                        'inventory_quantity' => $inventoryQuantity,
                    ];
                    $formattedVariants[] = $formattedVariant;
                }
                $image = $product['images'][0]['src'] ?? null;
                $formattedProduct = [
                    'id' => $product['id'],
                    'title' => $product['title'],
                    'image' => $image,
                    'vendor' => $product['vendor'],
                    'product_type' => $product['product_type'],
                    'variants' => $formattedVariants,
                ];
                $formattedProducts[] = $formattedProduct;
            }

            return response()->json(['products' => $formattedProducts]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch Shopify products'], 500);
        }
    }
}
