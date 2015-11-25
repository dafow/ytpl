
/*!
 * Module dependencies
 */

var mongoose = require('mongoose');
var Schema = mongoose.Schema;

/**
 * Video schema
 */

var VideoSchema = new Schema({
  ytid: { type: String, default: '' },
  name: { type: String, default: '' }
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

VideoSchema.method({

});

/**
 * Statics
 */

VideoSchema.static({

});

/**
 * Register
 */

mongoose.model('Video', VideoSchema);
