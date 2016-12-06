<?php

class ControllerModuleApimoduleApimodule extends Controller
{
    /**
     * @api {get} index.php?route=module/apimodule  getOrders
     * @apiName GetOrders
     * @apiGroup All
     *
     *
     * @apiSuccess {String} firstname Firstname of the client.
     * @apiSuccess {String} lastname  Lastname of the client.
     * @apiSuccess {Number} order_id  ID of the order.
     * @apiSuccess {Number} store_id  ID of the store.
     * @apiSuccess {String} email     Client's email.
     * @apiSuccess {String} telephone  Client's phone.
     * @apiSuccess {String} payment_company  Company of the User.
     * @apiSuccess {String} payment_address_1  First payment address.
     * @apiSuccess {String} payment_city  Payment city.
     * @apiSuccess {String} payment_method  Payment method.
     * @apiSuccess {String} shipping_method  Shipping method.
     * @apiSuccess {String} total  Total sum of the order.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *      "0" : "Array"
     *      {
     *         "order_id" : "1"
     *         "store_id" : "0"
     *         "firstname" : "Anton"
     *         "lastname" :"Kiselev"
     *         "email" : "anton.kiselev@pinta.com.ua"
     *         "telephone" : "+380985739209"
     *         "payment_firstname" : "Anton"
     *         "payment_lastname" : "Kiselev"
     *         "payment_company" : "Pinta"
     *         "payment_address_1" : "address"
     *         "payment_city" : "dnepropetrovsk"
     *         "payment_method" : "Оплата при доставке"
     *         "shipping_method" : "Доставка с фиксированной стоимостью доставки"
     *         "total" : "106.0000"
     *        }
     *    }
     *
     */
    public function index()
    {
        header("Access-Control-Allow-Origin: *");

        $this->load->model('module/apimodule/apimodule');
        $data['orders'] = $this->model_module_apimodule_apimodule->getOrders();
        echo json_encode($data['orders']);

    }

    /**
     * @api {get} index.php?route=module/apimodule/order  getOrderById
     * @apiName GetOrderById
     * @apiGroup All
     *
     ** @apiParam {Number} id Order unique ID.
     *
     * @apiSuccess {String} firstname Firstname of the client.
     * @apiSuccess {String} lastname  Lastname of the client.
     * @apiSuccess {Number} order_id  ID of the order.
     * @apiSuccess {Number} store_id  ID of the store.
     * @apiSuccess {String} email     Client's email.
     * @apiSuccess {String} telephone  Client's phone.
     * @apiSuccess {String} payment_company  Company of the User.
     * @apiSuccess {String} payment_address_1  First payment address.
     * @apiSuccess {String} payment_city  Payment city.
     * @apiSuccess {String} payment_method  Payment method.
     * @apiSuccess {String} shipping_method  Shipping method.
     * @apiSuccess {String} total  Total sum of the order.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *         "order_id" : "1"
     *         "store_id" : "0"
     *         "firstname" : "Anton"
     *         "lastname" :"Kiselev"
     *         "email" : "anton.kiselev@pinta.com.ua"
     *         "telephone" : "+380985739209"
     *         "payment_firstname" : "Anton"
     *         "payment_lastname" : "Kiselev"
     *         "payment_company" : "Pinta"
     *         "payment_address_1" : "address"
     *         "payment_city" : "dnepropetrovsk"
     *         "payment_method" : "Оплата при доставке"
     *         "shipping_method" : "Доставка с фиксированной стоимостью доставки"
     *         "total" : "106.0000"
     *    }
     *
     */
    public function order()
    {
        $id = $_REQUEST['id'];
        header("Access-Control-Allow-Origin: *");

        $this->load->model('module/apimodule/apimodule');
        $data['order'] = $this->model_module_apimodule_apimodule->getOrderById($id);
        if ($data['order']) {
            echo json_encode($data['order']);
        } else {
            echo json_encode('Order with id(' . $id . ') not found.');
        }
    }

