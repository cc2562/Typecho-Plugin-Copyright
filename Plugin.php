<?php

namespace TypechoPlugin\Copyright;

use Typecho\Plugin\PluginInterface;
use Typecho\Plugin\Exception as PluginException;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Widget\Options;
use Utils\Helper; 

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}
/**
 * Copyright for Typecho
 *
 * @package Copyright
 * @author  mikusa
 * @version 2.0.0
 * @link https://github.com/mikusaa/Copyright-for-Typecho
 */

 class Plugin implements PluginInterface {
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     *
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate() {
        \Typecho\Plugin::factory('Widget_Abstract_Contents')->contentEx = array('Copyright_Plugin', 'Copyright');
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     *
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate() {
    }

    /**
     * 获取插件配置面板
     *
     * @access public
     * @param Form $form 配置面板
     * @return void
     */
    public static function config(Form $form): void {
        echo '<p>欢迎使用 Typecho 版权插件，你正在使用的是增加了「<strong>封面来源</strong>」的修改版本，并简单 <s>利用复制粘贴</s> 适配了 Typecho 1.2、<s>靠 chatgpt </s>支持了markdown 语法。</p>';
        echo '<hr />';
        echo '<p>此插件帮助你设置文章与独立页面的版权声明，它会附在内容末尾。你也可以对特定某篇内容设置版权信息。</p>';
        echo '<p>版权信息借助插件与 Typecho 的自定义字段功能实现，只与插件或特定内容关联，而不会修改其内容本身，也不会在数据库中与文本混同。</p>';
        echo '<hr />';
        echo '<p>此处为<b>全局设置</b>，如需对特定某篇内容设置版权信息，请参阅<b><a href="https://github.com/Yves-X/Copyright-for-Typecho">详细说明</a></b>。特定设置的优先级始终高于全局设置，因此如果你为某篇文章单独设置了版权信息，那么设置的部分将会覆盖全局设置。</p>';
        echo '<hr />';

        $author = new Text('author', NULL, _t('作者名称'), _t('作者（支持 markdown 语法）'));
        $form->addInput($author);

        $notice = new Text('notice', NULL, _t('转载时须注明出处及本声明'), _t('声明（支持 markdown 语法）'));
        $form->addInput($notice);

        $showURL = new Form\Element\Radio('showURL', array('1' => _t('启用'),'0' => _t('不启用'),), '0', _t('显示原（本）文链接'));
        $form->addInput($showURL);

        $showOnPost = new Form\Element\Radio('showOnPost', array('1' => _t('启用'),'0' => _t('不启用'),), '0', _t('在文章页显示'));
        $form->addInput($showOnPost);

        $showOnPage = new Form\Element\Radio('showOnPage', array('1' => _t('启用'),'0' => _t('不启用'),), '0', _t('在独立页面显示'));
        $form->addInput($showOnPage);

    }

    /**
     * 个人用户的配置面板
     *
     * @access public
     * @param Form $form
     * @return void
     */
    public static function personalConfig(Form $form) {
    }

    /**
     * 插件实现方法
     *
     * @access public
     * @return void
     */

    public static function Copyright($content, $widget, $lastResult) {
        $content = empty($lastResult) ? $content : $lastResult;
        $cr = self::apply($widget);
        $cr_html = self::render($cr);
        $content = $content . $cr_html;
        return $content;
    }

    private static function globalCopyright($widget) {
        $cr = array('show_on_post' => '', 'show_on_page' => '', 'show_url' => '', 'author' => '', 'url' => '', 'notice' => '', 'cover' => '');
        $cr['show_on_post'] = \Typecho\Widget::widget('Widget_Options')->plugin('Copyright')->showOnPost;
        $cr['show_on_page'] = \Typecho\Widget::widget('Widget_Options')->plugin('Copyright')->showOnPage;
        $cr['show_url'] = \Typecho\Widget::widget('Widget_Options')->plugin('Copyright')->showURL[0];
        $cr['author'] = \Typecho\Widget::widget('Widget_Options')->plugin('Copyright')->author;
        $cr['url'] = \Typecho\Widget::widget('Widget_Options')->plugin('Copyright')->url;
        $cr['notice'] = \Typecho\Widget::widget('Widget_Options')->plugin('Copyright')->notice;
        $cr['cover'] = \Typecho\Widget::widget('Widget_Options')->plugin('Copyright')->cover;
        return $cr;
    }

    private static function localCopyright($widget) {
        $cr = array('switch_on' => '', 'author' => '', 'url' => '', 'notice' => '', 'cover' => '');
        $cr['switch_on'] = $widget->fields->switch;
        $cr['author'] = $widget->fields->author;
        $cr['url'] = $widget->fields->url;
        $cr['notice'] = $widget->fields->notice;
        $cr['cover'] = $widget->fields->cover;
        return $cr;
    }

    private static function apply($widget) {
        $gcr = self::globalCopyright($widget);
        $lcr = self::localCopyright($widget);
        $cr = array('is_enable' => '', 'is_original' => '', 'author' => '', 'url' => '', 'notice' => '', 'cover' => '');
        if ($widget->is('single')) {
            $cr['is_enable'] = 1;
        }
        if ($widget->parameter->type == 'post' && $gcr['show_on_post'] == 0) {
            $cr['is_enable'] = 0;
        }
        if ($widget->parameter->type == 'page' && $gcr['show_on_page'] == 0) {
            $cr['is_enable'] = 0;
        }
        if ($lcr['switch_on'] != '') {
            $cr['is_enable'] = $lcr['switch_on'];
        }
        if ($gcr['show_url'] == 0) {
            $cr['url'] = 0;
        }
        $cr['url'] = $lcr['url'] != '' ? $lcr['url'] : $gcr['url'];
        if ($gcr['show_url'] == 1 && $lcr['url'] == '') {
            $cr['is_original'] = 1;
            $cr['url'] = $widget->permalink;
        }
        $cr['author'] = $lcr['author'] != '' ? $lcr['author'] : $gcr['author'];
        $cr['notice'] = $lcr['notice'] != '' ? $lcr['notice'] : $gcr['notice'];
        $cr['cover'] = $lcr['cover'] != '' ? $lcr['cover'] : $gcr['cover'];
        return $cr;
    }

    private static function render($cr) {
        $copyright_html = '';
        $t_author = '';
        $t_cover = '';
        $t_notice = '';
        $t_url = '';
        if ($cr['is_enable']) {
            if ($cr['author']) {
                $parsedAuthor = \Typecho\Widget::widget('Widget_Abstract_Contents')->markdown($cr['author']);
                $parsedAuthor = strip_tags($parsedAuthor, '<a><em><strong>');  // 保留链接和强调标签
                $t_author = '<p class="content-copyright">本文作者：' . $parsedAuthor . '</p>';
            }
            if ($cr['url']) {
                if ($cr['is_original']) {
                    $t_url = '<p class="content-copyright">本文链接：<a class="content-copyright" href="' . $cr['url'] . '">' . $cr['url'] . '</a></p>';
                } else {
                    $t_url = '<p class="content-copyright">原文链接：<a class="content-copyright" target="_blank" href="' . $cr['url'] . '">' . $cr['url'] . '</a></p>';
                }
            }
            if ($cr['cover']) {
                $parsedCover = \Typecho\Widget::widget('Widget_Abstract_Contents')->markdown($cr['cover']);
                $parsedCover = strip_tags($parsedCover, '<a><em><strong>');  // 保留链接和强调标签
                $t_cover = '<p class="content-copyright">封面出处：' . $parsedCover . '</p>';
            }
            if ($cr['notice']) {
                $t_notice = '<p class="content-copyright">版权声明：' . $cr['notice'] . '</p>';
            }
            $copyright_html = '<style>p.content-copyright {
    color: var(--theme-color, #07F);margin: 0.5rem 0.5rem 0.5rem;    line-height: 1;
}</style><hr class="content-copyright" style="margin-top:50px" /><div class="ArtinArt" style="    width: 100%;
    margin: 10px;">' . $t_author . $t_url . $t_cover . $t_notice .'</blockquote>';
        }
        return $copyright_html;
    }
    
}
