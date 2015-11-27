
/*!
 * Module dependencies
 */

var mongoose = require('mongoose');
var Schema = mongoose.Schema;

/**
 * Video schema
 */

var PlaylistSchema = new Schema({
  _id: String,
  name: { type: String, default: '' },
  user: { type: String, ref: 'User' }
});

/**
 * Add your
 * - pre-save hooks
 * - validations
 * - virtuals
 */

/**
 * Methods
 */

PlaylistSchema.method({

});

/**
 * Statics
 */

PlaylistSchema.static({
	list: function(options) {
		const criteria = options.criteria || {};
		const page = options.page || 0;
		const limit = options.limit || 25;
		return this.find(criteria)
			.limit(limit)
			.skip(limit * page)
			.select('name')
			.exec();
	},
	
	get: function(id) {
		return this.findOne({ _id: id })
			.exec();
	}
});

/**
 * Register
 */

mongoose.model('Playlist', PlaylistSchema);
