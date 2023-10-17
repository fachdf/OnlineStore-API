<?php

namespace app\controllers;
use app\models\Product;
use Yii;
use yii\rest\ActiveController;
use app\models\Order;
use app\models\OrderProduct;
use yii\web\BadRequestHttpException;
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

    public function actionIndex()
    {
        $searchModel = new Order();
        $dataProvider = $searchModel->search($this->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single OrderProduct model.
     * @param int $id ID
     * @return string
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
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
