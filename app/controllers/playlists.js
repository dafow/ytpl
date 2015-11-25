/*!
 * Module dependencies.
 */

const wrap = require('co-express');
const mongoose = require('mongoose');
const Playlist = mongoose.model('Playlist');

exports.index = wrap(function* (req, res) {
	const list = yield Playlist.list({});
    res.render('playlists/index', {
        title: 'Synced Playlists',
		playlists: list
    });
});