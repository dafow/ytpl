/*!
 * Module dependencies.
 */

const config = require('../../config/config');
const wrap = require('co-express');
const mongoose = require('mongoose');
const google = require('googleapis');
const Playlist = mongoose.model('Playlist');

exports.index = wrap(function* (req, res) {
	const list = yield Playlist.list({});
    res.render('playlists/index', {
        title: 'Synced Playlists',
		playlists: list
    });
});

exports.get = wrap(function* (req, res) {
	var pl = yield Playlist.get(req.params.id);
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
		console.log('Creating playlist: ' + pl_id);
		const ytapi = google.youtube('v3');
		ytapi.playlists.list({
			auth: config.youtube.key,
			id: pl_id,
			fields: "pageInfo(totalResults),items(snippet(description)),contentDetails",
			part: "pageInfo,snippet,contentDetails"
		}, function(err, data) {
			if (err) {
				console.log('Unexpected error: ' + err);
			}
			else {
				console.log('Result: ' + data);
				// var pl = new Playlist({
					// _id: pl_id,
					// name: data.
			}	// });
		});
	}
	else {
		console.log('Error: playlist id required for /POST playlists');
	}
});