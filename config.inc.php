<?php

/**
 * YREWRITE Addon
 * @author jan.kristinus@yakamara.de
 * @package redaxo4.5
 */


/*
* TODOS:

- clang integrieren  / domain.de -> aid:5,clang:1 / domain.en -> aid:2,clang:0
- cache refresh wenn url neu geschrieben
- Validierungen bei domains anpassen
- article urls auch über das addon selbst erstellen können

forward
-  Externe URL nach "http://" prüfen

*/

$mypage = 'yrewrite';

$REX['ADDON']['name'][$mypage] = 'YRewrite';
$REX['ADDON']['version'][$mypage] = '1.1';
$REX['ADDON']['author'][$mypage] = 'Jan Kristinus';
$REX['ADDON']['supportpage'][$mypage] = 'www.redaxo.org/de/forum';
$REX['ADDON']['perm'][$mypage] = 'admin[]';
  
$UrlRewriteBasedir = dirname(__FILE__);
require_once $UrlRewriteBasedir . '/classes/class.rex_yrewrite.inc.php';
require_once $UrlRewriteBasedir . '/classes/class.rex_yrewrite_scheme.inc.php';
require_once $UrlRewriteBasedir . '/classes/class.rex_yrewrite_forward.inc.php';

rex_yrewrite::setScheme(new rex_yrewrite_scheme());

if ($REX['REDAXO']) {

    $I18N->appendFile($REX['INCLUDE_PATH'] . '/addons/' . $mypage . '/lang/');

    // ----- content page - url manipulation
    if ($REX['MOD_REWRITE'] !== false) {
        rex_register_extension('PAGE_CONTENT_MENU', function ($params) {
            global $REX, $I18N;
            $class = '';
            if ($params['mode'] == 'yrewrite') {
                $class = 'class="rex-active"';
            }
            $page = '<a ' . $class . ' href="index.php?page=content&amp;article_id=' . $params['article_id'] . '&amp;mode=yrewrite&amp;clang=' . $params['clang'] . '&amp;ctype=' . rex_request('ctype') . '">' . $I18N->msg('yrewrite_mode') . '</a>';
            array_splice($params['subject'], '-2', '-2', $page);

            array_pop($params['subject']);
            $params['subject'][] = '<a href="' . rex_getUrl($params['article_id'], $params['clang']) . '" target="_blank">' . $I18N->msg('show') . '</a>';

            return $params['subject'];
        });

        rex_register_extension('PAGE_CONTENT_OUTPUT', function ($params) {
            global $REX, $I18N;

            if ($params['mode'] == 'yrewrite') {
                include $REX['INCLUDE_PATH'] . '/addons/yrewrite/pages/content.inc.php';
            }
        });

    }

    // ----- backend pages for domains und urls
    $domainsPage = new rex_be_page($I18N->msg('yrewrite_domains'), array(
        'page' => 'yrewrite',
        'subpage' => ''
    )
    );
    $domainsPage->setHref('index.php?page=yrewrite&subpage=');

    $AliasDomainsPage = new rex_be_page($I18N->msg('yrewrite_alias_domains'), array(
        'page' => 'yrewrite',
        'subpage' => 'alias_domains'
    )
    );
    $AliasDomainsPage->setHref('index.php?page=yrewrite&subpage=alias_domains');

    $forwardPage = new rex_be_page($I18N->msg('yrewrite_forward'), array(
        'page' => 'yrewrite',
        'subpage' => 'forward'
    )
    );
    $forwardPage->setHref('index.php?page=yrewrite&subpage=forward');

    $setupPage = new rex_be_page($I18N->msg('yrewrite_setup'), array(
        'page' => 'yrewrite',
        'subpage' => 'setup'
    )
    );
    $setupPage->setHref('index.php?page=yrewrite&subpage=setup');

    $REX['ADDON']['pages'][$mypage] = array (
        $domainsPage, $AliasDomainsPage, $forwardPage, $setupPage
    );

}


if ($REX['MOD_REWRITE'] !== false && !$REX['SETUP']) {

    rex_register_extension('ADDONS_INCLUDED', function ($params) {

        global $REX;

        rex_yrewrite::init();

        // if anything changes -> refresh PathFile
        if ($REX['REDAXO']) {
            $extension = 'rex_yrewrite::generatePathFile';
            $extensionPoints = array(
                'CAT_ADDED',   'CAT_UPDATED',   'CAT_DELETED', 'CAT_STATUS',
                'ART_ADDED',   'ART_UPDATED',   'ART_DELETED', 'ART_STATUS',
                'CLANG_ADDED', 'CLANG_UPDATED', 'CLANG_DELETED',
                /*'ARTICLE_GENERATED'*/
                'ALL_GENERATED'
            );
            foreach ($extensionPoints as $extensionPoint) {
                rex_register_extension($extensionPoint, $extension);
            }
        }
        rex_register_extension('URL_REWRITE', 'rex_yrewrite::rewrite');

        // get ARTICLE_ID from URL
        if (!$REX['REDAXO']) {
            rex_yrewrite::prepare();
        }

    }, '', REX_EXTENSION_EARLY);

    rex_register_extension('YREWRITE_PREPARE', function ($params) {
        return rex_yrewrite_forward::getForward($params);
    });


}
