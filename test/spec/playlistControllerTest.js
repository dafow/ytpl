(function() {
	'use strict';

	var root = this;
	var SERVER_APP_PATH = '../../server/';

	root.define( function() {

		describe('PlaylistController', function () {
			it('.id should return the youtube video id', function () {
				var PlaylistController = require(SERVER_APP_PATH + 'controllers/playlistController.js');
				var playlistid = 'PLsomeid';
				var plc = new PlaylistController(playlistid);
				expect(plc.id).to.equal(playlistid);
			});
		});

	});

}).call( this );