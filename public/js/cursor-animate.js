/**
 * Curseur animé Kart — suivi du pointeur, rotation directionnelle et fumée.
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

	function CursorAnimator() {
		this.size = config.size || 48;
		this.smoothing = reduceMotion ? 1 : ( config.smoothing || 0.18 );
		this.smokeEnabled = ! reduceMotion && !! config.smokeEnabled;
		this.smokeIntensity = config.smokeIntensity || 0.7;

		// Position cible (curseur réel) et position lissée (kart affiché).
		this.target = { x: window.innerWidth / 2, y: window.innerHeight / 2 };
		this.current = { x: this.target.x, y: this.target.y };
		this.prev = { x: this.current.x, y: this.current.y };

		this.angle = 0;
		this.targetAngle = 0;
		this.hasMoved = false;
		this.visible = false;

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
			if ( ! self.visible ) {
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
			if ( self.hasMoved ) {
				self.visible = true;
				self.kart.classList.add( 'is-visible' );
			}
		} );
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

		// Met à jour l'angle seulement si le mouvement est significatif,
		// pour éviter que le kart pivote au repos.
		if ( speed > 0.4 ) {
			this.targetAngle = Math.atan2( dy, dx ) * 180 / Math.PI;
		}

		this.angle = this.lerpAngle( this.angle, this.targetAngle, reduceMotion ? 1 : 0.2 );

		// Le hotspot (point de clic) est le centre de l'image ; on centre le kart.
		var half = this.size / 2;
		this.kart.style.transform =
			'translate(' + ( this.current.x - half ) + 'px, ' + ( this.current.y - half ) + 'px) rotate(' + this.angle + 'deg)';

		if ( this.smokeEnabled && this.visible ) {
			this.maybeEmitSmoke( now, speed );
		}

		this.updateParticles();

		requestAnimationFrame( this.loop );
	};

	/**
	 * Interpolation angulaire avec gestion du passage 180/-180.
	 */
	CursorAnimator.prototype.lerpAngle = function ( from, to, t ) {
		var diff = ( ( to - from + 540 ) % 360 ) - 180;
		return from + diff * t;
	};

	CursorAnimator.prototype.maybeEmitSmoke = function ( now, speed ) {
		if ( speed < 1.5 ) {
			return;
		}

		// Intervalle d'émission modulé par l'intensité (plus fort = plus fréquent).
		var interval = 90 - this.smokeIntensity * 60;
		if ( now - this.lastEmit < interval ) {
			return;
		}
		this.lastEmit = now;

		// L'arrière du kart est à l'opposé de la direction (angle + 180°).
		var rad = ( this.angle + 180 ) * Math.PI / 180;
		var back = this.size * 0.42;
		var jitter = ( Math.random() - 0.5 ) * this.size * 0.25;
		var px = this.current.x + Math.cos( rad ) * back + Math.cos( rad + Math.PI / 2 ) * jitter;
		var py = this.current.y + Math.sin( rad ) * back + Math.sin( rad + Math.PI / 2 ) * jitter;

		this.spawnParticle( px, py );
	};

	CursorAnimator.prototype.spawnParticle = function ( x, y ) {
		var el = document.createElement( 'span' );
		el.className = 'wca-puff';
		var base = this.size * ( 0.18 + Math.random() * 0.14 );
		el.style.width = base + 'px';
		el.style.height = base + 'px';
		el.style.left = ( x - base / 2 ) + 'px';
		el.style.top = ( y - base / 2 ) + 'px';
		this.smokeLayer.appendChild( el );

		this.particles.push( {
			el: el,
			born: performance.now(),
			life: 550 + Math.random() * 300,
			drift: ( Math.random() - 0.5 ) * 20,
			rise: 8 + Math.random() * 14,
			maxScale: 1.6 + Math.random() * 0.9
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
			p.el.style.opacity = String( ( 1 - progress ) * 0.5 );
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
