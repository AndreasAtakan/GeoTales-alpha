/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, January 2022                  *
*******************************************************************************/

"use strict";


L.TileLayer.Mars = L.TileLayer.extend({

	getTileUrl: function(coords) {
		let bound = Math.pow(2, coords.z),
			x = coords.x,
			y = coords.y;

		// Don't repeat across y-axis (vertically).
		if(y < 0 || y >= bound) { return null; }

		// Repeat across x-axis.
		if(x < 0 || x >= bound) { x = (x % bound + bound) % bound; }

		let qstr = "t";
		for(let z = 0; z < coords.z; z++) {
			bound = bound / 2;
			if(y < bound) {
				if (x < bound) { qstr += "q"; }
				else {
					qstr += "r";
					x -= bound;
				}
			}else{
				if(x < bound) {
					qstr += "t";
					y -= bound;
				}else{
					qstr += "s";
					x -= bound;
					y -= bound;
				}
			}
		}

		return `https://mw1.google.com/mw-planetary/mars/${this.options.layer}/${qstr}.jpg`;
	},

	initialize: function(options) {
		L.TileLayer.prototype.initialize.call(this, "", options);
	}

});


L.tileLayer.mars = function(options) { return new L.TileLayer.Mars(options); }
