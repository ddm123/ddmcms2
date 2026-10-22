<?php

namespace Core\Design\Adminhtml\Default;

use Admin\Block\Template;

class Theme
{

    public function init(Template $template): void
    {
        $template->addCss('admin.css', 'admin');
        $template->addJs('common.js', 'core');
        $template->addJs('admin.js', 'admin');
        $template->addComboboxScript();
    }
}
