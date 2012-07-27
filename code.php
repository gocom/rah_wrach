<?php	##################
	#
	#	rah_write_each_section-plugin for Textpattern
	#	version 0.1
	#	by Jukka Svahn
	#	http://rahforum.biz
	#
	###################

	if (@txpinterface == 'admin') register_callback('rah_write_each_section','article');

	function rah_write_each_section() {
		$section = gps('s');
		if(!gps('ID') && !ps('ID') && gps('s') && !ps('Section') && $section != 'all_site_sections_opened_for_posting') {
			echo 
				'<script language="javascript" type="text/javascript">'.n.
				'	$(document).ready(function() {'.n.
				'		$("#section option[value='.str_replace('"','\"',$section).']").attr("selected","selected");'.n.
				'		$("#write-sort p:last").css("display","none");'.n.
				'	});'.n.
				'</script>';
		}
		$view = gps('view');
		if(!gps('s') && !ps('Section') && !gps('ID') && !$view) {
			echo 
				'<h1 style="width:600px;margin:15px auto;">'.gTxt('tab_write').' &#8250; '.gTxt('tab_sections').' &#8250; '.gTxt('select').'</h1>'.n.
				'<ul style="width:600px;margin:0 auto;">'.n;
			$rs = safe_rows_start('title, name', 'txp_section',"name != 'default' and in_rss = '1' order by title ASC");
			while ($a = nextRow($rs)) {
				extract($a);
				echo '	<li><a href="?event=article&amp;s='.$name.'">'.$title.'</a></li>'.n;
			}
			echo 
				'	<li><strong><a href="?event=article&amp;s=all_site_sections_opened_for_posting">'.gTxt('advanced_options').'</a></strong></li>'.n.
				'</ul>'.n.
				'<script language="javascript" type="text/javascript">'.n.
				'	$(document).ready(function() {'.n.
				'		$("form[name=article]").remove();'.n.
				'	});'.n.
				'</script>';
		}
	}