/*******************************************************************************
* Copyright (C) Nordfjord EDB AS - All Rights Reserved                         *
*                                                                              *
* Unauthorized copying of this file, via any medium is strictly prohibited     *
* Proprietary and confidential                                                 *
* Written by Andreas Atakan <aca@geotales.io>, January 2022                  *
*******************************************************************************/

"use strict";


L.ImageOverlay.include({

	_slideToUntil:		undefined,
	_slideToDuration:	undefined,
	_slideToBounds:		undefined,
	_slideFromBounds:	undefined,

	slideTo: function slideTo(bounds, options) {
		if(!this._map) { return; }

		this._slideToDuration = options.duration;
		this._slideToUntil    = performance.now() + options.duration;
		this._slideFromBounds = this.getBounds();
		this._slideToBounds   = L.latLngBounds(bounds);

		if(this._slideFromBounds.equals(this._slideToBounds)) { return; }
		this.fire("movestart");
		this._slideTo();

		return this;
	},

	slideCancel: function slideCancel() {
		L.Util.cancelAnimFrame(this._slideFrame);
	},

	_slideTo: function _slideTo() {
		if(!this._map) { return; }

		let remaining = this._slideToUntil - performance.now();

		if(remaining < 0) {
			this.setBounds(this._slideToBounds);
			this.fire("moveend");
			return this;
		}

		let percentDone = (this._slideToDuration - remaining) / this._slideToDuration;

		let currNW = this._map.latLngToContainerPoint( this._slideToBounds.getNorthWest() )
				.multiplyBy(percentDone)
				.add(
					this._map.latLngToContainerPoint( this._slideFromBounds.getNorthWest() )
						.multiplyBy(1 - percentDone)
				),
			currSE = this._map.latLngToContainerPoint( this._slideToBounds.getSouthEast() )
				.multiplyBy(percentDone)
				.add(
					this._map.latLngToContainerPoint( this._slideFromBounds.getSouthEast() )
						.multiplyBy(1 - percentDone)
				);

		this.setBounds([
			this._map.containerPointToLatLng(currNW),
			this._map.containerPointToLatLng(currSE)
		]);

		this._slideFrame = L.Util.requestAnimFrame(this._slideTo, this);
	}

});

L.ImageOverlay.addInitHook(function() {
	this.on("move", this.slideCancel, this);
});



L.Polyline.include({

	_slideToUntil:		undefined,
	_slideToDuration:	undefined,
	_slideToLatLngs:	undefined,
	_slideFromLatLngs:	undefined,

	slideTo: function slideTo(latlngs, options) {
		if(!this._map) { return; }

		this._slideToDuration  = options.duration;
		this._slideToUntil     = performance.now() + options.duration;
		this._slideFromLatLngs = this.getLatLngs();
		this._slideToLatLngs   = latlngs;

		if(this.getBounds().equals(L.polyline(latlngs).getBounds())) {
			let c = true;
			let check = (a,b) => {
				let l = Math.min(a.length, b.length);
				if(l < 200) {
					for(let i of a) {
						for(let j of b) {
							if(!L.latLng(i).equals(L.latLng(j))) {
								c = false; return;
							}
						}
					}
				}else{
					for(let i = 0; i < l*0.02; i++) {
						let r = Math.floor(Math.random() * l);
						if(!L.latLng(a[r]).equals(L.latLng(b[r]))) {
							c = false; return;
						}
					}
				}
			};
			let reduce = (a,b) => {
				if(Array.isArray(a[0]) && Array.isArray(b[0])) {
					for(let i of a) { for(let j of b) { reduce(i, j); } }
				}else if(Array.isArray(a[0])) {
					for(let i of a) { reduce(i, b); }
				}else if(Array.isArray(b[0])) {
					for(let j of b) { reduce(a, j); }
				}else{ check(a, b); }
			};
			reduce(this._slideToLatLngs, this._slideFromLatLngs);

			if(c) { return; }
		}

		this.fire("movestart");
		this._slideTo();

		return this;
	},

	slideCancel: function slideCancel() {
		L.Util.cancelAnimFrame(this._slideFrame);
	},

	_slideTo: function _slideTo() {
		if(!this._map) { return; }

		let remaining = this._slideToUntil - performance.now();

		if(remaining < 0) {
			this.setLatLngs(this._slideToLatLngs);
			this.fire("moveend");
			return this;
		}

		let percentDone = (this._slideToDuration - remaining) / this._slideToDuration;

		let f = (a,b) => {
			if(Array.isArray(a[0]) && Array.isArray(b[0])) {
				let r = [];
				for(let i = 0; i < Math.min(a.length, b.length); i++) {
					r.push( f(a[i], b[i]) );
				}
				return r;
			}else{
				let r = [];
				for(let i = 0; i < Math.min(a.length, b.length); i++) {
					r.push(
						this._map.containerPointToLatLng(
							this._map.latLngToContainerPoint( b[i] )
							.multiplyBy(percentDone)
							.add(
								this._map.latLngToContainerPoint( a[i] )
								.multiplyBy(1 - percentDone)
							)
						)
					);
				}
				return r;
			}
		};
		this.setLatLngs( f(this._slideFromLatLngs, this._slideToLatLngs) );

		this._slideFrame = L.Util.requestAnimFrame(this._slideTo, this);
	}

});

