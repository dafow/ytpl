const mongoose = require('mongoose');
const async = require('async');
const Promise = require('promise');

const playlistSchema = new mongoose.Schema({
	ytid: String,
	title: String,
	videos: [String]
}, {timestamps: true});

playlistSchema.statics.findUserPlaylists = function(userPlaylistsIds) {
	var that = this;
	return new Promise(function(resolve, reject) {
		var userPlaylists = [];
		
		async.each(userPlaylistsIds, (id, callback) => {
			that.findOne({ ytid: id }, (err, playlist) => {
				if (err) { reject(err); }
				if (playlist) {
					userPlaylists.push({ title: playlist.title, id: playlist.ytid});
					callback();
				}
				else {
					callback("Couldn't find matching playlist on /playlists");
				}
			});
		}, (err) => {
			if (err) { reject(err); }
			else {
				resolve(userPlaylists);
			}
		});
	});
}

const Playlist = mongoose.model('Playlist', playlistSchema);

module.exports = Playlist;