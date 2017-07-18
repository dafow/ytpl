<?php
// Kickstart the framework
$f3 = require('lib/base.php');
// Load configuration
$f3->config('config.ini');

$f3->route('GET /',
    function($f3) {
        echo \Template::instance()->render('home.htm');
    }
);

$f3->route('GET /login', 'User->login');
$f3->route('POST /login', 'User->auth');
$f3->route('GET /register', 'User->register');
$f3->route('POST /register', 'User->create');
$f3->route('GET /logout', 'User->logout');

$f3->route('GET /playlists', 'Playlist->index');
$f3->route('GET /playlists/page/@page', 'Playlist->index');
$f3->route('GET /playlists/@plid', 'Playlist->show');
$f3->route('POST /playlists', 'Playlist->addPlaylist');
$f3->route('POST /playlists/@plid/sync', 'Playlist->sync');

$f3->route('POST /videos/@id/update', 'Video->update');

$f3->run();