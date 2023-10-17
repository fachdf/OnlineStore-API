<?php

namespace app\controllers;
use app\models\Product;
use Yii;
use yii\rest\ActiveController;
use app\models\Order;
use app\models\OrderProduct;

class OrderController extends ActiveController
{
    public $modelClass = 'app\models\Order';

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

    public function actions()
    {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }
    public function actionIndex()
    {
        $orders = Order::find()->all();
        
        $ordersWithProducts = [];

        foreach ($orders as $order) {
            $products = $order->getProducts()->all();
            $ordersWithProducts[] = [
                'order' => $order,
                'products' => $products,
            ];
        }

        return $ordersWithProducts;
    }
// public function actionCreate()
// {
//     $model = new Order();
//     $params = Yii::$app->getRequest()->getBodyParams();
//     Yii::error(print_r($params, true), __METHOD__);
//     $model->load($params, '');
//     return json_decode(implode(" ", $model), true);
//     Yii::error('Request Data: ' . json_encode($model), 'app\controllers\OrderController');
//     if ($model->createOrder()) {
//         Yii::$app->response->setStatusCode(201); // Created
//         return $model;
//     } elseif ($model->hasErrors()) {
//         Yii::$app->response->setStatusCode(400); // Bad Request
//         return ['message' => 'Failed to create the order.', 'errors' => $model->getErrors()];
//     } else {
//         Yii::$app->response->setStatusCode(400); // Bad Request
//         return ['message' => 'Failed to create the order.'];
//     }
// }

    public function actionCreate(){
    $order = new Order();

        // Load the order data from the request
        $order->load(Yii::$app->request->getBodyParams(), '');

        if ($order->save()) {
            // Get product IDs and quantities from the request
            $orderData = Yii::$app->request->getBodyParam('products');
            Yii::error('Request Data: ' . json_encode($orderData), 'app\controllers\OrderController');
            if (!empty($orderData) && is_array($orderData)) {
                foreach ($orderData as $orderItem) {
                    $productId = $orderItem['product_id'];
                    $quantity = $orderItem['quantity'];

                    // Check if the product exists
                    $product = Product::findOne($productId);
                    if ($product) {
                        // Create a record in the OrderProduct junction table
                        //$order->link('products', $product);
                        // $orderproduct = OrderProduct::find()->where(['order_id', $order->id]);
                        // return $orderproduct->quantity;
                        $orderProduct = new OrderProduct();
                        $orderProduct->order_id = $order->id;
                        $orderProduct->product_id = $product->id;
                        $orderProduct->quantity = $quantity;
                    }else{
                        throw new ServerErrorHttpException('Failed to create the order.');

                    }
                }
            }

            return $order;
        } else {
            throw new ServerErrorHttpException('Failed to create the order.');
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
