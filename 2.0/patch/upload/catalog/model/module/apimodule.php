<?php

class ModelModuleApimodule extends Model
{
	private $API_VERSION = 2.0;

	public function getVersion(){
		return $this->API_VERSION;
	}

	public function getMaxOrderPrice()
    {
        $query = $this->db->query("SELECT MAX(total) AS total FROM `" . DB_PREFIX . "order`  o   WHERE o.order_status_id != 0 ");

        return number_format($query->row['total'], 2, '.','');
    }

    public function getOrders($data = array())
    {

        $sql = "SELECT  * FROM `" . DB_PREFIX . "order` AS o LEFT JOIN `" . DB_PREFIX . "order_status` AS s ON o.order_status_id = s.order_status_id  ";
        if (isset($data['filter'])) {
            if (isset($data['filter']['order_status_id']) && (int)($data['filter']['order_status_id']) != 0 && $data['filter']['order_status_id'] != '') {
                $sql .= " WHERE o.order_status_id = " . (int)$data['filter']['order_status_id'];
            } else {
                $sql .= " WHERE o.order_status_id != 0 ";
            }
            if (isset($data['filter']['fio']) && $data['filter']['fio'] != '') {
                $params = [];
                $newparam = explode(' ', $data['filter']['fio']);

                foreach ($newparam as $key => $value) {
                    if ($value == '') {
                        unset($newparam[$key]);
                    } else {
                        $params[] = $value;
                    }
                }

                $sql .= " AND ( o.firstname LIKE \"%" . $params[0] . "%\" OR o.lastname LIKE \"%" . $params[0] . "%\" OR o.payment_lastname LIKE \"%" . $params[0] . "%\" OR o.payment_firstname LIKE \"%" . $params[0] . "%\"";

                foreach ($params as $param) {
                    if ($param != $params[0]) {
                        $sql .= " OR o.firstname LIKE \"%" . $params[0] . "%\" OR o.lastname LIKE \"%" . $param . "%\" OR o.payment_lastname LIKE \"%" . $param . "%\" OR o.payment_firstname LIKE \"%" . $param . "%\"";
                    };
                }
                $sql .= " ) ";
            }
            if (isset($data['filter']['min_price']) && isset($data['filter']['max_price']) && $data['filter']['max_price'] != ''  && $data['filter']['min_price'] != 0) {
                $sql .= " AND o.total > " . $data['filter']['min_price'] . " AND o.total <= " . $data['filter']['max_price'];
            }
            if (isset($data['filter']['date_min']) && $data['filter']['date_min'] != '') {
                $date_min = date('y-m-d', strtotime($data['filter']['date_min']));
                $sql .= " AND DATE_FORMAT(o.date_added,'%y-%m-%d') > '" . $date_min . "'";
            }
            if (isset($data['filter']['date_max']) && $data['filter']['date_max'] != '') {
                $date_max = date('y-m-d', strtotime($data['filter']['date_max']));
                $sql .= " AND DATE_FORMAT(o.date_added,'%y-%m-%d') < '" . $date_max . "'";
            }


        } else {
            $sql .= " WHERE o.order_status_id != 0 ";
        }
        $sql .= " GROUP BY o.order_id ORDER BY o.order_id DESC";

        $total_sum = $this->db->query("SELECT sum(`total`) as `summa`, count(*) as `quantity` FROM `" . DB_PREFIX . "order` WHERE `" . DB_PREFIX . "order`.order_status_id != 0");
        $sum = $total_sum->rows[0]['summa'];
        $quantity = $total_sum->rows[0]['quantity'];

        $sql .= " LIMIT " . (int)$data['limit'] . " OFFSET " . (int)$data['page'];

        $query = $this->db->query($sql);
        $query->totalsumm=$sum;
        $query->quantity=$quantity;
        return $query;
    }


