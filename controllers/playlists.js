const request = require('request');
const url = require('url');
const async = require('async');
const Playlist = require('../models/Playlist');
const User = require('../models/User');


/**
 * GET /playlists
 * Playlists index page.
 */
exports.index = (req, res) => {
	var userPlaylists = [];
	async.each(req.user.playlists, (id, callback) => {
		Playlist.findOne({ ytid: id }, (err, playlist) => {
			if (err) { console.log(err); }
			if (playlist) {
				userPlaylists.push({ title: playlist.title, id: playlist.ytid});
				callback();
			}
			else {
				callback("Couldn't find matching playlist on /playlists");
			}
		});
	}, (err) => {
		if (err) { console.log(err); }
		else {
			res.render('playlists', {
				title: 'Playlists',
				playlists: userPlaylists
			});
		}
	});
};

/**
 * POST /playlists/create
 * Add playlist to user document
 */
exports.create = (req, res, next) => {
	req.assert('plURL', 'Oops! Looks like an invalid Youtube Playlist URL').notEmpty().isURL();
	const errors = req.validationErrors();
	
	if (errors) {
		req.flash('errors', errors);
		return res.redirect('/playlists');
	}
	
	const playlistId = url.parse(req.body.plURL, true).query.list;
	if (!playlistId) {
		req.flash('errors', { msg: 'Oops! Looks like an invalid Youtube Playlist URL' });
		return res.redirect('/playlists');
	}
	
	User.findOne({ email: req.user.email}, (err, user) => {
		if (err) { return next(err); }
		if (user.playlists.includes(playlistId)) {
			req.flash('errors', { msg: 'You already added this playlist' });
			return res.redirect('/playlists');
		}
		
		Playlist.findOne({ ytid: playlistId }, (err, pl) => {
			if (err) { return next(err); }
			//if playlist not already created, save it with basic info (no sync yet) to display it on next request
			if (!pl) {
				//GET playlist resource from Youtube API
				const playlistAPI = `https://www.googleapis.com/youtube/v3/playlists?part=snippet,status&fields=items(snippet/title,status)&id=${playlistId}&key=${process.env.YOUTUBE_KEY}`;
				request(playlistAPI, (err, res, body) => {
					if (!err && res.statusCode == 200) {
						try {
							const resParsed = JSON.parse(body);
							if (resParsed.items.length == 1) {
								Playlist.create({ ytid: playlistId, title: resParsed.items[0].snippet.title }, (err, newPlaylist) => {
									if (err) { return next(err); }
								});
							}
							else {
								req.flash('errors', { msg: 'An issue occurred while adding the playlist' });
								return res.redirect('/playlists');
							}
						}
						catch (JSONerr) {
							next(JSONerr);
						}
					}
					else {
						next(err);
					}
				});
			}
		});
		
		user.playlists.push(playlistId);
		user.save((err, updatedUser) => {
			if (err) { return next(err); }
			req.flash('success', { msg: 'Playlist successfully added' });
			res.redirect('/playlists');
		});
	});
};

exports.update = (req, res, next) => {

};