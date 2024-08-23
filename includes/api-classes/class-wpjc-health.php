<?php

if (! class_exists('WP_Site_Health')) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
}


class WPJC_Health extends WP_Site_Health
{

	public function wpjc_perform_test($callback)
	{
		return apply_filters('site_status_test_result', call_user_func($callback));
	}

	public function wpjc_health_info()
	{

		require_once ABSPATH . 'wp-admin/includes/admin.php';

		$health_check_js_variables = array(
			'nonce'       => array(
				'site_status'        => wp_create_nonce('health-check-site-status'),
				'site_status_result' => wp_create_nonce('health-check-site-status-result'),
			),
			'site_status' => array(
				'direct' => array(),
				'async'  => array(),
				'issues' => array(
					'good'        => 0,
					'recommended' => 0,
					'critical'    => 0,
				),
			),
		);

		$issue_counts = get_transient('health-check-site-status-result');

		if (false !== $issue_counts) {
			$issue_counts = json_decode($issue_counts);

			$health_check_js_variables['site_status']['issues'] = $issue_counts;
		}

		$tests = WPJC_Health::get_tests();

		foreach ($tests['direct'] as $test) {
			if (is_string($test['test'])) {
				$test_function = sprintf(
					'get_test_%s',
					$test['test']
				);

				if (method_exists($this, $test_function) && is_callable(array($this, $test_function))) {
					$health_check_js_variables['site_status']['direct'][] = $this->wpjc_perform_test(array($this, $test_function));
					continue;
				}
			}

			if (is_callable($test['test'])) {
				$health_check_js_variables['site_status']['direct'][] = $this->wpjc_perform_test($test['test']);
			}
		}

		foreach ($tests['async'] as $test) {
			if (is_string($test['test'])) {
				$health_check_js_variables['site_status']['async'][] = array(
					'test'      => $test['test'],
					'has_rest'  => (isset($test['has_rest']) ? $test['has_rest'] : false),
					'completed' => false,
					'headers'   => isset($test['headers']) ? $test['headers'] : array(),
				);
			}
		}

		return $health_check_js_variables;
	}
}