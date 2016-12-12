<?php

class ControllerModuleApimoduleApimodule extends Controller
{
    /**
     * @api {get} index.php?route=module/apimodule/apimodule/orders  getOrders
     * @apiName GetOrders
     * @apiGroup All
     *
     * @apiParam {Token} token your unique token.
     *
     * @apiSuccess {Number} order_id  ID of the order.
     * @apiSuccess {Number} order_number  Number of the order.
     * @apiSuccess {String} fio     Client's FIO.
     * @apiSuccess {String} status  Status of the order.
     * @apiSuccess {Number} total  Total sum of the order.
     * @apiSuccess {Date} date_added  Date added of the order.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *      "1" : "Array"
     *      {
     *         "order_id" : "1"
     *         "order_number" : "1"
     *         "fio" : "Anton Kiselev"
     *         "status" : "Сделка завершена"
     *         "total" : "106.0000"
     *         "date_added" : "2016-12-09 16:17:02"
     *        }
     *    }
     *
     */
    public function orders()
    {
        header("Access-Control-Allow-Origin: *");
        $error = $this->valid();
        if ($error != null) {
            echo json_encode($error);
            return;
        }

        $this->load->model('module/apimodule/apimodule');
        $orders = $this->model_module_apimodule_apimodule->getOrders();

        foreach ($orders as $order) {
            $data[$order['order_id']]['order_number'] = $order['order_id'];
            $data[$order['order_id']]['order_id'] = $order['order_id'];
            if (isset($order['firstname']) && isset($order['lastname'])) {
                $data[$order['order_id']]['fio'] = $order['firstname'] . ' ' . $order['lastname'];
            } else {
                $data[$order['order_id']]['fio'] = $order['payment_firstname'] . ' ' . $order['payment_lastname'];
            }
            $data[$order['order_id']]['status'] = $order['name'];
            $data[$order['order_id']]['total'] = $order['total'];
            $data[$order['order_id']]['date_added'] = $order['date_added'];


        }
        echo json_encode($data);
        return;

    }

    /**
     * @api {get} index.php?route=module/apimodule/apimodule/getorderinfo  getOrderInfo
     * @apiName getOrderInfo
     * @apiGroup All
     *
     * @apiParam {Number} order_id unique order ID.
     * @apiParam {Token} token your unique token.
     *
     * @apiSuccess {Number} order_number  Number of the order.
     * @apiSuccess {String} fio     Client's FIO.
     * @apiSuccess {String} status  Status of the order.
     * @apiSuccess {String} email  Client's email.
     * @apiSuccess {Number} phone  Client's phone.
     * @apiSuccess {Number} total  Total sum of the order.
     * @apiSuccess {Date} date_added  Date added of the order.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *
     *      {
     *         "order_number" : "1"
     *         "fio" : "Anton Kiselev"
     *         "status" : "Сделка завершена"
     *         "email" : "client@mail.ru"
     *         "phone" : "056 000-11-22"
     *         "total" : "106.0000"
     *         "date_added" : "2016-12-09 16:17:02"
     *        }
     */
    public function getorderinfo()
    {
        header("Access-Control-Allow-Origin: *");

        if (isset($_REQUEST['id']) && $_REQUEST['id'] != '') {
            $id = $_REQUEST['id'];

            $error = $this->valid();
            if ($error != null) {
                echo json_encode($error);
                return;
            }

            $this->load->model('module/apimodule/apimodule');
            $order = $this->model_module_apimodule_apimodule->getOrderById($id);

            if (count($order) > 0) {
                $data['order_number'] = $order[0]['order_id'];

                if (isset($order[0]['firstname']) && isset($order[0]['lastname'])) {
                    $data['fio'] = $order[0]['firstname'] . ' ' . $order[0]['lastname'];
                } else {
                    $data['fio'] = $order[0]['payment_firstname'] . ' ' . $order[0]['payment_lastname'];
                }
                if (isset($order[0]['email'])) {
                    $data['email'] = $order[0]['email'];
                }
                if (isset($order[0]['telephone'])) {
                    $data['telephone'] = $order[0]['telephone'];
                }

                $data['date_added'] = $order[0]['date_added'];

                if (isset($order[0]['total'])) {
                    $data['total'] = $order[0]['total'];
                }
                if (isset($order[0]['name'])) {
                    $data['status'] = $order[0]['name'];
                }
                echo json_encode($data);
            } else {
                echo json_encode('Can not found order with id = ' . $id);
            }
        } else {
            echo json_encode('You have not specified ID');
        }
    }

