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
		new rah_write_each_section();
	}

class rah_write_each_section {
	
	/**
	 * Constructor
	 */
	
	public function __construct() {
		register_callback(array($this, 'select'), 'article');
		register_callback(array($this, 'head'), 'admin_side', 'head_end');
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
				#rah_write_each_section_container .txp-grid-cell {
					width: 294px;
				}
				#rah_write_each_section_container span {
					float: right;
					margin: 0 0 0 0.3em;
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

		if(!$Section && !$ID && !$view) {
			
			$rs = 
				safe_rows(
					'title, name, (SELECT count(*) FROM '.safe_pfx('textpattern').' articles WHERE articles.Section = txp_section.name) AS article_count, in_rss',
					'txp_section',
					"name != 'default' order by title ASC"
				);

			foreach($rs as $a) {
				$out[] = 
					'<div class="txp-grid-cell">'.
						'<p>'.
							'<a href="?event=article&amp;Section='.htmlspecialchars($a['name']).'">'.
								htmlspecialchars($a['title']).
							'</a>'.n.
							'<span class="information">'.$a['article_count'].'</span>'.
							($a['in_rss'] ? '<span class="success">RSS</span>' : '').
							'<br />'.$a['name'].
						'</p>'.
					'</div>';
			}
			
			echo '<div id="rah_write_each_section_container" class="txp-grid">'.implode('', $out).'</div>';
		}
	}
}

?>