<?php
/**
 * @author Marcin Szczurek <marcin.szczurek@phplabs.pl>
 * @version 1.0
 */

namespace Eta\Interfaces;

interface PostRendererInterface {

    public function render($content) : string;

}