    /**
     * @api {get} index.php?route=module/apimodule/apimodule/paymentanddelivery  getOrderPaymentAndDelivery
     * @apiName getOrderPaymentAndDelivery
     * @apiGroup All
     *
     * @apiParam {Number} order_id unique order ID.
     * @apiParam {Token} token your unique token.
     *
     * @apiSuccess {String} payment_method     Payment method.
     * @apiSuccess {String} shipping_method  Shipping method.
     * @apiSuccess {String} shipping_address  Shipping address.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *
     *      {
     *         "payment_method" : "Оплата при доставке"
     *         "shipping_method" : "Доставка с фиксированной стоимостью доставки"
     *         "shipping_address" : "проспект Карла Маркса 1, Днепропетровск, Днепропетровская область, Украина."
     *        }
     */
    public function paymentanddelivery()
    {
        header("Access-Control-Allow-Origin: *");

        if (isset($_REQUEST['id']) && $_REQUEST['id'] != '') {
            $id = $_REQUEST['id'];

            $error = $this->valid();
            if ($error != null) {
                echo json_encode($error);
                return;
            }

            $this->load->model('module/apimodule/apimodule');
            $order = $this->model_module_apimodule_apimodule->getOrderById($id);


            if (count($order) > 0) {

                $data['shipping_address'] = '';

                if (isset($order[0]['payment_method'])) {
                    $data['payment_method'] = $order[0]['payment_method'];
                }
                if (isset($order[0]['shipping_method'])) {
                    $data['shipping_method'] = $order[0]['shipping_method'];
                }
                if (isset($order[0]['shipping_address_1']) && $order[0]['shipping_address_1'] != '') {
                    $data['shipping_address'] .= $order[0]['shipping_address_1'];
                }
                if (isset($order[0]['shipping_address_2']) && $order[0]['shipping_address_2'] != '') {
                    $data['shipping_address'] .= ', ' . $order[0]['shipping_address_2'];
                }
                if (isset($order[0]['shipping_city'])) {
                    $data['shipping_address'] .= ', ' . $order[0]['shipping_city'];
                }
                if (isset($order[0]['shipping_country'])) {
                    $data['shipping_address'] .= ', ' . $order[0]['shipping_country'];
                }
                if (isset($order[0]['shipping_zone'])) {
                    $data['shipping_address'] .= ', ' . $order[0]['shipping_zone'];
                }
                echo json_encode($data);

            } else {
                echo json_encode('Can not found order with id = ' . $id);
            }
        } else {
            echo json_encode('You have not specified ID');
        }
    }

    /**
     * @api {get} index.php?route=module/apimodule/apimodule/orderproducts  getOrderProducts
     * @apiName getOrderProducts
     * @apiGroup All
     *
     * @apiParam {Token} token your unique token.
     * @apiParam {ID} id unique order id.
     *
     * @apiSuccess {Url} image  Picture of the product.
     * @apiSuccess {Number} quantity  Quantity of the product.
     * @apiSuccess {String} name     Name of the product.
     * @apiSuccess {String} model  Model of the product.
     * @apiSuccess {Number} Price  Price of the product.
     * @apiSuccess {Number} total_order_price  Total sum of the order.
     * @apiSuccess {Number} total  Sum of product's prices.
     * @apiSuccess {Number} shipping_price  Cost of the shipping.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *      "0" : "Array"
     *      {
     *         "image" : "http://opencart/image/catalog/demo/htc_touch_hd_1.jpg"
     *         "name" : "HTC Touch HD"
     *         "model" : "Product 1"
     *         "quantity" : "3"
     *         "price" : "100.0000"
     *        }
     *      "1" : "Array"
     *      {
     *         "image" : "http://opencart/image/catalog/demo/iphone_1.jpg"
     *         "name" : "iPhone"
     *         "model" : "Product 11"
     *         "quantity" : "1"
     *         "price" : "500.0000"
     *        }
     *       "total_order_price" : "Array"
     *      {
     *         "total" : "800"
     *         "shipping_price" : "50.0000"
     *        }
     *
     *    }
     *
     */

