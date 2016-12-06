<?php

class ModelModuleApimoduleApimodule extends Model
{

    public function getOrders()
    {
        $orders = array();
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order ORDER BY order_id");
        // foreach ($query->rows as $result) {
        //   $orders[] = $result;
        //}
        return $query->rows;
    }


    public function getOrderById($id)
    {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "order WHERE order_id = " . $id . " ORDER BY order_id");
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

}