L.Polyline.addInitHook(function() {
	this.on("move", this.slideCancel, this);
});



/*L.Polygon.include({

	_slideTo: function _slideTo() {
		if(!this._map) { return; }

		let remaining = this._slideToUntil - performance.now();

		if(remaining < 0) {
			this.setLatLngs(this._slideToLatLngs);
			this.fire("moveend");
			return this;
		}

		let percentDone = (this._slideToDuration - remaining) / this._slideToDuration;

		let currLatLngs = [];
		for(let i = 0; i < Math.min(this._slideFromLatLngs.length, this._slideToLatLngs.length); i++) {
			let c = [];
			for(let j = 0; j < Math.min(this._slideFromLatLngs[i].length, this._slideToLatLngs[i].length); j++) {
				let curr = this._map.latLngToContainerPoint( this._slideToLatLngs[i][j] )
					.multiplyBy(percentDone)
					.add(
						this._map.latLngToContainerPoint( this._slideFromLatLngs[i][j] )
							.multiplyBy(1 - percentDone)
					);
				c.push( this._map.containerPointToLatLng(curr) );
			}
			currLatLngs.push(c);
		}
		this.setLatLngs(currLatLngs);

		this._slideFrame = L.Util.requestAnimFrame(this._slideTo, this);
	}

});*/



L.Circle.include({

	_slideToUntil:		undefined,
	_slideToDuration:	undefined,
	_slideToLatLng:		undefined,
	_slideFromLatLng:	undefined,
	_slideToRadius:		undefined,
	_slideFromRadius:	undefined,

	slideTo: function slideTo(latlng, options) {
		if(!this._map) { return; }

		this._slideToDuration = options.duration;
		this._slideToUntil    = performance.now() + options.duration;
		this._slideFromLatLng = this.getLatLng();
		this._slideToLatLng   = latlng;
		this._slideFromRadius = this.getRadius();
		this._slideToRadius   = options.radius;

		if(this._slideFromLatLng.equals(this._slideToLatLng)
		&& this._slideFromRadius == this._slideToRadius) { return; }

		this.fire("movestart");
		this._slideTo();

		return this;
	},

	slideCancel: function slideCancel() {
		L.Util.cancelAnimFrame(this._slideFrame);
	},

	_slideTo: function _slideTo() {
		if(!this._map) { return; }

		let remaining = this._slideToUntil - performance.now();

		if(remaining < 0) {
			this.setLatLng(this._slideToLatLng);
			this.setRadius(this._slideToRadius);
			this.fire("moveend");
			return this;
		}

		let percentDone = (this._slideToDuration - remaining) / this._slideToDuration;

		let currPoint = this._map.latLngToContainerPoint( this._slideToLatLng )
			.multiplyBy(percentDone)
			.add(
				this._map.latLngToContainerPoint( this._slideFromLatLng )
					.multiplyBy(1 - percentDone)
			);
		this.setLatLng(
			this._map.containerPointToLatLng(currPoint)
		);

		let currRadius = this._slideToRadius * percentDone + this._slideFromRadius * (1 - percentDone);
		this.setRadius(currRadius);

		this._slideFrame = L.Util.requestAnimFrame(this._slideTo, this);
	}

});

L.Circle.addInitHook(function() {
	this.on("move", this.slideCancel, this);
});
