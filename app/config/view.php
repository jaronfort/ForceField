<?php
use Hologram\Manifest\View;
use Hologram\Web\Html;

return [
    'callbacks' => [
        'onRender' => function (View $view) {
           // add a hack to modify the view before it is sent to the browser
           return $view;
        }
    ]
];