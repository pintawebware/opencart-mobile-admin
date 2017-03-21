<?php 

$this->db->query("CREATE TABLE " . DB_PREFIX."user_token_mob_api (id INT NOT NULL PRIMARY KEY, user_id INT NOT NULL, token VARCHAR(32) NOT NULL )");
$this->db->query("CREATE TABLE " . DB_PREFIX."user_device_mob_api (id INT NOT NULL PRIMARY KEY, user_id INT NOT NULL, device_token VARCHAR(500) )");
if(VERSION == '2.0.0.0'){
    $this->load->model('tool/event');
	$this->model_tool_event->addEvent('apimodule', 'post.order.history.add', 'module/apimodule/sendNotifications');
}else{
	$this->load->model('extension/event');
	$this->model_extension_event->addEvent('apimodule', 'post.order.history.add', 'module/apimodule/sendNotifications');
}


 
?>