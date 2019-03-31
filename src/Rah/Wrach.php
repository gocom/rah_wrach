<?php

/*
 * rah_wrach - Memcached templates for Textpattern CMS
 * https://github.com/gocom/rah_wrach
 *
 * Copyright (C) 2019 Jukka Svahn
 *
 * This file is part of rah_wrach.
 *
 * rah_wrach is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, version 2.
 *
 * rah_wrach is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with rah_wrach. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * Plugin class.
 */
final class Rah_Wrach
{
    /**
     * Whether the prompt is skipped.
     *
     * @var bool
     */
    private $skip = true;

    /**
     * Constructor.
     */
    public function __construct()
    {
        add_privs('plugin_prefs.rah_wrach', '1,2');
        add_privs('prefs.rah_wrach', '1,2');
        register_callback([$this, 'install'], 'plugin_lifecycle.rah_wrach', 'installed');
        register_callback([$this, 'uninstall'], 'plugin_lifecycle.rah_wrach', 'deleted');
        register_callback([$this, 'prefs'], 'plugin_prefs.rah_wrach');
        register_callback([$this, 'prompt'], 'article', '', 1);
        register_callback([$this, 'select'], 'article', '', 0);
        register_callback([$this, 'head'], 'admin_side', 'head_end');
    }

    /**
     * Installer.
     */
    public function install()
    {
        $position = 250;

        $options = [
            'rah_wrach_show_sections' => ['text_input', ''],
            'rah_wrach_hide_section_input' => ['yesnoradio', 0],
        ];

        foreach ($options as $name => $val) {
            if (get_pref($name, false) === false) {
                set_pref($name, $val[1], 'rah_wrach', PREF_PLUGIN, $val[0], $position);
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
     * Adds styles and JavaScript to the &lt;head&gt;.
     */
    public function head()
    {
        global $event;

        if ($event !== 'article') {
            return;
        }

        echo <<<EOF
            <style type="text/css">
                #rah_wrach .information,
                #rah_wrach .success {
                    float: right;
                    margin-left: 0.3em;
                }
            </style>
EOF;

        if (get_pref('rah_wrach_hide_section_input')) {
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

        extract(gpsa([
            'ID',
            'Section',
            'view'
        ]));

        $this->skip = ($Section || $ID || $view || $step);
    }

    /**
     * Prints section selection panel.
     */
    public function select()
    {
        global $txp_user;

        if ($this->skip) {
            return;
        }

        $sql = [];
        $sql[] = "name != 'default'";

        $sections = get_pref('rah_wrach_user_sections', get_pref('rah_wrach_show_sections'));

        if ($sections) {
            $sections = implode(',', quote_list(do_list($sections)));
            $sql[] = "name IN({$sections})";
        }

        $rs = safe_rows_start(
            'title, name, (SELECT count(*) FROM ' . safe_pfx('textpattern') .
            ' articles WHERE articles.Section = txp_section.name) AS article_count, in_rss, on_frontpage',
            'txp_section',
            implode(' and ', $sql).' order by '.($sections ? 'FIELD(name,'.$sections.')' : 'title ASC')
        );

        if (!numRows($rs)) {
            return;
        }

        ob_clean();
        pagetop(gTxt('tab_write'));
        $out = [];

        while ($a = nextRow($rs)) {
            $out[] = tag_start('div', ['class' => 'txp-grid-cell']).
                tag_start('p', ['class' => 'clearfix']).
                href(
                    txpspecialchars($a['title']),
                    [
                        'event' => 'article',
                        'Section' => $a['name'],
                    ]
                );

            if ($a['article_count']) {
                $out[] = href(
                    tag($a['article_count'], 'small'),
                    [
                        'event' => 'list',
                        'search_method' => 'section',
                        'crit' => '"' . $a['name'] . '"',
                    ],
                    [
                        'class' => 'information'
                    ]
                );
            }

            $out[] = br . $this->getSectionPath($a['name']);

            if ($a['on_frontpage']) {
                $out[] = tag(gTxt('rah_wrach_frontpage_label'), 'small', [
                    'title' => gTxt('rah_wrach_frontpage_tooltip'),
                    'class' => 'success',
                ]);
            }

            if ($a['in_rss']) {
                $out[] = tag(gTxt('rah_wrach_rss_label'), 'small', [
                    'title' => gTxt('rah_wrach_rss_tooltip'),
                    'class' => 'success',
                ]);
            }

            $out[] = tag_end('p') . tag_end('div');
        }

        echo hed(gTxt('tab_write'), 1, ['class' => 'txp-heading']) .
            tag(implode(n, $out), 'div', ['id' => 'rah_wrach', 'class' => 'txp-grid']);
    }

    /**
     * Get a section page path segment.
     *
     * @param  string $section The section name
     * @return string
     */
    private function getSectionPath($section): string
    {
        $url = pagelinkurl(['s' => $section]);
        $path = substr($url, strlen(hu) - 1);
        return (string)preg_replace('#^/index\.php\?#', '/?', $path);
    }

    /**
     * The plugin's options page.
     *
     * Redirects to preferences.
     */
    public function prefs()
    {
        header('Location: ?event=prefs&step=advanced_prefs#prefs-rah_wrach_show_sections');
        echo graf(href(gTxt('continue'), [
            'href' => '?event=prefs&amp;step=advanced_prefs#prefs-rah_wrach_show_sections'
        ]));
    }
}
