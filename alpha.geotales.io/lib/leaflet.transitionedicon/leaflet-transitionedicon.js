var L = window.L;

function transitionEvent() {
	var el = document.createElement('div')
	var names = {
		'WebkitTransition': 'webkitTransitionEnd',
		'MozTransition': 'transitionend',
		'OTransition': 'otransitionend',
		'transition': 'transitionend'
	};

	for (var name in names) {
		if (el.style[name] !== undefined) {
			return names[name];
		}
	}
}

var watching = 0;
var interval;
function transitionWatcher(el, callback) {
	var called;
	var done = function(e) {
		if (e && e.target !== el) return;
		if (called) return;

		called = true;
		el.removeEventListener(transitionEvent, done);
		callback(el);
	};

	el.addEventListener(transitionEvent, done, false);
	setTimeout(done, 1000);
};

function triggerTransition(els, className, jitter, callback) {
	callback = callback || function() {};
	if (!className) return callback();

	var i, n = els.length;
	var leave = /-leave$/.test(className);
	for (i = 0; i < n; i++) {
		els[i].className += ' ' + className;
		if (!leave) els[i].style.visibility = 'visible';
	}

	var completed = 0;
	var delay = Math.round(Math.random() * jitter);

	setTimeout(function() {
		for (i = 0; i < n; i++) {
			var el = els[i];
			transitionWatcher(el, transitionEnd);
			el.className = el.className.replace(className, className + '-active');
		}
	}, delay);

	function transitionEnd(el) {
		if (!leave) el.className = el.className.replace(className + '-active', '').replace(className, '');
		if (++completed === n) callback();
	}
}

var _onAdd = L.Marker.prototype.onAdd;
var _onRemove = L.Marker.prototype.onRemove;

L.Marker.prototype.onAdd = function() {
	var self = this;
	_onAdd.apply(self, arguments);
	var iconInstance = self.options.icon;
	if (iconInstance && iconInstance._cssIn) {
		var visible = self._map.getBounds().contains(self.getLatLng());
		if (visible) iconInstance._cssIn(self._icon, self._shadow);
	}
};

L.Marker.prototype.onRemove = function(map) {
	var self = this, args = arguments;
	var iconInstance = self.options.icon;
	if (iconInstance && iconInstance._cssOut) {
		var visible = self._map.getBounds().contains(self.getLatLng());
		if (!visible) return _onRemove.apply(self, args);
		iconInstance._cssOut(self._icon, self._shadow, function() {
			_onRemove.apply(self, args);
		});
	} else {
		_onRemove.apply(self, args);
	}
};

/**
 * A subclass of L.Icon that adds support for CSS transitions.
 *
 * The transition states will be:
 *   1) "{cssTransitionName}-enter"
 *   2) "{cssTransitionName}-enter-active"
 *   3) "{cssTransitionName}-leave"
 *   4) "{cssTransitionName}-leave-active"
 *
 * Usage:
 *   var MyIcon = L.TransitionedIcon.extend({
 *       options: {
 *           cssTransitionBatches: 3,
 *           cssTransitionJitterIn: 0,
 *           cssTransitionJitterOut: 0,
 *           cssTransitionName: 'my-transition'
 *       }
 *   });
 *
 * @type {L.Icon}
 */
L.TransitionedIcon = !transitionEvent ? L.Icon : L.Icon.extend({
	_cssIn: function(icon, shadow, callback) {
		var options = this.options;
		var transitionName = options.cssTransitionName;
		icon.style.visibility = 'hidden';
		icon.className += ' ' + transitionName;
		shadow.style.visibility = 'hidden';
		shadow.className += ' ' + transitionName;
		queueTransition(options.cssTransitionBatches, transitionName + '-enter', [icon, shadow], options.cssTransitionJitterIn, callback);
	},
	_cssOut: function(icon, shadow, callback) {
		var options = this.options;
		queueTransition(options.cssTransitionBatches, options.cssTransitionName + '-leave', [icon, shadow], options.cssTransitionJitterOut, callback);
	}
});

var _queuedTransitions = {};
function queueTransition(batchCount, className, els, jitter, callback) {
	// execute immediately if batching disabled
	if (!batchCount) return triggerTransition(els, className, jitter, callback);

	// push to queue
	var queue;
	var queueKey = className;
	if (!_queuedTransitions[queueKey]) {
		queue = _queuedTransitions[className] = {className: className, jitter: jitter, els: [], callbacks: []};
		setTimeout(queueExecutor(queueKey, batchCount));
	} else {
		queue = _queuedTransitions[className];
	}
	queue.els = queue.els.concat(els);
	if (callback) queue.callbacks.push(callback);
}

function queueExecutor(key, batchCount) {
	return function() {
		var queue = _queuedTransitions[key];
		if (!queue) return;
		delete _queuedTransitions[key];

		var i, n;
		var batches = [];
		var batchesExpected = Math.min(batchCount, queue.els.length);
		var batchesCompleted = 0;
		for (i = 0; i < batchCount; i++) {
			batches[i] = [];
		}
		for (i = 0, n = queue.els.length; i < n; i++) {
			var batchIndex = i % batchCount;
			batches[batchIndex].push(queue.els[i]);
		}
		for (i = 0; i < batchCount; i++) {
			triggerTransition(batches[i], queue.className, queue.jitter, done);
		}
		function done() {
			if (++batchesCompleted === batchesExpected) {
				for (var i = 0, n = queue.callbacks.length; i < n; i++) {
					queue.callbacks[i]();
				}
			}
		}
	};
}
