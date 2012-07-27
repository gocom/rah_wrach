<?php

/**
 * Rah_write_each_section plugin for Textpattern CMS.
 *
 * @author Jukka Svahn
 * @date 2008-
 * @license GNU GPLv2
 * @link http://rahforum.biz/plugins/rah_write_each_section
 * 
 * Copyright (C) 2011 Jukka Svahn <http://rahforum.biz>
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
					's',
					'Section',
					'view'
				)
			)
		);

		echo <<<EOF
			<style type="text/css">
				#rah_write_each_section_container {
					width: 350px;
					margin: 15px auto;
				}
			</style>
EOF;

		if(!$ID && !$Section && $s && $s != 'all_site_sections_opened_for_posting') {
			$value = str_replace('"','\"',$s);
			echo <<<EOF
				<script language="javascript" type="text/javascript">
					$(document).ready(function() {
						$("#section option[value={$value}]").attr("selected","selected");
						$("#write-sort p:last").css("display","none");
					});
				</script>
EOF;
		}

		if(!$s && !$Section && !$ID && !$view) {
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
					's',
					'Section',
					'view'
				)
			)
		);

		if(!$s && !$Section && !$ID && !$view) {
			echo 
				'<div id="rah_write_each_section_container">'.n.
				'	<h1>'.gTxt('tab_write').' &#8250; '.gTxt('tab_sections').' &#8250; '.gTxt('select').'</h1>'.n.
				'	<ul>'.n;
			
			$rs = 
				safe_rows(
					'title,name',
					'txp_section',
					"name != 'default' and in_rss = '1' order by title ASC"
				);

			foreach($rs as $a) {
				extract($a);
				echo 
					'		<li><a href="?event=article&amp;s='.htmlspecialchars($name).'">'.htmlspecialchars($title).'</a></li>'.n;
			}

			echo 
				'		<li><strong><a href="?event=article&amp;s=all_site_sections_opened_for_posting">'.gTxt('advanced_options').'</a></strong></li>'.n.
				'	</ul>'.n.
				'</div>'.n;
		}
	}

?>