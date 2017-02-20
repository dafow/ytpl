const request = require('request');
const url = require('url');
const async = require('async');
const Promise = require('promise');
const Playlist = require('../models/Playlist');
const User = require('../models/User');


/**
 * GET /playlists
 * Playlists index page.
 */
exports.index = (req, res) => {
	console.log("showing index");
	Playlist.findUserPlaylists(req.user.playlists).then(function(result) {
		res.render('playlists', {
			title: 'Playlists',
			playlists: result
		});
	}, function(error) { console.log(error); });
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
							return next(JSONerr);
						}
					}
					else {
						return next(err);
					}
				});
			}
			console.log("creating new playlist...");
			user.playlists.push(playlistId);
			user.save((err, updatedUser) => {
				if (err) { return next(err); }
				console.log("saving user...");
				req.flash('success', { msg: 'Playlist successfully added' });
				return res.redirect('/playlists');
			});
		});
		
	});
};

exports.show = (req, res, next) => {
	const playlist = Playlist.findOne({ ytid: req.params.ytid }, (err, pl) => {
		if (err) { next(err); }
		
		res.render('playlists/videos', {
			title: 'Playlists',
			playlist: pl
		});
	});
};

//need to refactor getting playlist info, json parsing...
exports.sync = (req, res, next) => {
	//check if playlist has been updated since
	const getPlaylistItemsUrl = `https://www.googleapis.com/youtube/v3/playlistItems?key=${process.env.YOUTUBE_KEY}&` +
			`part=snippet,id,status&fields=nextPageToken,pageInfo,items(snippet(title,description,thumbnails(default,medium),resourceId/videoId),status)` +
			`&maxResults=50&playlistId=${req.params.ytid}`;
	request(getPlaylistItemsUrl, (err, response, body) => {
		if (err) { return next(err); }
		if (response.statusCode != 200) {
			try {
				const error = JSON.parse(body);
				return next(error);
			}
			catch (JSONerr) {
				return next('Youtube API returned an error while retrieving playlist (error message parse failed): ' + JSONerr);
			}
		}
		//Check number of pages of results to check, and sync each page
		try {
			var playlistItems = JSON.parse(body);
			var stats = {
				"deleted": [],
				"private": [],
				"playlist_deleted": []
			};
			if (playlistItems.nextPageToken) {
				async.whilst(
					function() { return playlistItems.nextPageToken !== undefined },
					function(callback) {
						console.log("Getting next page..." + playlistItems.nextPageToken);
						var getNextPageUrl = getPlaylistItemsUrl + '&pageToken=' + playlistItems.nextPageToken;
						request(getNextPageUrl, (err, response, body) => {
							if (err) { return callback(err); }
							if (response.statusCode != 200) {
								try {
									const error = JSON.parse(body);
									callback(error);
								}
								catch (JSONerr) {
									callback('Youtube API returned an error while retrieving playlist (error message parse failed): ' + JSONerr);
								}
							}
							
							//Update current page
							try {
								playlistItems = JSON.parse(body);
								
								//Send current page to model to sync
								Playlist.sync(playlistItems, stats).then(function(result) {
									callback(null, result);
								}, function(error) { callback(error); });
							}
							catch (JSONerr) {
								callback('Youtube API returned an error while retrieving playlist (error message parse failed): ' + JSONerr);
							}
						});
					},
					function(err, stats) {
						if (err) { console.log("async.whilst error:" + err); }
						res.json(JSON.stringify(stats));
					}
				);
			}
			else {
				Playlist.sync(playlistItems, stats).then(function(result) {
					res.json(JSON.stringify(stats));
				}, function(error) { next(error); });
			}
		}
		catch (JSONerr) {
			return next('PlaylistItems response sync failed: ' + JSONerr);
		}
	});
};