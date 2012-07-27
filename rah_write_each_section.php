<?php

/**
 * Rah_write_each_section plugin for Textpattern CMS.
 *
 * @author Jukka Svahn
 * @date 2008-
 * @license GNU GPLv2
 * @link http://rahforum.biz/plugins/rah_write_each_section
 * 
 * Copyright (C) 2008 Jukka Svahn <http://rahforum.biz>
 * Licensed under GNU Genral Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

	if(@txpinterface == 'admin') {
		new rah_wrach();
	}

class rah_wrach {
	
	static public $version = '0.2';
	
	/**
	 * Installer
	 */
	
	static public function install($event='', $step='') {
		
		global $prefs;
		
		if($step == 'deleted') {
			
			safe_delete(
				'txp_prefs',
				"name like 'rah\_wrach\_%'"
			);
			
			return;
		}
		
		if((string) get_pref(__CLASS__.'_version') === self::$version) {
			return;
		}
		
		set_pref(__CLASS__.'_version', self::$version, __CLASS__, 2, '', 0);
		$prefs[__CLASS__.'_version'] = self::$version;
	}
	
	/**
	 * Constructor
	 */
	
	public function __construct() {
		register_callback(array($this, 'select'), 'article');
		register_callback(array($this, 'head'), 'admin_side', 'head_end');
		register_callback(array(__CLASS__, 'install'), 'plugin_lifecycle.'.__CLASS__);
	}

	/**
	 * Add styles and JavaScript to the <head>
	 */

	public function head() {
		
		global $event;
		
		if($event != 'article') {
			return;
		}

		extract(gpsa(array(
			'ID',
			'Section',
			'view'
		)));
		
		if($Section || $ID || $view) {
			return;
		}

		echo <<<EOF
			<style type="text/css">
				#rah_wrach .txp-grid-cell {
					width: 234px;
				}
				#rah_wrach .information,
				#rah_wrach .success {
					float: right;
					margin-left: 0.3em;
				}
				form#article_form {
					display: none;
				}
			</style>
EOF;
	}

	/**
	 * Section selection panel
	 */

	public function select() {
		
		extract(gpsa(array(
			'ID',
			'Section',
			'view'
		)));

		if($Section || $ID || $view) {
			return;
		}
		
		$rs = 
			safe_rows(
				'title, name, (SELECT count(*) FROM '.safe_pfx('textpattern').' articles WHERE articles.Section = txp_section.name) AS article_count, in_rss, on_frontpage',
				'txp_section',
				"name != 'default' order by title ASC"
			);

		foreach($rs as $a) {
			$out[] = 
				'<div class="txp-grid-cell">'.
					'<p class="clearfix">'.
						'<a href="?event=article&amp;Section='.txpspecialchars($a['name']).'">'.
							txpspecialchars($a['title']).
						'</a>'.n.
						($a['article_count']? '<a href="?event=list'.a.'search_method=section'.a.'crit=&quot;'.txpspecialchars($a['name']).'&quot;" class="information"><small>'.$a['article_count'].'</small></a>' : '').
						'<br />'.
						txpspecialchars($a['name']).
						($a['on_frontpage'] ? '<small class="success">'.gTxt('rah_write_each_section_frontpage_label').'</small>' : '').
						($a['in_rss'] ? '<small class="success">'.gTxt('rah_write_each_section_rss_label').'</small>' : '').
					'</p>'.
				'</div>';
		}
		
		echo 
			'<h1 class="txp-heading">'.gTxt('rah_write_each_section_title').'</h1>'.
			'<p class="information alert-block">'.gTxt('rah_write_each_section_start_by').'</p>'.
			'<div id="rah_wrach" class="txp-grid">'.implode('', $out).'</div>';
	}
}

?>