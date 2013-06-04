<?php

/**
 * Rah_wrach plugin for Textpattern CMS.
 *
 * @author  Jukka Svahn
 * @license GNU GPLv2
 * @link    http://rahforum.biz/plugins/rah_wrach
 *
 * Copyright (C) 2013 Jukka Svahn http://rahforum.biz
 * Licensed under GNU General Public License version 2
 * http://www.gnu.org/licenses/gpl-2.0.html
 */

class rah_wrach
{
    /**
     * If TRUE, skips the prompt.
     *
     * @var bool
     */

    public $skip = true;

    /**
     * Installer.
     */

    public function install()
    {
        global $prefs;

        $position = 250;
        $settings = array(
            'show_sections' => array('text_input', ''),
            'hide_section_input' => array('yesnoradio', 0),
        );

        foreach ($settings as $name => $val)
        {
            $n = __CLASS__.'_'.$name;

            if (!isset($prefs[$n]))
            {
                set_pref($n, $val[1], __CLASS__, PREF_ADVANCED, $val[0], $position);
                $prefs[$n] = $val[1];
            }

            $position++;
        }
    }

    /**
     * Uninstaller.
     */

    public function uninstall()
    {
        safe_delete(
            'txp_prefs',
            "name like 'rah\_wrach\_%'"
        );
    }

    /**
     * Constructor.
     */

    public function __construct()
    {
        add_privs('plugin_prefs.'.__CLASS__, '1,2');
        add_privs('prefs.'.__CLASS__, '1,2');
        register_callback(array($this, 'install'), 'plugin_lifecycle.rah_wrach', 'installed');
        register_callback(array($this, 'uninstall'), 'plugin_lifecycle.rah_wrach', 'deleted');
        register_callback(array($this, 'prefs'), 'plugin_prefs.'.__CLASS__);
        register_callback(array($this, 'prompt'), 'article', '', 1);
        register_callback(array($this, 'select'), 'article', '', 0);
        register_callback(array($this, 'head'), 'admin_side', 'head_end');
    }

    /**
     * Adds styles and JavaScript to the &lt;head&gt;.
     */

    public function head()
    {
        global $event;

        if ($event != 'article')
        {
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

        if (get_pref('rah_wrach_hide_section_input'))
        {
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
     * Checks prompt's visiblity.
     */

    public function prompt()
    {
        global $step;

        extract(gpsa(array(
            'ID',
            'Section',
            'view'
        )));

        $this->skip = ($Section || $ID || $view || $step);
    }

    /**
     * Prints section selection panel.
     */

    public function select()
    {
        global $txp_user;

        if ($this->skip)
        {
            return;
        }

        $sql = array();
        $sql[] = "name != 'default'";

        $sections = get_pref('rah_wrach_user_sections', get_pref('rah_wrach_show_sections'));

        if ($sections)
        {
            $sections = implode(',', quote_list(do_list($sections)));
            $sql[] = "name IN({$sections})";
        }

        $rs = 
            safe_rows_start(
                'title, name, (SELECT count(*) FROM '.safe_pfx('textpattern').' articles WHERE articles.Section = txp_section.name) AS article_count, in_rss, on_frontpage',
                'txp_section',
                implode(' and ', $sql).' order by '.($sections ? 'FIELD(name,'.$sections.')' : 'title ASC')
            );

        if (!numRows($rs))
        {
            return;
        }

        ob_clean();
        pagetop(gTxt('tab_write'));
        $out = array();

        while ($a = nextRow($rs))
        {
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
     * The plugin's options page.
     *
     * Redirects to preferences.
     */

    public function prefs()
    {
        header('Location: ?event=prefs&step=advanced_prefs#prefs-rah_wrach_show_sections');

        echo 
            '<p>'.n.
            '    <a href="?event=prefs&amp;step=advanced_prefs#prefs-rah_wrach_show_sections">'.gTxt('continue').'</a>'.
            '</p>';
    }
}

new rah_wrach();