<?php

namespace app\controllers;

use app\models\OrderProduct;
use app\models\OrderProductSearch;
use yii\rest\ActiveController;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * OrderProductController implements the CRUD actions for OrderProduct model.
 */
class OrderProductController extends ActiveController
{
    public $modelClass = 'app\models\Product';

    /**
     * Define any custom actions or behaviors here.
     */

    public function actions()
    {
        $actions = parent::actions();

        // Disable the "index" action to prevent listing all products.
        unset($actions['index']);

        return $actions;
    }

    /**
     * Custom action to list all products.
     */
    public function actionIndex()
    {
        $products = Product::find()->all();

        return $products;
    }

    /**
     * Custom action to view a product by ID.
     *
     * @param int $id
     * @return Product
     * @throws NotFoundHttpException if the product is not found
     */
    public function actionView($id)
    {
        $product = Product::findOne($id);

        if ($product === null) {
            throw new NotFoundHttpException("Product not found.");
        }

        return $product;
    }

    public function actionCreate()
    {
        $product = new Product();
        $product->load(Yii::$app->request->getBodyParams(), '');
        
        if ($product->save()) {
            Yii::$app->response->setStatusCode(201); // Set the response status code to 201 (Created)
            return $product;
        } else {
            Yii::$app->response->setStatusCode(422); // Set the response status code to 422 (Unprocessable Entity)
            return ['errors' => $product->getErrors()];
        }
    }
}
