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
		register_callback('rah_write_each_section', 'article');
		register_callback('rah_write_each_section_head', 'admin_side', 'head_end');
	}

/**
 * Add styles and JavaScript to the <head>
 */

	function rah_write_each_section_head() {
		
		global $event;
		
		if($event != 'article')
			return;

		extract(
			gpsa(
				array(
					'ID',
					'Section',
					'view'
				)
			)
		);

		echo <<<EOF
			<style type="text/css">
				#rah_write_each_section_container .txp-grid-cell {
					width: 294px;
				}
			</style>
EOF;

		if(!$Section && !$ID && !$view) {
			echo <<<EOF
				<script type="text/javascript">
					$(document).ready(function() {
						$("form[name=article], form#article_form").remove();
					});
				</script>
EOF;
		}
	}

/**
 * Adds the extra step to Write panel
 */

	function rah_write_each_section() {
		
		extract(
			gpsa(
				array(
					'ID',
					'Section',
					'view'
				)
			)
		);

		if(!$Section && !$ID && !$view) {
			
			$rs = 
				safe_rows(
					'title, name',
					'txp_section',
					"name != 'default' order by title ASC" // and in_rss = '1'
				);

			foreach($rs as $a) {
				$out[] = 
					'<div class="txp-grid-cell">'.
						'<p><a href="?event=article&amp;Section='.htmlspecialchars($a['name']).'">'.
							htmlspecialchars($a['title']).
						'</a></p>'.
					'</div>';
			}
			
			echo '<div id="rah_write_each_section_container" class="txp-grid">'.implode('', $out).'</div>';
		}
	}

?>