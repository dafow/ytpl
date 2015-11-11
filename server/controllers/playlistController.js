'use strict';

var playlistModel = require('../models/playlistModel.js');


module.exports = function(id) {

	
	
	this.yid = id;
	this.model = playlistModel;
	
	this.sync = function() {
		
	};
	
	this.getJSON = function() {
		model.findOne({ yid: this.yid}, function(err, playlist) {
			if (err) {
				console.log('error at playlistController.js: ' + err);
				return err;
			}
			return playlist;
		});
	};
};