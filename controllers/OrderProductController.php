<?php

namespace app\controllers;

use Yii;
use app\models\OrderProduct;
use yii\rest\ActiveController;
use yii\web\NotFoundHttpException;

class OrderProductController extends ActiveController
{
    public $modelClass = 'app\models\OrderProduct';

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

        unset($actions['index'], $actions['view']);

        return $actions;
    }

    /**
     * Custom action to list all products.
     */
    public function actionIndex()
    {
        $products = OrderProduct::find()->all();

        return $products;
    }

    /**
     * Custom action to view a OrderProduct by ID.
     *
     * @param int $id
     * @return OrderProduct
     * @throws NotFoundHttpException if the OrderProduct is not found
     */
    public function actionView($id)
    {
        $OrderProduct = OrderProduct::findOne($id);

        if ($OrderProduct === null) {
            throw new NotFoundHttpException("OrderProduct not found.");
        }

        return $OrderProduct;
    }

    public function actionCreate()
    {
        $OrderProduct = new OrderProduct();
        $OrderProduct->load(Yii::$app->request->getBodyParams(), '');
        
        if ($OrderProduct->save()) {
            Yii::$app->response->setStatusCode(201); // Set the response status code to 201 (Created)
            return $OrderProduct;
        } else {
            Yii::$app->response->setStatusCode(422); // Set the response status code to 422 (Unprocessable Entity)
            return ['errors' => $OrderProduct->getErrors()];
        }
    }

}
