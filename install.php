<?php 

$this->db->query("CREATE TABLE " . DB_PREFIX."user_token_mob_api ( user_id INT NOT NULL PRIMARY KEY, token VARCHAR(32) NOT NULL )");
$this->db->query("CREATE TABLE " . DB_PREFIX."user_device_mob_api ( user_id INT , device_token VARCHAR(500) )");
$this->load->model('extension/event');
$this->model_extension_event->addEvent('apimodule', 'post.customer.logout', 'module/apimodule/sendNotifications');

 
?>