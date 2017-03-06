<?php

class ControllerModuleApimodule extends Controller
{
    /**
     * @api {get} index.php?route=module/apimodule/orders  getOrders
     * @apiName GetOrders
     * @apiGroup All
     *
     * @apiParam {Token} token your unique token.
     * @apiParam {Number} page number of the page.
     * @apiParam {Number} limit limit of the orders for the page.
     * @apiParam {Array} filter array of the filter params.
     * @apiParam {String} filter[fio] full name of the client.
     * @apiParam {Number} filter[order_status_id] unique id of the order.
     * @apiParam {Number} filter[min_price] min price of order.
     * @apiParam {Number} filter[max_price] max price of order.
     * @apiParam {Date} filter[date_min] min date adding of the order.
     * @apiParam {Date} filter[date_max] max date adding of the order.
     *
     * @apiSuccess {Array} orders  Array of the orders.
     * @apiSuccess {Array} statuses  Array of the order statuses.
     * @apiSuccess {Number} order_id  ID of the order.
     * @apiSuccess {Number} order_number  Number of the order.
     * @apiSuccess {String} fio     Client's FIO.
     * @apiSuccess {String} status  Status of the order.
     * @apiSuccess {currency_code} status  Default currency of the shop.
     * @apiSuccess {Number} total  Total sum of the order.
     * @apiSuccess {Date} date_added  Date added of the order.
     * @apiSuccess {Date} total_quantity  Total quantity of the orders.
     *
     *
     *
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *   "Response"
     *   {
     *      "orders":
     *      {
     *            {
     *             "order_id" : "1",
     *             "order_number" : "1",
     *             "fio" : "Anton Kiselev",
     *             "status" : "Сделка завершена",
     *             "total" : "106.00",
     *             "date_added" : "2016-12-09 16:17:02",
     *             "currency_code": "RUB"
     *             },
     *            {
     *             "order_id" : "2",
     *             "order_number" : "2",
     *             "fio" : "Vlad Kochergin",
     *             "status" : "В обработке",
     *             "total" : "506.00",
     *             "date_added" : "2016-10-19 16:00:00",
     *             "currency_code": "RUB"
     *             }
     *       },
     *       "statuses" :
     *       {
     *             {
     *              "name": "Отменено",
     *              "order_status_id": "7",
     *              "language_id": "1"
     *              },
     *             {
     *              "name": "Сделка завершена",
     *              "order_status_id": "5",
     *              "language_id": "1"
     *              },
     *              {
     *               "name": "Ожидание",
     *               "order_status_id": "1",
     *               "language_id": "1"
     *               }
     *       },
     *       "currency_code": "RUB",
     *       "total_quantity": 50,
     *       "total_sum": "2026.00",
     *       "max_price": "1405.00"
     *   },
     *   "Status" : true
     * }
     * @apiErrorExample Error-Response:
     * {
     *      "Error" : "Not one order",
     *      "Status" : false
     * }
     *
     *
     */
    public function orders()
    {

        header("Access-Control-Allow-Origin: *");
        $this->response->addHeader('Content-Type: application/json');


        $error = $this->valid();
        if ($error != null) {

            $this->response->setOutput(json_encode(['error' => $error, 'status' => false]));
            return;
        }

        if (isset($_REQUEST['page']) && (int)$_REQUEST['page'] != 0 && isset($_REQUEST['limit']) && (int)$_REQUEST['limit'] != 0) {
            $page = ($_REQUEST['page'] - 1) * $_REQUEST['limit'];
            $limit = $_REQUEST['limit'];
        } else {
            $page = 0;
            $limit = 9999;
        }

        $this->load->model('module/apimodule');

        if (isset($_REQUEST['filter'])) {

            $orders = $this->model_module_apimodule->getOrders(array('filter' => $_REQUEST['filter'], 'page' => $page, 'limit' => $limit));
        } elseif (isset($_REQUEST['platform']) && $_REQUEST['platform'] == 'android') {
            $filter = [];
            if (isset($_REQUEST['order_status_id'])) {
                $filter['order_status_id'] = $_REQUEST['order_status_id'];
            }
            if (isset($_REQUEST['fio'])) {
                $filter['fio'] = $_REQUEST['fio'];
            }
            if (isset($_REQUEST['min_price']) && $_REQUEST['min_price'] != 0) {
                $filter['min_price'] = $_REQUEST['min_price'];
            } else {
                $filter['min_price'] = 1;
            }
            if (isset($_REQUEST['max_price'])) {
                $filter['max_price'] = $_REQUEST['max_price'];
            } else {
                $filter['max_price'] = $this->model_module_apimodule->getMaxOrderPrice();
            }


            $filter['date_min'] = $_REQUEST['date_min'];
            $filter['date_max'] = $_REQUEST['date_max'];

            $orders = $this->model_module_apimodule->getOrders(array('filter' => $filter, 'page' => $page, 'limit' => $limit));

        } else {
            $orders = $this->model_module_apimodule->getOrders(array('page' => $page, 'limit' => $limit));
        }
        $response = [];
        $orders_to_response = [];

        foreach ($orders->rows as $order) {

            $data['order_number'] = $order['order_id'];
            $data['order_id'] = $order['order_id'];
            if (isset($order['firstname']) && isset($order['lastname'])) {
                $data['fio'] = $order['firstname'] . ' ' . $order['lastname'];
            } else {
                $data['fio'] = $order['payment_firstname'] . ' ' . $order['payment_lastname'];
            }
            $data['status'] = $order['name'];

            $data['total'] = number_format($order['total'], 2, '.', '');
            $data['date_added'] = $order['date_added'];
            $data['currency_code'] = $order['currency_code'];
            $orders_to_response[] = $data;

        }

        $response['total_quantity'] = $orders->quantity;
        $response['currency_code'] = $this->model_module_apimodule->getDefaultCurrency();
        $response['total_sum'] = number_format($orders->totalsumm, 2, '.', '');
        $response['orders'] = $orders_to_response;
        $response['max_price'] = $this->model_module_apimodule->getMaxOrderPrice();
        $statuses = $this->model_module_apimodule->OrderStatusList();
        $response['statuses'] = $statuses;

        $this->response->setOutput(json_encode(['response' => $response, 'status' => true]));
        return;
    }

