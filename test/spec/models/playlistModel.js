(function() {
	'use strict';

	var root = this;

	root.define([
		'models/playlistModel'
		],
		function( Playlistmodel ) {

			describe('Playlistmodel Model', function () {

				it('should be an instance of Playlistmodel Model', function () {
					var playlistModel = new Playlistmodel();
					expect( playlistModel ).to.be.an.instanceof( Playlistmodel );
				});

				it('should have more test written', function(){
					expect( false ).to.be.ok;
				});
			});

		});

}).call( this );