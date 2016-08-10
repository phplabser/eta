<?php
/**
 * Eta Framework 2
 *
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @copyright Copyright (c) 2014-2015 Phplabs (http://www.phplabs.pl)
 */

namespace Eta\View;


use Eta\Interfaces\ViewHelperInterface;
use Eta\Model\Singleton;

class Helper extends Singleton implements ViewHelperInterface {

    public function execute(...$params) {
        return;
    }
} 