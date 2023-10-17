<?php

use yii\db\Migration;

/**
 * Class m231017_060145_create_tables
 */
class m231017_060145_create_tables extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        // Create the product table
        $this->createTable('product', [
            'id' => $this->primaryKey(),
            'Name' => $this->string()->notNull(),
            'Price' => $this->integer()->notNull(),
            'Stock' => $this->integer()->notNull(),
        ]);

        // Create the order table
        $this->createTable('order', [
            'id' => $this->primaryKey(),
        ]);

        // Create the order_product junction table
        $this->createTable('order_product', [
            'id' => $this->primaryKey(),
            'order_id' => $this->integer()->notNull(),
            'product_id' => $this->integer()->notNull(),
            'quantity' => $this->integer()->notNull(),
        ]);

        // Define foreign key constraints
        $this->addForeignKey('fk-order-product-order', 'order_product', 'order_id', 'order', 'id', 'CASCADE', 'CASCADE');
        $this->addForeignKey('fk-order-product-product', 'order_product', 'product_id', 'product', 'id', 'CASCADE', 'CASCADE');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        // Drop the tables in reverse order
        $this->dropTable('order_product');
        $this->dropTable('order');
        $this->dropTable('product');
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m231017_060145_create_tables cannot be reverted.\n";

        return false;
    }
    */
}
