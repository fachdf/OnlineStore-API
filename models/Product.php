<?php

namespace app\models;

use yii\db\ActiveRecord;

class Product extends ActiveRecord
{
    public static function tableName()
    {
        return 'product';
    }

    public function rules()
    {
        return [
            [['Name', 'Price', 'Stock'], 'required'],
            [['Name'], 'string', 'max' => 255],
            [['Price', 'Stock'], 'integer', 'min' => 0],
        ];
    }

    // Define a relation to orders via OrderProduct
    public function getOrders()
    {
        return $this->hasMany(Order::class, ['id' => 'order_id'])
        ->via('OrderProduct', function ($query) {
            // Define the relationship through the junction table
            $query->from(['OrderProduct' => 'order_product']);
            $query->where('OrderProduct.product_id = :product_id', [':product_id' => $this->id]);
        });
    }

    public function getOrderProducts()
    {
        return $this->hasMany(OrderProduct::class, ['product_id' => 'id']);
    }
}
