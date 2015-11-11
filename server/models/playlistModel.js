var mongoose = require('mongoose');
var Schema = mongoose.Schema;

var playlistSchema = new Schema({
	yid: String,
	name: String
});

module.exports = mongoose.model('Playlist', playlistSchema);