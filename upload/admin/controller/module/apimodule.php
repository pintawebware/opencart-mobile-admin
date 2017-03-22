<?php
class ControllerModuleApimodule extends Controller {
	private $error = array();
	private $API_VERSION = 1.8;

	public function checkVersion(){
		$return = false;
		$version = json_decode(file_get_contents('https://opencartapp.pro/app/index.php'),1);

		if($this->API_VERSION <(float)$version['version']){
			$return = $version['version'];
		}
		return $return;
	}
	public function index() {
		$this->load->language('module/apimodule');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {

			$this->model_setting_setting->editSetting('apimodule', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}


		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		$data['entry_status'] = $this->language->get('entry_status');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_update'] = $this->language->get('button_update');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_module'),
			'href' => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('module/apimodule', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['action'] = $this->url->link('module/apimodule', 'token=' . $this->session->data['token'], 'SSL');

		$data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');

		if (isset($this->request->post['apimodule_status'])) {
			$data['apimodule_status'] = $this->request->post['apimodule_status'];
		} else {
			$data['apimodule_status'] = $this->config->get('apimodule_status');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$data['version'] = $version = $this->checkVersion();
		$data['update'] = $this->url->link('module/apimodule/update&v='.$version, 'token=' . $this->session->data['token'], 'SSL');


		$this->response->setOutput($this->load->view('module/apimodule.tpl', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'module/apimodule')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
	private $path = "update";
	private $fileName = "";
	public function update() {


		if(isset($_GET['v'])) {
			//apimobile1.8.ocmod.zip
			$this->fileName = "apimobile" . $_GET['v'] .".ocmod.zip";
			$file = file_get_contents("https://opencartapp.pro/app/".$this->fileName);
			// If no temp directory exists create it

			if (!is_dir(DIR_UPLOAD . $this->path)) {
				mkdir(DIR_UPLOAD . $this->path, 0777);
			}

			$new_path = DIR_UPLOAD . $this->path ."/" . $this->fileName;
			if(!file_exists($new_path)) {
				file_put_contents( $new_path, $file );
			}

			$this->install($new_path);
		}else{
			$this->error['warning'] = "Not version";
		}
		return $this->error;
	}


	private function install($file) {
		$zip  = zip_open( $file );
		if ( $zip ) {
			// Zip
			$json['step'][] = array(
				'text' => $this->language->get( 'text_unzip' ),
				'url'  => str_replace( '&amp;', '&', $this->url->link( 'extension/installer/unzip', 'token=' . $this->session->data['token'], 'SSL' ) ),
				'path' => $this->path
			);

			// FTP
			$json['step'][] = array(
				'text' => $this->language->get( 'text_ftp' ),
				'url'  => str_replace( '&amp;', '&', $this->url->link( 'extension/installer/ftp', 'token=' . $this->session->data['token'], 'SSL' ) ),
				'path' => $this->path
			);

			// Send make and array of actions to carry out
			while ( $entry = zip_read( $zip ) ) {
				$zip_name = zip_entry_name( $entry );

				// SQL
				if ( substr( $zip_name, 0, 11 ) == 'install.sql' ) {
					$json['step'][] = array(
						'text' => $this->language->get( 'text_sql' ),
						'url'  => str_replace( '&amp;', '&', $this->url->link( 'extension/installer/sql', 'token=' . $this->session->data['token'], 'SSL' ) ),
						'path' => $this->path
					);
				}

				// XML
				if ( substr( $zip_name, 0, 11 ) == 'install.xml' ) {
					$json['step'][] = array(
						'text' => $this->language->get( 'text_xml' ),
						'url'  => str_replace( '&amp;', '&', $this->url->link( 'extension/installer/xml', 'token=' . $this->session->data['token'], 'SSL' ) ),
						'path' => $this->path
					);
				}

				// PHP
				if ( substr( $zip_name, 0, 11 ) == 'install.php' ) {
					$json['step'][] = array(
						'text' => $this->language->get( 'text_php' ),
						'url'  => str_replace( '&amp;', '&', $this->url->link( 'extension/installer/php', 'token=' . $this->session->data['token'], 'SSL' ) ),
						'path' => $this->path
					);
				}

				// Compare admin files
				$file = DIR_APPLICATION . substr( $zip_name, 13 );

				$is =  is_file( $file );
				$iz =  substr( $zip_name, 0, 13 ) == 'upload/admin/';
//				/echo "admin start ".$zip_name. " - ".$file. " ( ". $is ."-". $iz .")<br>";
				if ( is_file( $file ) && substr( $zip_name, 0, 13 ) == 'upload/admin/' ) {
					$json['overwrite'][] = substr( $zip_name, 7 );
				}else{
					//echo "admin false<br>";
				}

				// Compare catalog files
				$file = DIR_CATALOG . substr( $zip_name, 15 );
				//echo "catalog start<br>";
				if ( is_file( $file ) && substr( $zip_name, 0, 15 ) == 'upload/catalog/' ) {
					$json['overwrite'][] = substr( $zip_name, 7 );
				}else{
					//	echo "catalog false<br>";
				}

				// Compare image files
				$file = DIR_IMAGE . substr( $zip_name, 13 );

				if ( is_file( $file ) && substr( $zip_name, 0, 13 ) == 'upload/image/' ) {
					$json['overwrite'][] = substr( $zip_name, 7 );
				}

				// Compare system files
				$file = DIR_SYSTEM . substr( $zip_name, 14 );

				if ( is_file( $file ) && substr( $zip_name, 0, 14 ) == 'upload/system/' ) {
					$json['overwrite'][] = substr( $zip_name, 7 );
				}
			}

			// Clear temporary files
			$json['step'][] = array(
				'text' => $this->language->get( 'text_remove' ),
				'url'  => str_replace( '&amp;', '&', $this->url->link( 'extension/installer/remove', 'token=' . $this->session->data['token'], 'SSL' ) ),
				'path' => $this->path
			);

			zip_close( $zip );
			$this->session->data['success'] = "Модуль успешно обновлен";

			$this->unzip();
			$this->ftp();
			$this->php();
			$this->sql();

			$this->clear();
			/*echo "<pre>";
			print_r($json);
			echo "</pre>";*/
			$this->response->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
		}
	}


	public function unzip() {
		$this->load->language('extension/installer');

		$json = array();
		// Sanitize the filename
		$file = DIR_UPLOAD . str_replace(array('../', '..\\', '..'), '', $this->path) . '/'.$this->fileName;

		if (!file_exists($file)) {
			$json['error'] = $this->language->get('error_file');
		}

		if (!$json) {
			// Unzip the files
			$zip = new ZipArchive();

			if ($zip->open($file)) {
				$zip->extractTo(DIR_UPLOAD . str_replace(array('../', '..\\', '..'), '', $this->path ));
				//	echo DIR_UPLOAD . str_replace(array('../', '..\\', '..'), '', $this->path );

				$zip->close();
			} else {
				$json['error'] = $this->language->get('error_unzip');
			}

			// Remove Zip
			unlink($file);
		}

	}

	public function ftp() {
		$this->load->language('extension/installer');

		$json = array();

		// Check FTP status
		if (!$this->config->get('config_ftp_status')) {
			$json['error'] = $this->language->get('error_ftp_status');
		}

		$directory = DIR_UPLOAD . str_replace(array('../', '..\\', '..'), '', $this->path ) . '/upload/';

		if (!is_dir($directory)) {
			$json['error'] = $this->language->get('error_directory');
		}

		if (!$json) {
			// Get a list of files ready to upload
			$files = array();

			$path = array($directory . '*');

			while (count($path) != 0) {
				$next = array_shift($path);

				foreach ((array)glob($next) as $file) {
					if (is_dir($file)) {
						$path[] = $file . '/*';
					}

					$files[] = $file;
				}
			}

			// Connect to the site via FTP
			$connection = ftp_connect($this->config->get('config_ftp_hostname'), $this->config->get('config_ftp_port'));

			if ($connection) {
				$login = ftp_login($connection, $this->config->get('config_ftp_username'), $this->config->get('config_ftp_password'));

				if ($login) {
					if ($this->config->get('config_ftp_root')) {
						$root = ftp_chdir($connection, $this->config->get('config_ftp_root'));
					} else {
						$root = ftp_chdir($connection, '/');
					}

					if ($root) {
						foreach ($files as $file) {
							$destination = substr($file, strlen($directory));

							// Upload everything in the upload directory
							// Many people rename their admin folder for security purposes which I believe should be an option during installation just like setting the db prefix.
							// the following code would allow you to change the name of the following directories and any extensions installed will still go to the right directory.
							if (substr($destination, 0, 5) == 'admin') {
								$destination = basename(DIR_APPLICATION) . substr($destination, 5);
							}

							if (substr($destination, 0, 7) == 'catalog') {
								$destination = basename(DIR_CATALOG) . substr($destination, 7);
							}

							if (substr($destination, 0, 5) == 'image') {
								$destination = basename(DIR_IMAGE) . substr($destination, 5);
							}

							if (substr($destination, 0, 6) == 'system') {
								$destination = basename(DIR_SYSTEM) . substr($destination, 6);
							}

							if (is_dir($file)) {
								$list = ftp_nlist($connection, substr($destination, 0, strrpos($destination, '/')));

								// Basename all the directories because on some servers they don't return the fulll paths.
								$list_data = array();

								foreach ($list as $list) {
									$list_data[] = basename($list);
								}

								if (!in_array(basename($destination), $list_data)) {
									if (!ftp_mkdir($connection, $destination)) {
										$json['error'] = sprintf($this->language->get('error_ftp_directory'), $destination);
									}
								}
							}

							if (is_file($file)) {
								if (!ftp_put($connection, $destination, $file, FTP_BINARY)) {
									$json['error'] = sprintf($this->language->get('error_ftp_file'), $file);
								}
							}
						}
					} else {
						$json['error'] = sprintf($this->language->get('error_ftp_root'), $root);
					}
				} else {
					$json['error'] = sprintf($this->language->get('error_ftp_login'), $this->config->get('config_ftp_username'));
				}

				ftp_close($connection);
			} else {
				$json['error'] = sprintf($this->language->get('error_ftp_connection'), $this->config->get('config_ftp_hostname'), $this->config->get('config_ftp_port'));
			}
		}
	}

	public function sql() {
		$this->load->language('extension/installer');

		$json = array();

		$file = DIR_UPLOAD . str_replace(array('../', '..\\', '..'), '', $this->path ) . '/install.sql';

		if (!file_exists($file)) {
			$json['error'] = $this->language->get('error_file');
		}

		if (!$json) {
			$lines = file($file);

			if ($lines) {
				try {
					$sql = '';

					foreach ($lines as $line) {
						if ($line && (substr($line, 0, 2) != '--') && (substr($line, 0, 1) != '#')) {
							$sql .= $line;

							if (preg_match('/;\s*$/', $line)) {
								$sql = str_replace(" `oc_", " `" . DB_PREFIX, $sql);

								$this->db->query($sql);

								$sql = '';
							}
						}
					}
				} catch(Exception $exception) {
					$json['error'] = sprintf($this->language->get('error_exception'), $exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine());
				}
			}
		}
	}

	public function xml() {
		$this->load->language('extension/installer');

		$json = array();

		$file = DIR_UPLOAD . str_replace(array('../', '..\\', '..'), '', $this->path ) . '/install.xml';

		if (!file_exists($file)) {
			$json['error'] = $this->language->get('error_file');
		}

		if (!$json) {
			$this->load->model('extension/modification');

			// If xml file just put it straight into the DB
			$xml = file_get_contents($file);

			if ($xml) {
				try {
					$dom = new DOMDocument('1.0', 'UTF-8');
					$dom->loadXml($xml);

					$name = $dom->getElementsByTagName('name')->item(0);

					if ($name) {
						$name = $name->nodeValue;
					} else {
						$name = '';
					}

					$code = $dom->getElementsByTagName('code')->item(0);

					if ($code) {
						$code = $code->nodeValue;

						// Check to see if the modification is already installed or not.
						$modification_info = $this->model_extension_modification->getModificationByCode($code);

						if ($modification_info) {
							$json['error'] = sprintf($this->language->get('error_exists'), $modification_info['name']);
						}
					} else {
						$json['error'] = $this->language->get('error_code');
					}

					$author = $dom->getElementsByTagName('author')->item(0);

					if ($author) {
						$author = $author->nodeValue;
					} else {
						$author = '';
					}

					$version = $dom->getElementsByTagName('version')->item(0);

					if ($version) {
						$version = $version->nodeValue;
					} else {
						$version = '';
					}

					$link = $dom->getElementsByTagName('link')->item(0);

					if ($link) {
						$link = $link->nodeValue;
					} else {
						$link = '';
					}

					$modification_data = array(
						'name'    => $name,
						'code'    => $code,
						'author'  => $author,
						'version' => $version,
						'link'    => $link,
						'xml'     => $xml,
						'status'  => 1
					);

					if (!$json) {
						$this->model_extension_modification->addModification($modification_data);
					}
				} catch(Exception $exception) {
					$json['error'] = sprintf($this->language->get('error_exception'), $exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine());
				}
			}
		}
	}

	public function php() {
		$this->load->language('extension/installer');

		$json = array();

		if (!$this->user->hasPermission('modify', 'extension/installer')) {
			$json['error'] = $this->language->get('error_permission');
		}

		$file = DIR_UPLOAD . str_replace(array('../', '..\\', '..'), '', $this->path ) . '/install.php';

		if (!file_exists($file)) {
			$json['error'] = $this->language->get('error_file');
		}

		if (!$json) {
			try {
				include($file);
			} catch(Exception $exception) {
				$json['error'] = sprintf($this->language->get('error_exception'), $exception->getCode(), $exception->getMessage(), $exception->getFile(), $exception->getLine());
			}
		}

	}

	public function remove() {
		$this->load->language('extension/installer');

		$json = array();

		$directory = DIR_UPLOAD . str_replace(array('../', '..\\', '..'), '', $this->path );

		if (!is_dir($directory)) {
			$json['error'] = $this->language->get('error_directory');
		}

		if (!$json) {
			// Get a list of files ready to upload
			$files = array();

			$path = array($directory);

			while (count($path) != 0) {
				$next = array_shift($path);

				// We have to use scandir function because glob will not pick up dot files.
				foreach (array_diff(scandir($next), array('.', '..')) as $file) {
					$file = $next . '/' . $file;

					if (is_dir($file)) {
						$path[] = $file;
					}

					$files[] = $file;
				}
			}

			rsort($files);

			foreach ($files as $file) {
				if (is_file($file)) {
					unlink($file);
				} elseif (is_dir($file)) {
					rmdir($file);
				}
			}

			if (file_exists($directory)) {
				rmdir($directory);
			}

			$json['success'] = $this->language->get('text_success');
		}

	}

	public function clear() {
		$this->load->language('extension/installer');

		$json = array();

		if (!$json) {
			$directories = glob(DIR_UPLOAD . 'update', GLOB_ONLYDIR);

			foreach ($directories as $directory) {
				// Get a list of files ready to upload
				$files = array();

				$path = array($directory);

				while (count($path) != 0) {
					$next = array_shift($path);

					// We have to use scandir function because glob will not pick up dot files.
					foreach (array_diff(scandir($next), array('.', '..')) as $file) {
						$file = $next . '/' . $file;

						if (is_dir($file)) {
							$path[] = $file;
						}

						$files[] = $file;
					}
				}

				rsort($files);

				foreach ($files as $file) {
					if (is_file($file)) {
						unlink($file);
					} elseif (is_dir($file)) {
						rmdir($file);
					}
				}

				if (file_exists($directory)) {
					rmdir($directory);
				}
			}

			$json['success'] = $this->language->get('text_clear');
		}
	}
}