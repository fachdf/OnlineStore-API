<p align="center">
    <a href="https://github.com/yiisoft" target="_blank">
        <img src="https://avatars0.githubusercontent.com/u/993323" height="100px">
    </a>
    <h1 align="center">Yii 2 RestfulAPI - Online Store</h1>
    <br>
</p>

This Yii2 RESTful API application is created for the Fomotoko backend assesment test. It provides endpoints to manage orders and products, and it includes additional functionality for processing "Flash Sale" orders.

## Table of Contents

- [Endpoints](#endpoints)
- [API Input Format](#api-input-format)
- [Functionality](#functionality)
- [Running the Application](#running-the-application)
- [Additional Notes](#additional-notes)

## Endpoints

### Order
- **Create Order**: `POST /order` - Create a new order with a list of products and quantities.
- **Get All Order**: `GET /order` - Retrieve details of all existing order by its ID.
- **Get Order By ID**: `GET /order/{id}` - Retrieve details of a specific order by its ID.

### Product
- **Create Product**: `POST /product` - Create a new product with details such as Name, Price, and Stock.
- **Get Products**: `GET /product` - Retrieve a list of all available products.
- **Update Product**: `PUT /product/{id}` - Update the details of a specific product by its ID.

### Custom Function
- **Flash Sale Order Generation**: To process "Flash Sale" orders for specific products and discounts, run the following command:
	```
	php yii generate-orders/generateorders <num_of_order>`
	```


## API Input Format

The API expects data in JSON format. Here are examples of the input format for products and orders:

**Product:**

```json
{
  "Name": "Milk",
  "Price": 10000,
  "Stock": 100
}
```


**Order:**
```json
{
    "products": [
        {
            "product_id": 3,
            "quantity": 5,
            "discount": 5
        }
    ]
}
```
Note that creating an order requires existing Product.

## Functionality
### Flash Sale Order Processing
The application includes a custom function for processing "Flash Sale" orders. The function allows you to create a specified number of Flash Sale orders, each with a random product, quantity, and discount. The generated orders are stored in the database.

#### To generate Flash Sale orders, use the following command:

`php yii generate-orders/generateorders <num_of_order>`

#### Example
```
~: php yii generate-orders/generateorders 15

Banana is on a Flash Sale! 20% off!.
Order no. 79 bought 1 of Banana!
Order no. 80 bought 2 of Banana!
Order no. 81 bought 1 of Banana!
Order no. 82 bought 1 of Banana!
Order no. 83 bought 1 of Banana!
Order no. 84 bought 2 of Banana!
Order no. 85 bought 1 of Banana!
Order no. 86 bought 1 of Banana!
Order failed: Ordered Quantity Exceeds Stock Capacity.
Order failed: Ordered Quantity Exceeds Stock Capacity.
Order failed: Ordered Quantity Exceeds Stock Capacity.
Order failed: Ordered Quantity Exceeds Stock Capacity.
Order failed: Ordered Quantity Exceeds Stock Capacity.
Order failed: Ordered Quantity Exceeds Stock Capacity.
Order failed: Ordered Quantity Exceeds Stock Capacity.
```
Note that the early buyer successfully purchased the flash sale item, but the latest one couldn't because the store ran out of stock.


## Running the Application

1. Clone this repository to your local environment.
2. Configure database connection string in `config/db.php`
Example:
	```
	'dsn' => 'mysql:host=localhost;dbname=OnlineStoreDB',
	```
4. Install the project's dependencies using Composer
   ```shell
   composer install
	```
5. Run migrations to create the necessary database tables and schema:
   ```shell
   php yii migrate
	```
6. Start your Yii2 Application server
   ```shell
   php yii serve
	```
	Your application should now be accessible at `http://localhost:8080`.

## Additional Notes
-   Make sure you have PHP, Yii2, Composer, and MySQL server installed on your system.
-   Test the API using tools like Postman or cURL.
-  Test Flash Sale functionality using CLI command.



