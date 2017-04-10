<?php

class ModelModuleApimodule extends Model
{
	private $API_VERSION = 1.8;

	public function getVersion(){
		return $this->API_VERSION;
	}

	public function getMaxOrderPrice()
    {
        $query = $this->db->query("SELECT MAX(total) AS total FROM " . DB_PREFIX . "order  o   WHERE o.order_status_id != 0 ");

        return number_format($query->row['total'], 2, '.','');
    }

    public function getOrders($data = array())
    {

        $sql = "SELECT  * FROM " . DB_PREFIX . "order AS o LEFT JOIN " . DB_PREFIX . "order_status AS s ON o.order_status_id = s.order_status_id  ";
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

        $total_sum = $this->db->query($sql);
        $sum = 0;
        $quantity = 0;
        foreach ($total_sum->rows as $value){
            $sum = $sum + $value['total'];
            $quantity++;
        }

        $sql .= " LIMIT " . (int)$data['limit'] . " OFFSET " . (int)$data['page'];

        $query = $this->db->query($sql);
        $query->totalsumm=$sum;
        $query->quantity=$quantity;
        return $query;
    }


    public function getOrderById($id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order AS o LEFT JOIN " . DB_PREFIX . "order_status AS s ON o.order_status_id = s.order_status_id WHERE order_id = " . $id . " GROUP BY o.order_id ORDER BY o.order_id");
        return $query->rows;
    }

	public function getOrderFindById($id)
	{
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order AS o 
				LEFT JOIN " . DB_PREFIX . "order_status AS s ON o.order_status_id = s.order_status_id 
				WHERE o.order_id = " . $id . " and o.order_status_id != 0 GROUP BY o.order_id ORDER BY o.order_id");
		return $query->row;
	}

    public function AddComment($orderID, $statusID, $comment = '', $inform = false)
    {
        $setStatus = $this->db->query("UPDATE " . DB_PREFIX . "order SET order_status_id = " . $statusID . " WHERE order_id = " . $orderID);
        if ($setStatus === true) {
            $getStatus = $this->db->query("SELECT name, date_added FROM " . DB_PREFIX . "order_status AS s LEFT JOIN " . DB_PREFIX . "order AS o ON o.order_status_id = s.order_status_id WHERE o.order_id = " . $orderID);
            $this->db->query("INSERT INTO " . DB_PREFIX . "order_history (order_id, order_status_id, comment, date_added)  VALUES (" . $orderID . ", " . $statusID . ",\"" . $comment . "\", NOW() ) ");

            $email = $this->db->query("SELECT o.email, o.store_name, o.firstname  FROM " . DB_PREFIX . "order AS o WHERE o.order_id = " . $orderID);
            if($inform == true){
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
                //$mail->setHtml($this->load->view('mail/order', $data));
                $mail->setText('Status of your order changed to ' . $getStatus->row['name'] );
                $mail->send();
            }
        }
        return $getStatus->row;

    }

    public function getProducts($page)
    {
        $sql = "SELECT p.product_id";
        $sql .= " FROM " . DB_PREFIX . "product p";
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";
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
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "user WHERE username = '" . $username . "' AND (password = SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('" . $this->db->escape(htmlspecialchars($password, ENT_QUOTES)) . "'))))) OR password = '" . $this->db->escape(md5($password)) . "') AND status = '1'");

