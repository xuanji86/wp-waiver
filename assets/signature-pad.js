/**
 * Minimal signature pad for the waiver form. Vanilla JS, pointer events
 * (mouse / touch / stylus). Strokes are kept in memory and redrawn on
 * resize; on submit the canvas is exported as a PNG data URL into the
 * hidden w_sig_img field. No signature drawn -> submit is blocked.
 */
(function () {
	var canvas = document.getElementById('aaa-sigpad');
	if (!canvas) {
		return;
	}
	var form = canvas.closest('form');
	var hidden = document.getElementById('aaa-sig-data');
	var clearBtn = document.getElementById('aaa-sig-clear');
	var hint = document.getElementById('aaa-sig-hint');
	var ctx = canvas.getContext('2d');
	var strokes = [];
	var current = null;
	var drawing = false;

	var PAPER = '#E8E6DE';
	var INK = '#14130E';

	function redraw() {
		ctx.fillStyle = PAPER;
		ctx.fillRect(0, 0, canvas.clientWidth, canvas.clientHeight);
		ctx.strokeStyle = INK;
		ctx.fillStyle = INK;
		ctx.lineWidth = 2.2;
		ctx.lineCap = 'round';
		ctx.lineJoin = 'round';
		strokes.forEach(function (s) {
			if (s.length < 2) {
				ctx.beginPath();
				ctx.arc(s[0].x, s[0].y, 1.3, 0, Math.PI * 2);
				ctx.fill();
				return;
			}
			ctx.beginPath();
			ctx.moveTo(s[0].x, s[0].y);
			for (var i = 1; i < s.length; i++) {
				ctx.lineTo(s[i].x, s[i].y);
			}
			ctx.stroke();
		});
	}

	function resize() {
		var ratio = window.devicePixelRatio || 1;
		canvas.width = canvas.clientWidth * ratio;
		canvas.height = canvas.clientHeight * ratio;
		ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
		redraw();
	}

	function pos(e) {
		var r = canvas.getBoundingClientRect();
		return { x: e.clientX - r.left, y: e.clientY - r.top };
	}

	canvas.addEventListener('pointerdown', function (e) {
		e.preventDefault();
		canvas.setPointerCapture(e.pointerId);
		drawing = true;
		current = [pos(e)];
		strokes.push(current);
		redraw();
	});
	canvas.addEventListener('pointermove', function (e) {
		if (!drawing) {
			return;
		}
		e.preventDefault();
		current.push(pos(e));
		redraw();
	});
	['pointerup', 'pointercancel'].forEach(function (type) {
		canvas.addEventListener(type, function () {
			drawing = false;
			current = null;
		});
	});

	clearBtn.addEventListener('click', function (e) {
		e.preventDefault();
		strokes = [];
		hidden.value = '';
		redraw();
	});

	form.addEventListener('submit', function (e) {
		if (!strokes.length) {
			e.preventDefault();
			hint.style.display = 'block';
			return;
		}
		hint.style.display = 'none';
		hidden.value = canvas.toDataURL('image/png');
	});

	window.addEventListener('resize', resize);
	resize();
})();
