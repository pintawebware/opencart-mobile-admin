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
            $this->db->query("INSERT INTO `" . DB_PREFIX . "order_history` (order_id, order_status_id, comment, date_added)  VALUES (" . $orderID . ", " . $statusID . ",\"" . $comment . "\", NOW() ) ");

            $email = $this->db->query("SELECT o.email, o.store_name, o.firstname  FROM `" . DB_PREFIX . "order` AS o WHERE o.order_id = " . $orderID);
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

        $query = $this->db->query("SELECT * FROM (SELECT image, product_id FROM `" . DB_PREFIX . "product`  ) AS p LEFT JOIN (SELECT order_id, product_id, model, quantity, price,  name FROM `" . DB_PREFIX . "order_product` WHERE order_id = " . $id . " ) AS o ON o.product_id = p.product_id LEFT JOIN (SELECT store_url, order_id, total, currency_code FROM `" . DB_PREFIX . "order` WHERE order_id = " . $id . " ) t2 ON o.order_id = t2.order_id LEFT JOIN (SELECT order_id, code, value FROM `" . DB_PREFIX . "order_total` WHERE code = 'shipping' AND order_id = " . $id . " ) t5 ON o.order_id = t5.order_id WHERE o.order_id = " . $id);

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
        $sql = "SELECT p.product_id, p.model, p.quantity, p.image, p.price, pd.name 
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
        $sql = "SELECT p.product_id, p.model, p.quantity,  p.price, pd.name 
						pd.description, p.sku, p.status,
 						ss.name stock_status_name 
				FROM `" . DB_PREFIX . "product` AS p 
				LEFT JOIN `" . DB_PREFIX . "product_description` pd ON p.product_id = pd.product_id 
				LEFT JOIN `" . DB_PREFIX . "stock_status` ss ON p.stock_status_id = ss.stock_status_id 
				WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.product_id = ". $id ;

        $query = $this->db->query($sql);

        return $query->row;
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
			meta_title = '" . $this->db->escape($value['name']) . "', 
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

		if (isset($data['product_option'])) {
			foreach ($data['product_option'] as $product_option) {
				if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
					if (isset($product_option['product_option_value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

						$product_option_id = $this->db->getLastId();

						foreach ($product_option['product_option_value'] as $product_option_value) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
						}
					}
				} else {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
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
		$this->db->query("UPDATE " . DB_PREFIX . "product SET 
		model = '" . $this->db->escape($data['model']) . "', 
		sku = '" . $this->db->escape($data['sku']) . "', 
		quantity = '" . (int)$data['quantity'] . "', 
		price = '" . (float)$data['price'] . "', 
		status = '" . (int)$data['status'] . "', 
		stock_status_id = '" . (int)$data['stock_status_id'] . "', 
	    date_modified = NOW() WHERE product_id = '" . (int)$product_id . "'");

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


		if (isset($data['product_option'])) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_option WHERE product_id = '" . (int)$product_id . "'");
			$this->db->query("DELETE FROM " . DB_PREFIX . "product_option_value WHERE product_id = '" . (int)$product_id . "'");

			foreach ($data['product_option'] as $product_option) {
				if ($product_option['type'] == 'select' || $product_option['type'] == 'radio' || $product_option['type'] == 'checkbox' || $product_option['type'] == 'image') {
					if (isset($product_option['product_option_value'])) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', required = '" . (int)$product_option['required'] . "'");

						$product_option_id = $this->db->getLastId();

						foreach ($product_option['product_option_value'] as $product_option_value) {
							$this->db->query("INSERT INTO " . DB_PREFIX . "product_option_value SET product_option_value_id = '" . (int)$product_option_value['product_option_value_id'] . "', product_option_id = '" . (int)$product_option_id . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', option_value_id = '" . (int)$product_option_value['option_value_id'] . "', quantity = '" . (int)$product_option_value['quantity'] . "', subtract = '" . (int)$product_option_value['subtract'] . "', price = '" . (float)$product_option_value['price'] . "', price_prefix = '" . $this->db->escape($product_option_value['price_prefix']) . "', points = '" . (int)$product_option_value['points'] . "', points_prefix = '" . $this->db->escape($product_option_value['points_prefix']) . "', weight = '" . (float)$product_option_value['weight'] . "', weight_prefix = '" . $this->db->escape($product_option_value['weight_prefix']) . "'");
						}
					}
				} else {
					$this->db->query("INSERT INTO " . DB_PREFIX . "product_option SET product_option_id = '" . (int)$product_option['product_option_id'] . "', product_id = '" . (int)$product_id . "', option_id = '" . (int)$product_option['option_id'] . "', value = '" . $this->db->escape($product_option['value']) . "', required = '" . (int)$product_option['required'] . "'");
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
		$query = $this->db->query("SELECT stock_status_id, name FROM `" . DB_PREFIX . "stock_status` WHERE language_id = ".(int)$this->config->get('config_language_id'));
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

		$query = $this->db->query("SELECT c.name, c.stock_status_id FROM  `" . DB_PREFIX . "stock_status` c  
					WHERE c.language_id = ".(int)$this->config->get('config_language_id'));
		return $query->rows;
	}
}
