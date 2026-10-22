function Carousel(target, opt) {
  opt = opt || {};

  var el = (typeof target === 'string') ? document.querySelector(target) : target;
  if (!el) throw new Error('Carousel: 容器不存在');

  var images = opt.images || [];
  if (!images.length) throw new Error('Carousel: images 不能为空');

  this.el       = el;
  this.data     = images;
  this.W        = opt.width    || 800;
  this.H        = opt.height   || 450;
  this.interval = opt.interval || 3000;
  this.duration = opt.duration || 700;
  this.target   = opt.target   || '_self';
  this.cur      = 0;
  this.busy     = false;
  this.started  = false;
  this.order    = null;
  this.timer    = null;

  var cv = document.createElement('canvas');
  cv.width  = this.W;
  cv.height = this.H;
  el.appendChild(cv);
  this.cv  = cv;
  this.ctx = cv.getContext('2d');

  var base = document.createElement('canvas'); base.width = this.W; base.height = this.H;
  var nxt  = document.createElement('canvas'); nxt.width  = this.W; nxt.height  = this.H;
  this.base = base; this.nxt = nxt;
  this.bctx = base.getContext('2d');
  this.nctx = nxt.getContext('2d');

  this.fx    = this._makeEffects();
  this.names = Object.keys(this.fx);

  var self = this;
  cv.onclick = function() {
    var item = self.data[self.cur];
    if (!item || !item.link) return;
    var t = item.target || self.target;
    if (t === '_self') window.location.href = item.link;
    else               window.open(item.link, t);
  };

  this._load();
}

/* ================================================================
   公共静态方法
   ================================================================ */
Carousel.clamp = function(v, a, b) { return v < a ? a : v > b ? b : v; };

Carousel.ease = function(p) {
  return p < 0.5 ? 2 * p * p : 1 - Math.pow(-2 * p + 2, 2) / 2;
};

Carousel.now = (window.performance && window.performance.now)
  ? function() { return window.performance.now(); }
  : function() { return Date.now(); };

Carousel.raf = (function() {
  var native = window.requestAnimationFrame
            || window.webkitRequestAnimationFrame
            || window.mozRequestAnimationFrame;
  if (native) return function(cb) { return native(cb); };
  return function(cb) {
    return setTimeout(function() { cb(Carousel.now()); }, 16);
  };
})();

/* ================================================================
   原型方法
   ================================================================ */
