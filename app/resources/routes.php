<?php
use ForceField\Routing\Router;
use ForceField\Network\Url;
use ForceField\Security\Password;

// a simple redirect
Router::get('www.example.net/', 'https://www.example.com/');


// Default 404
Router::error404('/', function () {
    $view = view('site');
    $view->title('My Site | 404 Error');
    $view->find('h1')->text('404 Page Not Found');
    return $view;
});

/**
 * Global Routes
 */

// Images
Router::get('/img/{img}', function ($img) {
    // File exists or 404
    $url = Url::current();
    if (is_file(LIBPATH . '/images/' . $img))
        return image($img);
    $site = $url->domain(2) . '/';
    return ($file = image($site . $img)) ? $file : 404;
})->limit('img', '[a-zA-Z0-9\_\-]+\.(jpg|jpeg|bmp|png|gif|svg)');

// Font
Router::get('/font/{font}', function ($font) {
    // File exists or 404
    return ($file = font($font)) ? readfile($file->fullpath()) : 404;
})->limit('font', '[a-zA-Z0-9\_\-]+\.(otf|ttf|woff|woff2)');

// CSS
Router::get('/css/{css}', function ($css) {
    // File exists or 404
    $site = '';
    $url = Url::current();
    $globals = [
        'fonts.css',
        'main.css'
    ];
    if (! in_array($css, $globals))
        $site = $url->domain(2) . '/' . $url->domain(3) . '/';
    
    return ($file = css(str_replace('.css', '', $site . $css))) ? $file : 404;
})->limit('css', '[a-zA-Z0-9\_\-]+\.css');

// JavaScript
Router::get('/js/{js}', function ($js) {
    if ($js == 'jquery.js')
        return getfile(LIBPATH . '/js/jquery.min.js');
    
    // File exists or 404
    $site = '';
    $url = Url::current();
    
    // If not in root JS directory
    if (! is_file(LIBPATH . '/js/' . $js))
        $site = $url->domain(2) . '/';
    
    return ($file = js(str_replace('.js', '', $site . $js))) ? $file : 404;
})->limit('js', '[a-zA-Z0-9\_\-]+\.js');