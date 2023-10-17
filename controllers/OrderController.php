<?php

namespace app\controllers;
use app\models\Product;
use Yii;
use yii\rest\ActiveController;
use app\models\Order;
use app\models\OrderProduct;
use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
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
        unset($actions['index'], $actions['view'], $actions['create'], $actions['update'], $actions['delete']);
        return $actions;
    }

    public function actionIndex()
    {
        $orders = Order::find()->all();
        
        $ordersWithProducts = [];
        ;
        foreach ($orders as $order) {
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
                $totalOrder = $totalOrder + $quantity*$product->Price;
                // Include product, quantity, and selected fields in the response
                $orderWithProducts['products'][] = [
                    'id' => $product->id,
                    'name' => $product->Name,
                    'price' => $product->Price,
                    'quantity' => $quantity,
                    'subtotal' => $quantity*$product->Price
                ];
            }
            $orderWithProducts['total'] = $totalOrder;
            $ordersWithProducts[] = $orderWithProducts;
        }

        return $ordersWithProducts;
    }


    /**
     * Displays a single OrderProduct model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        $order = Order::findOne($id);

        if ($order === null) {
            throw new NotFoundHttpException("Order not found.");
        }

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
            $totalOrder += $quantity * $product->Price;

            // Include product, quantity, and selected fields in the response
            $orderWithProducts['products'][] = [
                'id' => $product->id,
                'name' => $product->Name,
                'price' => $product->Price,
                'quantity' => $quantity,
                'subtotal' => $quantity * $product->Price
            ];
        }

        $orderWithProducts['total'] = $totalOrder;

        return $orderWithProducts;
    }
    
    public function actionCreate(){
        $order = new Order();

        // Load the order data from the request
        $order->load(Yii::$app->request->getBodyParams(), '');

        if ($order->save()) {
            // Get product IDs and quantities from the request
            $orderData = Yii::$app->request->getBodyParam('products');
            //Yii::error('Request Data: ' . json_encode($orderData), 'app\controllers\OrderController');

            if (!empty($orderData) && is_array($orderData)) {
                $transaction = Yii::$app->db->beginTransaction();
            //    try{
                    foreach ($orderData as $orderItem) {
                        $productId = $orderItem['product_id'];
                        $quantity = $orderItem['quantity'];
    
                        // Check if the product exists
                        $product = Product::findOne($productId);
                        if (!$product){
                            throw new BadRequestHttpException('Product Doesn\'t Exist.');
                        }

                        if ($product->Stock >= $quantity) {
                            $orderProduct = new OrderProduct();
                            $orderProduct->order_id = $order->id;
                            $orderProduct->product_id = $product->id;
                            $orderProduct->quantity = $quantity;
                            if($orderProduct->save()){
                                $product->Stock -= $quantity;
                                $product->save();
                            }else{
                                $transaction->rollBack();
                                throw new ServerErrorHttpException('Failed to create the order.');
                            }
                        }else{
                            $transaction->rollBack();
                            $order->delete();
                            throw new BadRequestHttpException('Ordered Quantity Exceed Stock Capacity.');
    
                        }
                    }
                    $transaction->commit();
                    return $order;
            //    } catch(\Exception $e){
            //        $transaction->rollBack();
            //        throw new ServerErrorHttpException('Failed to create the order.');
            //    }
                
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