Carousel.prototype = {

  /* ---------- 图片裁成 cover 铺满离屏画布 ---------- */
  _cover: function(g, im) {
    var W = this.W, H = this.H;
    var r = Math.max(W / im.width, H / im.height);
    var w = im.width * r, h = im.height * r;
    g.drawImage(im, (W - w) / 2, (H - h) / 2, w, h);
  },

  /* ---------- 在指定矩形内盖上 nxt（整数像素对齐，杜绝缝隙） ---------- */
  _put: function(x, y, w, h) {
    var x0 = Math.round(x), y0 = Math.round(y);
    var x1 = Math.round(x + w), y1 = Math.round(y + h);
    var ww = x1 - x0, hh = y1 - y0;
    if (ww <= 0 || hh <= 0) return;
    this.ctx.drawImage(this.nxt, x0, y0, ww, hh, x0, y0, ww, hh);
  },

  /* ---------- 效果库 ---------- */
  _makeEffects: function() {
    var s = this;
    return {
      fade: function(p) {
        s.ctx.globalAlpha = p;
        s.ctx.drawImage(s.nxt, 0, 0);
        s.ctx.globalAlpha = 1;
      },

      blindsY: function(p) {
        var n = 14, h = s.H / n;
        for (var i = 0; i < n; i++) {
          var a = Carousel.clamp((p - i * 0.6 / n) / 0.4, 0, 1);
          if (a <= 0) continue;
          /* 用 y0/y1 两端点取整，保证相邻瓦片共享同一个取整边界 */
          var y0 = Math.round(i * h);
          var y1 = Math.round(i * h + h * a);
          var hh = y1 - y0;
          if (hh <= 0) continue;
          s.ctx.drawImage(s.nxt, 0, y0, s.W, hh, 0, y0, s.W, hh);
        }
      },

      blindsX: function(p) {
        var n = 20, w = s.W / n;
        for (var i = 0; i < n; i++) {
          var a = Carousel.clamp((p - i * 0.6 / n) / 0.4, 0, 1);
          if (a <= 0) continue;
          var x0 = Math.round(i * w);
          var x1 = Math.round(i * w + w * a);
          var ww = x1 - x0;
          if (ww <= 0) continue;
          s.ctx.drawImage(s.nxt, x0, 0, ww, s.H, x0, 0, ww, s.H);
        }
      },

      blocks: function(p) {
        var C = 12, R = 7, cw = s.W / C, ch = s.H / R, N = C * R;
        if (!s.order) {
          var arr = [];
          for (var k = 0; k < N; k++) arr.push(k);
          arr.sort(function() { return Math.random() - 0.5; });
          s.order = arr;
        }
        for (var i = 0; i < N; i++) {
          var a = Carousel.clamp(p * N - i, 0, 1);
          if (a <= 0) break;
          var idx = s.order[i];
          var col = idx % C, row = Math.floor(idx / C);
          /* 每个格子的四边取整，格子之间严丝合缝 */
          var x0 = Math.round(col * cw), x1 = Math.round((col + 1) * cw);
          var y0 = Math.round(row * ch), y1 = Math.round((row + 1) * ch);
          var ww = x1 - x0, hh = y1 - y0;
          if (ww <= 0 || hh <= 0) continue;
          s.ctx.globalAlpha = a;
          s.ctx.drawImage(s.nxt, x0, y0, ww, hh, x0, y0, ww, hh);
        }
        s.ctx.globalAlpha = 1;
      },

      circle: function(p) {
        var r = Math.sqrt(s.W * s.W + s.H * s.H) / 2 * p;
        s.ctx.save();
        s.ctx.beginPath();
        s.ctx.arc(s.W / 2, s.H / 2, r, 0, 7);
        s.ctx.clip();
        s.ctx.drawImage(s.nxt, 0, 0);
        s.ctx.restore();
      },

      wipe: function(p) {
        s._put(s.W / 2 - s.W * p / 2, 0, s.W * p, s.H);
      },

      slide: function(p) {
        s._put(0, 0, s.W, s.H * p);
      }
    };
  },

  /* ---------- 首图先下载，其余随后并行 ---------- */
  _load: function() {
    var s = this;
    this.imgs = [];
    for (var i = 0; i < this.data.length; i++) this.imgs.push(new Image());

    var loadRest = function() {
      for (var j = 1; j < s.imgs.length; j++) s.imgs[j].src = s.data[j].src;
    };

    for (var k = 0; k < this.imgs.length; k++) {
      (function(idx) {
        var im = s.imgs[idx];
        im.onload = function() {
          im.ok = true;
          if (idx === 0) loadRest();
          s._tryStart();
        };
        im.onerror = function() {
          if (idx === 0) loadRest();
        };
      })(k);
    }

    this.imgs[0].src = this.data[0].src;
  },

  /* ---------- 首图就绪即开播 ---------- */
  _tryStart: function() {
    if (this.started) return;
    var i = -1;
    for (var k = 0; k < this.imgs.length; k++) {
      if (this.imgs[k].ok) { i = k; break; }
    }
    if (i < 0) return;

    this.started = true;
    this.cur = i;
    this._cover(this.ctx, this.imgs[i]);

    var self = this;
    this.timer = setInterval(function() { self._step(); }, this.interval);
  },

  /* ---------- 找一个已就绪的下一张 ---------- */
  _step: function() {
    if (this.busy) return;
    var n = this.imgs.length;
    for (var k = 1; k < n; k++) {
      var j = (this.cur + k) % n;
      if (this.imgs[j].ok) return this._morph(j);
    }
  },

  /* ---------- 执行一次随机效果切换 ---------- */
  _morph: function(j) {
    var s = this;
    this.busy = true;
    this.bctx.drawImage(this.cv, 0, 0);
    this._cover(this.nctx, this.imgs[j]);
    this.order = null;

    var fx = this.fx[this.names[Math.floor(Math.random() * this.names.length)]];
    var t0 = Carousel.now();

    function tick(t) {
      var p = Math.min(1, (t - t0) / s.duration);
      s.ctx.drawImage(s.base, 0, 0);
      fx(Carousel.ease(p));
      if (p < 1) Carousel.raf(tick);
      else { s.cur = j; s.busy = false; }
    }
    Carousel.raf(tick);
  },

  destroy: function() {
    if (this.timer) clearInterval(this.timer);
    if (this.cv && this.cv.parentNode) this.cv.parentNode.removeChild(this.cv);
  }

};
