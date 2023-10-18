<?php

namespace app\commands;

use Yii;
use yii\console\Controller;
use app\models\Order;
use app\models\Product;
use app\models\OrderProduct;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

/* 
 *  GenerateOrderCommand Class to handle a race condition during a flash sale, in which
 *  a burst of Orders will be created and all of them will try to buy a specific Product
 *
 *  To execute this command:
 *  php yii generate-orders/generateorders <num_of_order>
 *  
 *  default num_of_order =  10
 */

class GenerateOrdersCommand extends Controller
{

    public function actionGenerateorders($count = 10)
    {
        /**
         * Flash sale need existing Product to be discounted for
         * 1. Find all available Product
         * 2. One of the product will be selected for Flash Sale
         * 3. Discount will be determined (random)
         */
        $availableProducts = Product::find()->all();
        $flashSaleIdx = rand(0, count($availableProducts) - 1);
        $flashSaleProduct = $availableProducts[$flashSaleIdx];
        $discount = rand(10, 20);

        // Info
        echo "$flashSaleProduct->Name is on a Flash Sale! $discount% off!.\n";

        /**
         * Loop for $count amount of order 'race condition'
         * If order success, Info will be echoed
         * If error, Error message will be echoed (ex. Sold out)
         */
        for ($i = 0; $i < $count; $i++) {
            // Define item quantity that will be bought
            $quantity = rand(1, 2);

            // Define data to post (product_id, quantity, discount)
            $templateData = [
                'products' => [
                    [
                        'product_id' => $flashSaleProduct->id,
                        'quantity' => rand(1, 2),
                        'discount' => $discount
                    ]
                ]
            ];
            
            // Encode as JSON 
            $jsonData = json_encode($templateData);
            
            //POST Using CURL
            $postUrl = 'http://localhost:8080/order';
            $response = $this->makeCurlPOSTRequest($postUrl, $jsonData);
            
            // Parse Response Data
            $responseData = json_decode($response, true);
            if ($responseData !== null) {
                // Check if the response contains an "id" field
                if (isset($responseData['id'])) {
                    // It's a valid response with an "id" field
                    $id = $responseData['id'];
                    echo "Order no. $id bought {$responseData['products'][0]['quantity']} of {$flashSaleProduct->Name}!\n";
                } else {
                    // It's a response with an error message
                    echo "Order failed: {$responseData['message']}\n";
                }
            } else {
                // Failed to decode the JSON response, handle as an error
                echo "Failed to decode the JSON response. Error: $response\n";
            }
        }
    }

    function makeCurlPOSTRequest($url, $data) {
        $ch = curl_init(); // Initialize cURL session
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']); // Set content type as JSON
        curl_setopt($ch, CURLOPT_URL, $url); // Set the URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
        curl_setopt($ch, CURLOPT_POST, 1); // Set the request method to POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); // Set the POST data
        $response = curl_exec($ch); // Execute the cURL session
        curl_close($ch); // Close the cURL session
        return $response; // Return the response
    }
}
