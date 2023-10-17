<?php

namespace app\models;

use yii\db\ActiveRecord;

class Order extends ActiveRecord
{
    public static function tableName()
    {
        return 'order';
    }
    public static function primaryKey()
    {
        return ['id'];
    }
    // Define a relation to products via OrderProduct
    public function getProducts()
    {
        return $this->hasMany(Product::class, ['id' => 'product_id'])
        ->via('orderProducts', function ($query) {
            // Define the relationship through the junction table
            $query->from(['orderProducts' => 'orderproduct']);
            $query->where('orderProducts.order_id = :order_id', [':order_id' => $this->id]);
        });
    }

    // Define a relation to the junction table OrderProduct
    public function getOrderProducts()
    {
        return $this->hasMany(OrderProduct::class, ['order_id' => 'id']);
    }
}
