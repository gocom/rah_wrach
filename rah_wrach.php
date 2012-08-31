<?php

/**
 * Rah_wrach plugin for Textpattern CMS.
 *
 * @author Jukka Svahn
 * @date 2008-
 * @license GNU GPLv2
 * @link http://rahforum.biz/plugins/rah_wrach
 * 
 * Copyright (C) 2012 Jukka Svahn <http://rahforum.biz>
 * Licensed under GNU Genral Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

	new rah_wrach();

class rah_wrach {
	
	static public $version = '0.2';
	
	/**
	 * @var bool Skip the prompt
	 */
	
	public $skip = true;
	
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
		
		$position = 250;
		
		foreach(
			array(
				'show_sections' => array('text_input', ''),
				'hide_section_input' => array('yesnoradio', 0),
			) as $name => $val
		) {
			$n = __CLASS__.'_'.$name;
			
			if(!isset($prefs[$n])) {
				set_pref($n, $val[1], __CLASS__, PREF_ADVANCED, $val[0], $position);
				$prefs[$n] = $val[1];
			}
			
			$position++;
		}
		
		set_pref(__CLASS__.'_version', self::$version, __CLASS__, 2, '', 0);
		$prefs[__CLASS__.'_version'] = self::$version;
	}
	
	/**
	 * Constructor
	 */
	
	public function __construct() {
		add_privs('plugin_prefs.'.__CLASS__, '1,2');
		register_callback(array(__CLASS__, 'install'), 'plugin_lifecycle.'.__CLASS__);
		register_callback(array($this, 'prefs'), 'plugin_prefs.'.__CLASS__);
		register_callback(array($this, 'prompt'), 'article', '', 1);
		register_callback(array($this, 'select'), 'article', '', 0);
		register_callback(array($this, 'head'), 'admin_side', 'head_end');
	}

	/**
	 * Add styles and JavaScript to the <head>
	 */

	public function head() {
		
		global $event, $prefs;
		
		if($event != 'article') {
			return;
		}

		echo <<<EOF
			<style type="text/css">
				#rah_wrach .txp-grid-cell {
					width: 294px;
				}
				#rah_wrach .information,
				#rah_wrach .success {
					float: right;
					margin-left: 0.3em;
				}
			</style>
EOF;

		if($prefs['rah_wrach_hide_section_input']) {
			echo <<<EOF
				<style type="text/css">
					#write-sort .section {
						display: none;
					}
				</style>
EOF;
		}

	}

	/**
	 * Check prompt's visiblity
	 */

	public function prompt() {
		
		global $step;
		
		extract(gpsa(array(
			'ID',
			'Section',
			'view'
		)));
		
		$this->skip = ($Section || $ID || $view || $step);
	}

	/**
	 * Section selection panel
	 */

	public function select() {
		
		global $prefs, $txp_user;
		
		if($this->skip) {
			return;
		}
		
		$sql = array();
		$sql[] = "name != 'default'";
		
		$s = $prefs[__CLASS__.'_show_sections'];
		
		if(isset($prefs[__CLASS__.'_s.'.$txp_user])) {
			$s = $prefs[__CLASS__.'_s.'.$txp_user];
		}
		
		if($s) {
			$s = implode(',', quote_list(do_list($s)));
			$sql[] = "name IN({$s})";
		}
		
		$rs = 
			safe_rows(
				'title, name, (SELECT count(*) FROM '.safe_pfx('textpattern').' articles WHERE articles.Section = txp_section.name) AS article_count, in_rss, on_frontpage',
				'txp_section',
				implode(' and ', $sql).' order by '.($s ? 'FIELD(name,'.$s.')' : 'title ASC')
			);
		
		if(!$rs) {
			return;
		}
		
		ob_clean();
		pagetop(gTxt('tab_write'));
		$out = array();

		foreach($rs as $a) {
			$out[] = 
				'<div class="txp-grid-cell">'.
					'<p class="clearfix">'.
						'<a href="?event=article&amp;Section='.txpspecialchars($a['name']).'">'.
							txpspecialchars($a['title']).
						'</a>'.
						($a['article_count'] ? '<a href="?event=list&amp;search_method=section&amp;crit=&quot;'.txpspecialchars($a['name']).'&quot;" class="information"><small>'.$a['article_count'].'</small></a>' : '').
						'<br />'.
						preg_replace('#^/index\.php\?#', '/?', substr(pagelinkurl(array('s' => $a['name'])), strlen(hu)-1)).
						($a['on_frontpage'] ? '<small title="'.gTxt('rah_wrach_frontpage_tooltip').'" class="success">'.gTxt('rah_wrach_frontpage_label').'</small>' : '').
						($a['in_rss'] ? '<small title="'.gTxt('rah_wrach_rss_tooltip').'" class="success">'.gTxt('rah_wrach_rss_label').'</small>' : '').
					'</p>'.
				'</div>';
		}
		
		echo 
			'<h1 class="txp-heading">'.gTxt('tab_write').'</h1>'.
			'<div id="rah_wrach" class="txp-grid">'.implode('', $out).'</div>';
	}

	/**
	 * Options page
	 */

	public function prefs() {
		header('Location: ?event=prefs&step=advanced_prefs#prefs-rah_wrach_show_sections');
		
		echo 
			'<p>'.n.
			'	<a href="?event=prefs&amp;step=advanced_prefs#prefs-rah_wrach_show_sections">'.gTxt('continue').'</a>'.
			'</p>';
	}
}

?>