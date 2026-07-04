/**
 * Curseur animé Kart — suivi du pointeur, orientation horizontale et fumée.
 *
 * La configuration est fournie par wp_localize_script dans window.wcaConfig.
 */
( function () {
	'use strict';

	var config = window.wcaConfig || {};

	if ( ! config.imageUrl ) {
		return;
	}

	// Respect de la préférence système : mouvement direct, sans fumée.
	var reduceMotion = false;
	if ( window.matchMedia ) {
		reduceMotion = window.matchMedia( '( prefers-reduced-motion: reduce )' ).matches;
	}

	var CLICKABLE_SELECTOR = 'a[href], button, input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), summary, label[for], [role="button"], [role="link"], [onclick]';

	function isClickableElement( el ) {
		if ( ! el || el === document.documentElement || el === document.body ) {
			return false;
		}
		if ( el.closest( '.wca-layer' ) ) {
			return false;
		}
		var match = el.closest( CLICKABLE_SELECTOR );
		if ( ! match ) {
			return false;
		}
		if ( match.matches( 'a[href]' ) && match.getAttribute( 'href' ) === '' ) {
			return false;
		}
		return true;
	}

	function CursorAnimator() {
		this.size = config.size || 48;
		this.smoothing = reduceMotion ? 1 : ( config.smoothing || 0.18 );
		this.smokeEnabled = ! reduceMotion && !! config.smokeEnabled;
		this.smokeIntensity = config.smokeIntensity || 0.7;
		this.nativeOnClickable = !! config.nativeOnClickable;

		// Position cible (curseur réel) et position lissée (kart affiché).
		this.target = { x: window.innerWidth / 2, y: window.innerHeight / 2 };
		this.current = { x: this.target.x, y: this.target.y };
		this.prev = { x: this.current.x, y: this.current.y };

		// Orientation route : gauche/droite + légère inclinaison selon le mouvement.
		this.facingRight = true;
		this.tilt = 0;
		this.targetTilt = 0;
		this.maxTilt = typeof config.maxTilt === 'number' ? config.maxTilt : 22;
		this.hasMoved = false;
		this.visible = false;
		this.overClickable = false;

		this.particles = [];
		this.lastEmit = 0;

		this.build();
		this.bind();
		this.loop = this.loop.bind( this );
		requestAnimationFrame( this.loop );
	}

	CursorAnimator.prototype.build = function () {
		var layer = document.createElement( 'div' );
		layer.className = 'wca-layer';
		this.layer = layer;

		var smoke = document.createElement( 'div' );
		smoke.className = 'wca-smoke';
		this.smokeLayer = smoke;

		var kart = document.createElement( 'div' );
		kart.className = 'wca-kart';
		kart.style.width = this.size + 'px';
		kart.style.height = this.size + 'px';
		kart.style.backgroundImage = 'url("' + config.imageUrl + '")';
		this.kart = kart;

		layer.appendChild( smoke );
		layer.appendChild( kart );
		document.body.appendChild( layer );
	};

	CursorAnimator.prototype.bind = function () {
		var self = this;

		document.addEventListener( 'mousemove', function ( e ) {
			self.target.x = e.clientX;
			self.target.y = e.clientY;
			self.hasMoved = true;

			if ( self.nativeOnClickable ) {
				var hit = document.elementFromPoint( e.clientX, e.clientY );
				self.setOverClickable( isClickableElement( hit ) );
			}

			if ( ! self.visible && ! self.overClickable ) {
				self.visible = true;
				self.kart.classList.add( 'is-visible' );
			}
		}, { passive: true } );

		// Masque le kart quand le pointeur quitte la fenêtre.
		document.addEventListener( 'mouseleave', function () {
			self.visible = false;
			self.kart.classList.remove( 'is-visible' );
		} );

		document.addEventListener( 'mouseenter', function () {
			if ( self.hasMoved && ! self.overClickable ) {
				self.visible = true;
				self.kart.classList.add( 'is-visible' );
			}
		} );
	};

	CursorAnimator.prototype.setOverClickable = function ( isOver ) {
		if ( this.overClickable === isOver ) {
			return;
		}
		this.overClickable = isOver;
		document.body.classList.toggle( 'wca-over-clickable', isOver );
		if ( isOver ) {
			this.kart.classList.remove( 'is-visible' );
		} else if ( this.hasMoved ) {
			this.kart.classList.add( 'is-visible' );
		}
	};

	CursorAnimator.prototype.loop = function ( now ) {
		var s = this.smoothing;

		this.prev.x = this.current.x;
		this.prev.y = this.current.y;

		this.current.x += ( this.target.x - this.current.x ) * s;
		this.current.y += ( this.target.y - this.current.y ) * s;

		var dx = this.current.x - this.prev.x;
		var dy = this.current.y - this.prev.y;
		var speed = Math.sqrt( dx * dx + dy * dy );

		// Sens horizontal + inclinaison légère (jamais vertical ni à l'envers).
		if ( speed > 0.4 ) {
			if ( Math.abs( dx ) > 0.3 ) {
				this.facingRight = dx >= 0;
			}

			var rawTilt = ( dy / speed ) * this.maxTilt;
			rawTilt = Math.max( -this.maxTilt, Math.min( this.maxTilt, rawTilt ) );
			this.targetTilt = this.facingRight ? rawTilt : -rawTilt;
		} else {
			this.targetTilt = 0;
		}

		this.tilt += ( this.targetTilt - this.tilt ) * ( reduceMotion ? 1 : 0.18 );

		if ( ! this.overClickable ) {
			var half = this.size / 2;
			var scaleX = this.facingRight ? 1 : -1;
			this.kart.style.transform =
				'translate(' + ( this.current.x - half ) + 'px, ' + ( this.current.y - half ) + 'px) ' +
				'scaleX(' + scaleX + ') rotate(' + this.tilt.toFixed( 2 ) + 'deg)';

			if ( this.smokeEnabled && this.visible ) {
				this.maybeEmitSmoke( now, speed );
			}
		}

		this.updateParticles();

		requestAnimationFrame( this.loop );
	};

	CursorAnimator.prototype.maybeEmitSmoke = function ( now, speed ) {
		if ( speed < 1.5 ) {
			return;
		}

		var intensity = this.smokeIntensity;

		// Plus l'intensité est haute, plus l'émission est fréquente.
		var interval = Math.max( 8, 95 - intensity * 58 );
		if ( now - this.lastEmit < interval ) {
			return;
		}
		this.lastEmit = now;

		// Rafales multiples uniquement au niveau maximal.
		var burst = intensity >= 1.2 ? 4 : 1;

		var back = this.size * 0.42;
		var tiltRad = this.tilt * Math.PI / 180;
		var facingSign = this.facingRight ? 1 : -1;
		var backX = -facingSign * Math.cos( tiltRad ) * back;
		var backY = -Math.sin( tiltRad ) * back;
		var spread = this.size * ( 0.2 + intensity * 0.12 );

		for ( var i = 0; i < burst; i++ ) {
			var jitterX = ( Math.random() - 0.5 ) * spread;
			var jitterY = ( Math.random() - 0.5 ) * spread * 0.45;
			var px = this.current.x + backX + jitterX;
			var py = this.current.y + backY + jitterY;
			this.spawnParticle( px, py, intensity );
		}
	};

	CursorAnimator.prototype.spawnParticle = function ( x, y, intensity ) {
		intensity = intensity || this.smokeIntensity;

		var el = document.createElement( 'span' );
		el.className = 'wca-puff';
		var sizeMul = 0.85 + intensity * 0.3;
		var base = this.size * ( 0.18 + Math.random() * 0.14 ) * sizeMul;
		el.style.width = base + 'px';
		el.style.height = base + 'px';
		el.style.left = ( x - base / 2 ) + 'px';
		el.style.top = ( y - base / 2 ) + 'px';
		this.smokeLayer.appendChild( el );

		this.particles.push( {
			el: el,
			born: performance.now(),
			life: 500 + intensity * 180 + Math.random() * 280,
			drift: ( Math.random() - 0.5 ) * ( 16 + intensity * 10 ),
			rise: 8 + Math.random() * ( 10 + intensity * 8 ),
			maxScale: 1.5 + intensity * 0.35 + Math.random() * 0.8,
			maxOpacity: Math.min( 0.82, 0.32 + intensity * 0.32 )
		} );
	};

	CursorAnimator.prototype.updateParticles = function () {
		var now = performance.now();

		for ( var i = this.particles.length - 1; i >= 0; i-- ) {
			var p = this.particles[ i ];
			var age = now - p.born;
			var progress = age / p.life;

			if ( progress >= 1 ) {
				if ( p.el.parentNode ) {
					p.el.parentNode.removeChild( p.el );
				}
				this.particles.splice( i, 1 );
				continue;
			}

			var scale = 1 + ( p.maxScale - 1 ) * progress;
			var tx = p.drift * progress;
			var ty = -p.rise * progress;
			var peakOpacity = p.maxOpacity || 0.5;
			p.el.style.opacity = String( ( 1 - progress ) * peakOpacity );
			p.el.style.transform = 'translate(' + tx + 'px, ' + ty + 'px) scale(' + scale + ')';
		}
	};

	function init() {
		new CursorAnimator();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
