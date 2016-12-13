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
     *      "0" : "Array"
     *      {
     *         "order_id" : "1"
     *         "order_number" : "1"
     *         "fio" : "Anton Kiselev"
     *         "status" : "Сделка завершена"
     *         "total" : "106.0000"
     *         "date_added" : "2016-12-09 16:17:02"
     *        }
     *      "1" : "Array"
     *      {
     *         "order_id" : "2"
     *         "order_number" : "2"
     *         "fio" : "Vlad Kochergin"
     *         "status" : "В обработке"
     *         "total" : "506.0000"
     *         "date_added" : "2016-10-19 16:00:00"
     *        }
     *    }
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "Not one order"
     *     }
     *
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
        if (count($orders) > 0) {
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
        } else {
            echo json_encode('Not one order');
        }
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
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "Can not found order with id = 5"
     *     }
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
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "You have not specified ID"
     *     }
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
     * @api {get} index.php?route=module/apimodule/apimodule/orderhistory  getOrderHistory
     * @apiName getOrderHistory
     * @apiGroup All
     *
     * @apiParam {Number} id unique order ID.
     * @apiParam {Token} token your unique token.
     *
     * @apiSuccess {String} name     Status of the order.
     * @apiSuccess {Number} order_status_id  ID of the status of the order.
     * @apiSuccess {Date} date_added  Date of adding status of the order.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *       {
     *          "0" : "Array"
     *              {
     *                  "name" : "Отменено"
     *                  "order_status_id" : "7"
     *                  "date_added" : "2016-12-13 08:27:48."
     *              }
     *          "1" : "Array"
     *              {
     *                  "name" : "Сделка завершена"
     *                  "order_status_id" : "5"
     *                  "date_added" : "2016-12-25 09:30:10."
     *              }
     *          "2" : "Array"
     *              {
     *                  "name" : "Ожидание"
     *                  "order_status_id" : "1"
     *                  "date_added" : "2016-12-01 11:25:18."
     *              }
     *       }
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "Can not found any statuses for order with id = 5"
     *     }
     */

    public function orderhistory()
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
            $statuses = $this->model_module_apimodule_apimodule->getOrderHistory();

            $data = array();

            if (count($statuses) > 0) {
                for ($i = 0; $i < count($statuses); $i++) {

                    $data[$i]['name'] = $statuses[$i]['name'];
                    $data[$i]['order_status_id'] = $statuses[$i]['order_status_id'];
                    $data[$i]['date_added'] = $statuses[$i]['date_added'];

                }

                echo json_encode($data);

            } else {
                echo json_encode('Can not found any statuses for order with id = ' . $id);
            }
        } else {
            echo json_encode('You have not specified ID');
        }
    }

    /**
     * @api {get} index.php?route=module/apimodule/apimodule/orderstatuses  OrderStatusList
     * @apiName OrderStatusList
     * @apiGroup All
     *
     * @apiParam {Token} token your unique token.
     *
     * @apiSuccess {String} name     Status of the order.
     * @apiSuccess {Number} order_status_id  ID of the status of the order.
     * @apiSuccess {Date} language_id  ID of the language of the status name .
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *       {
     *          "0" : "Array"
     *              {
     *                  "name" : "Отменено"
     *                  "order_status_id" : "7"
     *                  "language_id" : "1"
     *              }
     *          "1" : "Array"
     *              {
     *                  "name" : "Сделка завершена"
     *                  "order_status_id" : "5"
     *                  "language_id" : "1"
     *              }
     *          "2" : "Array"
     *              {
     *                  "name" : "Ожидание"
     *                  "order_status_id" : "1"
     *                  "language_id" : "1"
     *              }
     *       }
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "Your token is no longer relevant!"
     *     }
     */
    public function orderstatuses()
    {

        header("Access-Control-Allow-Origin: *");

        $error = $this->valid();
        if ($error != null) {
            echo json_encode($error);
            return;
        }

        $this->load->model('module/apimodule/apimodule');
        $statuses = $this->model_module_apimodule_apimodule->OrderStatusList();


        if (count($statuses) > 0) {
            print_r($statuses);
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
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "Can not found any products in order with id = 10"
     *     }
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
                        $discount_sum = ($products[$i]['price'] - $discount_price['price']) * $products[$i]['quantity'];
                    }else{
                        $discount_sum = 0;
                    }
                    $a = $products[$i]['price'] * $products[$i]['quantity'];

                    if ($i > 0) {
                        $a = $a + $products[$i]['price'] * $products[$i]['quantity'];
                        if(isset($discount_price['price']) && $discount_price['price'] != ''){
                            $total_discount_sum =  $total_discount_sum + ($products[$i]['price'] * $products[$i]['quantity']);
                        }
                    }else{
                        $total_discount_sum = $discount_sum;
                    }
                    $shipping_price = $products[$i]['value'];


                }
                $data['total_order_price'] = array(
                    'total_discount' => $total_discount_sum,
                    'total_price' => $a,
                    'shipping_price' => $shipping_price,
                    'total'=> $a + $shipping_price
                );


                 //echo json_encode($data);
                 print_r($data);

            } else {
                echo json_encode('Can not found any products in order with id = ' . $id);
            }
        } else {
            echo json_encode('You have not specified ID');
        }
    }


    /**
     * @api {get} index.php?route=module/apimodule/apimodule/status  changeStatus
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
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "Missing some params"
     *     }
     *
     */
    public function status()
    {
        header("Access-Control-Allow-Origin: *");
        $error = $this->valid();
        if ($error != null) {
            echo json_encode($error);
            return;
        }
        if (isset($_REQUEST['status_id']) && $_REQUEST['status_id'] != '' && isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {
            $statusId = $_REQUEST['status_id'];
            $orderID = $_REQUEST['order_id'];

            $this->load->model('module/apimodule/apimodule');
            $data['status'] = $this->model_module_apimodule_apimodule->changeStatus($orderID, $statusId);
            if ($data['status']) {
                echo json_encode($data['status']);
            } else {
                echo json_encode('Can not change status');
            }
        } else {
            echo json_encode('Missing some params');
        }
    }

    /**
     * @api {get} index.php?route=module/apimodule/apimodule/setstatus  OrderStatusSet
     * @apiName OrderStatusSet
     * @apiGroup All
     *
     * @apiParam {String} name Name for the new status.
     * @apiParam {Number} language_id unique language ID(default = 1).
     * @apiParam {Token} token your unique token.
     *
     * @apiSuccess {String} status New status of the order.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *         "name" : "Оплачено"
     *    }
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "Can not set status"
     *     }
     *
     */

    public function setstatus()
    {
        header("Access-Control-Allow-Origin: *");
        $error = $this->valid();
        if ($error != null) {
            echo json_encode($error);
            return;
        }
        if (isset($_REQUEST['name']) && $_REQUEST['name'] != '') {
            $name = $_REQUEST['name'];
            if (isset($_REQUEST['language_id']) && $_REQUEST['language_id'] != '') {
                $languageID = $_REQUEST['language_id'];
            } else {
                $languageID = 1;
            }

            $this->load->model('module/apimodule/apimodule');
            $data['status'] = $this->model_module_apimodule_apimodule->OrderStatusSet($name, $languageID);
            if ($data['status']) {
                echo json_encode($data['status']);
            } else {
                echo json_encode('Can not set  status');
            }
        } else {
            echo json_encode('Missing some params');
        }
    }

    /**
     * @api {get} index.php?route=module/apimodule/apimodule/delivery  ChangeOrderDelivery
     * @apiName ChangeOrderDelivery
     * @apiGroup All
     *
     * @apiParam {String} address New shipping address.
     * @apiParam {String} city New shipping city.
     * @apiParam {Number} order_id unique order ID.
     * @apiParam {Token} token your unique token.
     *
     * @apiSuccess {Boolean} response Status of change address.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *         "true"
     *    }
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "Can not change address"
     *     }
     *
     */

    public function delivery()
    {
        header("Access-Control-Allow-Origin: *");
        $error = $this->valid();
        if ($error != null) {
            echo json_encode($error);
            return;
        }

        if (isset($_REQUEST['address']) && $_REQUEST['address'] != '' && isset($_REQUEST['order_id'])) {
            $address = $_REQUEST['address'];
            $order_id = $_REQUEST['order_id'];
            if (isset($_REQUEST['city']) && $_REQUEST['city'] != '') {
                $city = $_REQUEST['city'];
            } else {
                $city = false;
            }

            $this->load->model('module/apimodule/apimodule');
            $data = $this->model_module_apimodule_apimodule->ChangeOrderDelivery($address, $city, $order_id);
            if ($data) {
                echo json_encode($data);
            } else {
                echo json_encode('Can not change address');
            }
        } else {
            echo json_encode('Missing some params');
        }
    }

    /**
     * @api {get} index.php?route=module/apimodule/apimodule/addcomment  AddComment
     * @apiName AddComment
     * @apiGroup All
     *
     * @apiParam {String} comment New comment for order status.
     * @apiParam {Number} order_id unique order ID.
     * @apiParam {Token} token your unique token.
     *
     * @apiSuccess {Boolean} response Status of adding comment.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *         "true"
     *    }
     *
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "Missing some params"
     *     }
     *
     */

    public function addcomment()
    {
        header("Access-Control-Allow-Origin: *");

        $error = $this->valid();
        if ($error != null) {
            echo json_encode($error);
            return;
        }
        if (isset($_REQUEST['comment']) && $_REQUEST['comment'] != '' && isset($_REQUEST['order_id'])) {
            $comment = $_REQUEST['comment'];
            $order_id = $_REQUEST['order_id'];
            $this->load->model('module/apimodule/apimodule');
            $data = $this->model_module_apimodule_apimodule->AddComment($order_id, $comment);
            echo json_encode($data);
        } else {
            echo json_encode('Missing some params');
        }
        return;
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
    public function login()
    {
        header("Access-Control-Allow-Origin: *");

        $this->load->model('module/apimodule/apimodule');
        $user = $this->model_module_apimodule_apimodule->Login($this->request->post['username'], $this->request->post['password']);

        if (!isset($this->request->post['username']) || !isset($this->request->post['password']) || !isset($user['user_id'])) {
            echo 'Incorrect username or password';
            return;
        }

        $token = $this->model_module_apimodule_apimodule->getUserToken($user['user_id']);
        if (!isset($token['token'])) {
            $token = token(32);
            $this->model_module_apimodule_apimodule->setUserToken($user['user_id'], $token);
        }
        $token = $this->model_module_apimodule_apimodule->getUserToken($user['user_id']);
        echo json_encode($token);

    }


    private function valid()
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




