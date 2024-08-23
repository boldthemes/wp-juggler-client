<?php

/**
 * AJAX-specific functionality for the plugin.
 *
 * @link       https://wpjuggler.com
 * @since      1.0.0
 *
 * @package    WP_Juggler_Server
 * @subpackage WP_Juggler_Server/includes
 */

// Prevent direct access.
if (! defined('WPJC_PATH')) exit;

if (! class_exists('WP_Async_Request')) {
	require_once WPJC_PATH . 'vendor/wp-async-request.php';;
}

if (! class_exists('WP_Background_Process')) {
	require_once WPJC_PATH . 'vendor/wp-background-process.php';;
}

class WPJC_Background_Process extends WP_Background_Process
{

	protected $prefix = 'wpjc';

	protected $action = 'wpjc_process';

	protected $api_class;

	public function __construct( $api ) 
   { 
      parent::__construct();
	  $this->api_class = $api;
   } 


	protected function task($item)
	{

		$task_id = $item['taskId'];
		$task_type = $item['taskType'];
		

		if ($task_type == 'checkChecksum'){

			$data=[];
			$datacheck =[];
			
			$data['taskId'] = $task_id;
			$data['taskType'] = $task_type;

			$datacheck['core'] = $this->api_class->core_checksum->get_core_checksum();
			$datacheck['plugins'] = $this->api_class->plugin_checksum->get_plugin_checksum();

			$data['data'] = $datacheck;

			//WPJC_Server_Api::call_server_api( 'finishTask', $data );
		}

		return false;
	}

	protected function complete()
	{
		parent::complete();
	}
}