    public function orderproducts()
    {
        header("Access-Control-Allow-Origin: *");

        if (isset($_REQUEST['id']) && $_REQUEST['id'] != '') {
            $id = $_REQUEST['id'];

            $error = $this->valid();
            if ($error != null) {
                echo json_encode($error);
                return;
            }

            $this->load->model('module/apimodule/apimodule');
            $products = $this->model_module_apimodule_apimodule->getOrderProducts($id);


            if (count($products) > 0) {
                $data = array();
                for ($i = 0; $i < count($products); $i++) {

                    if (isset($products[$i]['store_url']) && $products[$i]['image'] && $products[$i]['image'] != '') {
                        $data[$i]['image'] = $products[$i]['store_url'] . 'image/' . $products[$i]['image'];
                    }
                    if (isset($products[$i]['name']) && $products[$i]['name'] != '') {
                        $data[$i]['name'] = $products[$i]['name'];
                    }
                    if (isset($products[$i]['model']) && $products[$i]['model'] != '') {
                        $data[$i]['model'] = $products[$i]['model'];
                    }
                    if (isset($products[$i]['quantity']) && $products[$i]['quantity'] != '') {
                        $data[$i]['quantity'] = $products[$i]['quantity'];
                    }
                    if (isset($products[$i]['price']) && $products[$i]['price'] != '') {
                        $data[$i]['price'] = $products[$i]['price'];
                    }

                    $discount_price = $this->model_module_apimodule_apimodule->getProductDiscount($products[$i]['product_id'], $products[$i]['quantity']);
                    if (isset($discount_price['price']) && $discount_price['price'] != '') {
                        $data[$i]['discount_price'] = $discount_price['price'];
                        $discount = (($products[$i]['price']) - $discount_price['price']) / ($products[$i]['price']);
                        $data[$i]['discount'] = ($discount * 100) . '%';

                    }
                    $a = $products[$i]['price'] * $products[$i]['quantity'];
                    if ($i > 0) {
                        $a = $a + $products[$i]['price'] * $products[$i]['quantity'];
                    }
                    $data['total_order_price'] = array(
                       // 'total' => $a + $products[$i]['value'],
                        'total' => $a,
                        'shipping_price' => $products[$i]['value']
                    );

                }


                // echo json_encode($data);
                print_r($data);
            } else {
                echo json_encode('Can not found order with id = ' . $id);
            }
        } else {
            echo json_encode('You have not specified ID');
        }
    }


    /**
     * @api {get} index.php?route=module/apimodule/status  changeStatus
     * @apiName changeStatus
     * @apiGroup All
     *
     * @apiParam {Number} order_id unique order ID.
     * @apiParam {Number} status_id new status ID.
     * @apiParam {Token} token your unique token.
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
    public
    function status()
    {
        header("Access-Control-Allow-Origin: *");
        $error = $this->valid();
        if ($error != null) {
            echo json_encode($error);
            return;
        }

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
     * @api {get} index.php?route=module/apimodule/apimodule/product  getProduct
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
    public
    function product()
    {
        header("Access-Control-Allow-Origin: *");
        $error = $this->valid();
        if ($error != null) {
            echo json_encode($error);
            return;
        }
        $id = $_REQUEST['product_id'];

        $this->load->model('catalog/product');

        $product = $this->model_catalog_product->getProduct($id);
        echo json_encode($product);

    }

    /**
     * @api {get} index.php?route=module/apimodule/apimodule/products  getProducts
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
    public
    function products()
    {
        header("Access-Control-Allow-Origin: *");
        $error = $this->valid();
        if ($error != null) {
            echo json_encode($error);
            return;
        }
        if ($_REQUEST['page']) {
            $page = ($_REQUEST['page'] - 1) * 5;
        } else {
            $page = 0;
        }
        $this->load->model('module/apimodule/apimodule');

        $products = $this->model_module_apimodule_apimodule->getProducts($page);
        echo json_encode($products);

    }

    /**
     * @api {post} index.php?route=module/apimodule/apimodule/login  Login
     * @apiName Login
     * @apiGroup All
     *
     * @apiParam {String} username User unique username.
     * @apiParam {Number} password User's  password.
     *
     * @apiSuccess {String} token  Token.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *         "token" : "e9cf23a55429aa79c3c1651fe698ed7b"
     *
     *    }
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "Incorrect username or password"
     *     }
     *
     */
    public
    function login()
    {
        header("Access-Control-Allow-Origin: *");
        //$this->session->data['token'] = $token;
        $this->load->model('module/apimodule/apimodule');
        $user = $this->model_module_apimodule_apimodule->Login($this->request->post['username'], $this->request->post['password']);
        //$password = sha1($user['salt'].sha1($user['salt'].htmlspecialchars($this->request->post['password'], ENT_QUOTES)));
        if (!isset($this->request->post['username']) || !isset($this->request->post['password']) || !isset($user['user_id'])) {
            echo 'Incorrect username or password';
            return;
        }
        //  $token = $this->createToken();
        $token = $this->model_module_apimodule_apimodule->getUserToken($user['user_id']);
        if (!isset($token['token'])) {
            $token = token(32);
            $this->model_module_apimodule_apimodule->setUserToken($user['user_id'], $token);
        }
        $token = $this->model_module_apimodule_apimodule->getUserToken($user['user_id']);
        echo json_encode($token);

    }

    private
    function createToken()
    {
        return md5(date("d.m.y") . "apimobile");
    }

    private
    function valid()
    {

        if (!isset($_REQUEST['token']) || $_REQUEST['token'] == '') {
            $error = 'You need to be logged!';
        } else {
            $this->load->model('module/apimodule/apimodule');
            $tokens = $this->model_module_apimodule_apimodule->getTokens();
            if (count($tokens) > 0) {
                foreach ($tokens as $token) {
                    if ($_REQUEST['token'] == $token['token']) {
                        $error = null;
                    } else {
                        $error = 'Your token is no longer relevant!';
                    }
                }
            } else {
                $error = 'You need to be logged!';
            }
        }
        return $error;
    }

}