        return $query->row;
    }

    public function setUserToken($id, $token)
    {

        $sql = "INSERT INTO " . DB_PREFIX . "user_token_mob_api (user_id, token)  VALUES (" . $id . ",\"" . $token . " \") ";

        $query = $this->db->query($sql);
        return $query;
    }

    public function getUserToken($id)
    {

        $query = $this->db->query("SELECT token FROM " . DB_PREFIX . "user_token_mob_api WHERE user_id = " . $id);

        return $query->row;
    }

    public function getTokens()
    {

        $query = $this->db->query("SELECT token FROM " . DB_PREFIX . "user_token_mob_api ");

        return $query->rows;
    }


    public function getOrderProducts($id)
    {

        $query = $this->db->query("SELECT * FROM (SELECT image, product_id FROM " . DB_PREFIX . "product  ) AS p LEFT JOIN (SELECT order_id, product_id, model, quantity, price,  name FROM " . DB_PREFIX . "order_product WHERE order_id = " . $id . " ) AS o ON o.product_id = p.product_id LEFT JOIN (SELECT store_url, order_id, total, currency_code FROM " . DB_PREFIX . "order WHERE order_id = " . $id . " ) t2 ON o.order_id = t2.order_id LEFT JOIN (SELECT order_id, code, value FROM " . DB_PREFIX . "order_total WHERE code = 'shipping' AND order_id = " . $id . " ) t5 ON o.order_id = t5.order_id WHERE o.order_id = " . $id);

        return $query->rows;
    }


    public function getProductDiscount($id, $quantity)
    {

        $query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_discount AS pd2 WHERE pd2.product_id = " . $id . " AND pd2.quantity <= " . $quantity . " AND pd2.date_start < NOW() AND pd2.date_end > NOW() LIMIT 1");

        return $query->row;
    }

    public function getOrderHistory($id)
    {
        $query = $this->db->query("SELECT  * FROM " . DB_PREFIX . "order_history h  LEFT JOIN " . DB_PREFIX . "order_status s ON h.order_status_id = s.order_status_id WHERE h.order_id = ". $id ." AND s.name IS NOT NULL GROUP BY h.date_added ORDER BY h.date_added DESC  ");

        return $query->rows;
    }

    public function OrderStatusList()
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order_status WHERE language_id = '" . (int)$this->config->get('config_language_id') . "'");

        return $query->rows;
    }

    public function ChangeOrderDelivery($address, $city, $order_id)
    {

        $sql = "UPDATE " . DB_PREFIX . "order SET shipping_address_1 = \"" . $address . "\" ";
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

        $sql = "SELECT  SUM(o.total) sum, COUNT(o.total) quantity, c.firstname, c.lastname, c.date_added, c.customer_id FROM " . DB_PREFIX . "customer AS c LEFT JOIN " . DB_PREFIX . "order AS o ON c.customer_id = o.customer_id  WHERE c.customer_id != 0 ";

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

        $sql = "SELECT  SUM(o.total) sum, COUNT(o.total) quantity, c.firstname, c.lastname, c.date_added, c.customer_id, c.email, c.telephone FROM " . DB_PREFIX . "customer AS c LEFT JOIN " . DB_PREFIX . "order AS o ON c.customer_id = o.customer_id ";

        $sql .= "  WHERE c.customer_id = ".$id ;
        $sql .= " group by c.customer_id";

        $completed = $this->db->query("SELECT  COUNT(o.total) completed  FROM " . DB_PREFIX . "order AS o LEFT JOIN " . DB_PREFIX . "customer AS c ON c.customer_id = o.customer_id WHERE c.customer_id = ". $id ." AND o.order_status_id = 5 group by c.customer_id");

        $cancelled = $this->db->query("SELECT  COUNT(o.total) cancelled FROM " . DB_PREFIX . "order AS o LEFT JOIN " . DB_PREFIX . "customer AS c ON c.customer_id = o.customer_id WHERE c.customer_id = ". $id ." AND o.order_status_id = 7 group by c.customer_id");

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
            $sql = "SELECT o.order_id, o.total, o.date_added, os.name FROM " . DB_PREFIX . "order AS o LEFT JOIN " . DB_PREFIX . "order_status AS os ON o.order_status_id = os.order_status_id WHERE o.customer_id = " . $id;
            $sql .= " GROUP BY o.order_id ORDER BY " . $sort . " DESC";
            $query = $this->db->query($sql);
        }elseif($sort == 'cancelled'){
            $sql = "SELECT o.order_id, o.total, o.date_added, os.name FROM " . DB_PREFIX . "order AS o LEFT JOIN " . DB_PREFIX . "order_status AS os ON o.order_status_id = os.order_status_id WHERE o.customer_id = " . $id . " AND  os.order_status_id != 7";
            $sql .= " GROUP BY o.order_id ORDER BY o.date_added DESC";
            $query = $this->db->query("SELECT o.order_id, o.total, o.date_added, os.name FROM " . DB_PREFIX . "order AS o LEFT JOIN " . DB_PREFIX . "order_status AS os ON o.order_status_id = os.order_status_id WHERE o.customer_id = " . $id . " AND  os.order_status_id = 7 GROUP BY o.order_id ORDER BY o.date_added DESC");
            $cancelled = $this->db->query($sql);
            foreach ($cancelled->rows as $value){
                $query->rows[] = $value;
            }
        }elseif($sort == 'completed'){
            $sql = "SELECT o.order_id, o.total, o.date_added, os.name FROM " . DB_PREFIX . "order AS o LEFT JOIN " . DB_PREFIX . "order_status AS os ON o.order_status_id = os.order_status_id WHERE o.customer_id = " . $id . " AND  os.order_status_id != 5";
            $sql .= " GROUP BY o.order_id ORDER BY o.date_added DESC";
            $query = $this->db->query("SELECT o.order_id, o.total, o.date_added, os.name FROM " . DB_PREFIX . "order AS o LEFT JOIN " . DB_PREFIX . "order_status AS os ON o.order_status_id = os.order_status_id WHERE o.customer_id = " . $id . " AND  os.order_status_id = 5 GROUP BY o.order_id ORDER BY o.date_added DESC");
            $cancelled = $this->db->query($sql);
            foreach ($cancelled->rows as $value){
                $query->rows[] = $value;
            }
        }



        return $query->rows;
    }

    public function getProductsList ($page, $limit, $name = '')
    {
        $sql = "SELECT p.product_id, p.model, p.quantity, p.image, p.price, pd.name FROM " . DB_PREFIX . "product AS p LEFT JOIN " . DB_PREFIX . "product_description pd ON p.product_id = pd.product_id WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'" ;
        if($name != ''){
            $sql .= " AND (pd.name LIKE \"%" .$name. "%\" OR p.model LIKE \"%" .$name. "%\")";
        }
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$page;

        $query = $this->db->query($sql);

        return $query->rows;
    }

    public function getProductsByID ($id)
    {
        $sql = "SELECT p.product_id, p.model, p.quantity,  p.price, pd.name FROM " . DB_PREFIX . "product AS p LEFT JOIN " . DB_PREFIX . "product_description pd ON p.product_id = pd.product_id WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.product_id = ". $id ;

        $query = $this->db->query($sql);

        return $query->row;
    }

    public function getProductImages($product_id) {

        $main_image = $this->db->query("SELECT p.image, pd.description FROM " . DB_PREFIX . "product p LEFT JOIN " . DB_PREFIX . "product_description pd ON p.product_id = pd.product_id WHERE p.product_id = '" . (int)$product_id . "' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
        $images[] = $main_image->row['image'];
        $all_images = $this->db->query("SELECT * FROM " . DB_PREFIX . "product_image WHERE product_id = '" . (int)$product_id . "' ORDER BY sort_order ASC");
        foreach ($all_images->rows as $image){
            $images[]=$image['image'];
        }
        $response['description'] = $main_image->row['description'];
        $response['images'] = $images;

        return $response;
    }

    public function getDefaultCurrency(){
        $sql = "SELECT c.code FROM " . DB_PREFIX . "currency c WHERE  c.value = 1";
        $query = $this->db->query($sql);
        return $query->row['code'];
    }

    public function setUserDeviceToken($user_id, $token,$os_type){
        $sql = "INSERT INTO " . DB_PREFIX . "user_device_mob_api (user_id, device_token,os_type) 
                VALUES (" . $user_id . ",'" . $token . "','" . $os_type . "') ";
        $this->db->query($sql);
        return;
    }
    public function getUserDevices(){
        $sql = "SELECT device_token,os_type FROM " . DB_PREFIX . "user_device_mob_api  ";
        $query = $this->db->query($sql);
        return $query->rows;
    }
    public function deleteUserDeviceToken($token){
        $sql = "DELETE FROM " . DB_PREFIX . "user_device_mob_api  WHERE  device_token = '". $token."'";
	    $query = $this->db->query($sql);
	    $sql = "SELECT * FROM " . DB_PREFIX . "user_device_mob_api WHERE device_token = '". $token ."';";
        $query = $this->db->query($sql);
        return $query->rows;
    }

	public function updateUserDeviceToken($old, $new){
		$sql = "UPDATE " . DB_PREFIX . "user_device_mob_api SET device_token = '". $new ."' WHERE  device_token = '". $old ."';";

		$query = $this->db->query($sql);

		$sql = "SELECT * FROM " . DB_PREFIX . "user_device_mob_api WHERE device_token = '". $new ."';";
		$query = $this->db->query($sql);
		return $query->rows;
	}
}