    /**
     * @api {get} index.php?route=module/apimodule/status  changeStatus
     * @apiName changeStatus
     * @apiGroup All
     *
     **@apiParam {Number} order_id unique order ID.
     * @apiParam {Number} status_id new status ID.
     *
     * @apiSuccess {String} status Updated status of the order.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *         "name" : "Сделка завершена"
     *    }
     *
     */
    public function status()
    {
        header("Access-Control-Allow-Origin: *");

        $statusId = $_REQUEST['status_id'];
        $orderID = $_REQUEST['order_id'];
        $this->load->model('module/apimodule/apimodule');
        $data['status'] = $this->model_module_apimodule_apimodule->changeStatus($orderID, $statusId);
        if ($data['status']) {
            echo json_encode($data['status']);
        } else {
            echo json_encode('Can not change status');
        }
    }

    /**
     * @api {get} index.php?route=module/apimodule/product  getProduct
     * @apiName getProduct
     * @apiGroup All
     *
     ** @apiParam {Number} id Product unique ID.
     *
     * @apiSuccess {Number} product_id  ID of the product.
     * @apiSuccess {Number} store_id  ID of the store.
     * @apiSuccess {url}    image     Product image.
     * @apiSuccess {Number} price     Product price.
     * @apiSuccess {Number} quantity  Product quantity.
     * @apiSuccess {String} description  Product description.
     * @apiSuccess {String} name  Product name.
     * @apiSuccess {Number} price  Product  price.
     * @apiSuccess {Number} rating  Product rating.
     * @apiSuccess {String} stock_status  Product status in shop.
     * @apiSuccess {Number} viewed  Count of product views.
     * @apiSuccess {Number} weight  Weight of the  product.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *         "product_id" : "28"
     *         "image" : "catalog/demo/htc_touch_hd_1.jpg"
     *         "price" :"100.0000"
     *         "quantity" : "939"
     *         "description" : "HTC Touch - in High Definition."
     *         "name" : "HTC Touch HD"
     *         "rating" : "5"
     *         "stock_status" : "В наличии"
     *         "viewed" : "350"
     *         "weight" : "133.00000000"
     *    }
     *
     */
    public function product()
    {
        header("Access-Control-Allow-Origin: *");
        $id = $_REQUEST['product_id'];

        $this->load->model('catalog/product');

        $product = $this->model_catalog_product->getProduct($id);
        echo json_encode($product);

    }

    /**
     * @api {get} index.php?route=module/apimodule/products  getProducts
     * @apiName getProducts
     * @apiGroup All
     *
     * @apiParam {Number} page Number of pagination pages.
     *
     * @apiSuccess {Number} product_id  ID of the product.
     * @apiSuccess {Number} store_id  ID of the store.
     * @apiSuccess {url}    image     Product image.
     * @apiSuccess {Number} price     Product price.
     * @apiSuccess {Number} quantity  Product quantity.
     * @apiSuccess {String} description  Product description.
     * @apiSuccess {String} name  Product name.
     * @apiSuccess {Number} price  Product  price.
     * @apiSuccess {Number} rating  Product rating.
     * @apiSuccess {String} stock_status  Product status in shop.
     * @apiSuccess {Number} viewed  Count of product views.
     * @apiSuccess {Number} weight  Weight of the  product.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *      "0" : "Array"
     *      {
     *         "product_id" : "28"
     *         "image" : "catalog/demo/htc_touch_hd_1.jpg"
     *         "price" :"100.0000"
     *         "quantity" : "939"
     *         "description" : "HTC Touch - in High Definition."
     *         "name" : "HTC Touch HD"
     *         "rating" : "5"
     *         "stock_status" : "В наличии"
     *         "viewed" : "350"
     *         "weight" : "210.00000000"
     *        }
     *      "1" : "Array"
     *      {
     *         "product_id" : "30"
     *         "image" : "catalog/demo/palm_treo_pro_1.jpg"
     *         "price" :"150.0000"
     *         "quantity" : "999"
     *         "description" : "HRedefine your workday with the Palm Treo Pro smartphone."
     *         "name" : "Palm Treo Pro"
     *         "rating" : "0"
     *         "stock_status" : "Ожидание 2-3 дня"
     *         "viewed" : "39"
     *         "weight" : "30.00000000"
     *        }
     *    }
     *
     */
    public function products()
    {
        header("Access-Control-Allow-Origin: *");
        if($_REQUEST['page']){
            $page = ($_REQUEST['page'] - 1) * 5;
        }else{
            $page = 0;
        }
        $this->load->model('module/apimodule/apimodule');

        $products = $this->model_module_apimodule_apimodule->getProducts($page);
        echo json_encode($products);

    }
}

