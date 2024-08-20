<?php

namespace TrendpilotEssentials;

class Cron {

	public function registerHooks() {
		add_action( 'wp', [ $this, 'setup_tp_flusher_cron' ] );
		add_action( 'tp_flusher_cron_event', [ $this, 'tp_flusher_cron_job' ] );
	}

	public function setup_tp_flusher_cron() {
		if ( ! wp_next_scheduled( 'tp_flusher_cron_event' ) ) {
			wp_schedule_event( strtotime( '00:01:00' ), 'daily', 'tp_flusher_cron_event' );
		}
	}

	public function tp_flusher_cron_job() {
		flush_old_page_views();
		flush_old_click_data();
	}

}