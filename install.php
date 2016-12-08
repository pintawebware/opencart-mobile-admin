<?php 

$this->db->query("CREATE TABLE " . DB_PREFIX."user_token_mob_api ( user_id INT NOT NULL PRIMARY KEY, token VARCHAR(32) NOT NULL )");
 //$this->db->query("INSERT INTO oc_user_token_mob_api (user_id, token)  VALUES (1,'75QJTF6zbI84R5I69X6rTqREQ7W6wUcz' ) ");

?>