    public function getOrderById($id)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` AS o LEFT JOIN `" . DB_PREFIX . "order_status` AS s ON o.order_status_id = s.order_status_id WHERE order_id = " . $id . " GROUP BY o.order_id ORDER BY o.order_id");
        return $query->rows;
    }

	public function getOrderFindById($id)
	{
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order` AS o 
				LEFT JOIN `" . DB_PREFIX . "order_status` AS s ON o.order_status_id = s.order_status_id 
				WHERE o.order_id = " . $id . " and o.order_status_id != 0 GROUP BY o.order_id ORDER BY o.order_id");
		return $query->row;
	}

    public function AddComment($orderID, $statusID, $comment = '', $inform = false)
    {
        $setStatus = $this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = " . $statusID . " WHERE order_id = " . $orderID);
        if ($setStatus === true) {
            $getStatus = $this->db->query("SELECT name, date_added FROM `" . DB_PREFIX . "order_status` AS s LEFT JOIN `" . DB_PREFIX . "order` AS o ON o.order_status_id = s.order_status_id WHERE o.order_id = " . $orderID);
            $notify = ( $inform == 'true' ) ? 1 : 0;
            $this->db->query("INSERT INTO `" . DB_PREFIX . "order_history` (order_id, order_status_id, notify, comment, date_added)  VALUES (" . $orderID . ", " . $statusID . ", ".$notify.", \"" . $comment . "\", NOW() ) ");

            $email = $this->db->query("SELECT o.email, o.store_name, o.firstname  FROM `" . DB_PREFIX . "order` AS o WHERE o.order_id = " . $orderID);
            if( $inform == 'true' ){
                $mail = new Mail();
                $mail->protocol = $this->config->get('config_mail_protocol');
                $mail->parameter = $this->config->get('config_mail_parameter');
                $mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
                $mail->smtp_username = $this->config->get('config_mail_smtp_username');
                $mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
                $mail->smtp_port = $this->config->get('config_mail_smtp_port');
                $mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

                $mail->setTo($email->row['email']);
                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender(html_entity_decode($email->row['store_name'], ENT_QUOTES, 'UTF-8'));
                $mail->setSubject(html_entity_decode($email->row['firstname'], ENT_QUOTES, 'UTF-8'));

                $data = $this->getTemplateForEmail( $orderID, $getStatus->row['name'], $comment , $inform );

                if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/mail/order.tpl')) {
                    $html = $this->load->view($this->config->get('config_template') . '/template/mail/order.tpl', $data);
                } else {
                    $html = $this->load->view('default/template/mail/order.tpl', $data);
                }

                $mail->setHtml($html);
                $mail->send();

            }
        }
        return $getStatus->row;

    }


    public function getTemplateForEmail( $orderID, $status,  $comment, $notify) {
        $order_id = $orderID;
        $order_info = $this->getOrder($order_id);

        // Load the language for any mails that might be required to be sent out
        $language = new Language($order_info['language_code']);
        $language->load($order_info['language_code']);
        $language->load('mail/order');


        // HTML Mail
        $data = array();

        $data['title'] = sprintf($language->get('text_new_subject'), $order_info['store_name'], $order_id);

        $data['text_greeting'] = sprintf($language->get('text_new_greeting'), $order_info['store_name']);
        $data['text_link'] = $language->get('text_new_link');
        $data['text_download'] = $language->get('text_new_download');
        $data['text_order_detail'] = $language->get('text_new_order_detail');
        $data['text_instruction'] = $language->get('text_new_instruction');
        $data['text_order_id'] = $language->get('text_new_order_id');
        $data['text_date_added'] = $language->get('text_new_date_added');
        $data['text_payment_method'] = $language->get('text_new_payment_method');
        $data['text_shipping_method'] = $language->get('text_new_shipping_method');
        $data['text_email'] = $language->get('text_new_email');
        $data['text_telephone'] = $language->get('text_new_telephone');
        $data['text_ip'] = $language->get('text_new_ip');
        $data['text_order_status'] = $language->get('text_new_order_status');
        $data['text_payment_address'] = $language->get('text_new_payment_address');
        $data['text_shipping_address'] = $language->get('text_new_shipping_address');
        $data['text_product'] = $language->get('text_new_product');
        $data['text_model'] = $language->get('text_new_model');
        $data['text_quantity'] = $language->get('text_new_quantity');
        $data['text_price'] = $language->get('text_new_price');
        $data['text_total'] = $language->get('text_new_total');
        $data['text_footer'] = $language->get('text_new_footer');

        $data['logo'] = $this->config->get('config_url') . 'image/' . $this->config->get('config_logo');
        $data['store_name'] = $order_info['store_name'];
        $data['store_url'] = $order_info['store_url'];
        $data['customer_id'] = $order_info['customer_id'];
        $data['link'] = $order_info['store_url'] . 'index.php?route=account/order/info&order_id=' . $order_id;

        $data['download'] = '';

        $data['order_id'] = $order_id;
        $data['date_added'] = date($language->get('date_format_short'), strtotime($order_info['date_added']));
        $data['payment_method'] = $order_info['payment_method'];
        $data['shipping_method'] = $order_info['shipping_method'];
        $data['email'] = $order_info['email'];
        $data['telephone'] = $order_info['telephone'];
        $data['ip'] = $order_info['ip'];
        $data['order_status'] = $status;

        if ($comment && $notify) {
            $data['comment'] = nl2br($comment);
        } else {
            $data['comment'] = '';
        }

        if ($order_info['payment_address_format']) {
            $format = $order_info['payment_address_format'];
        } else {
            $format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
        }

        $find = array(
            '{firstname}',
            '{lastname}',
            '{company}',
            '{address_1}',
            '{address_2}',
            '{city}',
            '{postcode}',
            '{zone}',
            '{zone_code}',
            '{country}'
        );

        $replace = array(
            'firstname' => $order_info['payment_firstname'],
            'lastname'  => $order_info['payment_lastname'],
            'company'   => $order_info['payment_company'],
            'address_1' => $order_info['payment_address_1'],
            'address_2' => $order_info['payment_address_2'],
            'city'      => $order_info['payment_city'],
            'postcode'  => $order_info['payment_postcode'],
            'zone'      => $order_info['payment_zone'],
            'zone_code' => $order_info['payment_zone_code'],
            'country'   => $order_info['payment_country']
        );

        $data['payment_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

        if ($order_info['shipping_address_format']) {
            $format = $order_info['shipping_address_format'];
        } else {
            $format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
        }

        $find = array(
            '{firstname}',
            '{lastname}',
            '{company}',
            '{address_1}',
            '{address_2}',
            '{city}',
            '{postcode}',
            '{zone}',
            '{zone_code}',
            '{country}'
        );

        $replace = array(
            'firstname' => $order_info['shipping_firstname'],
            'lastname'  => $order_info['shipping_lastname'],
            'company'   => $order_info['shipping_company'],
            'address_1' => $order_info['shipping_address_1'],
            'address_2' => $order_info['shipping_address_2'],
            'city'      => $order_info['shipping_city'],
            'postcode'  => $order_info['shipping_postcode'],
            'zone'      => $order_info['shipping_zone'],
            'zone_code' => $order_info['shipping_zone_code'],
            'country'   => $order_info['shipping_country']
        );

        $data['shipping_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

        $this->load->model('tool/upload');

        // Products
        $data['products'] = array();

        // Stock subtraction
        $order_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

        foreach ($order_product_query->rows as $order_product) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");

            $order_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product['order_product_id'] . "'");

            foreach ($order_option_query->rows as $option) {
                $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$option['product_option_value_id'] . "' AND subtract = '1'");
            }
        }

        foreach ($order_product_query->rows as $product) {
            $option_data = array();

            $order_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$product['order_product_id'] . "'");

            foreach ($order_option_query->rows as $option) {
                if ($option['type'] != 'file') {
                    $value = $option['value'];
                } else {
                    $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                    if ($upload_info) {
                        $value = $upload_info['name'];
                    } else {
                        $value = '';
                    }
                }

                $option_data[] = array(
                    'name'  => $option['name'],
                    'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                );
            }

            $data['products'][] = array(
                'name'     => $product['name'],
                'model'    => $product['model'],
                'option'   => $option_data,
                'quantity' => $product['quantity'],
                'price'    => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
                'total'    => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value'])
            );
        }

        // Vouchers
        $data['vouchers'] = array();

        $order_voucher_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int)$order_id . "'");

        foreach ($order_voucher_query->rows as $voucher) {
            $data['vouchers'][] = array(
                'description' => $voucher['description'],
                'amount'      => $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value']),
            );
        }

        // Order Totals
        $data['totals'] = array();

        $order_total_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order ASC");

        foreach ($order_total_query->rows as $total) {
            $data['totals'][] = array(
                'title' => $total['title'],
                'text'  => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value']),
            );
        }

        return $data;
    }

    public function getProducts($page)
    {
        $sql = "SELECT p.product_id";
        $sql .= " FROM `" . DB_PREFIX . "product` p";
        $sql .= " LEFT JOIN `" . DB_PREFIX . "product_description` pd ON (p.product_id = pd.product_id) LEFT JOIN `" . DB_PREFIX . "product_to_store` p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $sql .= " GROUP BY p.product_id";
        $sql .= " ORDER BY p.product_id ASC";
        $sql .= " LIMIT 5 OFFSET " . $page;
        $query = $this->db->query($sql);
        $this->load->model('catalog/product');
        foreach ($query->rows as $result) {
            $product_data[$result['product_id']] = $this->model_catalog_product->getProduct($result['product_id']);
        }
        return $product_data;

    }

    public function checkLogin($username, $password)
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "user` WHERE username = '" . $username . "' AND (password = SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('" . $this->db->escape(htmlspecialchars($password, ENT_QUOTES)) . "'))))) OR password = '" . $this->db->escape(md5($password)) . "') AND status = '1'");

        return $query->row;
    }

    public function setUserToken($id, $token)
    {

        $sql = "INSERT INTO `" . DB_PREFIX . "user_token_mob_api` (user_id, token)  VALUES (" . $id . ",\"" . $token . " \") ";

        $query = $this->db->query($sql);
        return $query;
    }

    public function getUserToken($id)
    {

        $query = $this->db->query("SELECT token FROM `" . DB_PREFIX . "user_token_mob_api` WHERE user_id = " . $id);

        return $query->row;
    }

    public function getTokens()
    {

        $query = $this->db->query("SELECT token FROM `" . DB_PREFIX . "user_token_mob_api` ");

        return $query->rows;
    }


    public function getOrderProducts($id)
    {

        $query = $this->db->query("SELECT * FROM (SELECT image, product_id, tax_class_id FROM `" . DB_PREFIX . "product`  ) AS p LEFT JOIN (SELECT order_id, product_id, model, quantity, price,  name FROM `" . DB_PREFIX . "order_product` WHERE order_id = " . $id . " ) AS o ON o.product_id = p.product_id LEFT JOIN (SELECT store_url, order_id, total, currency_code FROM `" . DB_PREFIX . "order` WHERE order_id = " . $id . " ) t2 ON o.order_id = t2.order_id LEFT JOIN (SELECT order_id, code, value FROM `" . DB_PREFIX . "order_total` WHERE code = 'shipping' AND order_id = " . $id . " ) t5 ON o.order_id = t5.order_id WHERE o.order_id = " . $id);

        return $query->rows;
    }


    public function getProductDiscount($id, $quantity)
    {

        $query = $this->db->query("SELECT price FROM `" . DB_PREFIX . "product_discount` AS pd2 WHERE pd2.product_id = " . $id . " AND pd2.quantity <= " . $quantity . " AND pd2.date_start < NOW() AND pd2.date_end > NOW() LIMIT 1");

        return $query->row;
    }

    public function getOrderHistory($id)
    {
        $query = $this->db->query("SELECT  * FROM `" . DB_PREFIX . "order_history` h  LEFT JOIN `" . DB_PREFIX . "order_status` s ON h.order_status_id = s.order_status_id WHERE h.order_id = ". $id ." AND s.name IS NOT NULL GROUP BY h.date_added ORDER BY h.date_added DESC  ");

        return $query->rows;
    }

    public function OrderStatusList()
    {
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_status` WHERE language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->rows;
    }

    public function ChangeOrderDelivery($address, $city, $order_id)
    {

        $sql = "UPDATE `" . DB_PREFIX . "order` SET shipping_address_1 = \"" . $address . "\" ";
        if ($city !== false) {
            $sql .= " , shipping_city = \"" . $city . "\" WHERE order_id = \"" . $order_id . "\"";
        } else {
            $sql .= " WHERE order_id = \"" . $order_id . "\"";
        }

        $setStatus = $this->db->query($sql);

        return $setStatus;
    }


    public function getTotalSales($data = array())
    {
        $sql = "SELECT SUM(total) AS total FROM `" . DB_PREFIX . "order` WHERE order_status_id > '0'";

        if (!empty($data['this_year'])) {
            $sql .= " AND DATE_FORMAT(date_added,'%Y') = DATE_FORMAT(NOW(),'%Y')";
        }

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    public function getTotalOrders($data = array())
    {
        if (isset($data['filter'])) {
            $sql = "SELECT date_added FROM `" . DB_PREFIX . "order` WHERE order_status_id > '0'";

            if ($data['filter'] == 'day') {
                $sql .= " AND DATE(date_added) = DATE(NOW())";
            } elseif ($data['filter'] == 'week') {
                $date_start = strtotime('-' . date('w') . ' days');
                $sql .= "AND DATE(date_added) >= DATE('" . $this->db->escape(date('Y-m-d', $date_start)) . "') ";

            } elseif ($data['filter'] == 'month') {
                $sql .= "AND DATE(date_added) >= '" . $this->db->escape(date('Y') . '-' . date('m') . '-1') . "' ";

            } elseif ($data['filter'] == 'year') {
                $sql .= "AND YEAR(date_added) = YEAR(NOW())";
            } else {
                return false;
            }
        } else {
            $sql = "SELECT COUNT(*) FROM `" . DB_PREFIX . "order` WHERE order_status_id > '0'";
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getTotalCustomers($data = array())
    {

        if (isset($data['filter'])) {
            $sql = "SELECT date_added FROM `" . DB_PREFIX . "customer` ";
            if ($data['filter'] == 'day') {
                $sql .= " WHERE DATE(date_added) = DATE(NOW())";
            } elseif ($data['filter'] == 'week') {
                $date_start = strtotime('-' . date('w') . ' days');
                $sql .= "WHERE DATE(date_added) >= DATE('" . $this->db->escape(date('Y-m-d', $date_start)) . "') ";
            } elseif ($data['filter'] == 'month') {
                $sql .= "WHERE DATE(date_added) >= '" . $this->db->escape(date('Y') . '-' . date('m') . '-1') . "' ";
            } elseif ($data['filter'] == 'year') {
                $sql .= "WHERE YEAR(date_added) = YEAR(NOW()) ";
            } else {
                return false;
            }
        } else {
            $sql = "SELECT COUNT(*) FROM `" . DB_PREFIX . "customer` ";
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }
    public function getClients($data = array())
    {

        $sql = "SELECT  SUM(o.total) sum, COUNT(o.total) quantity, c.firstname, c.lastname, c.date_added, c.customer_id FROM `" . DB_PREFIX . "customer` AS c LEFT JOIN `" . DB_PREFIX . "order` AS o ON c.customer_id = o.customer_id  WHERE c.customer_id != 0 ";

        if (isset($data['fio']) && $data['fio'] != '') {
            $params = [];
            $newparam = explode(' ', $data['fio']);

            foreach ($newparam as $key => $value) {
                if ($value == '') {
                    unset($newparam[$key]);
                } else {
                    $params[] = $value;
                }
            }

            $sql .= " AND ( c.firstname LIKE \"%" . $params[0] . "%\" OR c.lastname LIKE \"%" . $params[0] . "%\" ";

            foreach ($params as $param) {
                if ($param != $params[0]) {
                    $sql .= " OR c.firstname LIKE \"%" . $params[0] . "%\" OR  c.lastname LIKE \"%" . $param . "%\" ";
                };
            }
            $sql .= " ) ";
        }
        $sql .= " group by c.customer_id";

        if(isset($data['order']) && $data['order'] != ''){
            $sql .= " ORDER BY ". $data['order'] ." DESC";
        }


        $sql .= " LIMIT " . (int)$data['limit'] . " OFFSET " . (int)$data['page'];

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getClientInfo ($id)
    {

        $sql = "SELECT  SUM(o.total) sum, COUNT(o.total) quantity, c.firstname, c.lastname, c.date_added, c.customer_id, c.email, c.telephone FROM `" . DB_PREFIX . "customer` AS c LEFT JOIN `" . DB_PREFIX . "order` AS o ON c.customer_id = o.customer_id ";

        $sql .= "  WHERE c.customer_id = ".$id ;
        $sql .= " group by c.customer_id";

        $completed = $this->db->query("SELECT  COUNT(o.total) completed  FROM `" . DB_PREFIX . "order` AS o LEFT JOIN `" . DB_PREFIX . "customer` AS c ON c.customer_id = o.customer_id WHERE c.customer_id = ". $id ." AND o.order_status_id = 5 group by c.customer_id");

        $cancelled = $this->db->query("SELECT  COUNT(o.total) cancelled FROM `" . DB_PREFIX . "order` AS o LEFT JOIN `" . DB_PREFIX . "customer` AS c ON c.customer_id = o.customer_id WHERE c.customer_id = ". $id ." AND o.order_status_id = 7 group by c.customer_id");

        $query = $this->db->query($sql);
        if (isset($completed->row['completed']) && $completed->row['completed'] != '') {
            $query->row['completed'] = $completed->row['completed'];
        }else{
            $query->row['completed'] = '0';
        }
        if (isset($cancelled->row['cancelled']) && $cancelled->row['cancelled'] != '') {
            $query->row['cancelled'] = $cancelled->row['cancelled'];
        }else{
            $query->row['cancelled'] = '0';
        }

        return $query->row;
    }

    public function getClientOrders($id, $sort)
    {

        if ($sort != 'cancelled' && $sort != 'completed'){
            $sql = "SELECT o.order_id, o.total, o.date_added, os.name FROM `" . DB_PREFIX . "order` AS o LEFT JOIN `" . DB_PREFIX . "order_status` AS os ON o.order_status_id = os.order_status_id WHERE o.customer_id = " . $id;
            $sql .= " GROUP BY o.order_id ORDER BY " . $sort . " DESC";
            $query = $this->db->query($sql);
        }elseif($sort == 'cancelled'){
            $sql = "SELECT o.order_id, o.total, o.date_added, os.name FROM `" . DB_PREFIX . "order` AS o LEFT JOIN `" . DB_PREFIX . "order_status` AS os ON o.order_status_id = os.order_status_id WHERE o.customer_id = " . $id . " AND  os.order_status_id != 7";
            $sql .= " GROUP BY o.order_id ORDER BY o.date_added DESC";
            $query = $this->db->query("SELECT o.order_id, o.total, o.date_added, os.name FROM `" . DB_PREFIX . "order` AS o LEFT JOIN `" . DB_PREFIX . "order_status` AS os ON o.order_status_id = os.order_status_id WHERE o.customer_id = " . $id . " AND  os.order_status_id = 7 GROUP BY o.order_id ORDER BY o.date_added DESC");
            $cancelled = $this->db->query($sql);
            foreach ($cancelled->rows as $value){
                $query->rows[] = $value;
            }
        }elseif($sort == 'completed'){
            $sql = "SELECT o.order_id, o.total, o.date_added, os.name FROM `" . DB_PREFIX . "order` AS o LEFT JOIN `" . DB_PREFIX . "order_status` AS os ON o.order_status_id = os.order_status_id WHERE o.customer_id = " . $id . " AND  os.order_status_id != 5";
            $sql .= " GROUP BY o.order_id ORDER BY o.date_added DESC";
            $query = $this->db->query("SELECT o.order_id, o.total, o.date_added, os.name FROM `" . DB_PREFIX . "order` AS o LEFT JOIN `" . DB_PREFIX . "order_status` AS os ON o.order_status_id = os.order_status_id WHERE o.customer_id = " . $id . " AND  os.order_status_id = 5 GROUP BY o.order_id ORDER BY o.date_added DESC");
            $cancelled = $this->db->query($sql);
            foreach ($cancelled->rows as $value){
                $query->rows[] = $value;
            }
        }



        return $query->rows;
    }

    public function getProductsList ($page, $limit, $name = '')
    {
        $sql = "SELECT p.product_id, p.model, p.quantity, p.image, p.price, pd.name, p.tax_class_id 
					FROM `" . DB_PREFIX . "product` AS p 
					LEFT JOIN `" . DB_PREFIX . "product_description` pd ON p.product_id = pd.product_id 
					WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'" ;
        if($name != ''){
            $sql .= " AND (pd.name LIKE \"%" .$name. "%\" OR p.model LIKE \"%" .$name. "%\")";
        }
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$page;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProductsByID ($id)
    {
        $sql = "SELECT p.product_id, p.model, p.quantity,  p.price, pd.name, p.tax_class_id,
						pd.description, p.sku, p.status,
 						ss.name stock_status_name 
				FROM `" . DB_PREFIX . "product` AS p 
				LEFT JOIN `" . DB_PREFIX . "product_description` pd ON p.product_id = pd.product_id 
				LEFT JOIN `" . DB_PREFIX . "stock_status` ss ON p.stock_status_id = ss.stock_status_id 
				WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.product_id = ". $id ;

        $query = $this->db->query($sql);

        return $query->row;
    }

    public function getProductOptionsByID ($id)
    {
      $sql = "SELECT pov.option_id option_id, pov.option_value_id option_value_id,
                     ovd.name option_value_name, ovd.language_id language_id, od.name option_name
             FROM `" . DB_PREFIX . "product_option_value` AS pov
             LEFT JOIN `" . DB_PREFIX . "option_value_description` AS ovd ON pov.option_value_id = ovd.option_value_id
             LEFT JOIN `" . DB_PREFIX . "option_description` AS od ON pov.option_id = od.option_id
             WHERE ovd.language_id = '" . (int)$this->config->get('config_language_id') . "'
              AND od.language_id = '" . (int)$this->config->get('config_language_id') . "'
              AND pov.product_id = " . $id ;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProductImages($product_id) {

        $main_image = $this->db->query("SELECT p.image, pd.description FROM `" . DB_PREFIX . "product` p 
                                        LEFT JOIN `" . DB_PREFIX . "product_description` pd ON p.product_id = pd.product_id 
                                        WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        $all_images = $this->db->query("SELECT * FROM `" . DB_PREFIX . "product_image` WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");

        $response['description'] = $main_image->row['description'];
	    $all_images->rows[] = ['product_image_id' => -1, 'image' => $main_image->row['image']];
	    $response['images'] = array_reverse($all_images->rows);

        return $response;
    }

    public function getDefaultCurrency(){
        $sql = "SELECT c.value code FROM `" . DB_PREFIX . "setting` c WHERE  c.key = 'config_currency'";
        $query = $this->db->query($sql);
        return $query->row['code'];
    }

    public function setUserDeviceToken($user_id, $token,$os_type){
        $sql = "INSERT INTO `" . DB_PREFIX . "user_device_mob_api` (user_id, device_token,os_type) 
                VALUES (" . $user_id . ",'" . $token . "','" . $os_type . "') ";
        $this->db->query($sql);
        return;
    }
    public function getUserDevices(){
        $sql = "SELECT device_token,os_type FROM `" . DB_PREFIX . "user_device_mob_api`  ";
        $query = $this->db->query($sql);
        return $query->rows;
    }
    public function deleteUserDeviceToken($token){
        $sql = "DELETE FROM `" . DB_PREFIX . "user_device_mob_api`  WHERE  device_token = '". $token."'";
	    $query = $this->db->query($sql);
	    $sql = "SELECT * FROM `" . DB_PREFIX . "user_device_mob_api` WHERE device_token = '". $token ."';";
        $query = $this->db->query($sql);
        return $query->rows;
    }
	public function findUserToken($token){
        $sql = "SELECT *  FROM " . DB_PREFIX . "user_device_mob_api WHERE device_token = '".$token."' ";
        $query = $this->db->query($sql);
        return $query->rows;
    }

	public function updateUserDeviceToken($old, $new){
		$sql = "UPDATE `" . DB_PREFIX . "user_device_mob_api` SET device_token = '". $new ."' WHERE  device_token = '". $old ."';";

		$query = $this->db->query($sql);

		$sql = "SELECT * FROM `" . DB_PREFIX . "user_device_mob_api` WHERE device_token = '". $new ."';";
		$query = $this->db->query($sql);
		return $query->rows;
	}

	public function setProductQuantity($quantity, $product_id){
		$sql = "UPDATE `" . DB_PREFIX . "product` SET quantity = '". $quantity ."' WHERE  product_id = '". $product_id ."';";

		$this->db->query($sql);

		$sql = "SELECT quantity FROM `" . DB_PREFIX . "product` WHERE product_id = '". $product_id ."';";
		$query = $this->db->query($sql);
		return $query->row['quantity'];
	}

	public function addProduct($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "product SET 
		model = '" . $this->db->escape($data['model']) . "', 
		sku = '" . $this->db->escape($data['sku']) . "', 
	    stock_status_id = '". (int)$data['stock_status_id']."',  
		quantity = '" . (int)$data['quantity'] . "', 
		status = '" . (int)$data['status'] . "', 
	
		price = '" . (float)$data['price'] . "',
	
		date_added = NOW()");

		$product_id = $this->db->getLastId();

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "'
			 WHERE product_id = '" . (int)$product_id . "'");
		}

		foreach ($data['product_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET 
			product_id = '" . (int)$product_id . "', 
			language_id = '" . (int)$language_id . "', 
			name = '" . $this->db->escape($value['name']) . "', 
			description = '" . $this->db->escape($value['description']) . "'		
			");

		}

		if (isset($data['product_store'])) {
			foreach ($data['product_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET 
				product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		if (isset($data['product_attribute'])) {
			foreach ($data['product_attribute'] as $product_attribute) {
				if ($product_attribute['attribute_id']) {
					// Removes duplicates
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

					foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
						$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "' AND language_id = '" . (int)$language_id . "'");

						$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
					}
				}
			}
		}

    /*
     * Add product options.
     */
    if (isset($data['product_option'])) {

      foreach ($data['product_option'] as $option_id => $option_value_ids) {

        /*
         * Verify that the option id is present in the database.
         */
        $option_id_is_correct_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "option` WHERE option_id = '" . (int)$option_id . "'");
        $option_id_is_correct =  $option_id_is_correct_query->row['total'];

        if ($option_id_is_correct) {

          $this->db->query("INSERT INTO `" . DB_PREFIX . "product_option` (product_id, option_id) VALUES ('" . (int)$product_id . "', '" . (int)$option_id . "')");
          $product_option_id = $this->db->getLastId();

          foreach ($option_value_ids as $option_value_id) {

            /*
             * Check if the given option id is allowed to be associated with the given option value id
             */
            $option_value_id_is_correct_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "option_value` WHERE option_id = '" . (int)$option_id . "' AND option_value_id = '" . (int)$option_value_id . "'");
            $option_value_id_is_correct = $option_value_id_is_correct_query->row['total'];

            if ($option_value_id_is_correct) {

              /*
               * Register the option id, option value id and the product id in the database.
               */

              $this->db->query("INSERT INTO `" . DB_PREFIX . "product_option_value` (
                product_option_id,
                product_id,
                option_id,
                option_value_id
              ) VALUES ( " .
                "'" . (int)$product_option_id . "'," .
                "'" . (int)$product_id . "'," .
                "'" .  (int)$option_id . "'," . 
                "'" .  (int)$option_value_id . "')"
              );
            }
          }
        }
      }
    }

		if (isset($data['product_discount'])) {
			foreach ($data['product_discount'] as $product_discount) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
			}
		}

		if (isset($data['product_special'])) {
			foreach ($data['product_special'] as $product_special) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
			}
		}

		if (isset($data['product_image'])) {
			foreach ($data['product_image'] as $product_image) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET 
				product_id = '" . (int)$product_id . "', 
				image = '" . $this->db->escape($product_image) . "', 
				sort_order = '" . 0 . "'");
			}
		}

		if (isset($data['product_download'])) {
			foreach ($data['product_download'] as $download_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int)$product_id . "', download_id = '" . (int)$download_id . "'");
			}
		}

		if (isset($data['product_category'])) {
			foreach ($data['product_category'] as $category_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
			}
		}

		if (isset($data['product_filter'])) {
			foreach ($data['product_filter'] as $filter_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$product_id . "', filter_id = '" . (int)$filter_id . "'");
			}
		}

		if (isset($data['product_related'])) {
			foreach ($data['product_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
			}
		}

		if (isset($data['product_reward'])) {
			foreach ($data['product_reward'] as $customer_group_id => $product_reward) {
				if ((int)$product_reward['points'] > 0) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$product_reward['points'] . "'");
				}
			}
		}

		if (isset($data['product_layout'])) {
			foreach ($data['product_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}
		/*
				if ($data['keyword']) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET
					query = 'product_id=" . (int)$product_id . "',
					keyword = '" . $this->db->escape($data['keyword']) . "'");
				}
		*/
		if (isset($data['product_recurring'])) {
			foreach ($data['product_recurring'] as $recurring) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_recurring` SET `product_id` = " . (int)$product_id . ", customer_group_id = " . (int)$recurring['customer_group_id'] . ", `recurring_id` = " . (int)$recurring['recurring_id']);
			}
		}

		$this->cache->delete('product');

		return $product_id;
	}

	public function editProduct($product_id, $data) {
        if ( !isset($data['price_old']) ) {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', quantity = '" . (int)$data['quantity'] . "', price = '" . (float)$data['price'] . "',  status = '" . (int)$data['status'] . "',  stock_status_id = '" . (int)$data['stock_status_id'] . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");
        } else {
            $this->db->query("UPDATE " . DB_PREFIX . "product SET model = '" . $this->db->escape($data['model']) . "', sku = '" . $this->db->escape($data['sku']) . "', quantity = '" . (int)$data['quantity'] . "',  status = '" . (int)$data['status'] . "',  stock_status_id = '" . (int)$data['stock_status_id'] . "', date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");
        }

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "product SET image = '" . $this->db->escape($data['image']) . "' WHERE product_id = '" . (int)$product_id . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "product_description WHERE product_id = '" . (int)$product_id . "'");

		foreach ($data['product_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "product_description SET 
			product_id = '" . (int)$product_id . "', language_id = '" . (int)$language_id . "', 
			name = '" . $this->db->escape($value['name']) . "', 
			description = '" . $this->db->escape($value['description']) . "'");
		}


		if (isset($data['product_store'])) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_store WHERE product_id = '" . (int)$product_id . "'");

			foreach ($data['product_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_store SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "'");
			}
		}


		if (!empty($data['product_attribute'])) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "'");

			foreach ($data['product_attribute'] as $product_attribute) {
				if ($product_attribute['attribute_id']) {
					// Removes duplicates
					$this->db->query("DELETE FROM " . DB_PREFIX . "product_attribute WHERE product_id = '" . (int)$product_id . "' AND attribute_id = '" . (int)$product_attribute['attribute_id'] . "'");

					foreach ($product_attribute['product_attribute_description'] as $language_id => $product_attribute_description) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_attribute SET product_id = '" . (int)$product_id . "', attribute_id = '" . (int)$product_attribute['attribute_id'] . "', language_id = '" . (int)$language_id . "', text = '" .  $this->db->escape($product_attribute_description['text']) . "'");
					}
				}
			}
		}

    /*
     * Update product options.
     */
    if (isset($data['product_option'])) {

      $this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
      $this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");

      foreach ($data['product_option'] as $option_id => $option_value_ids) {

        /*
         * Verify that the option id is present in the database.
         */
        $option_id_is_correct_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "option` WHERE option_id = '" . (int)$option_id . "'");
        $option_id_is_correct =  $option_id_is_correct_query->row['total'];

        if ($option_id_is_correct) {

          /*
           * Check if the given option id is already associated with the product id.
           * If not, associate the given option id with the given product id.
           */
          $product_option_id_query = $this->db->query("SELECT product_option_id FROM `" . DB_PREFIX . "product_option` WHERE option_id = '" . (int)$option_id . "' AND product_id = '" . (int)$product_id . "'");

          if (isset($product_option_id_query->row['product_option_id'])) {
            
            $product_option_id = $product_option_id_query->row['product_option_id'];

          } else{

            $this->db->query("INSERT INTO `" . DB_PREFIX . "product_option` (product_id, option_id) VALUES ('" . (int)$product_id . "', '" . (int)$option_id . "')");
            $product_option_id = $this->db->getLastId();

          }

          foreach ($option_value_ids as $option_value_id) {

            /*
             * Check if the given option id is allowed to be associated with the given option value id
             */
            $option_value_id_is_correct_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "option_value` WHERE option_id = '" . (int)$option_id . "' AND option_value_id = '" . (int)$option_value_id . "'");
            $option_value_id_is_correct = $option_value_id_is_correct_query->row['total'];

            if ($option_value_id_is_correct) {

              /*
               * Register the option id, option value id and the product id in the database.
               */

              $this->db->query("INSERT INTO `" . DB_PREFIX . "product_option_value` (
                product_option_id,
                product_id,
                option_id,
                option_value_id
              ) VALUES ( " .
                "'" . (int)$product_option_id . "'," .
                "'" . (int)$product_id . "'," .
                "'" .  (int)$option_id . "'," . 
                "'" .  (int)$option_value_id . "')"
              );
            }
          }
        }
      }
    }

		if (isset($data['product_discount'])) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_discount WHERE product_id = '" . (int)$product_id . "'");

			foreach ($data['product_discount'] as $product_discount) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_discount SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_discount['customer_group_id'] . "', quantity = '" . (int)$product_discount['quantity'] . "', priority = '" . (int)$product_discount['priority'] . "', price = '" . (float)$product_discount['price'] . "', date_start = '" . $this->db->escape($product_discount['date_start']) . "', date_end = '" . $this->db->escape($product_discount['date_end']) . "'");
			}
		}


		if (isset($data['product_special'])) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_special WHERE product_id = '" . (int)$product_id . "'");

			foreach ($data['product_special'] as $product_special) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_special SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$product_special['customer_group_id'] . "', priority = '" . (int)$product_special['priority'] . "', price = '" . (float)$product_special['price'] . "', date_start = '" . $this->db->escape($product_special['date_start']) . "', date_end = '" . $this->db->escape($product_special['date_end']) . "'");
			}
		}


		if (isset($data['product_image'])) {
			///$this->db->query("DELETE FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "'");

			foreach ($data['product_image'] as $product_image) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_image SET 
				product_id = '" . (int)$product_id . "', 
				image = '" . $this->db->escape($product_image) . "', 
				sort_order = '" . 0 . "'");
			}
		}


		if (isset($data['product_download'])) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_download WHERE product_id = '" . (int)$product_id . "'");

			foreach ($data['product_download'] as $download_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_download SET product_id = '" . (int)$product_id . "', download_id = '" . (int)$download_id . "'");
			}
		}


		if (isset($data['product_category'])) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_category WHERE product_id = '" . (int)$product_id . "'");

			foreach ($data['product_category'] as $category_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_category SET product_id = '" . (int)$product_id . "', category_id = '" . (int)$category_id . "'");
			}
		}


		if (isset($data['product_filter'])) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_filter WHERE product_id = '" . (int)$product_id . "'");

			foreach ($data['product_filter'] as $filter_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_filter SET product_id = '" . (int)$product_id . "', filter_id = '" . (int)$filter_id . "'");
			}
		}


		if (isset($data['product_related'])) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE related_id = '" . (int)$product_id . "'");

			foreach ($data['product_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$product_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$product_id . "', related_id = '" . (int)$related_id . "'");
				$this->db->query("DELETE FROM " . DB_PREFIX . "product_related WHERE product_id = '" . (int)$related_id . "' AND related_id = '" . (int)$product_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_related SET product_id = '" . (int)$related_id . "', related_id = '" . (int)$product_id . "'");
			}
		}


		if (isset($data['product_reward'])) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_reward WHERE product_id = '" . (int)$product_id . "'");

			foreach ($data['product_reward'] as $customer_group_id => $value) {
				if ((int)$value['points'] > 0) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_reward SET product_id = '" . (int)$product_id . "', customer_group_id = '" . (int)$customer_group_id . "', points = '" . (int)$value['points'] . "'");
				}
			}
		}


		if (isset($data['product_layout'])) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_to_layout WHERE product_id = '" . (int)$product_id . "'");

			foreach ($data['product_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "product_to_layout SET product_id = '" . (int)$product_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}


		if (isset($data['keyword'])) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "url_alias WHERE query = 'product_id=" . (int)$product_id . "'");

			$this->db->query("INSERT INTO " . DB_PREFIX . "url_alias SET query = 'product_id=" . (int)$product_id . "', keyword = '" . $this->db->escape($data['keyword']) . "'");
		}


		if (isset($data['product_recurring'])) {
			$this->db->query("DELETE FROM `" . DB_PREFIX . "product_recurring` WHERE product_id = " . (int)$product_id);

			foreach ($data['product_recurring'] as $product_recurring) {
				$this->db->query("INSERT INTO `" . DB_PREFIX . "product_recurring` SET `product_id` = " . (int)$product_id . ", customer_group_id = " . (int)$product_recurring['customer_group_id'] . ", `recurring_id` = " . (int)$product_recurring['recurring_id']);
			}
		}

		$this->cache->delete('product');
	}

	public function updateProduct($data = array()){
		// print_r($data);die;
		foreach ($data as $table => $fields_data){
			if($table != 'categories'){
				if(!empty($fields_data) && is_array($fields_data)){
					$update = '';
					$values = '';
					$fields = '';
					if(isset($data['product_id']) && $data['product_id']!=0){
						$values = $data['product_id'] . ', ';
						$fields = 'product_id, ';
					}
					if($table == 'product_description') {
						$values .=  $data['language_id'] . ', ';
						$fields .= ' language_id, ';
					}
					foreach ($fields_data as $key => $value){

						if(end($fields_data) == $value && $table == 'product_description'){
							$values .= '"'.$value.'"';
							$fields .= $key;
							$update .= $key.' = "'.$value.'"';
						}else{
							$values .= '"'.$value.'", ';
							$fields .= $key.', ';
							$update .= $key.' = "'.$value.'", ';
						}
					}
					if($table == 'product') {
						if(empty($data['product_id'])){
							$values .= ' NOW(),';
							$fields .= ' date_added,';
						}
						$values .= ' NOW()';
						$fields .= ' date_modified';
						$update .= ' date_modified = NOW()';
					}

					$sql = 'INSERT INTO `' . DB_PREFIX .$table.'` ('.$fields.') VALUES ('.$values.') 
                            ON DUPLICATE KEY UPDATE '.$update;
					// print_r($sql);die;
					$this->db->query($sql);
				}
			}
		}

		if(!empty($data['categories'])){
			$sql = 'DELETE FROM `' . DB_PREFIX .'product_to_category` WHERE product_id ='.$data['product_id'];
			$this->db->query($sql);
			foreach ($data['categories'] as $fd){
				$sql = 'INSERT INTO `' . DB_PREFIX .'product_to_category` (product_id, category_id) VALUES ('.$data['product_id'].', '.$fd.')';
				$this->db->query($sql);
			}
		}
	}

	public function addProductImages($new_images, $product_id){
		$images = [];
		foreach ($new_images as $image){
			$this->db->query("INSERT INTO `" . DB_PREFIX . "product_image` (product_id, image) 
            VALUES (" . $product_id . ",'" . $image. "')");
			$val = [];
			$val['image_id'] = $this->db->getLastId();
			$val['image'] = $image;
			$images[] = $val;
		}
		return $images;
	}

	public function removeProductImages($removed_image, $product_id){

		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_image` WHERE product_id = " . $product_id . " AND image = '" . $removed_image . "'");

	}

	public function removeProductImageById($image_id, $product_id){

		$this->db->query("DELETE FROM `" . DB_PREFIX . "product_image` WHERE product_id = " . $product_id . " AND product_image_id = '" . $image_id . "'");

	}
	public function removeProductMainImage($product_id){

		$this->db->query("UPDATE `" . DB_PREFIX . "product` SET image = ' ' WHERE product_id = " . $product_id);
		$sql = "SELECT image FROM `" . DB_PREFIX . "product` WHERE product_id = '". $product_id ."'";
		$query = $this->db->query($sql);
		return $query->row['image'];

	}
	public function getStockStatuses(){
		$query = $this->db->query("SELECT stock_status_id status_id, name FROM `" . DB_PREFIX . "stock_status` WHERE language_id = ".(int)$this->config->get('config_language_id'));
		return $query->rows;
	}

	public function setMainImage($main_image, $product_id){
		$this->db->query("UPDATE `" . DB_PREFIX . "product` SET image = '" . $main_image . "' WHERE product_id = " . $product_id);

		$sql = "SELECT image FROM `" . DB_PREFIX . "product` WHERE product_id = '". $product_id ."'";
		$query = $this->db->query($sql);
		return $query->row['image'];
	}

	public function setMainImageByImageId($image_id, $product_id){
		$new_main_image = $this->db->query("SELECT image FROM `" . DB_PREFIX . "product_image` 
            WHERE product_id = '". $product_id ."' AND product_image_id = ".$image_id)->row['image'];


		$old_main_image = $this->db->query("SELECT image FROM `" . DB_PREFIX . "product` 
            WHERE product_id = ". $product_id)->row['image'];

		$this->db->query("UPDATE `" . DB_PREFIX . "product` SET image = '" . $new_main_image . "' 
        	WHERE product_id = " . $product_id);


		if(trim($old_main_image)!=""){
			$sql = "UPDATE `" . DB_PREFIX . "product_image` SET image = '" . $old_main_image . "' WHERE product_id = " . $product_id ." AND product_image_id = ".$image_id;

			$this->db->query($sql);
		}else{
			$sql = "DELETE FROM `" . DB_PREFIX . "product_image` WHERE product_image_id = ".$image_id;

			$this->db->query($sql);
		}    }

	public function getProductCategoriesMain($product_id){

		$query = $this->db->query("SELECT cd.name, cd.category_id, c.parent_id FROM `" . DB_PREFIX . "product_to_category` ptc
                LEFT JOIN `" . DB_PREFIX . "category` c ON ptc.category_id = c.category_id 
                LEFT JOIN `" . DB_PREFIX . "category_description` cd ON c.category_id = cd.category_id  
                WHERE cd.language_id = ".(int)$this->config->get('config_language_id')."
                 AND  ptc.product_id = " . $product_id ." LIMIT 0,1");
		$return =  $query->rows;

		return $return;
	}

	public $ar = [];
	public $categories = [];
	public function getProductCategories($product_id){

		$query = $this->db->query("SELECT cd.name, cd.category_id, c.parent_id FROM `" . DB_PREFIX . "product_to_category` ptc
                LEFT JOIN `" . DB_PREFIX . "category` c ON ptc.category_id = c.category_id 
                LEFT JOIN `" . DB_PREFIX . "category_description` cd ON c.category_id = cd.category_id  
                WHERE cd.language_id = ".(int)$this->config->get('config_language_id')."
                 AND  ptc.product_id = " . $product_id);
		$cats =  $query->rows;

		$query = $this->db->query("SELECT cd.name, cd.category_id category_id,c.parent_id  FROM  `" . DB_PREFIX . "category` c  
                LEFT JOIN `" . DB_PREFIX . "category_description` cd ON c.category_id = cd.category_id  
                WHERE  cd.language_id = ".(int)$this->config->get('config_language_id'). " ORDER BY cd.category_id ASC ");

		$categories  = $query->rows;

		foreach ($categories as $cat):
			$this->categories[$cat['category_id']] = $cat;
		endforeach;

		$return = [];
		foreach ($cats as $one):
			$this->ar = [];
			$category = [];
			$category['category_id'] = $one['category_id'];
			$category['name'] = $this->categoryTree($one['category_id']);
			$return[] = $category;
		endforeach;

		foreach ($return as $k => $one):
			$name = implode(' - ',array_reverse($one['name']));
			$return[$k]['name'] = $name;
		endforeach;
		sort($return);
		return $return;
	}

	public function categoryTree($id){
		if($this->categories[$id]['parent_id'] != 0){
			$this->ar[] = $this->categories[$id]['name'];
			$this->categoryTree($this->categories[$id]['parent_id']);
		}else{
			$this->ar[] = $this->categories[$id]['name'];
		}
		return $this->ar;
	}

	public function getCategories(){

		$query = $this->db->query("SELECT cd.name, cd.category_id category_id FROM  `" . DB_PREFIX . "category` c  
                LEFT JOIN `" . DB_PREFIX . "category_description` cd ON c.category_id = cd.category_id  
                WHERE c.top = 1 AND cd.language_id = ".(int)$this->config->get('config_language_id'));

		$categories = $query->rows;

		$query = $this->db->query("SELECT c.parent_id FROM  `" . DB_PREFIX . "category` c  ");
		$parents = $query->rows;
		$array =  array_map(function($v) { return $v['parent_id']; },$parents);
		$return = [];
		foreach ($categories as $one):
			$cat = [];
			$cat['name'] = $one['name'];
			$cat['category_id'] = $one['category_id'];
			if(in_array($one['category_id'],$array)){
				$cat['parent'] = true;
			}else{
				$cat['parent'] = false;
			}
			$return[] = $cat;
		endforeach;

		return $return;
	}
	public function getCategoriesById($id){

		$query = $this->db->query("SELECT cd.name, cd.category_id category_id FROM  `" . DB_PREFIX . "category` c  LEFT JOIN `" . DB_PREFIX . "category_description` cd ON c.category_id = cd.category_id  WHERE c.parent_id = ".$id." AND cd.language_id = ".(int)$this->config->get('config_language_id'));
		return $query->rows;
	}

	public function getSubstatus(){

		$query = $this->db->query("SELECT c.name, c.stock_status_id status_id FROM  `" . DB_PREFIX . "stock_status` c  
					WHERE c.language_id = ".(int)$this->config->get('config_language_id'));
		return $query->rows;
	}


    public function getOrder($order_id) {
        $order_query = $this->db->query("SELECT *, (SELECT os.name FROM `" . DB_PREFIX . "order_status` os WHERE os.order_status_id = o.order_status_id AND os.language_id = o.language_id) AS order_status FROM `" . DB_PREFIX . "order` o WHERE o.order_id = '" . (int)$order_id . "'");

        if ($order_query->num_rows) {
            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['payment_country_id'] . "'");

            if ($country_query->num_rows) {
                $payment_iso_code_2 = $country_query->row['iso_code_2'];
                $payment_iso_code_3 = $country_query->row['iso_code_3'];
            } else {
                $payment_iso_code_2 = '';
                $payment_iso_code_3 = '';
            }

            $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['payment_zone_id'] . "'");

            if ($zone_query->num_rows) {
                $payment_zone_code = $zone_query->row['code'];
            } else {
                $payment_zone_code = '';
            }

            $country_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "country` WHERE country_id = '" . (int)$order_query->row['shipping_country_id'] . "'");

            if ($country_query->num_rows) {
                $shipping_iso_code_2 = $country_query->row['iso_code_2'];
                $shipping_iso_code_3 = $country_query->row['iso_code_3'];
            } else {
                $shipping_iso_code_2 = '';
                $shipping_iso_code_3 = '';
            }

            $zone_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "zone` WHERE zone_id = '" . (int)$order_query->row['shipping_zone_id'] . "'");

            if ($zone_query->num_rows) {
                $shipping_zone_code = $zone_query->row['code'];
            } else {
                $shipping_zone_code = '';
            }

            $this->load->model('localisation/language');

            $language_info = $this->model_localisation_language->getLanguage($order_query->row['language_id']);

            if ($language_info) {
                $language_code = $language_info['code'];
                $language_directory = $language_info['directory'];
            } else {
                $language_code = '';
                $language_directory = '';
            }

            return array(
                'order_id'                => $order_query->row['order_id'],
                'invoice_no'              => $order_query->row['invoice_no'],
                'invoice_prefix'          => $order_query->row['invoice_prefix'],
                'store_id'                => $order_query->row['store_id'],
                'store_name'              => $order_query->row['store_name'],
                'store_url'               => $order_query->row['store_url'],
                'customer_id'             => $order_query->row['customer_id'],
                'firstname'               => $order_query->row['firstname'],
                'lastname'                => $order_query->row['lastname'],
                'email'                   => $order_query->row['email'],
                'telephone'               => $order_query->row['telephone'],
                'fax'                     => $order_query->row['fax'],
                'custom_field'            => unserialize($order_query->row['custom_field']),
                'payment_firstname'       => $order_query->row['payment_firstname'],
                'payment_lastname'        => $order_query->row['payment_lastname'],
                'payment_company'         => $order_query->row['payment_company'],
                'payment_address_1'       => $order_query->row['payment_address_1'],
                'payment_address_2'       => $order_query->row['payment_address_2'],
                'payment_postcode'        => $order_query->row['payment_postcode'],
                'payment_city'            => $order_query->row['payment_city'],
                'payment_zone_id'         => $order_query->row['payment_zone_id'],
                'payment_zone'            => $order_query->row['payment_zone'],
                'payment_zone_code'       => $payment_zone_code,
                'payment_country_id'      => $order_query->row['payment_country_id'],
                'payment_country'         => $order_query->row['payment_country'],
                'payment_iso_code_2'      => $payment_iso_code_2,
                'payment_iso_code_3'      => $payment_iso_code_3,
                'payment_address_format'  => $order_query->row['payment_address_format'],
                'payment_custom_field'    => unserialize($order_query->row['payment_custom_field']),
                'payment_method'          => $order_query->row['payment_method'],
                'payment_code'            => $order_query->row['payment_code'],
                'shipping_firstname'      => $order_query->row['shipping_firstname'],
                'shipping_lastname'       => $order_query->row['shipping_lastname'],
                'shipping_company'        => $order_query->row['shipping_company'],
                'shipping_address_1'      => $order_query->row['shipping_address_1'],
                'shipping_address_2'      => $order_query->row['shipping_address_2'],
                'shipping_postcode'       => $order_query->row['shipping_postcode'],
                'shipping_city'           => $order_query->row['shipping_city'],
                'shipping_zone_id'        => $order_query->row['shipping_zone_id'],
                'shipping_zone'           => $order_query->row['shipping_zone'],
                'shipping_zone_code'      => $shipping_zone_code,
                'shipping_country_id'     => $order_query->row['shipping_country_id'],
                'shipping_country'        => $order_query->row['shipping_country'],
                'shipping_iso_code_2'     => $shipping_iso_code_2,
                'shipping_iso_code_3'     => $shipping_iso_code_3,
                'shipping_address_format' => $order_query->row['shipping_address_format'],
                'shipping_custom_field'   => unserialize($order_query->row['shipping_custom_field']),
                'shipping_method'         => $order_query->row['shipping_method'],
                'shipping_code'           => $order_query->row['shipping_code'],
                'comment'                 => $order_query->row['comment'],
                'total'                   => $order_query->row['total'],
                'order_status_id'         => $order_query->row['order_status_id'],
                'order_status'            => $order_query->row['order_status'],
                'affiliate_id'            => $order_query->row['affiliate_id'],
                'commission'              => $order_query->row['commission'],
                'language_id'             => $order_query->row['language_id'],
                'language_code'           => $language_code,
                'language_directory'      => $language_directory,
                'currency_id'             => $order_query->row['currency_id'],
                'currency_code'           => $order_query->row['currency_code'],
                'currency_value'          => $order_query->row['currency_value'],
                'ip'                      => $order_query->row['ip'],
                'forwarded_ip'            => $order_query->row['forwarded_ip'],
                'user_agent'              => $order_query->row['user_agent'],
                'accept_language'         => $order_query->row['accept_language'],
                'date_modified'           => $order_query->row['date_modified'],
                'date_added'              => $order_query->row['date_added']
            );
        } else {
            return false;
        }
    }

    public function addOrderHistory($order_id, $order_status_id, $comment = '', $notify = false) {
        $this->event->trigger('pre.order.history.add', $order_id);

        $order_info = $this->getOrder($order_id);

        if ($order_info) {
            // Fraud Detection
            $this->load->model('account/customer');

            $customer_info = $this->model_account_customer->getCustomer($order_info['customer_id']);

            if ($customer_info && $customer_info['safe']) {
                $safe = true;
            } else {
                $safe = false;
            }

            if ($this->config->get('config_fraud_detection')) {
                $this->load->model('checkout/fraud');

                $risk_score = $this->model_checkout_fraud->getFraudScore($order_info);

                if (!$safe && $risk_score > $this->config->get('config_fraud_score')) {
                    $order_status_id = $this->config->get('config_fraud_status_id');
                }
            }

            // Ban IP
            if (!$safe) {
                $status = false;

                if ($order_info['customer_id']) {
                    $results = $this->model_account_customer->getIps($order_info['customer_id']);

                    foreach ($results as $result) {
                        if ($this->model_account_customer->isBanIp($result['ip'])) {
                            $status = true;

                            break;
                        }
                    }
                } else {
                    $status = $this->model_account_customer->isBanIp($order_info['ip']);
                }

                if ($status) {
                    $order_status_id = $this->config->get('config_order_status_id');
                }
            }

            $this->db->query("UPDATE `" . DB_PREFIX . "order` SET order_status_id = '" . (int)$order_status_id . "', date_modified = NOW() WHERE order_id = '" . (int)$order_id . "'");

            $this->db->query("INSERT INTO " . DB_PREFIX . "order_history SET order_id = '" . (int)$order_id . "', order_status_id = '" . (int)$order_status_id . "', notify = '" . (int)$notify . "', comment = '" . $this->db->escape($comment) . "', date_added = NOW()");

            // If current order status is not processing or complete but new status is processing or complete then commence completing the order
            if (!in_array($order_info['order_status_id'], array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status'))) && in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
                // Stock subtraction
                $order_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

                foreach ($order_product_query->rows as $order_product) {
                    $this->db->query("UPDATE " . DB_PREFIX . "product SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_id = '" . (int)$order_product['product_id'] . "' AND subtract = '1'");

                    $order_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$order_product['order_product_id'] . "'");

                    foreach ($order_option_query->rows as $option) {
                        $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity - " . (int)$order_product['quantity'] . ") WHERE product_option_value_id = '" . (int)$option['product_option_value_id'] . "' AND subtract = '1'");
                    }
                }

                // Redeem coupon, vouchers and reward points
                $order_total_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order ASC");

                foreach ($order_total_query->rows as $order_total) {
                    $this->load->model('total/' . $order_total['code']);

                    if (method_exists($this->{'model_total_' . $order_total['code']}, 'confirm')) {
                        $this->{'model_total_' . $order_total['code']}->confirm($order_info, $order_total);
                    }
                }

                // Add commission if sale is linked to affiliate referral.
                if ($order_info['affiliate_id'] && $this->config->get('config_affiliate_auto')) {
                    $this->load->model('affiliate/affiliate');

                    $this->model_affiliate_affiliate->addTransaction($order_info['affiliate_id'], $order_info['commission'], $order_id);
                }
            }

            // If old order status is the processing or complete status but new status is not then commence restock, and remove coupon, voucher and reward history
            if (in_array($order_info['order_status_id'], array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status'))) && !in_array($order_status_id, array_merge($this->config->get('config_processing_status'), $this->config->get('config_complete_status')))) {
                // Restock
                $product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

                foreach($product_query->rows as $product) {
                    $this->db->query("UPDATE `" . DB_PREFIX . "product` SET quantity = (quantity + " . (int)$product['quantity'] . ") WHERE product_id = '" . (int)$product['product_id'] . "' AND subtract = '1'");

                    $option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$product['order_product_id'] . "'");

                    foreach ($option_query->rows as $option) {
                        $this->db->query("UPDATE " . DB_PREFIX . "product_option_value SET quantity = (quantity + " . (int)$product['quantity'] . ") WHERE product_option_value_id = '" . (int)$option['product_option_value_id'] . "' AND subtract = '1'");
                    }
                }

                // Remove coupon, vouchers and reward points history
                $this->load->model('account/order');

                $order_total_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order ASC");

                foreach ($order_total_query->rows as $order_total) {
                    $this->load->model('total/' . $order_total['code']);

                    if (method_exists($this->{'model_total_' . $order_total['code']}, 'unconfirm')) {
                        $this->{'model_total_' . $order_total['code']}->unconfirm($order_id);
                    }
                }

                // Remove commission if sale is linked to affiliate referral.
                if ($order_info['affiliate_id']) {
                    $this->load->model('affiliate/affiliate');

                    $this->model_affiliate_affiliate->deleteTransaction($order_id);
                }
            }

            $this->cache->delete('product');

            // If order status is 0 then becomes greater than 0 send main html email
            if (!$order_info['order_status_id'] && $order_status_id) {
                // Check for any downloadable products
                $download_status = false;

                $order_product_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_product WHERE order_id = '" . (int)$order_id . "'");

                foreach ($order_product_query->rows as $order_product) {
                    // Check if there are any linked downloads
                    $product_download_query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "product_to_download` WHERE product_id = '" . (int)$order_product['product_id'] . "'");

                    if ($product_download_query->row['total']) {
                        $download_status = true;
                    }
                }

                // Load the language for any mails that might be required to be sent out
                $language = new Language($order_info['language_directory']);
                $language->load('default');
                $language->load('mail/order');

                $order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$order_status_id . "' AND language_id = '" . (int)$order_info['language_id'] . "'");

                if ($order_status_query->num_rows) {
                    $order_status = $order_status_query->row['name'];
                } else {
                    $order_status = '';
                }

                $subject = sprintf($language->get('text_new_subject'), $order_info['store_name'], $order_id);

                // HTML Mail
                $data = array();

                $data['title'] = sprintf($language->get('text_new_subject'), html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'), $order_id);

                $data['text_greeting'] = sprintf($language->get('text_new_greeting'), html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'));
                $data['text_link'] = $language->get('text_new_link');
                $data['text_download'] = $language->get('text_new_download');
                $data['text_order_detail'] = $language->get('text_new_order_detail');
                $data['text_instruction'] = $language->get('text_new_instruction');
                $data['text_order_id'] = $language->get('text_new_order_id');
                $data['text_date_added'] = $language->get('text_new_date_added');
                $data['text_payment_method'] = $language->get('text_new_payment_method');
                $data['text_shipping_method'] = $language->get('text_new_shipping_method');
                $data['text_email'] = $language->get('text_new_email');
                $data['text_telephone'] = $language->get('text_new_telephone');
                $data['text_ip'] = $language->get('text_new_ip');
                $data['text_order_status'] = $language->get('text_new_order_status');
                $data['text_payment_address'] = $language->get('text_new_payment_address');
                $data['text_shipping_address'] = $language->get('text_new_shipping_address');
                $data['text_product'] = $language->get('text_new_product');
                $data['text_model'] = $language->get('text_new_model');
                $data['text_quantity'] = $language->get('text_new_quantity');
                $data['text_price'] = $language->get('text_new_price');
                $data['text_total'] = $language->get('text_new_total');
                $data['text_footer'] = $language->get('text_new_footer');

                $data['logo'] = $this->config->get('config_url') . 'image/' . $this->config->get('config_logo');
                $data['store_name'] = $order_info['store_name'];
                $data['store_url'] = $order_info['store_url'];
                $data['customer_id'] = $order_info['customer_id'];
                $data['link'] = $order_info['store_url'] . 'index.php?route=account/order/info&order_id=' . $order_id;

                if ($download_status) {
                    $data['download'] = $order_info['store_url'] . 'index.php?route=account/download';
                } else {
                    $data['download'] = '';
                }

                $data['order_id'] = $order_id;
                $data['date_added'] = date($language->get('date_format_short'), strtotime($order_info['date_added']));
                $data['payment_method'] = $order_info['payment_method'];
                $data['shipping_method'] = $order_info['shipping_method'];
                $data['email'] = $order_info['email'];
                $data['telephone'] = $order_info['telephone'];
                $data['ip'] = $order_info['ip'];
                $data['order_status'] = $order_status;

                if ($comment && $notify) {
                    $data['comment'] = nl2br($comment);
                } else {
                    $data['comment'] = '';
                }

                if ($order_info['payment_address_format']) {
                    $format = $order_info['payment_address_format'];
                } else {
                    $format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
                }

                $find = array(
                    '{firstname}',
                    '{lastname}',
                    '{company}',
                    '{address_1}',
                    '{address_2}',
                    '{city}',
                    '{postcode}',
                    '{zone}',
                    '{zone_code}',
                    '{country}'
                );

                $replace = array(
                    'firstname' => $order_info['payment_firstname'],
                    'lastname'  => $order_info['payment_lastname'],
                    'company'   => $order_info['payment_company'],
                    'address_1' => $order_info['payment_address_1'],
                    'address_2' => $order_info['payment_address_2'],
                    'city'      => $order_info['payment_city'],
                    'postcode'  => $order_info['payment_postcode'],
                    'zone'      => $order_info['payment_zone'],
                    'zone_code' => $order_info['payment_zone_code'],
                    'country'   => $order_info['payment_country']
                );

                $data['payment_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

                if ($order_info['shipping_address_format']) {
                    $format = $order_info['shipping_address_format'];
                } else {
                    $format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{address_2}' . "\n" . '{city} {postcode}' . "\n" . '{zone}' . "\n" . '{country}';
                }

                $find = array(
                    '{firstname}',
                    '{lastname}',
                    '{company}',
                    '{address_1}',
                    '{address_2}',
                    '{city}',
                    '{postcode}',
                    '{zone}',
                    '{zone_code}',
                    '{country}'
                );

                $replace = array(
                    'firstname' => $order_info['shipping_firstname'],
                    'lastname'  => $order_info['shipping_lastname'],
                    'company'   => $order_info['shipping_company'],
                    'address_1' => $order_info['shipping_address_1'],
                    'address_2' => $order_info['shipping_address_2'],
                    'city'      => $order_info['shipping_city'],
                    'postcode'  => $order_info['shipping_postcode'],
                    'zone'      => $order_info['shipping_zone'],
                    'zone_code' => $order_info['shipping_zone_code'],
                    'country'   => $order_info['shipping_country']
                );

                $data['shipping_address'] = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));

                $this->load->model('tool/upload');

                // Products
                $data['products'] = array();

                foreach ($order_product_query->rows as $product) {
                    $option_data = array();

                    $order_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . (int)$product['order_product_id'] . "'");

                    foreach ($order_option_query->rows as $option) {
                        if ($option['type'] != 'file') {
                            $value = $option['value'];
                        } else {
                            $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                            if ($upload_info) {
                                $value = $upload_info['name'];
                            } else {
                                $value = '';
                            }
                        }

                        $option_data[] = array(
                            'name'  => $option['name'],
                            'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
                        );
                    }

                    $data['products'][] = array(
                        'name'     => $product['name'],
                        'model'    => $product['model'],
                        'option'   => $option_data,
                        'quantity' => $product['quantity'],
                        'price'    => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $order_info['currency_code'], $order_info['currency_value']),
                        'total'    => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value'])
                    );
                }

                // Vouchers
                $data['vouchers'] = array();

                $order_voucher_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_voucher WHERE order_id = '" . (int)$order_id . "'");

                foreach ($order_voucher_query->rows as $voucher) {
                    $data['vouchers'][] = array(
                        'description' => $voucher['description'],
                        'amount'      => $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value']),
                    );
                }

                // Order Totals
                $order_total_query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "order_total` WHERE order_id = '" . (int)$order_id . "' ORDER BY sort_order ASC");

                foreach ($order_total_query->rows as $total) {
                    $data['totals'][] = array(
                        'title' => $total['title'],
                        'text'  => $this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value']),
                    );
                }

                if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/mail/order.tpl')) {
                    $html = $this->load->view($this->config->get('config_template') . '/template/mail/order.tpl', $data);
                } else {
                    $html = $this->load->view('default/template/mail/order.tpl', $data);
                }

                // Can not send confirmation emails for CBA orders as email is unknown
                $this->load->model('payment/amazon_checkout');

                if (!$this->model_payment_amazon_checkout->isAmazonOrder($order_info['order_id'])) {
                    // Text Mail
                    $text  = sprintf($language->get('text_new_greeting'), html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8')) . "\n\n";
                    $text .= $language->get('text_new_order_id') . ' ' . $order_id . "\n";
                    $text .= $language->get('text_new_date_added') . ' ' . date($language->get('date_format_short'), strtotime($order_info['date_added'])) . "\n";
                    $text .= $language->get('text_new_order_status') . ' ' . $order_status . "\n\n";

                    if ($comment && $notify) {
                        $text .= $language->get('text_new_instruction') . "\n\n";
                        $text .= $comment . "\n\n";
                    }

                    // Products
                    $text .= $language->get('text_new_products') . "\n";

                    foreach ($order_product_query->rows as $product) {
                        $text .= $product['quantity'] . 'x ' . $product['name'] . ' (' . $product['model'] . ') ' . html_entity_decode($this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']), ENT_NOQUOTES, 'UTF-8') . "\n";

                        $order_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . $product['order_product_id'] . "'");

                        foreach ($order_option_query->rows as $option) {
                            if ($option['type'] != 'file') {
                                $value = $option['value'];
                            } else {
                                $upload_info = $this->model_tool_upload->getUploadByCode($option['value']);

                                if ($upload_info) {
                                    $value = $upload_info['name'];
                                } else {
                                    $value = '';
                                }
                            }

                            $text .= chr(9) . '-' . $option['name'] . ' ' . (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value) . "\n";
                        }
                    }

                    foreach ($order_voucher_query->rows as $voucher) {
                        $text .= '1x ' . $voucher['description'] . ' ' . $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value']);
                    }

                    $text .= "\n";

                    $text .= $language->get('text_new_order_total') . "\n";

                    foreach ($order_total_query->rows as $total) {
                        $text .= $total['title'] . ': ' . html_entity_decode($this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value']), ENT_NOQUOTES, 'UTF-8') . "\n";
                    }

                    $text .= "\n";

                    if ($order_info['customer_id']) {
                        $text .= $language->get('text_new_link') . "\n";
                        $text .= $order_info['store_url'] . 'index.php?route=account/order/info&order_id=' . $order_id . "\n\n";
                    }

                    if ($download_status) {
                        $text .= $language->get('text_new_download') . "\n";
                        $text .= $order_info['store_url'] . 'index.php?route=account/download' . "\n\n";
                    }

                    // Comment
                    if ($order_info['comment']) {
                        $text .= $language->get('text_new_comment') . "\n\n";
                        $text .= $order_info['comment'] . "\n\n";
                    }

                    $text .= $language->get('text_new_footer') . "\n\n";

                    $mail = new Mail($this->config->get('config_mail'));
                    $mail->setTo($order_info['email']);
                    $mail->setFrom($this->config->get('config_email'));
                    $mail->setSender($order_info['store_name']);
                    $mail->setSubject($subject);
                    $mail->setHtml($html);
                    $mail->setText($text);
                    $mail->send();
                }

                // Admin Alert Mail
                if ($this->config->get('config_order_mail')) {
                    $subject = sprintf($language->get('text_new_subject'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'), $order_id);

                    // HTML Mail
                    $data['text_greeting'] = $language->get('text_new_received');

                    if ($comment) {
                        if ($order_info['comment']) {
                            $data['comment'] = nl2br($comment) . '<br/><br/>' . $order_info['comment'];
                        } else {
                            $data['comment'] = nl2br($comment);
                        }
                    } else {
                        if ($order_info['comment']) {
                            $data['comment'] = $order_info['comment'];
                        } else {
                            $data['comment'] = '';
                        }
                    }
                    $data['text_download'] = '';

                    $data['text_footer'] = '';

                    $data['text_link'] = '';
                    $data['link'] = '';
                    $data['download'] = '';

                    if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/mail/order.tpl')) {
                        $html = $this->load->view($this->config->get('config_template') . '/template/mail/order.tpl', $data);
                    } else {
                        $html = $this->load->view('default/template/mail/order.tpl', $data);
                    }

                    // Text
                    $text  = $language->get('text_new_received') . "\n\n";
                    $text .= $language->get('text_new_order_id') . ' ' . $order_id . "\n";
                    $text .= $language->get('text_new_date_added') . ' ' . date($language->get('date_format_short'), strtotime($order_info['date_added'])) . "\n";
                    $text .= $language->get('text_new_order_status') . ' ' . $order_status . "\n\n";
                    $text .= $language->get('text_new_products') . "\n";

                    foreach ($order_product_query->rows as $product) {
                        $text .= $product['quantity'] . 'x ' . $product['name'] . ' (' . $product['model'] . ') ' . html_entity_decode($this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $order_info['currency_code'], $order_info['currency_value']), ENT_NOQUOTES, 'UTF-8') . "\n";

                        $order_option_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_option WHERE order_id = '" . (int)$order_id . "' AND order_product_id = '" . $product['order_product_id'] . "'");

                        foreach ($order_option_query->rows as $option) {
                            if ($option['type'] != 'file') {
                                $value = $option['value'];
                            } else {
                                $value = utf8_substr($option['value'], 0, utf8_strrpos($option['value'], '.'));
                            }

                            $text .= chr(9) . '-' . $option['name'] . ' ' . (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value) . "\n";
                        }
                    }

                    foreach ($order_voucher_query->rows as $voucher) {
                        $text .= '1x ' . $voucher['description'] . ' ' . $this->currency->format($voucher['amount'], $order_info['currency_code'], $order_info['currency_value']);
                    }

                    $text .= "\n";

                    $text .= $language->get('text_new_order_total') . "\n";

                    foreach ($order_total_query->rows as $total) {
                        $text .= $total['title'] . ': ' . html_entity_decode($this->currency->format($total['value'], $order_info['currency_code'], $order_info['currency_value']), ENT_NOQUOTES, 'UTF-8') . "\n";
                    }

                    $text .= "\n";

                    if ($order_info['comment']) {
                        $text .= $language->get('text_new_comment') . "\n\n";
                        $text .= $order_info['comment'] . "\n\n";
                    }

                    $mail = new Mail($this->config->get('config_mail'));
                    $mail->setTo($this->config->get('config_email'));
                    $mail->setFrom($this->config->get('config_email'));
                    $mail->setReplyTo($order_info['email']);
                    $mail->setSender($order_info['store_name']);
                    $mail->setSubject($subject);
                    $mail->setHtml($html);
                    $mail->setText(html_entity_decode($text, ENT_QUOTES, 'UTF-8'));
                    $mail->send();

                    // Send to additional alert emails
                    $emails = explode(',', $this->config->get('config_mail_alert'));

                    foreach ($emails as $email) {
                        if ($email && preg_match('/^[^\@]+@.*.[a-z]{2,15}$/i', $email)) {
                            $mail->setTo($email);
                            $mail->send();
                        }
                    }
                }
            }

            // If order status is not 0 then send update text email
            if ($order_info['order_status_id'] && $order_status_id) {
                $language = new Language($order_info['language_directory']);
                $language->load('default');
                $language->load('mail/order');

                $subject = sprintf($language->get('text_update_subject'), html_entity_decode($order_info['store_name'], ENT_QUOTES, 'UTF-8'), $order_id);

                $message  = $language->get('text_update_order') . ' ' . $order_id . "\n";
                $message .= $language->get('text_update_date_added') . ' ' . date($language->get('date_format_short'), strtotime($order_info['date_added'])) . "\n\n";

                $order_status_query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE order_status_id = '" . (int)$order_status_id . "' AND language_id = '" . (int)$order_info['language_id'] . "'");

                if ($order_status_query->num_rows) {
                    $message .= $language->get('text_update_order_status') . "\n\n";
                    $message .= $order_status_query->row['name'] . "\n\n";
                }

                if ($order_info['customer_id']) {
                    $message .= $language->get('text_update_link') . "\n";
                    $message .= $order_info['store_url'] . 'index.php?route=account/order/info&order_id=' . $order_id . "\n\n";
                }

                if ($notify && $comment) {
                    $message .= $language->get('text_update_comment') . "\n\n";
                    $message .= strip_tags($comment) . "\n\n";
                }

                $message .= $language->get('text_update_footer');

                $mail = new Mail($this->config->get('config_mail'));
                $mail->setTo($order_info['email']);
                $mail->setFrom($this->config->get('config_email'));
                $mail->setSender($order_info['store_name']);
                $mail->setSubject($subject);
                $mail->setText(html_entity_decode($message, ENT_QUOTES, 'UTF-8'));
                $mail->send();
            }

            // If order status in the complete range create any vouchers that where in the order need to be made available.
            if (in_array($order_info['order_status_id'], $this->config->get('config_complete_status'))) {
                // Send out any gift voucher mails
                $this->load->model('checkout/voucher');

                $this->model_checkout_voucher->confirm($order_id);
            }
        }

        $this->event->trigger('post.order.history.add', $order_id);
    }

    public function getUserCurrency(){
        $sql = "SELECT c.value code FROM `" . DB_PREFIX . "setting` c WHERE  c.key = 'config_currency'";
        $query = $this->db->query($sql);
        return $query->row['code'];
    }

    public function sendNotifications( $output ) {

        header("Access-Control-Allow-Origin: *");
        $id = $output;
        $registrationIds = array();
        $this->load->model('module/apimodule');
        $devices = $this->model_module_apimodule->getUserDevices();
        $ids = [];

        foreach ($devices as $device) {
            if (strtolower($device['os_type']) == 'ios') {
                $ids['ios'][] = $device['device_token'];
            } else {
                $ids['android'][] = $device['device_token'];
            }
        }

        $this->load->model('module/apimodule');
        $order = $this->model_module_apimodule->getOrderFindById($id);
        file_put_contents('order_'.$id.'.txt', $id);
        $msg = array(
            'body' => $this->currency->format($order['total'], $order['currency_code'], $order['currency_value']),
            'title' => "http://" . $_SERVER['HTTP_HOST'],
            'vibrate' => 1,
            'sound' => 1,
            'priority' => 'high',
            'new_order' => [
                'order_id' => $id,
                'total' => $this->currency->format($order['total'], $order['currency_code'], $order['currency_value']),
                'currency_code' => $order['currency_code'],
                'site_url' => "http://" . $_SERVER['HTTP_HOST'],
            ],
            'event_type' => 'new_order'
        );

        $msg_android = array(

            'new_order' => [
                'order_id' => $id,
                'total' => $this->currency->format($order['total'], $order['currency_code'], $order['currency_value']),
                'currency_code' => $order['currency_code'],
                'site_url' => "http://" . $_SERVER['HTTP_HOST'],
            ],
            'event_type' => 'new_order'
        );

        foreach ($ids as $k => $mas):
            if ($k == 'ios') {
                $fields = array
                (
                    'registration_ids' => $ids[$k],
                    'notification' => $msg,
                );
            } else {
                $fields = array
                (
                    'registration_ids' => $ids[$k],
                    'data' => $msg_android
                );
            }
            $this->sendCurl($fields);

        endforeach;
    }

    private function sendCurl($fields)
    {
        $API_ACCESS_KEY = 'AAAAlhKCZ7w:APA91bFe6-ynbVuP4ll3XBkdjar_qlW5uSwkT5olDc02HlcsEzCyGCIfqxS9JMPj7QeKPxHXAtgjTY89Pv1vlu7sgtNSWzAFdStA22Ph5uRKIjSLs5z98Y-Z2TCBN3gl2RLPDURtcepk';
        $headers = array
        (
            'Authorization: key=' . $API_ACCESS_KEY,
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        curl_exec($ch);
        curl_close($ch);
    }

    public function getLanguages() {
        $query = $this->db->query("SELECT c.language_id as id, c.code, c.name FROM  `" . DB_PREFIX . "language` c  
                    WHERE c.status = 1");

        return $query->rows;
    }
}
