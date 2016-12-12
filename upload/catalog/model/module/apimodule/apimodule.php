<?php

class ModelModuleApimoduleApimodule extends Model
{

    public function getOrders()
    {
        $orders = array();
       // $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order AS o  INNER JOIN " . DB_PREFIX . "order_status  ORDER BY o.order_id");
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order AS o LEFT JOIN " . DB_PREFIX . "order_status AS s ON o.order_status_id = s.order_status_id  ORDER BY o.order_id");
        // foreach ($query->rows as $result) {
        //   $orders[] = $result;
        //}
        return $query->rows;
    }


    public function getOrderById($id)
    {
       // $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order WHERE order_id = " . $id . " ORDER BY order_id");
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order AS o LEFT JOIN " . DB_PREFIX . "order_status AS s ON o.order_status_id = s.order_status_id WHERE order_id = " . $id . " ORDER BY order_id");
        return $query->rows;
    }


    public function changeStatus($orderID, $statusId)
    {
        $setStatus = $this->db->query("UPDATE " . DB_PREFIX . "order SET order_status_id = " . $statusId . " WHERE order_id = " . $orderID);
        if ($setStatus === true) {
            $getStatus = $this->db->query("SELECT name FROM " . DB_PREFIX . "order_status AS s LEFT JOIN " . DB_PREFIX . "order AS o ON o.order_status_id = s.order_status_id WHERE o.order_id = 1");
        }
        return $getStatus->row;

    }

    public function getProducts($page)
    {
        //$sql = "SELECT p.product_id, (SELECT AVG(rating) AS total FROM " . DB_PREFIX . "review r1 WHERE r1.product_id = p.product_id AND r1.status = '1' GROUP BY r1.product_id) AS rating, (SELECT price FROM " . DB_PREFIX . "product_discount pd2 WHERE pd2.product_id = p.product_id AND pd2.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND pd2.quantity = '1' AND ((pd2.date_start = '0000-00-00' OR pd2.date_start < NOW()) AND (pd2.date_end = '0000-00-00' OR pd2.date_end > NOW())) ORDER BY pd2.priority ASC, pd2.price ASC LIMIT 1) AS discount, (SELECT price FROM " . DB_PREFIX . "product_special ps WHERE ps.product_id = p.product_id AND ps.customer_group_id = '" . (int)$this->config->get('config_customer_group_id') . "' AND ((ps.date_start = '0000-00-00' OR ps.date_start < NOW()) AND (ps.date_end = '0000-00-00' OR ps.date_end > NOW())) ORDER BY ps.priority ASC, ps.price ASC LIMIT 1) AS special";
        $sql = "SELECT p.product_id";
        $sql .= " FROM " . DB_PREFIX . "product p";
        $sql .= " LEFT JOIN " . DB_PREFIX . "product_description pd ON (p.product_id = pd.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";
        $sql .= " GROUP BY p.product_id";
        $sql .= " ORDER BY p.product_id ASC";
        $sql .= " LIMIT 5 OFFSET ". $page ;
        $query =  $this->db->query($sql);
        $this->load->model('catalog/product');
        foreach ($query->rows as $result) {
            $product_data[$result['product_id']] = $this->model_catalog_product->getProduct($result['product_id']);
        }
        return $product_data;

    }
    public function Login($username, $password) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "user WHERE username = '" . $username . "' AND (password = SHA1(CONCAT(salt, SHA1(CONCAT(salt, SHA1('" . $this->db->escape(htmlspecialchars($password, ENT_QUOTES)) . "'))))) OR password = '" . $this->db->escape(md5($password)) . "') AND status = '1'");

        return $query->row;
    }
    public function setUserToken($id, $token){

        $sql="INSERT INTO " . DB_PREFIX . "user_token_mob_api (user_id, token)  VALUES (" . $id . ",\"" . $token ." \") ";

        $query = $this->db->query($sql);
        return $query;
    }
    public function getUserToken($id){

        $query = $this->db->query("SELECT token FROM " . DB_PREFIX . "user_token_mob_api WHERE user_id = " . $id );

        return $query->row;
    }

    public function getTokens(){

        $query = $this->db->query("SELECT token FROM " . DB_PREFIX . "user_token_mob_api " );

        return $query->rows;
    }


    public function getOrderProducts($id) {

        $query = $this->db->query("SELECT * FROM (SELECT image, product_id FROM " . DB_PREFIX . "product  ) AS p LEFT JOIN (SELECT order_id, product_id, model, quantity, price,  name FROM " . DB_PREFIX . "order_product WHERE order_id = " . $id . " ) AS o ON o.product_id = p.product_id LEFT JOIN (SELECT store_url, order_id, total FROM " . DB_PREFIX . "ORDER WHERE order_id = " . $id . " ) t2 ON o.order_id = t2.order_id LEFT JOIN (SELECT order_id, code, value FROM " . DB_PREFIX . "order_total WHERE code = 'shipping' AND order_id = " . $id . " ) t5 ON o.order_id = t5.order_id WHERE o.order_id = " . $id );

        return $query->rows;
    }


    public function getProductDiscount($id, $quantity){

        $query = $this->db->query("SELECT price FROM " . DB_PREFIX . "product_discount AS pd2 WHERE pd2.product_id = " . $id . " AND pd2.quantity <= " . $quantity . " AND pd2.date_start < NOW() AND pd2.date_end > NOW() LIMIT 1");

        return $query->row;
    }
}