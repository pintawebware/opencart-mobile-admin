<?php 

$this->db->query("CREATE TABLE " . DB_PREFIX."user_token_mob_api ( user_id INT NOT NULL PRIMARY KEY, token VARCHAR(32) NOT NULL )");
$this->db->query("CREATE TABLE " . DB_PREFIX."user_device_mob_api (user_id INT NOT NULL PRIMARY KEY user_id INT , device_token VARCHAR(500) )");
if(VERSION == '2.0.0.0'){
    $this->load->model('tool/event');
	$this->model_tool_event->addEvent('apimodule', 'post.order.add', 'module/apimodule/sendNotifications');
}else{
	$this->load->model('extension/event');
	$this->model_extension_event->addEvent('apimodule', 'post.order.add', 'module/apimodule/sendNotifications');
}


 
?>