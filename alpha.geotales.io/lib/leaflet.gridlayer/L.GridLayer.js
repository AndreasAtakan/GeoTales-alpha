/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, January 2022                  *
*******************************************************************************/

"use strict";


// BUG-FIX; White-lines on basemap

let originalInitTile = L.GridLayer.prototype._initTile;
L.GridLayer.include({
	_initTile: function (tile) {
		originalInitTile.call(this, tile);

		let tileSize = this.getTileSize();
		tile.style.width = `${tileSize.x + 1}px`;
		tile.style.height = `${tileSize.y + 1}px`;
	}
});
