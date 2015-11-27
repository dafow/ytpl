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

exports.get = wrap(function* (req, res) {
	const pl = yield Playlist.get(req.params.id);
	if (pl) {
		res.render('playlists/index', {
			title: 'Playlist: ' + pl.name,
			playlists: pl
		});
	}
});

exports.insert = wrap(function* (req, res) {
	var pl_id = req.body.pl_id;
	if (pl_id) {
		
	}
});