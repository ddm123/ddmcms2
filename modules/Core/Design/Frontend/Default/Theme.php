<?php

namespace Core\Design\Frontend\Default;

use Core\Block\Frontend\Template;

class Theme
{

    public function init(Template $template): void
    {
        $template->addCss('common.css', 'core');
        $template->addCss('style.css');
        $template->addJs('common.js', 'core');
    }
}
