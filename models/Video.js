const mongoose = require('mongoose');

const videoSchema = new mongoose.Schema({
	ytid: String,
	title: String,
	publishedAt: Date,
	thumbnails {
		small: String,
		medium: String
	}
});

const Video = mongoose.model("Video", videoSchema);

module.exports = Video;