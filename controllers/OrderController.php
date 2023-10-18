<?php

namespace app\controllers;
use app\models\Product;
use Yii;
use yii\rest\ActiveController;
use app\models\Order;
use app\models\OrderProduct;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

/**
 * Order Class to handle an Order (purchase of a Product)
 */
class OrderController extends ActiveController
{
    public $modelClass = 'app\models\Order';

    /**
     * Set behaviour to respond using JSON instead of views
     */
    public function behaviors()
    {
        return [
            [
                'class' => \yii\filters\ContentNegotiator::class,
                'only' => ['index', 'view', 'create', 'update', 'delete'],
                'formats' => [
                    'application/json' => \yii\web\Response::FORMAT_JSON,
                ],
            ],
        ];
    }

    /**
     * Override parent's method
     */
    public function actions()
    {
        $actions = parent::actions();
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }

    /**
     * View All method for Order 
     * Including: Products and its total price (after discount is theres any)
     */
    public function actionIndex()
    {
        // Get all order
        $orders = Order::find()->all();
        $ordersWithProducts = [];

        // Iterate each order to construct a output JSON
        foreach ($orders as $order) {
            // Initial data
            $orderWithProducts = [
                'id' => $order->id,
                'products' => [],
                'total' => 0
            ];
            $totalOrder = 0;

            // Get the products associated with the Order
            $orderProducts = $order->getOrderProducts()->all();
            foreach ($orderProducts as $orderProduct) {
                $product = $orderProduct->product;
                $quantity = $orderProduct->quantity;
                $discount = $orderProduct->discount;
                $subtotal = $quantity*$product->Price*((100-$discount)/100);
                $totalOrder = $totalOrder + $subtotal;
                // Include product, quantity, and selected fields in the response
                $orderWithProducts['products'][] = [
                    'id' => $product->id,
                    'name' => $product->Name,
                    'price' => $product->Price,
                    'quantity' => $quantity,
                    'discount' => $discount . "%",
                    'subtotal' => $subtotal,
                ];
            }
            $orderWithProducts['total'] = $totalOrder;
            $ordersWithProducts[] = $orderWithProducts;
        }

        return $ordersWithProducts;
    }


    /**
     * View One method for Order 
     * Including: Products and its total price (after discount is theres any)
     */
    public function actionView($id)
    {
        // Get order by Id
        $order = Order::findOne($id);

        if ($order === null) {
            throw new NotFoundHttpException("Order not found.");
        }

        // Initial data
        $orderWithProducts = [
            'id' => $order->id,
            'products' => [],
            'total' => 0
        ];
        $totalOrder = 0;

        // Get the products associated with the order
        $orderProducts = $order->getOrderProducts()->all();
        foreach ($orderProducts as $orderProduct) {
            $product = $orderProduct->product;
            $quantity = $orderProduct->quantity;
            $discount = $orderProduct->discount;
            $subtotal = $quantity*$product->Price*((100-$discount)/100);
            $totalOrder = $totalOrder + $subtotal;
            // Include product, quantity, and selected fields in the response
            $orderWithProducts['products'][] = [
                'id' => $product->id,
                'name' => $product->Name,
                'price' => $product->Price,
                'quantity' => $quantity,
                'discount' => $discount . "%",
                'subtotal' => $subtotal,
            ];
        }

        $orderWithProducts['total'] = $totalOrder;
        return $orderWithProducts;
    }
    
    /**
     * Create an order
     * Requirement : Existing Product, Quantity, Discount
     * *Making an order will decrease Product's stock*
     */
    public function actionCreate()
    {
        $order = new Order();

        // Load the order data from the request
        $order->load(Yii::$app->request->getBodyParams(), '');

        if (!$order->save()) {
            throw new ServerErrorHttpException('Failed to create the order.');
        }

        // Get Order Data (Products that are being bought)
        $orderData = Yii::$app->request->getBodyParam('products');

        if (empty($orderData) || !is_array($orderData)) {
            throw new BadRequestHttpException('Invalid or missing product data.');
        }

        // Begin db transaction
        $transaction = Yii::$app->db->beginTransaction();

        try {
            $orderProducts = [];

            // Iterate each product being bought
            foreach ($orderData as $orderItem) {
                // Initialize data from ordered item (input)
                $productId = $orderItem['product_id'];
                $quantity = $orderItem['quantity'];
                $discount = !empty($orderItem['discount']) ? $orderItem['discount'] : 0;

                // Check if the product exist
                $product = Product::findOne($productId);
                if (!$product) {
                    throw new BadRequestHttpException('Product Doesn\'t Exist.');
                }

                // Check if bought quantity is more than available stock
                if ($product->Stock < $quantity) {
                    throw new BadRequestHttpException('Ordered Quantity Exceeds Stock Capacity.');
                }

                // Create conjunction table object to store order data
                $orderProduct = new OrderProduct();
                $orderProduct->order_id = $order->id;
                $orderProduct->product_id = $product->id;
                $orderProduct->quantity = $quantity;
                $orderProduct->discount = $discount;

                if (!$orderProduct->save()) {
                    throw new BadRequestHttpException('Failed to save the order.');
                }

                // Decrease available stock 
                $product->Stock -= $quantity;
                $product->save();

                // Insert ordered product data for return data
                $orderedProducts[] = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'discount' => $discount,
                ];
            }
            // Commit txn to DB
            $transaction->commit();

            // Build response data
            $orderResponse = [
                'id' => $order->id,
                'products' => $orderedProducts,
            ];
            return $orderResponse;

        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e; // Re-throw the exception
        }
    }


public function actionUpdate($id)
{
    $model = Order::findOne($id);

    if ($model === null) {
        Yii::$app->response->setStatusCode(404); // Not Found
        return ['message' => 'Order not found.'];
    }

    $model->load(Yii::$app->getRequest()->getBodyParams(), '');

    if ($model->createOrder()) {
        return $model;
    } elseif ($model->hasErrors()) {
        return $model;
    } else {
        Yii::$app->response->setStatusCode(400); // Bad Request
        return ['message' => 'Failed to update the order.'];
    }
}

public function actionDelete($id)
{
    $model = Order::findOne($id);

    if ($model === null) {
        Yii::$app->response->setStatusCode(404); // Not Found
        return ['message' => 'Order not found.'];
    }

    if ($model->delete()) {
        Yii::$app->response->setStatusCode(204); // No Content
    } else {
        Yii::$app->response->setStatusCode(400); // Bad Request
        return ['message' => 'Failed to delete the order.'];
    }
}

}