    /**
     * @api {get} index.php?route=module/apimodule/getorderinfo  getOrderInfo
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
     * @apiSuccess {Array} statuses  Statuses list for order.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *      "response" :
     *          {
     *              "order_number" : "6",
     *              "fio" : "Anton Kiselev",
     *              "email" : "client@mail.ru",
     *              "telephone" : "056 000-11-22",
     *              "date_added" : "2016-12-24 12:30:46",
     *              "total" : "1405.00",
     *              "status" : "Сделка завершена",
     *              "statuses" :
     *                  {
     *                         {
     *                             "name": "Отменено",
     *                             "order_status_id": "7",
     *                             "language_id": "1"
     *                         },
     *                         {
     *                             "name": "Сделка завершена",
     *                             "order_status_id": "5",
     *                             "language_id": "1"
     *                          },
     *                          {
     *                              "name": "Ожидание",
     *                              "order_status_id": "1",
     *                              "language_id": "1"
     *                           }
     *                    }
     *          },
     *      "status" : true
     * }
     *
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "error" : "Can not found order with id = 5",
     *       "Status" : false
     *     }
     */
    public function getorderinfo()
    {
        header("Access-Control-Allow-Origin: *");
        $this->response->addHeader('Content-Type: application/json');
        if (isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {
            $id = $_REQUEST['order_id'];

            $error = $this->valid();
            if ($error != null) {

                $this->response->setOutput(json_encode(['error' => $error, 'status' => false]));
                return;
            }

            $this->load->model('module/apimodule');
            $order = $this->model_module_apimodule->getOrderById($id);

            if (count($order) > 0) {
                $data['order_number'] = $order[0]['order_id'];

                if (isset($order[0]['firstname']) && isset($order[0]['lastname'])) {
                    $data['fio'] = $order[0]['firstname'] . ' ' . $order[0]['lastname'];
                } else {
                    $data['fio'] = $order[0]['payment_firstname'] . ' ' . $order[0]['payment_lastname'];
                }
                if (isset($order[0]['email'])) {
                    $data['email'] = $order[0]['email'];
                } else {
                    $data['email'] = '';
                }
                if (isset($order[0]['telephone'])) {
                    $data['telephone'] = $order[0]['telephone'];
                } else {
                    $data['telephone'] = '';
                }

                $data['date_added'] = $order[0]['date_added'];

                if (isset($order[0]['total'])) {
                    $data['total'] = number_format($order[0]['total'], 2, '.', '');;
                }
                if (isset($order[0]['name'])) {
                    $data['status'] = $order[0]['name'];
                } else {
                    $data['status'] = '';
                }
                $statuses = $this->model_module_apimodule->OrderStatusList();
                $data['statuses'] = $statuses;

                $this->response->setOutput(json_encode(['response' => $data, 'status' => true]));

            } else {

                $this->response->setOutput(json_encode(['error' => 'Can not found order with id = ' . $id, 'status' => false]));
            }
        } else {

            $this->response->setOutput(json_encode(['error' => 'You have not specified ID', 'status' => false]));
        }
    }

    /**
     * @api {get} index.php?route=module/apimodule/paymentanddelivery  getOrderPaymentAndDelivery
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
     *          "response":
     *              {
     *                  "payment_method" : "Оплата при доставке",
     *                  "shipping_method" : "Доставка с фиксированной стоимостью доставки",
     *                  "shipping_address" : "проспект Карла Маркса 1, Днепропетровск, Днепропетровская область, Украина."
     *              },
     *          "status": true
     *      }
     * @apiErrorExample Error-Response:
     *
     *    {
     *      "error": "Can not found order with id = 90",
     *      "status": false
     *   }
     *
     */

    public function paymentanddelivery()
    {
        header("Access-Control-Allow-Origin: *");
        $this->response->addHeader('Content-Type: application/json');
        if (isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {
            $id = $_REQUEST['order_id'];

            $error = $this->valid();
            if ($error != null) {

                $this->response->setOutput(json_encode(['error' => $error, 'status' => false]));
                return;
            }

            $this->load->model('module/apimodule');
            $order = $this->model_module_apimodule->getOrderById($id);


            if (count($order) > 0) {

                $data['shipping_address'] = '';

                if (isset($order[0]['payment_method']) && $order[0]['payment_method'] != '') {
                    $data['payment_method'] = $order[0]['payment_method'];
                }
                if (isset($order[0]['shipping_method']) && $order[0]['shipping_method'] != '') {
                    $data['shipping_method'] = $order[0]['shipping_method'];
                }
                if (isset($order[0]['shipping_address_1']) && $order[0]['shipping_address_1'] != '') {
                    $data['shipping_address'] .= $order[0]['shipping_address_1'];
                }
                if (isset($order[0]['shipping_address_2']) && $order[0]['shipping_address_2'] != '') {
                    $data['shipping_address'] .= ', ' . $order[0]['shipping_address_2'];
                }
                if (isset($order[0]['shipping_city']) && $order[0]['shipping_city'] != '') {
                    $data['shipping_address'] .= ', ' . $order[0]['shipping_city'];
                }
                if (isset($order[0]['shipping_country']) && $order[0]['shipping_country'] != '') {
                    $data['shipping_address'] .= ', ' . $order[0]['shipping_country'];
                }
                if (isset($order[0]['shipping_zone']) && $order[0]['shipping_zone'] != '') {
                    $data['shipping_address'] .= ', ' . $order[0]['shipping_zone'];
                }

                $this->response->setOutput(json_encode(['response' => $data, 'status' => true]));


            } else {

                $this->response->setOutput(json_encode(['error' => 'Can not found order with id = ' . $id, 'status' => false]));
            }
        } else {

            $this->response->setOutput(json_encode(['error' => 'You have not specified ID', 'status' => false]));
        }
    }

    /**
     * @api {get} index.php?route=module/apimodule/orderhistory  getOrderHistory
     * @apiName getOrderHistory
     * @apiGroup All
     *
     * @apiParam {Number} order_id unique order ID.
     * @apiParam {Token} token your unique token.
     *
     * @apiSuccess {String} name     Status of the order.
     * @apiSuccess {Number} order_status_id  ID of the status of the order.
     * @apiSuccess {Date} date_added  Date of adding status of the order.
     * @apiSuccess {String} comment  Some comment added from manager.
     * @apiSuccess {Array} statuses  Statuses list for order.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *       {
     *           "response":
     *               {
     *                   "orders":
     *                      {
     *                          {
     *                              "name": "Отменено",
     *                              "order_status_id": "7",
     *                              "date_added": "2016-12-13 08:27:48.",
     *                              "comment": "Some text"
     *                          },
     *                          {
     *                              "name": "Сделка завершена",
     *                              "order_status_id": "5",
     *                              "date_added": "2016-12-25 09:30:10.",
     *                              "comment": "Some text"
     *                          },
     *                          {
     *                              "name": "Ожидание",
     *                              "order_status_id": "1",
     *                              "date_added": "2016-12-01 11:25:18.",
     *                              "comment": "Some text"
     *                           }
     *                       },
     *                    "statuses":
     *                        {
     *                             {
     *                                  "name": "Отменено",
     *                                  "order_status_id": "7",
     *                                  "language_id": "1"
     *                             },
     *                             {
     *                                  "name": "Сделка завершена",
     *                                  "order_status_id": "5",
     *                                  "language_id": "1"
     *                              },
     *                              {
     *                                  "name": "Ожидание",
     *                                  "order_status_id": "1",
     *                                  "language_id": "1"
     *                              }
     *                         }
     *               },
     *           "status": true
     *       }
     * @apiErrorExample Error-Response:
     *
     *     {
     *          "status": false,
     *          "error": "Can not found any statuses for order with id = 5"
     *     }
     */

    public function orderhistory()
    {

        header("Access-Control-Allow-Origin: *");
        $this->response->addHeader('Content-Type: application/json');
        if (isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {
            $id = $_REQUEST['order_id'];

            $error = $this->valid();
            if ($error != null) {

                $this->response->setOutput(json_encode(['error' => $error, 'status' => false]));
                return;
            }

            $this->load->model('module/apimodule');
            $statuses = $this->model_module_apimodule->getOrderHistory($id);

            $data = array();
            $response = [];
            if (count($statuses) > 0) {

                for ($i = 0; $i < count($statuses); $i++) {

                    $data['name'] = $statuses[$i]['name'];
                    $data['order_status_id'] = $statuses[$i]['order_status_id'];
                    $data['date_added'] = $statuses[$i]['date_added'];
                    $data['comment'] = $statuses[$i]['comment'];
                    $response['orders'][] = $data;
                }

                $statuses = $this->model_module_apimodule->OrderStatusList();
                $response['statuses'] = $statuses;

                $this->response->setOutput(json_encode(['response' => $response, 'status' => true]));

            } else {

                $this->response->setOutput(json_encode(['error' => 'Can not found any statuses for order with id = ' . $id, 'status' => false]));
            }
        } else {

            $this->response->setOutput(json_encode(['error' => 'You have not specified ID', 'status' => false]));
        }
    }


    /**
     * @api {get} index.php?route=module/apimodule/orderproducts  getOrderProducts
     * @apiName getOrderProducts
     * @apiGroup All
     *
     * @apiParam {Token} token your unique token.
     * @apiParam {ID} order_id unique order id.
     *
     * @apiSuccess {Url} image  Picture of the product.
     * @apiSuccess {Number} quantity  Quantity of the product.
     * @apiSuccess {String} name     Name of the product.
     * @apiSuccess {String} model  Model of the product.
     * @apiSuccess {Number} Price  Price of the product.
     * @apiSuccess {Number} total_order_price  Total sum of the order.
     * @apiSuccess {Number} total_price  Sum of product's prices.
     * @apiSuccess {Number} shipping_price  Cost of the shipping.
     * @apiSuccess {Number} total  Total order sum.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *      "response":
     *          {
     *              "products": [
     *              {
     *                  "image" : "http://opencart/image/catalog/demo/htc_touch_hd_1.jpg",
     *                  "name" : "HTC Touch HD",
     *                  "model" : "Product 1",
     *                  "quantity" : 3,
     *                  "price" : 100.00
     *              },
     *              {
     *                  "image" : "http://opencart/image/catalog/demo/iphone_1.jpg",
     *                  "name" : "iPhone",
     *                  "model" : "Product 11",
     *                  "quantity" : 1,
     *                  "price" : 500.00
     *               }
     *            ],
     *            "total_order_price":
     *              {
     *                   "total_discount": 0,
     *                   "total_price": 2250,
     *                   "shipping_price": 35,
     *                   "total": 2285
     *               }
     *
     *         },
     *      "status": true
     * }
     *
     *
     * @apiErrorExample Error-Response:
     *
     *     {
     *          "status": false,
     *          "error": "Can not found any products in order with id = 10"
     *     }
     *
     */

    public function orderproducts()
    {
        header("Access-Control-Allow-Origin: *");
        $this->response->addHeader('Content-Type: application/json');
        if (isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {
            $id = $_REQUEST['order_id'];

            $error = $this->valid();
            if ($error != null) {

                $this->response->setOutput(json_encode(['error' => $error, 'status' => false]));
                return;
            }

            $this->load->model('module/apimodule');
            $products = $this->model_module_apimodule->getOrderProducts($id);


            if (count($products) > 0) {
                $data = array();
                $total_discount_sum = 0;
                $a = 0;
                $this->load->model('tool/image');
                for ($i = 0; $i < count($products); $i++) {

                    if ($products[$i]['image'] && $products[$i]['image'] != '') {
                        $product['image'] = $this->model_tool_image->resize($products[$i]['image'], 200, 200);;
                    }
                    if (isset($products[$i]['name']) && $products[$i]['name'] != '') {
                        $product['name'] = strip_tags(htmlspecialchars_decode($products[$i]['name']));
                    }
                    if (isset($products[$i]['model']) && $products[$i]['model'] != '') {
                        $product['model'] = $products[$i]['model'];
                    }
                    if (isset($products[$i]['quantity']) && $products[$i]['quantity'] != '') {
                        $product['quantity'] = number_format($products[$i]['quantity'], 2, '.', '');
                    }
                    if (isset($products[$i]['price']) && $products[$i]['price'] != '') {
                        $product['price'] = number_format($products[$i]['price'], 2, '.', '');
                    }
                    $product['product_id'] = $products[$i]['product_id'];

                    $discount_price = $this->model_module_apimodule->getProductDiscount($products[$i]['product_id'], $products[$i]['quantity']);

                    if (isset($discount_price['price']) && $discount_price['price'] != '') {
                        $product['discount_price'] = $discount_price['price'];
                        $discount = (($products[$i]['price']) - $discount_price['price']) / ($products[$i]['price']);
                        $product['discount'] = ($discount * 100) . '%';
                        $discount_sum = ($products[$i]['price'] - $discount_price['price']) * $products[$i]['quantity'];
                    } else {
                        $discount_sum = 0;
                    }


                    if ($i > 0) {
                        $a = $a + ($products[$i]['price'] * $products[$i]['quantity']);
                        if (isset($discount_price['price']) && $discount_price['price'] != '') {
                            $total_discount_sum = $total_discount_sum + ($products[$i]['price'] * $products[$i]['quantity']);
                        }
                    } else {
                        $a = $products[$i]['price'] * $products[$i]['quantity'];
                        $total_discount_sum = $discount_sum;
                    }
                    $shipping_price = $products[$i]['value'];

                    $data['products'][] = $product;
                }

                $data['total_order_price'] = array(
                    'total_discount' => $total_discount_sum,
                    'total_price' => $a,
                    'shipping_price' => +number_format($shipping_price, 2, '.', ''),
                    'total' => $a + $shipping_price
                );


                $this->response->addHeader('Content-Type: application/json; charset=utf-8');
                $this->response->setOutput(json_encode(['response' => $data, 'status' => true]));

            } else {

                $this->response->setOutput(json_encode(['error' => 'Can not found any products in order with id = ' . $id, 'status' => false]));

            }
        } else {

            $this->response->setOutput(json_encode(['error' => 'You have not specified ID', 'status' => false]));

        }
    }


    /**
     * @api {get} index.php?route=module/apimodule/delivery  ChangeOrderDelivery
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
     *         "status": true
     *    }
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "error": "Can not change address",
     *       "status": false
     *     }
     *
     */

    public function delivery()
    {
        header("Access-Control-Allow-Origin: *");
        $this->response->addHeader('Content-Type: application/json');
        $error = $this->valid();
        if ($error != null) {

            $this->response->setOutput(json_encode(['error' => $error, 'status' => false]));
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

            $this->load->model('module/apimodule');
            $data = $this->model_module_apimodule->ChangeOrderDelivery($address, $city, $order_id);
            if ($data) {

                $this->response->setOutput(json_encode(['status' => true]));

            } else {

                $this->response->setOutput(json_encode(['error' => 'Can not change address', 'status' => false]));

            }
        } else {

            $this->response->setOutput(json_encode(['error' => 'Missing some params', 'status' => false]));

        }
    }

    /**
     * @api {get} index.php?route=module/apimodule/changestatus  ChangeStatus
     * @apiName ChangeStatus
     * @apiGroup All
     *
     * @apiParam {String} comment New comment for order status.
     * @apiParam {Number} order_id unique order ID.
     * @apiParam {Number} status_id unique status ID.
     * @apiParam {Token} token your unique token.
     * @apiParam {Boolean} inform status of the informing client.
     *
     * @apiSuccess {String} name Name of the new status.
     * @apiSuccess {String} date_added Date of adding status.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *          "response":
     *              {
     *                  "name" : "Сделка завершена",
     *                  "date_added" : "2016-12-27 12:01:51"
     *              },
     *          "status": true
     *   }
     *
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "error" : "Missing some params",
     *       "status" : false
     *     }
     *
     */

    public function changestatus()
    {
        header("Access-Control-Allow-Origin: *");
        $this->response->addHeader('Content-Type: application/json');
        $error = $this->valid();
        if ($error != null) {

            $this->response->setOutput(json_encode(['error' => $error, 'status' => false]));
            return;
        }

        if (isset($_REQUEST['comment']) && isset($_REQUEST['status_id']) && $_REQUEST['status_id'] != '' && isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {
            $statusID = $_REQUEST['status_id'];
            $orderID = $_REQUEST['order_id'];
            $comment = $_REQUEST['comment'];
            $this->load->model('module/apimodule');
            $data = $this->model_module_apimodule->AddComment($orderID, $statusID, $comment);

            $this->response->setOutput(json_encode(['response' => $data, 'status' => true]));
        } else {

            $this->response->setOutput(json_encode(['error' => 'Missing some params', 'status' => false]));

        }
        return;
    }


    /**
     * @api {post} index.php?route=module/apimodule/login  Login
     * @apiName Login
     * @apiGroup All
     *
     * @apiParam {String} username User unique username.
     * @apiParam {Number} password User's  password.
     * @apiParam {String} device_token User's device's token for firebase notifications.
     *
     * @apiSuccess {String} token  Token.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *       "response":
     *       {
     *          "token": "e9cf23a55429aa79c3c1651fe698ed7b"
     *       }
     *   }
     *
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "error": "Incorrect username or password"
     *     }
     *
     */
    public function login()
    {
        header("Access-Control-Allow-Origin: *");
        $this->response->addHeader('Content-Type: application/json');

        $this->load->model('module/apimodule');
        $user = $this->model_module_apimodule->checkLogin($_REQUEST['username'], $_REQUEST['password']);

        if (!isset($this->request->post['username']) || !isset($this->request->post['password']) || !isset($user['user_id'])) {


            $this->response->setOutput(json_encode(['error' => 'Incorrect username or password', 'status' => false]));
            return;
        }

        if (isset($_REQUEST['device_token']) && $_REQUEST['device_token'] != '') {
            $devices = $this->model_module_apimodule->getUserDevices($user['user_id']);
            $matches = 0;
            foreach ($devices as $device) {
                if ($_REQUEST['device_token'] == $device['device_token']) {
                    $matches++;
                }
            }
            if ($matches == 0) {
                $this->model_module_apimodule->setUserDeviceToken($user['user_id'], $_REQUEST['device_token']);
            }
        }


        $token = $this->model_module_apimodule->getUserToken($user['user_id']);
        if (!isset($token['token'])) {
            $token = md5(mt_rand());
            $this->model_module_apimodule->setUserToken($user['user_id'], $token);
        }
        $token = $this->model_module_apimodule->getUserToken($user['user_id']);

        $this->response->setOutput(json_encode(['response' => ['token' => $token['token']], 'status' => true]));


    }
    /**
     * @api {post} index.php?route=module/apimodule/deletedevicetoken  deleteUserDeviceToken
     * @apiName deleteUserDeviceToken
     * @apiGroup All
     *
     * @apiParam {String} old_token User's device's token for firebase notifications.
     *
     * @apiSuccess {Boolean} status  true.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *       "response":
     *       {
     *          "status": true
     *       }
     *   }
     *
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "error": "Missing some params",
     *       "status": false
     *     }
     *
     */
    public function deletedevicetoken(){
        header("Access-Control-Allow-Origin: *");
        $this->response->addHeader('Content-Type: application/json');
        if (isset($_REQUEST['old_token'])) {
            $deleted = $this->model_module_apimodule->deleteUserDeviceToken($_REQUEST['old_token']);
            if(count($deleted) == 0){
                $this->response->setOutput(json_encode(['status' => true]));
            }else{
                $this->response->setOutput(json_encode(['error' => 'Can not find your token', 'status' => false]));
            }
        }else{
            $this->response->setOutput(json_encode(['error' => 'Missing some params', 'status' => false]));
        }
    }

    /**
     * @api {post} index.php?route=module/apimodule/updatedevicetoken  updateUserDeviceToken
     * @apiName updateUserDeviceToken
     * @apiGroup All
     *
     * @apiParam {String} new_token User's device's new token for firebase notifications.
     * @apiParam {String} old_token User's device's old token for firebase notifications.
     *
     * @apiSuccess {Boolean} status  true.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *       "response":
     *       {
     *          "status": true
     *       }
     *   }
     *
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "error": "Missing some params",
     *       "status": false
     *     }
     *
     */
    public function updatedevicetoken(){
        header("Access-Control-Allow-Origin: *");
        $this->response->addHeader('Content-Type: application/json');
        if (isset($_REQUEST['old_token']) && isset($_REQUEST['new_token'])) {
            $updated = $this->model_module_apimodule->updateUserDeviceToken($_REQUEST['old_token'], $_REQUEST['new_token']);
            if(count($updated) != 0){
                $this->response->setOutput(json_encode(['status' => true]));
            }else{
                $this->response->setOutput(json_encode(['error' => 'Can not find your token', 'status' => false]));
            }
        }else{
            $this->response->setOutput(json_encode(['error' => 'Missing some params', 'status' => false]));
        }
    }

    public function sendNotifications()
    {
        header("Access-Control-Allow-Origin: *");
        $registrationIds = array();
        $this->load->model('module/apimodule');
        $devices = $this->model_module_apimodule->getUserDevices();
        foreach($devices as $device){
            $registrationIds[] = $device['device_token'];
        }
        define('API_ACCESS_KEY', 'AAAAlhKCZ7w:APA91bFe6-ynbVuP4ll3XBkdjar_qlW5uSwkT5olDc02HlcsEzCyGCIfqxS9JMPj7QeKPxHXAtgjTY89Pv1vlu7sgtNSWzAFdStA22Ph5uRKIjSLs5z98Y-Z2TCBN3gl2RLPDURtcepk');
        //  $registrationIds = array("eltSyRt4SqY:APA91bGuUbO6GsORjt6SED5KzI7uLjW7B4KNWiuXCaNy6NYjWo0TLlwbzySUOldQ012eHeKvkAQW7TwyBwHCj_PsRmAzNQouQb-_Rap2R9GcXxyeUvc4w7KDYuCCXoiXfAoNPhJdv3vl");
// prep the bundle
        $msg = array
        (
            'body' => 'test1',
            'title' => 'test2',
            'vibrate' => 1,
            'sound' => 1,
        );
        $fields = array
        (
            'registration_ids' => $registrationIds,

            'data' => $msg
        );

        $headers = array
        (
            'Authorization: key=' . API_ACCESS_KEY,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        curl_close($ch);
        //echo $result;
    }


    /**
     * @api {get} index.php?route=module/apimodule/statistic  getDashboardStatistic
     * @apiName getDashboardStatistic
     * @apiGroup All
     *
     * @apiParam {String} filter Period for filter(day/week/month/year).
     * @apiParam {Token} token your unique token.
     *
     * @apiSuccess {Array} xAxis Period of the selected filter.
     * @apiSuccess {Array} Clients Clients for the selected period.
     * @apiSuccess {Array} Orders Orders for the selected period.
     * @apiSuccess {String} currency_code  Default currency of the shop.
     * @apiSuccess {Number} total_sales  Sum of sales of the shop.
     * @apiSuccess {Number} sale_year_total  Sum of sales of the current year.
     * @apiSuccess {Number} orders_total  Total orders of the shop.
     * @apiSuccess {Number} clients_total  Total clients of the shop.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     *   {
     *           "response": {
     *               "xAxis": [
     *                  1,
     *                  2,
     *                  3,
     *                  4,
     *                  5,
     *                  6,
     *                  7
     *              ],
     *              "clients": [
     *                  0,
     *                  0,
     *                  0,
     *                  0,
     *                  0,
     *                  0,
     *                  0
     *              ],
     *              "orders": [
     *                  1,
     *                  0,
     *                  0,
     *                  0,
     *                  0,
     *                  0,
     *                  0
     *              ],
     *              "total_sales": "1920.00",
     *              "sale_year_total": "305.00",
     *              "currency_code": "UAH",
     *              "orders_total": "4",
     *              "clients_total": "3"
     *           },
     *           "status": true
     *  }
     *
     * @apiErrorExample Error-Response:
     *
     *     {
     *       "status": false,
     *       "error": "Unknown filter set"
     *     }
     *
     */

    public function statistic()
    {
        header("Access-Control-Allow-Origin: *");
        $this->response->addHeader('Content-Type: application/json');
        $error = $this->valid();
        if ($error != null) {

            $this->response->setOutput(json_encode(['error' => $error, 'status' => false]));
            return;
        }
        $this->load->model('module/apimodule');

        if (isset($_REQUEST['filter']) && $_REQUEST['filter'] != '') {
            $clients = $this->model_module_apimodule->getTotalCustomers(array('filter' => $_REQUEST['filter']));
            $orders = $this->model_module_apimodule->getTotalOrders(array('filter' => $_REQUEST['filter']));

            if ($clients === false || $orders === false) {

                $this->response->setOutput(json_encode(['error' => 'Unknown filter set', 'status' => false]));
                return;

            } else {
                $clients_for_time = [];
                $orders_for_time = [];
                if ($_REQUEST['filter'] == 'month') {

                    $hours = range(1, 30);
                    for ($i = 1; $i <= 30; $i++) {
                        $b = 0;
                        $o = 0;
                        foreach ($clients as $value) {

                            $day = strtotime($value['date_added']);
                            $day = date("d", $day);

                            if ($day == $i) {
                                $b = $b + 1;
                            }
                        }
                        $clients_for_time[] = $b;

                        foreach ($orders as $value) {

                            $day = strtotime($value['date_added']);
                            $day = date("d", $day);

                            if ($day == $i) {
                                $o = $o + 1;
                            }
                        }
                        $orders_for_time[] = $o;
                    }
                } elseif ($_REQUEST['filter'] == 'day') {
                    $hours = range(0, 23);

                    for ($i = 0; $i <= 23; $i++) {
                        $b = 0;
                        $o = 0;
                        foreach ($clients as $value) {

                            $hour = strtotime($value['date_added']);
                            $hour = date("h", $hour);

                            if ($hour == $i) {
                                $b = $b + 1;
                            }
                        }
                        $clients_for_time[] = $b;

                        foreach ($orders as $value) {

                            $day = strtotime($value['date_added']);
                            $day = date("h", $day);

                            if ($day == $i) {
                                $o = $o + 1;
                            }
                        }
                        $orders_for_time[] = $o;

                    }

                } elseif ($_REQUEST['filter'] == 'week') {
                    $hours = range(1, 7);

                    for ($i = 1; $i <= 7; $i++) {
                        $b = 0;
                        $o = 0;
                        foreach ($clients as $value) {

                            $date = strtotime($value['date_added']);

                            $f = date("N", $date);

                            if ($f == $i) {
                                $b = $b + 1;
                            }
                        }
                        $clients_for_time[] = $b;

                        foreach ($orders as $val) {

                            $day = strtotime($val['date_added']);
                            $day = date("N", $day);

                            if ($day == $i) {
                                $o = $o + 1;
                            }
                        }
                        $orders_for_time[] = $o;

                    }

                } elseif ($_REQUEST['filter'] == 'year') {
                    $hours = range(1, 12);

                    for ($i = 1; $i <= 12; $i++) {
                        $b = 0;
                        $o = 0;
                        foreach ($clients as $value) {

                            $date = strtotime($value['date_added']);

                            $f = date("m", $date);

                            if ($f == $i) {
                                $b = $b + 1;
                            }
                        }
                        $clients_for_time[] = $b;

                        foreach ($orders as $val) {

                            $day = strtotime($val['date_added']);
                            $day = date("m", $day);

                            if ($day == $i) {
                                $o = $o + 1;
                            }
                        }
                        $orders_for_time[] = $o;
                    }
                }

                $data['xAxis'] = $hours;
                $data['clients'] = $clients_for_time;
                $data['orders'] = $orders_for_time;
            }

            $sale_total = $this->model_module_apimodule->getTotalSales();

            $data['total_sales'] = number_format($sale_total, 2, '.', '');
            $sale_year_total = $this->model_module_apimodule->getTotalSales(array('this_year' => true));


            $data['sale_year_total'] = number_format($sale_year_total, 2, '.', '');
            $orders_total = $this->model_module_apimodule->getTotalOrders();
            $data['orders_total'] = $orders_total[0]['COUNT(*)'];
            $clients_total = $this->model_module_apimodule->getTotalCustomers();
            $data['clients_total'] = $clients_total[0]['COUNT(*)'];
            $data['currency_code'] = $this->model_module_apimodule->getDefaultCurrency();


            $this->response->setOutput(json_encode(['response' => $data, 'status' => true]));

        } else {

            $this->response->setOutput(json_encode(['error' => 'Missing some params', 'status' => false]));

        }
    }


    private function valid()
    {

        if (!isset($_REQUEST['token']) || $_REQUEST['token'] == '') {
            $error = 'You need to be logged!';
        } else {
            $this->load->model('module/apimodule');
            $tokens = $this->model_module_apimodule->getTokens();
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


    /**
     * @api {get} index.php?route=module/apimodule/clients  getClients
     * @apiName GetClients
     * @apiGroup All
     *
     * @apiParam {Token} token your unique token.
     * @apiParam {Number} page number of the page.
     * @apiParam {Number} limit limit of the orders for the page.
     * @apiParam {String} fio full name of the client.
     * @apiParam {String} sort param for sorting clients(sum/quantity/date_added).
     *
     * @apiSuccess {Number} client_id  ID of the client.
     * @apiSuccess {String} fio     Client's FIO.
     * @apiSuccess {Number} total  Total sum of client's orders.
     * @apiSuccess {String} currency_code  Default currency of the shop.
     * @apiSuccess {Number} quantity  Total quantity of client's orders.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *   "Response"
     *   {
     *     "clients"
     *      {
     *          {
     *              "client_id" : "88",
     *              "fio" : "Anton Kiselev",
     *              "total" : "1006.00",
     *              "currency_code": "UAH",
     *              "quantity" : "5"
     *          },
     *          {
     *              "client_id" : "10",
     *              "fio" : "Vlad Kochergin",
     *              "currency_code": "UAH",
     *              "total" : "555.00",
     *              "quantity" : "1"
     *          }
     *      }
     *    },
     *    "Status" : true
     * }
     * @apiErrorExample Error-Response:
     * {
     *      "Error" : "Not one client found",
     *      "Status" : false
     * }
     *
     *
     */
    public function clients()
    {
        header("Access-Control-Allow-Origin: *");
        $this->response->addHeader('Content-Type: application/json');
        $error = $this->valid();
        if ($error != null) {

            $this->response->setOutput(json_encode(['error' => $error, 'status' => false]));
            return;
        }

        if (isset($_REQUEST['page']) && (int)$_REQUEST['page'] != 0 && (int)$_REQUEST['limit'] != 0 && isset($_REQUEST['limit'])) {
            $page = ($_REQUEST['page'] - 1) * $_REQUEST['limit'];
            $limit = $_REQUEST['limit'];
        } else {
            $page = 0;
            $limit = 20;
        }
        if (isset($_REQUEST['sort']) && $_REQUEST['sort'] != '') {
            $order = $_REQUEST['sort'];
        } else {
            $order = 'date_added';
        }
        if (isset($_REQUEST['fio']) && $_REQUEST['fio'] != '') {
            $fio = $_REQUEST['fio'];
        } else {
            $fio = '';
        }

        $this->load->model('module/apimodule');

        $clients = $this->model_module_apimodule->getClients(array('page' => $page, 'limit' => $limit, 'order' => $order, 'fio' => $fio));
        $response = [];
        if (count($clients) > 0) {
            $currency = $this->model_module_apimodule->getDefaultCurrency();
            $data = [];
            foreach ($clients as $client) {

                $data['client_id'] = $client['customer_id'];
                if (isset($client['firstname']) && $client['firstname'] != '') {
                    $data['fio'] = $client['firstname'] . ' ' . $client['lastname'];
                } elseif (isset($client['lastname']) && $client['lastname'] != '') {
                    $data['fio'] .= ' ' . $client['lastname'];
                }

                $data['total'] = number_format($client['sum'], 2, '.', '');
                $data['quantity'] = $client['quantity'];
                $data['currency_code'] = $currency;
                $clients_to_response[] = $data;

            }
            $response['clients'] = $clients_to_response;


        } else {
            $response['clients'] = [];
        }
        $this->response->setOutput(json_encode(['response' => $response, 'status' => true]));
        return;
    }

    /**
     * @api {get} index.php?route=module/apimodule/clientinfo  getClientInfo
     * @apiName getClientInfo
     * @apiGroup All
     *
     * @apiParam {Token} token your unique token.
     * @apiParam {Number} client_id unique client ID.
     *
     * @apiSuccess {Number} client_id  ID of the client.
     * @apiSuccess {String} fio     Client's FIO.
     * @apiSuccess {Number} total  Total sum of client's orders.
     * @apiSuccess {Number} quantity  Total quantity of client's orders.
     * @apiSuccess {String} email  Client's email.
     * @apiSuccess {String} telephone  Client's telephone.
     * @apiSuccess {Number} cancelled  Total quantity of cancelled orders.
     * @apiSuccess {Number} completed  Total quantity of completed orders.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *   "Response"
     *   {
     *         "client_id" : "88",
     *         "fio" : "Anton Kiselev",
     *         "total" : "1006.00",
     *         "quantity" : "5",
     *         "cancelled" : "1",
     *         "completed" : "2",
     *         "email" : "client@mail.ru",
     *         "telephone" : "13456789"
     *   },
     *   "Status" : true
     * }
     * @apiErrorExample Error-Response:
     * {
     *      "Error" : "Not one client found",
     *      "Status" : false
     * }
     *
     *
     */
    public function clientinfo()
    {
        header("Access-Control-Allow-Origin: *");
        $this->response->addHeader('Content-Type: application/json');
        if (isset($_REQUEST['client_id']) && $_REQUEST['client_id'] != '') {
            $id = $_REQUEST['client_id'];

            $error = $this->valid();
            if ($error != null) {

                $this->response->setOutput(json_encode(['error' => $error, 'status' => false]));
                return;
            }

            $this->load->model('module/apimodule');
            $client = $this->model_module_apimodule->getClientInfo($id);

            if (count($client) > 0) {
                $data['client_id'] = $client['customer_id'];

                if (isset($client['firstname']) && $client['firstname'] != '') {
                    $data['fio'] = $client['firstname'] . ' ' . $client['lastname'];
                } elseif (isset($client['lastname']) && $client['lastname'] != '') {
                    $data['fio'] .= ' ' . $client['lastname'];
                }
                if (isset($client['email']) && $client['email'] != '') {
                    $data['email'] = $client['email'];
                }
                if (isset($client['telephone']) && $client['telephone'] != '') {
                    $data['telephone'] = $client['telephone'];
                }

                $data['total'] = number_format($client['sum'], 2, '.', '');
                $data['quantity'] = $client['quantity'];

                $data['completed'] = $client['completed'];
                $data['cancelled'] = $client['cancelled'];

                $this->response->setOutput(json_encode(['response' => $data, 'status' => true]));

            } else {

                $this->response->setOutput(json_encode(['error' => 'Can not found client with id = ' . $id, 'status' => false]));
            }
        } else {

            $this->response->setOutput(json_encode(['error' => 'You have not specified ID', 'status' => false]));
        }
    }

    /**
     * @api {get} index.php?route=module/apimodule/clientorders  getClientOrders
     * @apiName getClientOrders
     * @apiGroup All
     *
     * @apiParam {Token} token your unique token.
     * @apiParam {Number} client_id unique client ID.
     * @apiParam {String} sort param for sorting orders(total/date_added/completed/cancelled).
     *
     * @apiSuccess {Number} order_id  ID of the order.
     * @apiSuccess {Number} order_number  Number of the order.
     * @apiSuccess {String} status  Status of the order.
     * @apiSuccess {String} currency_code  Default currency of the shop.
     * @apiSuccess {Number} total  Total sum of the order.
     * @apiSuccess {Date} date_added  Date added of the order.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *   "Response"
     *   {
     *       "orders":
     *          {
     *             "order_id" : "1",
     *             "order_number" : "1",
     *             "status" : "Сделка завершена",
     *             "currency_code": "UAH",
     *             "total" : "106.00",
     *             "date_added" : "2016-12-09 16:17:02"
     *          },
     *          {
     *             "order_id" : "2",
     *             "currency_code": "UAH",
     *             "order_number" : "2",
     *             "status" : "В обработке",
     *             "total" : "506.00",
     *             "date_added" : "2016-10-19 16:00:00"
     *          }
     *    },
     *    "Status" : true
     * }
     * @apiErrorExample Error-Response:
     * {
     *      "Error" : "You have not specified ID",
     *      "Status" : false
     * }
     *
     *
     */

    public function clientorders()
    {
        header("Access-Control-Allow-Origin: *");
        $this->response->addHeader('Content-Type: application/json');
        if (isset($_REQUEST['client_id']) && $_REQUEST['client_id'] != '') {
            $id = $_REQUEST['client_id'];

            $error = $this->valid();
            if ($error != null) {

                $this->response->setOutput(json_encode(['error' => $error, 'status' => false]));
                return;
            }
            if (isset($_REQUEST['sort']) && $_REQUEST['sort'] != '') {
                switch ($_REQUEST['sort']) {
                    case 'date_added':
                        $sort = 'date_added';
                        break;
                    case 'total':
                        $sort = 'total';
                        break;
                    case 'completed':
                        $sort = 'completed';
                        break;
                    case 'cancelled':
                        $sort = 'cancelled';
                        break;
                    default:
                        $sort = 'date_added';
                }
            } else {
                $sort = 'date_added';
            }

            $this->load->model('module/apimodule');
            $orders = $this->model_module_apimodule->getClientOrders($id, $sort);
            $currency_code = $this->model_module_apimodule->getDefaultCurrency();
            if (count($orders) > 0) {
                foreach ($orders as $order) {
                    $data['order_id'] = $order['order_id'];
                    $data['order_number'] = $order['order_id'];
                    $data['total'] = number_format($order['total'], 2, '.', '');
                    $data['date_added'] = $order['date_added'];
                    $data['currency_code'] = $currency_code;
                    if (isset($order['name'])) {
                        $data['status'] = $order['name'];
                    } else {
                        $data['status'] = '';
                    }

                    $to_response[] = $data;
                }
                $response['orders'] = $to_response;

                $this->response->setOutput(json_encode(['response' => $response, 'status' => true]));

            } else {

                $this->response->setOutput(json_encode(['response' => ['orders' => []], 'status' => true]));
            }
        } else {

            $this->response->setOutput(json_encode(['error' => 'You have not specified ID', 'status' => false]));
        }
    }


    /**
     * @api {get} index.php?route=module/apimodule/products  getProductsList
     * @apiName getProductsList
     * @apiGroup All
     *
     * @apiParam {Token} token your unique token.
     * @apiParam {Number} page number of the page.
     * @apiParam {Number} limit limit of the orders for the page.
     *
     * @apiSuccess {Number} product_id  ID of the product.
     * @apiSuccess {String} model     Model of the product.
     * @apiSuccess {String} name  Name of the product.
     * @apiSuccess {String} currency_code  Default currency of the shop.
     * @apiSuccess {Number} price  Price of the product.
     * @apiSuccess {Number} quantity  Actual quantity of the product.
     * @apiSuccess {Url} image  Url to the product image.
     *
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *   "Response":
     *   {
     *      "products":
     *      {
     *           {
     *             "product_id" : "1",
     *             "model" : "Black",
     *             "name" : "HTC Touch HD",
     *             "price" : "100.00",
     *             "currency_code": "UAH",
     *             "quantity" : "83",
     *             "image" : "http://site-url/image/catalog/demo/htc_touch_hd_1.jpg"
     *           },
     *           {
     *             "product_id" : "2",
     *             "model" : "White",
     *             "name" : "iPhone",
     *             "price" : "300.00",
     *             "currency_code": "UAH",
     *             "quantity" : "30",
     *             "image" : "http://site-url/image/catalog/demo/iphone_1.jpg"
     *           }
     *      }
     *   },
     *   "Status" : true
     * }
     * @apiErrorExample Error-Response:
     * {
     *      "Error" : "Not one product not found",
     *      "Status" : false
     * }
     *
     *
     */

    public function products()
    {
        header("Access-Control-Allow-Origin: *");
        $this->response->addHeader('Content-Type: application/json');
        $error = $this->valid();
        if ($error != null) {

            $this->response->setOutput(json_encode(['error' => $error, 'status' => false]));
            return;
        }
        if (isset($_REQUEST['page']) && (int)$_REQUEST['page'] != 0 && (int)$_REQUEST['limit'] != 0 && isset($_REQUEST['limit'])) {
            $page = ($_REQUEST['page'] - 1) * $_REQUEST['limit'];
            $limit = $_REQUEST['limit'];
        } else {
            $page = 0;
            $limit = 10;
        }
        if (isset($_REQUEST['name']) && $_REQUEST['name'] != '') {
            $name = $_REQUEST['name'];
        } else {
            $name = '';
        }

        $this->load->model('module/apimodule');
        $products = $this->model_module_apimodule->getProductsList($page, $limit, $name);
        if (count($products) > 0) {
            foreach ($products as $product) {
                $data['product_id'] = $product['product_id'];
                $data['model'] = $product['model'];
                $data['quantity'] = $product['quantity'];
                $this->load->model('tool/image');
                if (isset($product['image'])) {
                    $data['image'] = $this->model_tool_image->resize($product['image'], 200, 200);
                } else {
                    $data['image'] = '';
                }
                $data['price'] = number_format($product['price'], 2, '.', '');
                $data['name'] = strip_tags(htmlspecialchars_decode($product['name']));
                $data['currency_code'] = $this->model_module_apimodule->getDefaultCurrency();
                $to_response[] = $data;
            }
            $response['products'] = $to_response;

            $this->response->setOutput(json_encode(['response' => $response, 'status' => true]));
        } else {

            $this->response->setOutput(json_encode(['error' => 'Not one product not found', 'status' => false]));
        }
    }

    /**
     * @api {get} index.php?route=module/apimodule/productinfo  getProductInfo
     * @apiName getProductInfo
     * @apiGroup All
     *
     * @apiParam {Token} token your unique token.
     * @apiParam {Number} product_id unique product ID.
     *
     * @apiSuccess {Number} product_id  ID of the product.
     * @apiSuccess {String} model     Model of the product.
     * @apiSuccess {String} name  Name of the product.
     * @apiSuccess {Number} price  Price of the product.
     * @apiSuccess {String} currency_code  Default currency of the shop.
     * @apiSuccess {Number} quantity  Actual quantity of the product.
     * @apiSuccess {String} description     Detail description of the product.
     * @apiSuccess {Array} images  Array of the images of the product.
     *
     * @apiSuccessExample Success-Response:
     *     HTTP/1.1 200 OK
     * {
     *   "Response":
     *   {
     *       "product_id" : "1",
     *       "model" : "Black",
     *       "name" : "HTC Touch HD",
     *       "price" : "100.00",
     *       "currency_code": "UAH"
     *       "quantity" : "83",
     *       "main_image" : "http://site-url/image/catalog/demo/htc_iPhone_1.jpg",
     *       "description" : "Revolutionary multi-touch interface.↵	iPod touch features the same multi-touch screen technology as iPhone.",
     *       "images" :
     *       [
     *           "http://site-url/image/catalog/demo/htc_iPhone_1.jpg",
     *           "http://site-url/image/catalog/demo/htc_iPhone_2.jpg",
     *           "http://site-url/image/catalog/demo/htc_iPhone_3.jpg"
     *       ]
     *   },
     *   "Status" : true
     * }
     * @apiErrorExample Error-Response:
     * {
     *      "Error" : "Can not found product with id = 10",
     *      "Status" : false
     * }
     *
     *
     */

    public function productinfo()
    {
        header("Access-Control-Allow-Origin: *");
        $this->response->addHeader('Content-Type: application/json');
        $error = $this->valid();
        if ($error != null) {
            $this->response->setOutput(json_encode(['error' => $error, 'status' => false]));
            return;
        }
        if (isset($_REQUEST['product_id']) && (int)$_REQUEST['product_id'] != 0) {
            $id = $_REQUEST['product_id'];

            $this->load->model('module/apimodule');
            $product = $this->model_module_apimodule->getProductsByID($id);

            $product_img = $this->model_module_apimodule->getProductImages($id);

            if (count($product) > 0) {
                $response['product_id'] = $product['product_id'];
                $response['model'] = $product['model'];
                $response['quantity'] = $product['quantity'];
                $response['price'] = number_format($product['price'], 2, '.', '');
                $response['name'] = strip_tags(htmlspecialchars_decode($product['name']));
                $response['currency_code'] = $this->model_module_apimodule->getDefaultCurrency();
                $this->load->model('tool/image');

                $response['description'] = strip_tags(htmlspecialchars_decode($product_img['description']));
                if (count($product_img['images']) > 0) {
                    $response['images'] = [];
                    foreach ($product_img['images'] as $image) {
                        $image_to_response = $this->model_tool_image->resize($image, 600, 800);
                        if ($image_to_response) {
                            $response['images'][] = $image_to_response;
                        }
                    }
                } else {
                    $response['images'] = [];
                }

                $this->response->setOutput(json_encode(['response' => $response, 'status' => true]));
            } else {
                $this->response->setOutput(json_encode(['error' => 'Can not found order with id = ' . $_REQUEST['product_id'], 'status' => false]));
            }
        } else {
            $this->response->setOutput(json_encode(['error' => 'You have not specified ID', 'status' => false]));
        }
    }

}





