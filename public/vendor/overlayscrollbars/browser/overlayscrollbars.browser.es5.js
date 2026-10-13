/*!
 * OverlayScrollbars
 * Version: 2.16.0
 *
 * Copyright (c) Rene Haas | KingSora.
 * https://github.com/KingSora
 *
 * Released under the MIT license.
 */
var OverlayScrollbarsGlobal = function(r) {
  "use strict";
  var e = function createCache(r, e) {
    var a = r.i, n = r.v, t = r.o;
    var i = a;
    var v;
    var o = function cacheUpdateContextual(r, e) {
      var a = i;
      var o = r;
      var u = e || (n ? !n(a, o) : a !== o);
      if (u || t) {
        i = o;
        v = a;
      }
      return [ i, u, v ];
    };
    var u = function cacheUpdateIsolated(r) {
      return o(e(i, v), r);
    };
    var c = function getCurrentCache(r) {
      return [ i, !!r, v ];
    };
    return [ e ? u : o, c ];
  };
  var a = typeof window !== "undefined" && typeof HTMLElement !== "undefined" && !!window.document;
  var n = a ? window : {};
  var t = Math.max;
  var i = Math.min;
  var v = Math.round;
  var o = Math.abs;
  var u = Math.sign;
  var c = n.cancelAnimationFrame;
  var f = n.requestAnimationFrame;
  var l = n.setTimeout;
  var s = n.clearTimeout;
  var d = function getApi(r) {
    return typeof n[r] !== "undefined" ? n[r] : void 0;
  };
  var p = d("MutationObserver");
  var _ = d("IntersectionObserver");
  var g = d("ResizeObserver");
  var h = d("ScrollTimeline");
  var b = function isUndefined(r) {
    return r === void 0;
  };
  var m = function isNull(r) {
    return r === null;
  };
  var y = function type(r) {
    return b(r) || m(r) ? "" + r : Object.prototype.toString.call(r).replace(/^\[object (.+)\]$/, "$1").toLowerCase();
  };
  var S = function isNumber(r) {
    return typeof r === "number";
  };
  var w = function isString(r) {
    return typeof r === "string";
  };
  var O = function isBoolean(r) {
    return typeof r === "boolean";
  };
  var C = function isFunction(r) {
    return typeof r === "function";
  };
  var x = function isArray(r) {
    return Array.isArray(r);
  };
  var E = function isObject(r) {
    return typeof r === "object" && !x(r) && !m(r);
  };
  var A = function isArrayLike(r) {
    var e = !!r && r.length;
    var a = S(e) && e > -1 && e % 1 == 0;
    return x(r) || !C(r) && a ? e > 0 && E(r) ? e - 1 in r : true : false;
  };
  var T = function isPlainObject(r) {
    return !!r && r.constructor === Object;
  };
  var D = function isHTMLElement(r) {
    return r instanceof HTMLElement;
  };
  var H = function isElement(r) {
    return r instanceof Element;
  };
  var P = function animationCurrentTime() {
    return performance.now();
  };
  var z = function animateNumber(r, e, a, n, i) {
    var v = 0;
    var o = P();
    var u = t(0, a);
    var l = function frame(a) {
      var c = P();
      var s = c - o;
      var d = s >= u;
      var p = a ? 1 : 1 - (t(0, o + u - c) / u || 0);
      var _ = (e - r) * (C(i) ? i(p, p * u, 0, 1, u) : p) + r;
      var g = d || p === 1;
      n(_, p, g);
      v = g ? 0 : f((function() {
        return l();
      }));
    };
    l();
    return function(r) {
      c(v);
      if (r) {
        l(r);
      }
    };
  };
  function each(r, e) {
    if (A(r)) {
      for (var a = 0; a < r.length; a++) {
        if (e(r[a], a, r) === false) {
          break;
        }
      }
    } else if (r) {
      each(Object.keys(r), (function(a) {
        return e(r[a], a, r);
      }));
    }
    return r;
  }
  var M = function inArray(r, e) {
    return r.indexOf(e) >= 0;
  };
  var k = function concat(r, e) {
    return r.concat(e);
  };
  var I = function push(r, e, a) {
    if (!w(e) && A(e)) {
      Array.prototype.push.apply(r, e);
    } else {
      r.push(e);
    }
    return r;
  };
  var L = function from(r) {
    return Array.from(r || []);
  };
  var V = function createOrKeepArray(r) {
    if (x(r)) {
      return r;
    }
    return !w(r) && A(r) ? L(r) : [ r ];
  };
  var R = function isEmptyArray(r) {
    return !!r && !r.length;
  };
  var F = function deduplicateArray(r) {
    return L(new Set(r));
  };
  var N = function runEachAndClear(r, e, a) {
    var n = function runFn(r) {
      return r ? r.apply(void 0, e || []) : true;
    };
    each(r, n);
    if (!a) {
      r.length = 0;
    }
  };
  var j = "paddingTop";
  var U = "paddingRight";
  var q = "paddingLeft";
  var B = "paddingBottom";
  var Y = "marginLeft";
  var W = "marginRight";
  var X = "marginBottom";
  var Z = "overflowX";
  var G = "overflowY";
  var Q = "width";
  var $ = "height";
  var J = "visible";
  var K = "hidden";
  var rr = "scroll";
  var er = function capitalizeFirstLetter(r) {
    var e = String(r || "");
    return e ? e[0].toUpperCase() + e.slice(1) : "";
  };
  var ar = function equal(r, e, a, n) {
    if (r && e) {
      var t = true;
      each(a, (function(a) {
        var n = r[a];
        var i = e[a];
        if (n !== i) {
          t = false;
        }
      }));
      return t;
    }
    return false;
  };
  var nr = function equalWH(r, e) {
    return ar(r, e, [ "w", "h" ]);
  };
  var tr = function equalXY(r, e) {
    return ar(r, e, [ "x", "y" ]);
  };
  var ir = function equalTRBL(r, e) {
    return ar(r, e, [ "t", "r", "b", "l" ]);
  };
  var vr = function bind(r) {
    for (var e = arguments.length, a = new Array(e > 1 ? e - 1 : 0), n = 1; n < e; n++) {
      a[n - 1] = arguments[n];
    }
    return r.bind.apply(r, [ 0 ].concat(a));
  };
  var or = function selfClearTimeout(r) {
    var e;
    var a = r ? l : f;
    var n = r ? s : c;
    return [ function(t) {
      n(e);
      e = a((function() {
        return t();
      }), C(r) ? r() : r);
    }, function() {
      return n(e);
    } ];
  };
  var ur = function getDebouncer(r) {
    var e = C(r) ? r() : r;
    if (S(e)) {
      var a = e ? l : f;
      var n = e ? s : c;
      return function(r) {
        var t = a((function() {
          return r();
        }), e);
        return function() {
          n(t);
        };
      };
    }
    return e && e.u;
  };
  var cr = function debounce(r, e) {
    var a = e || {}, n = a.p, t = a._, i = a.m, v = a.S;
    var o;
    var u;
    var c;
    var f;
    var l = function invokeFunctionToDebounce(e) {
      if (u) {
        u();
      }
      if (o) {
        o();
      }
      f = u = o = c = void 0;
      r.apply(this, e);
    };
    var s = function mergeParms(r) {
      return v && c ? v(c, r) : r;
    };
    var d = function flush() {
      if (u && c) {
        l(s(c) || c);
      }
    };
    var p = function debouncedFn() {
      var r = L(arguments);
      var e = ur(n);
      if (e) {
        var a = typeof i === "function" ? i() : i;
        var v = ur(t);
        var p = s(r);
        var _ = p || r;
        var g = l.bind(0, _);
        if (u) {
          u();
        }
        if (a && !f) {
          g();
          f = true;
          u = e((function() {
            return f = void 0;
          }));
        } else {
          u = e(g);
          if (v && !o) {
            o = v(d);
          }
        }
        c = _;
      } else {
        l(r);
      }
    };
    p.O = d;
    return p;
  };
  var fr = function hasOwnProperty(r, e) {
    return Object.prototype.hasOwnProperty.call(r, e);
  };
  var lr = function keys(r) {
    return r ? Object.keys(r) : [];
  };
  var sr = function assignDeep(r, e, a, n, t, i, v) {
    var o = [ e, a, n, t, i, v ];
    if ((typeof r !== "object" || m(r)) && !C(r)) {
      r = {};
    }
    each(o, (function(e) {
      each(e, (function(a, n) {
        var t = e[n];
        if (r === t) {
          return true;
        }
        var i = x(t);
        if (t && T(t)) {
          var v = r[n];
          var o = v;
          if (i && !x(v)) {
            o = [];
          } else if (!i && !T(v)) {
            o = {};
          }
          r[n] = sr(o, t);
        } else {
          r[n] = i ? t.slice() : t;
        }
      }));
    }));
    return r;
  };
  var dr = function removeUndefinedProperties(r, e) {
    return each(sr({}, r), (function(r, e, a) {
      if (r === void 0) {
        delete a[e];
      } else if (r && T(r)) {
        a[e] = dr(r);
      }
    }));
  };
  var pr = function isEmptyObject(r) {
    return !lr(r).length;
  };
  var _r = function noop() {};
  var gr = function capNumber(r, e, a) {
    return t(r, i(e, a));
  };
  var hr = function getDomTokensArray(r) {
    return F((x(r) ? r : (r || "").split(" ")).filter((function(r) {
      return r;
    })));
  };
  var br = function getAttr(r, e) {
    return r && r.getAttribute(e);
  };
  var mr = function hasAttr(r, e) {
    return r && r.hasAttribute(e);
  };
  var yr = function setAttrs(r, e, a) {
    each(hr(e), (function(e) {
      if (r) {
        r.setAttribute(e, String(a || ""));
      }
    }));
  };
  var Sr = function removeAttrs(r, e) {
    each(hr(e), (function(e) {
      return r && r.removeAttribute(e);
    }));
  };
  var wr = function domTokenListAttr(r, e) {
    var a = hr(br(r, e));
    var n = vr(yr, r, e);
    var t = function domTokenListOperation(r, e) {
      var n = new Set(a);
      each(hr(r), (function(r) {
        n[e](r);
      }));
      return L(n).join(" ");
    };
    return {
      C: function _remove(r) {
        return n(t(r, "delete"));
      },
      A: function _add(r) {
        return n(t(r, "add"));
      },
      T: function _has(r) {
        var e = hr(r);
        return e.reduce((function(r, e) {
          return r && a.includes(e);
        }), e.length > 0);
      }
    };
  };
  var Or = function removeAttrClass(r, e, a) {
    wr(r, e).C(a);
    return vr(Cr, r, e, a);
  };
  var Cr = function addAttrClass(r, e, a) {
    wr(r, e).A(a);
    return vr(Or, r, e, a);
  };
  var xr = function addRemoveAttrClass(r, e, a, n) {
    return (n ? Cr : Or)(r, e, a);
  };
  var Er = function hasAttrClass(r, e, a) {
    return wr(r, e).T(a);
  };
  var Ar = function createDomTokenListClass(r) {
    return wr(r, "class");
  };
  var Tr = function removeClass(r, e) {
    Ar(r).C(e);
  };
  var Dr = function addClass(r, e) {
    Ar(r).A(e);
    return vr(Tr, r, e);
  };
  var Hr = function find(r, e) {
    var a = e ? H(e) && e : document;
    return a ? L(a.querySelectorAll(r)) : [];
  };
  var Pr = function findFirst(r, e) {
    var a = e ? H(e) && e : document;
    return a && a.querySelector(r);
  };
  var zr = function is(r, e) {
    return H(r) && r.matches(e);
  };
  var Mr = function isBodyElement(r) {
    return zr(r, "body");
  };
  var kr = function contents(r) {
    return r ? L(r.childNodes) : [];
  };
  var Ir = function parent(r) {
    return r && r.parentElement;
  };
  var Lr = function closest(r, e) {
    return H(r) && r.closest(e);
  };
  var Vr = function getFocusedElement(r) {
    return document.activeElement;
  };
  var Rr = function liesBetween(r, e, a) {
    var n = Lr(r, e);
    var t = r && Pr(a, n);
    var i = Lr(t, e) === n;
    return n && t ? n === r || t === r || i && Lr(Lr(r, a), e) !== n : false;
  };
  var Fr = function removeElements(r) {
    each(V(r), (function(r) {
      var e = Ir(r);
      if (r && e) {
        e.removeChild(r);
      }
    }));
  };
  var Nr = function appendChildren(r, e) {
    return vr(Fr, r && e && each(V(e), (function(e) {
      if (e) {
        r.appendChild(e);
      }
    })));
  };
  var jr;
  var Ur = function getTrustedTypePolicy() {
    return jr;
  };
  var qr = function setTrustedTypePolicy(r) {
    jr = r;
  };
  var Br = function createDiv(r) {
    var e = document.createElement("div");
    yr(e, "class", r);
    return e;
  };
  var Yr = function createDOM(r) {
    var e = Br();
    var a = Ur();
    var n = r.trim();
    e.innerHTML = a ? a.createHTML(n) : n;
    return each(kr(e), (function(r) {
      return Fr(r);
    }));
  };
  var Wr = function getCSSVal(r, e) {
    return r.getPropertyValue(e) || r[e] || "";
  };
  var Xr = function validFiniteNumber(r) {
    var e = r || 0;
    return isFinite(e) ? e : 0;
  };
  var Zr = function parseToZeroOrNumber(r) {
    return Xr(parseFloat(r || ""));
  };
  var Gr = function roundCssNumber(r) {
    return Math.round(r * 1e4) / 1e4;
  };
  var Qr = function numberToCssPx(r) {
    return Gr(Xr(r)) + "px";
  };
  function setStyles(r, e) {
    r && e && each(e, (function(e, a) {
      try {
        var n = r.style;
        var t = m(e) || O(e) ? "" : S(e) ? Qr(e) : e;
        if (a.indexOf("--") === 0) {
          n.setProperty(a, t);
        } else {
          n[a] = t;
        }
      } catch (i) {}
    }));
  }
  function getStyles(r, e, a) {
    var t = w(e);
    var i = t ? "" : {};
    if (r) {
      var v = n.getComputedStyle(r, a) || r.style;
      i = t ? Wr(v, e) : L(e).reduce((function(r, e) {
        r[e] = Wr(v, e);
        return r;
      }), i);
    }
    return i;
  }
  var $r = function topRightBottomLeft(r, e, a) {
    var n = e ? e + "-" : "";
    var t = a ? "-" + a : "";
    var i = n + "top" + t;
    var v = n + "right" + t;
    var o = n + "bottom" + t;
    var u = n + "left" + t;
    var c = getStyles(r, [ i, v, o, u ]);
    return {
      t: Zr(c[i]),
      r: Zr(c[v]),
      b: Zr(c[o]),
      l: Zr(c[u])
    };
  };
  var Jr = function getTrasformTranslateValue(r, e) {
    return "translate" + (E(r) ? "(" + r.x + "," + r.y + ")" : (e ? "X" : "Y") + "(" + r + ")");
  };
  var Kr = function elementHasDimensions(r) {
    return !!(r.offsetWidth || r.offsetHeight || r.getClientRects().length);
  };
  var re = {
    w: 0,
    h: 0
  };
  var ee = function getElmWidthHeightProperty(r, e) {
    return e ? {
      w: e[r + "Width"],
      h: e[r + "Height"]
    } : re;
  };
  var ae = function getWindowSize(r) {
    return ee("inner", r || n);
  };
  var ne = vr(ee, "offset");
  var te = vr(ee, "client");
  var ie = vr(ee, "scroll");
  var ve = function getFractionalSize(r) {
    var e = parseFloat(getStyles(r, Q)) || 0;
    var a = parseFloat(getStyles(r, $)) || 0;
    return {
      w: e - v(e),
      h: a - v(a)
    };
  };
  var oe = function getBoundingClientRect(r) {
    return r.getBoundingClientRect();
  };
  var ue = function hasDimensions(r) {
    return !!r && Kr(r);
  };
  var ce = function domRectHasDimensions(r) {
    return !!(r && (r[$] || r[Q]));
  };
  var fe = function domRectAppeared(r, e) {
    var a = ce(r);
    var n = ce(e);
    return !n && a;
  };
  var le = function removeEventListener(r, e, a, n) {
    each(hr(e), (function(e) {
      if (r) {
        r.removeEventListener(e, a, n);
      }
    }));
  };
  var se = function addEventListener(r, e, a, n) {
    var t;
    var i = (t = n && n.D) != null ? t : true;
    var v = n && n.H || false;
    var o = n && n.P || false;
    var u = {
      passive: i,
      capture: v
    };
    return vr(N, hr(e).map((function(e) {
      var n = o ? function(t) {
        le(r, e, n, v);
        if (a) {
          a(t);
        }
      } : a;
      if (r) {
        r.addEventListener(e, n, u);
      }
      return vr(le, r, e, n, v);
    })));
  };
  var de = function stopPropagation(r) {
    return r.stopPropagation();
  };
  var pe = function preventDefault(r) {
    return r.preventDefault();
  };
  var _e = function stopAndPrevent(r) {
    return de(r) || pe(r);
  };
  var ge = function scrollElementTo(r, e) {
    var a = S(e) ? {
      x: e,
      y: e
    } : e || {}, n = a.x, t = a.y;
    S(n) && (r.scrollLeft = n);
    S(t) && (r.scrollTop = t);
  };
  var he = function getElementScroll(r) {
    return {
      x: r.scrollLeft,
      y: r.scrollTop
    };
  };
  var be = function getZeroScrollCoordinates() {
    return {
      M: {
        x: 0,
        y: 0
      },
      k: {
        x: 0,
        y: 0
      }
    };
  };
  var me = function sanitizeScrollCoordinates(r, e) {
    var a = r.M, n = r.k;
    var t = e.w, i = e.h;
    var v = function sanitizeAxis(r, e, a) {
      var n = u(r) * a;
      var t = u(e) * a;
      if (n === t) {
        var i = o(r);
        var v = o(e);
        t = i > v ? 0 : t;
        n = i < v ? 0 : n;
      }
      n = n === t ? 0 : n;
      return [ n + 0, t + 0 ];
    };
    var c = v(a.x, n.x, t), f = c[0], l = c[1];
    var s = v(a.y, n.y, i), d = s[0], p = s[1];
    return {
      M: {
        x: f,
        y: d
      },
      k: {
        x: l,
        y: p
      }
    };
  };
  var ye = function isDefaultDirectionScrollCoordinates(r) {
    var e = r.M, a = r.k;
    var n = function getAxis(r, e) {
      return r === 0 && r <= e;
    };
    return {
      x: n(e.x, a.x),
      y: n(e.y, a.y)
    };
  };
  var Se = function getScrollCoordinatesPercent(r, e) {
    var a = r.M, n = r.k;
    var t = function getAxis(r, e, a) {
      return gr(0, 1, (r - a) / (r - e) || 0);
    };
    return {
      x: t(a.x, n.x, e.x),
      y: t(a.y, n.y, e.y)
    };
  };
  var we = function focusElement(r) {
    if (r && r.focus) {
      r.focus({
        preventScroll: true,
        focusVisible: false
      });
    }
  };
  var Oe = function manageListener(r, e) {
    each(V(e), r);
  };
  var Ce = function createEventListenerHub(r) {
    var e = new Map;
    var a = function removeEvent(r, a) {
      if (r) {
        var n = e.get(r);
        Oe((function(r) {
          if (n) {
            n[r ? "delete" : "clear"](r);
          }
        }), a);
      } else {
        e.forEach((function(r) {
          r.clear();
        }));
        e.clear();
      }
    };
    var n = function addEvent(r, t) {
      if (w(r)) {
        var i = e.get(r) || new Set;
        e.set(r, i);
        Oe((function(r) {
          if (C(r)) {
            i.add(r);
          }
        }), t);
        return vr(a, r, t);
      }
      if (O(t) && t) {
        a();
      }
      var v = lr(r);
      var o = [];
      each(v, (function(e) {
        var a = r[e];
        if (a) {
          I(o, n(e, a));
        }
      }));
      return vr(N, o);
    };
    var t = function triggerEvent(r, a) {
      each(L(e.get(r)), (function(r) {
        if (a && !R(a)) {
          r.apply(0, a);
        } else {
          r();
        }
      }));
    };
    n(r || {});
    return [ n, a, t ];
  };
  var xe = {};
  var Ee = {};
  var Ae = function addPlugins(r) {
    each(r, (function(r) {
      return each(r, (function(e, a) {
        xe[a] = r[a];
      }));
    }));
  };
  var Te = function registerPluginModuleInstances(r, e, a) {
    return lr(r).map((function(n) {
      var t = r[n], i = t.static, v = t.instance;
      var o = a || [], u = o[0], c = o[1], f = o[2];
      var l = a ? v : i;
      if (l) {
        var s = a ? l(u, c, e) : l(e);
        return (f || Ee)[n] = s;
      }
    }));
  };
  var De = function getInstancePluginModuleInstance(r, e) {
    return r[e];
  };
  var He = function getStaticPluginModuleInstance(r) {
    return De(Ee, r);
  };
  function getDefaultExportFromCjs(r) {
    return r && r.I && Object.prototype.hasOwnProperty.call(r, "default") ? r["default"] : r;
  }
  var Pe = {
    exports: {}
  };
  (function(r) {
    function _extends() {
      return r.exports = _extends = Object.assign ? Object.assign.bind() : function(r) {
        for (var e = 1; e < arguments.length; e++) {
          var a = arguments[e];
          for (var n in a) {
            ({}).hasOwnProperty.call(a, n) && (r[n] = a[n]);
          }
        }
        return r;
      }, r.exports.I = true, r.exports["default"] = r.exports, _extends.apply(null, arguments);
    }
    r.exports = _extends, r.exports.I = true, r.exports["default"] = r.exports;
  })(Pe);
  var ze = Pe.exports;
  var Me = /*@__PURE__*/ getDefaultExportFromCjs(ze);
  var ke = {
    boolean: "__TPL_boolean_TYPE__",
    number: "__TPL_number_TYPE__",
    string: "__TPL_string_TYPE__",
    array: "__TPL_array_TYPE__",
    object: "__TPL_object_TYPE__",
    function: "__TPL_function_TYPE__",
    null: "__TPL_null_TYPE__"
  };
  var Ie = function validateRecursive(r, e, a, n) {
    var t = {};
    var i = Me({}, e);
    var v = lr(r).filter((function(r) {
      return fr(e, r);
    }));
    each(v, (function(v) {
      var o = e[v];
      var u = r[v];
      var c = T(u);
      var f = n ? n + "." : "";
      if (c && T(o)) {
        var l = Ie(u, o, a, f + v), s = l[0], d = l[1];
        t[v] = s;
        i[v] = d;
        each([ i, t ], (function(r) {
          if (pr(r[v])) {
            delete r[v];
          }
        }));
      } else if (!c) {
        var p = false;
        var _ = [];
        var g = [];
        var h = y(o);
        var m = V(u);
        each(m, (function(r) {
          var e;
          each(ke, (function(a, n) {
            if (a === r) {
              e = n;
            }
          }));
          var a = b(e);
          if (a && w(o)) {
            var n = r.split(" ");
            p = !!n.find((function(r) {
              return r === o;
            }));
            I(_, n);
          } else {
            p = ke[h] === r;
          }
          I(g, a ? ke.string : e);
          return !p;
        }));
        if (p) {
          t[v] = o;
        } else if (a) {
          console.warn('The option "' + f + v + "\" wasn't set, because it doesn't accept the type [ " + h.toUpperCase() + ' ] with the value of "' + o + '".\r\n' + "Accepted types are: [ " + g.join(", ").toUpperCase() + " ].\r\n" + (_.length > 0 ? "\r\nValid strings are: [ " + _.join(", ") + " ]." : ""));
        }
        delete i[v];
      }
    }));
    return [ t, i ];
  };
  var Le = function validateOptions(r, e, a) {
    return Ie(r, e, a);
  };
  var Ve = "__osOptionsValidationPlugin";
  /* @__PURE__ */  (function(r) {
    return r = {}, r[Ve] = {
      static: function _static() {
        var r = ke.number;
        var e = ke.boolean;
        var a = [ ke.array, ke.null ];
        var n = "hidden scroll visible visible-hidden";
        var t = "visible hidden auto";
        var i = "never scroll leavemove";
        var v = [ e, ke.string, ke.function ];
        var o = {
          paddingAbsolute: e,
          showNativeOverlaidScrollbars: e,
          update: {
            elementEvents: a,
            attributes: a,
            debounce: [ ke.number, ke.array, ke.object, ke.null ],
            ignoreMutation: [ ke.function, ke.null ],
            flowDirectionStyles: [ ke.function, ke.null ]
          },
          overflow: {
            x: n,
            y: n
          },
          scrollbars: {
            theme: [ ke.string, ke.null ],
            visibility: t,
            autoHide: i,
            autoHideDelay: r,
            autoHideSuspend: e,
            dragScroll: e,
            clickScroll: v,
            pointers: [ ke.array, ke.null ]
          }
        };
        return function(r, e) {
          var a = Le(o, r, e), n = a[0], t = a[1];
          return Me({}, t, n);
        };
      }
    }, r;
  })();
  var Re = "data-overlayscrollbars";
  var Fe = "os-environment";
  var Ne = Fe + "-scrollbar-hidden";
  var je = Re + "-initialize";
  var Ue = "noClipping";
  var qe = Re + "-body";
  var Be = Re;
  var Ye = "host";
  var We = Re + "-viewport";
  var Xe = Z;
  var Ze = G;
  var Ge = "arrange";
  var Qe = "measuring";
  var $e = "scrolling";
  var Je = "scrollbarHidden";
  var Ke = "noContent";
  var ra = Re + "-padding";
  var ea = Re + "-content";
  var aa = "os-size-observer";
  var na = aa + "-appear";
  var ta = aa + "-listener";
  var ia = ta + "-scroll";
  var va = ta + "-item";
  var oa = va + "-final";
  var ua = "os-trinsic-observer";
  var ca = "os-theme-none";
  var fa = "os-scrollbar";
  var la = fa + "-rtl";
  var sa = fa + "-horizontal";
  var da = fa + "-vertical";
  var pa = fa + "-track";
  var _a = fa + "-handle";
  var ga = fa + "-visible";
  var ha = fa + "-cornerless";
  var ba = fa + "-interaction";
  var ma = fa + "-unusable";
  var ya = fa + "-auto-hide";
  var Sa = ya + "-hidden";
  var wa = fa + "-wheel";
  var Oa = pa + "-interactive";
  var Ca = _a + "-interactive";
  var xa = "__osSizeObserverPlugin";
  var Ea = /* @__PURE__ */ function(r) {
    return r = {}, r[xa] = {
      static: function _static() {
        return function(r, e, a) {
          var n;
          var t = 3333333;
          var i = "scroll";
          var v = Yr('<div class="' + va + '" dir="ltr"><div class="' + va + '"><div class="' + oa + '"></div></div><div class="' + va + '"><div class="' + oa + '" style="width: 200%; height: 200%"></div></div></div>');
          var o = v[0];
          var u = o.lastChild;
          var l = o.firstChild;
          var s = l == null ? void 0 : l.firstChild;
          var d = ne(o);
          var p = d;
          var _ = false;
          var g;
          var h = function reset() {
            ge(l, t);
            ge(u, t);
          };
          var b = function onResized(r) {
            g = 0;
            if (_) {
              d = p;
              e(r === true);
            }
          };
          var m = function onScroll(r) {
            p = ne(o);
            _ = !r || !nr(p, d);
            if (r) {
              de(r);
              if (_ && !g) {
                c(g);
                g = f(b);
              }
            } else {
              b(r === false);
            }
            h();
          };
          var y = [ Nr(r, v), se(l, i, m), se(u, i, m) ];
          Dr(r, ia);
          setStyles(s, (n = {}, n[Q] = t, n[$] = t, n));
          f(h);
          return [ a ? vr(m, false) : h, y ];
        };
      }
    }, r;
  }();
  var Aa = function getShowNativeOverlaidScrollbars(r, e) {
    var a = e.L;
    var n = r("showNativeOverlaidScrollbars"), t = n[0], i = n[1];
    return [ t && a.x && a.y, i ];
  };
  var Ta = function overflowIsVisible(r) {
    return r.indexOf(J) === 0;
  };
  var Da = function overflowBehaviorToOverflowStyle(r) {
    return r.replace(J + "-", "");
  };
  var Ha = function overflowCssValueToOverflowStyle(r, e) {
    if (r === "auto") {
      return e ? rr : K;
    }
    var a = r || K;
    return [ K, rr, J ].includes(a) ? a : K;
  };
  var Pa = function getElementOverflowStyle(r, e) {
    var a = getStyles(r, [ Z, G ]), n = a.overflowX, t = a.overflowY;
    return {
      x: Ha(n, e.x),
      y: Ha(t, e.y)
    };
  };
  var za = "__osScrollbarsHidingPlugin";
  var Ma = /* @__PURE__ */ function(r) {
    return r = {}, r[za] = {
      static: function _static() {
        return {
          V: function _viewportArrangement(r, e, a, n, t) {
            var i = r.R, v = r.F;
            var o = n.N, u = n.L, c = n.j;
            var f = !i && !o && (u.x || u.y);
            var l = Aa(t, n), s = l[0];
            var d = function _getViewportOverflowHideOffset(r) {
              var e = o || s ? 0 : 42;
              var a = function getHideOffsetPerAxis(r, a, n) {
                var t = r ? e : n;
                var i = a && !o ? t : 0;
                var v = r && !!e;
                return [ i, v ];
              };
              var n = a(u.x, r.x === rr, c.x), t = n[0], i = n[1];
              var v = a(u.y, r.y === rr, c.y), f = v[0], l = v[1];
              return {
                U: {
                  x: t,
                  y: f
                },
                q: {
                  x: i,
                  y: l
                }
              };
            };
            var p = function _hideNativeScrollbars(r) {
              if (!i) {
                var n;
                var t = a.B;
                var v = sr({}, (n = {}, n[W] = 0, n[X] = 0, n[Y] = 0, n));
                var o = d(r), u = o.U, c = o.q;
                var l = c.x, s = c.y;
                var p = u.x, _ = u.y;
                var g = e.Y;
                var h = t ? Y : W;
                var b = t ? q : U;
                var m = g[h];
                var y = g[X];
                var S = g[b];
                var w = g[B];
                v[Q] = "calc(100% + " + (_ + m * -1) + "px)";
                v[h] = -_ + m;
                v[X] = -p + y;
                if (f) {
                  v[b] = S + (s ? _ : 0);
                  v[B] = w + (l ? p : 0);
                }
                return v;
              }
            };
            var _ = function _arrangeViewport(r, n, t) {
              if (f) {
                var i = e.Y;
                var o = d(r), u = o.U, c = o.q;
                var l = c.x, s = c.y;
                var p = u.x, _ = u.y;
                var g = a.B;
                var h = g ? U : q;
                var b = i[h];
                var m = i.paddingTop;
                var y = n.w + t.w;
                var S = n.h + t.h;
                var w = {
                  w: _ && s ? _ + y - b + "px" : "",
                  h: p && l ? p + S - m + "px" : ""
                };
                setStyles(v, {
                  "--os-vaw": w.w,
                  "--os-vah": w.h
                });
              }
              return f;
            };
            var g = function _undoViewportArrange() {
              if (f) {
                var r = e.W, a = e.Y;
                var n = Pa(v, r);
                var t = d(n), i = t.q;
                var o = i.x, u = i.y;
                var c = {};
                var l = function assignProps(r) {
                  return each(r, (function(r) {
                    c[r] = a[r];
                  }));
                };
                if (o) {
                  l([ X, j, B ]);
                }
                if (u) {
                  l([ Y, W, q, U ]);
                }
                var s = getStyles(v, lr(c));
                var _ = Or(v, We, Ge);
                setStyles(v, c);
                return function() {
                  setStyles(v, sr({}, s, p(n)));
                  _();
                };
              }
              return _r;
            };
            return {
              X: _,
              Z: g,
              G: p
            };
          }
        };
      }
    }, r;
  }();
  var ka = "__osClickScrollPlugin";
  var Ia = /* @__PURE__ */ function(r) {
    return r = {}, r[ka] = {
      static: function _static() {
        return function(r, e, a, n, t, i, v, o) {
          var u = false;
          var c = _r;
          var f = {
            clickScrollDistance: t,
            clickScrollDuration: 200,
            clickPressDelay: 150,
            pressDistanceDuration: 90
          };
          var l = function easeOutQuad(r) {
            return 1 - (1 - r) * (1 - r);
          };
          var s = function easeInOutQuad(r) {
            return r < .5 ? 2 * r * r : 1 - Math.pow(-2 * r + 2, 2) / 2;
          };
          var d = sr({}, f, C(i) ? i(v) : f), p = d.clickScrollDistance, _ = d.clickScrollDuration, g = d.clickPressDelay, h = d.pressDistanceDuration;
          var b = p === 0;
          var m = h * 2.3;
          var y = h * 2.5;
          var S = p ? t / p : 0;
          var w = or(Math.max(22, g)), O = w[0], x = w[1];
          var E = a();
          var A = Math.sign(n);
          var T = z(0, b ? n : p * A, _, (function(t, i, v) {
            if (b) {
              e(t);
            } else {
              r(t);
            }
            if (v) {
              o(u);
              O((function() {
                if (u || b || !h) {
                  return;
                }
                var r = a();
                var t = r - E;
                var i = t * S;
                var v = n - t;
                var o = i ? v / i : 0;
                var f = o <= 2.2;
                var d = Math.max(1, o || 0);
                var p = (!o || o > .5) && Math.sign(v) === A;
                if (p) {
                  c = z(t, f ? n : n - i, f ? m * d : h * d, (function(r, a, t) {
                    e(r);
                    if (t && !f) {
                      c = z(r, n, y, e, l);
                    }
                  }), f && s);
                }
              }));
            }
          }), s);
          return function(r) {
            u = true;
            if (r) {
              T();
            }
            x();
            c();
          };
        };
      }
    }, r;
  }();
  var La = function opsStringify(r) {
    return JSON.stringify(r, (function(r, e) {
      if (C(e)) {
        throw 0;
      }
      return e;
    }));
  };
  var Va = function getPropByPath(r, e) {
    return r ? ("" + e).split(".").reduce((function(r, e) {
      return r && fr(r, e) ? r[e] : void 0;
    }), r) : void 0;
  };
  var Ra = [ 0, 33 ];
  var Fa = [ 33, 99 ];
  var Na = [ 222, 666, true ];
  var ja = {
    paddingAbsolute: false,
    showNativeOverlaidScrollbars: false,
    update: {
      elementEvents: [ [ "img", "load" ] ],
      debounce: {
        mutation: Ra,
        resize: null,
        event: Fa,
        env: Na
      },
      attributes: null,
      ignoreMutation: null,
      flowDirectionStyles: null
    },
    overflow: {
      x: "scroll",
      y: "scroll"
    },
    scrollbars: {
      theme: "os-theme-dark",
      visibility: "auto",
      autoHide: "never",
      autoHideDelay: 1300,
      autoHideSuspend: false,
      dragScroll: true,
      clickScroll: false,
      pointers: [ "mouse", "touch", "pen" ]
    }
  };
  var Ua = function getOptionsDiff(r, e) {
    var a = {};
    var n = k(lr(e), lr(r));
    each(n, (function(n) {
      var t = r[n];
      var i = e[n];
      if (E(t) && E(i)) {
        sr(a[n] = {}, Ua(t, i));
        if (pr(a[n])) {
          delete a[n];
        }
      } else if (fr(e, n) && i !== t) {
        var v = true;
        if (x(t) || x(i)) {
          try {
            if (La(t) === La(i)) {
              v = false;
            }
          } catch (o) {}
        }
        if (v) {
          a[n] = i;
        }
      }
    }));
    return a;
  };
  var qa = function createOptionCheck(r, e, a) {
    return function(n) {
      return [ Va(r, n), a || Va(e, n) !== void 0 ];
    };
  };
  var Ba;
  var Ya = function getNonce() {
    return Ba;
  };
  var Wa = function setNonce(r) {
    Ba = r;
  };
  var Xa;
  var Za = function createEnvironment() {
    var r = function getNativeScrollbarSize(r, e, a) {
      Nr(document.body, r);
      Nr(document.body, r);
      var n = te(r);
      var t = ne(r);
      var i = ve(e);
      if (a) {
        Fr(r);
      }
      return {
        x: t.h - n.h + i.h,
        y: t.w - n.w + i.w
      };
    };
    var a = function getNativeScrollbarsHiding(r) {
      var e = false;
      var a = Dr(r, Ne);
      try {
        e = getStyles(r, "scrollbar-width") === "none" || getStyles(r, "display", "::-webkit-scrollbar") === "none";
      } catch (n) {}
      a();
      return e;
    };
    var t = "." + Fe + "{scroll-behavior:auto!important;position:fixed;opacity:0;visibility:hidden;overflow:scroll;height:200px;width:200px;z-index:-1}." + Fe + " div{width:200%;height:200%;margin:10px 0}." + Ne + "{scrollbar-width:none!important}." + Ne + "::-webkit-scrollbar,." + Ne + "::-webkit-scrollbar-corner{appearance:none!important;display:none!important;width:0!important;height:0!important}";
    var i = Yr('<div class="' + Fe + '"><div></div><style>' + t + "</style></div>");
    var v = i[0];
    var o = v.firstChild;
    var u = v.lastChild;
    var c = Ya();
    if (c) {
      u.nonce = c;
    }
    var f = Ce(), l = f[0], s = f[2];
    var d = e({
      i: r(v, o),
      v: tr
    }, vr(r, v, o, true)), p = d[0], _ = d[1];
    var g = _(), b = g[0];
    var m = a(v);
    var y = {
      x: b.x === 0,
      y: b.y === 0
    };
    var S = {
      elements: {
        host: null,
        padding: !m,
        viewport: function viewport(r) {
          return m && Mr(r) && r;
        },
        content: false
      },
      scrollbars: {
        slot: true
      },
      cancel: {
        nativeScrollbarsOverlaid: false,
        body: null
      }
    };
    var w = sr({}, ja);
    var O = vr(sr, {}, w);
    var x = vr(sr, {}, S);
    var E = {
      j: b,
      L: y,
      N: m,
      $: !!h,
      J: vr(l, "r"),
      K: x,
      rr: function _setDefaultInitialization(r) {
        return sr(S, r) && x();
      },
      er: O,
      ar: function _setDefaultOptions(r) {
        return sr(w, r) && O();
      },
      nr: sr({}, S),
      tr: sr({}, w)
    };
    Sr(v, "style");
    Fr(v);
    se(n, "resize", (function() {
      s("r", []);
    }));
    if (C(n.matchMedia) && !m && (!y.x || !y.y)) {
      var A = function addZoomListener(r) {
        var e = n.matchMedia("(resolution: " + n.devicePixelRatio + "dppx)");
        se(e, "change", (function() {
          r();
          A(r);
        }), {
          P: true
        });
      };
      A((function() {
        var r = p(), e = r[0], a = r[1];
        sr(E.j, e);
        s("r", [ a ]);
      }));
    }
    return E;
  };
  var Ga = function getEnvironment() {
    if (!Xa) {
      Xa = Za();
    }
    return Xa;
  };
  var Qa = function createEventContentChange(r, e, a) {
    var n = false;
    var t = a ? new WeakMap : false;
    var i = function destroy() {
      n = true;
    };
    var v = function updateElements(i) {
      if (t && a) {
        var v = a.map((function(e) {
          var a = e || [], n = a[0], t = a[1];
          var v = t && n ? (i || Hr)(n, r) : [];
          return [ v, t ];
        }));
        each(v, (function(a) {
          return each(a[0], (function(i) {
            var v = a[1];
            var o = t.get(i) || [];
            var u = r.contains(i);
            if (u && v) {
              var c = se(i, v, (function(r) {
                if (n) {
                  c();
                  t.delete(i);
                } else {
                  e(r);
                }
              }));
              t.set(i, I(o, c));
            } else {
              N(o);
              t.delete(i);
            }
          }));
        }));
      }
    };
    v();
    return [ i, v ];
  };
  var $a = function createDOMObserver(r, e, a, n) {
    var t = false;
    var i = n || {}, v = i.ir, o = i.vr, u = i.ur, c = i.cr, f = i.lr, l = i.sr;
    var s = Qa(r, (function() {
      return t && a(true);
    }), u), d = s[0], _ = s[1];
    var g = v || [];
    var h = o || [];
    var b = k(g, h);
    var m = function observerCallback(t, i) {
      if (!R(i)) {
        var v = f || _r;
        var o = l || _r;
        var u = [];
        var s = [];
        var d = false;
        var p = false;
        each(i, (function(a) {
          var t = a.attributeName, i = a.target, f = a.type, l = a.oldValue, _ = a.addedNodes, g = a.removedNodes;
          var b = f === "attributes";
          var m = f === "childList";
          var y = r === i;
          var S = b && t;
          var O = S && br(i, t || "");
          var C = w(O) ? O : null;
          var x = S && l !== C;
          var E = M(h, t) && x;
          if (e && (m || !y)) {
            var A = b && x;
            var T = A && c && zr(i, c);
            var D = T ? !v(i, t, l, C) : !b || A;
            var H = D && !o(a, !!T, r, n);
            each(_, (function(r) {
              return I(u, r);
            }));
            each(g, (function(r) {
              return I(u, r);
            }));
            p = p || H;
          }
          if (!e && y && x && !v(i, t, l, C)) {
            I(s, t);
            d = d || E;
          }
        }));
        _((function(r) {
          return F(u).reduce((function(e, a) {
            I(e, Hr(r, a));
            return zr(a, r) ? I(e, a) : e;
          }), []);
        }));
        if (e) {
          if (!t && p) {
            a(false);
          }
          return [ false ];
        }
        if (!R(s) || d) {
          var g = [ F(s), d ];
          if (!t) {
            a.apply(0, g);
          }
          return g;
        }
      }
    };
    var y = new p(vr(m, false));
    return [ function() {
      y.observe(r, {
        attributes: true,
        attributeOldValue: true,
        attributeFilter: b,
        subtree: e,
        childList: e,
        characterData: e
      });
      t = true;
      return function() {
        if (t) {
          d();
          y.disconnect();
          t = false;
        }
      };
    }, function() {
      if (t) {
        return m(true, y.takeRecords());
      }
    } ];
  };
  var Ja = null;
  var Ka = function createSizeObserver(r, a, n) {
    var t = n || {}, i = t.dr;
    var v = He(xa);
    var o = e({
      i: false,
      o: true
    }), u = o[0];
    return function() {
      var e = [];
      var n = Yr('<div class="' + aa + '"><div class="' + ta + '"></div></div>');
      var t = n[0];
      var o = t.firstChild;
      var c = function onSizeChangedCallbackProxy(r) {
        var e = x(r) && !R(r);
        var n = false;
        var t = false;
        if (e) {
          var i = r[0];
          var v = u(i.contentRect), o = v[0], c = v[2];
          var f = ce(o);
          t = fe(o, c);
          n = !t && !f;
        } else {
          t = r === true;
        }
        if (!n) {
          a({
            pr: true,
            dr: t
          });
        }
      };
      if (g) {
        if (!O(Ja)) {
          var f = new g(_r);
          f.observe(r, {
            get box() {
              Ja = true;
            }
          });
          Ja = Ja || false;
          f.disconnect();
        }
        var l = cr(c, {
          p: 0,
          _: 0
        });
        var s = function resizeObserverCallback(r) {
          return l(r);
        };
        var d = new g(s);
        d.observe(Ja ? r : o);
        I(e, [ function() {
          d.disconnect();
        }, !Ja && Nr(r, t) ]);
        if (Ja) {
          var p = new g(s);
          p.observe(r, {
            box: "border-box"
          });
          I(e, (function() {
            return p.disconnect();
          }));
        }
      } else if (v) {
        var _ = v(o, c, i), h = _[0], b = _[1];
        I(e, k([ Dr(t, na), se(t, "animationstart", h), Nr(r, t) ], b));
      } else {
        return _r;
      }
      return vr(N, e);
    };
  };
  var rn = function createTrinsicObserver(r, a) {
    var n;
    var t = function isHeightIntrinsic(r) {
      return r.h === 0 || r.isIntersecting || r.intersectionRatio > 0;
    };
    var i = Br(ua);
    var v = e({
      i: false
    }), o = v[0];
    var u = function triggerOnTrinsicChangedCallback(r, e) {
      if (r) {
        var n = o(t(r));
        var i = n[1];
        return i && !e && a(n) && [ n ];
      }
    };
    var c = function intersectionObserverCallback(r, e) {
      return u(e.pop(), r);
    };
    return [ function() {
      var e = [];
      if (_) {
        n = new _(vr(c, false), {
          root: r
        });
        n.observe(i);
        I(e, (function() {
          n.disconnect();
        }));
      } else {
        var a = function onSizeChanged() {
          var r = ne(i);
          u(r);
        };
        I(e, Ka(i, a)());
        a();
      }
      return vr(N, I(e, Nr(r, i)));
    }, function() {
      return n && c(true, n.takeRecords());
    } ];
  };
  var en = function createObserversSetup(r, a, n, t) {
    var i;
    var v;
    var o;
    var u;
    var c;
    var f;
    var l;
    var s;
    var d = "[" + Be + "]";
    var p = "[" + We + "]";
    var _ = [ "id", "class", "style", "open", "wrap", "cols", "rows" ];
    var h = r._r, b = r.gr, m = r.F, y = r.hr, w = r.br, O = r.R, E = r.mr, A = r.yr, D = r.Sr, H = r.wr;
    var P = function getDirectionIsRTL(r) {
      return getStyles(r, "direction") === "rtl";
    };
    var z = function createDebouncedObservesUpdate() {
      var r;
      var e;
      var a;
      var n = cr(t, {
        p: function _debounceTiming() {
          return r;
        },
        _: function _maxDebounceTiming() {
          return e;
        },
        m: function _leading() {
          return a;
        },
        S: function _mergeParams(r, e) {
          var a = r[0];
          var n = e[0];
          return [ k(lr(a), lr(n)).reduce((function(r, e) {
            r[e] = a[e] || n[e];
            return r;
          }), {}) ];
        }
      });
      var i = function fn(t, i) {
        if (x(i)) {
          var v = i[0], o = i[1], u = i[2];
          r = v;
          e = o;
          a = u;
        } else if (S(i)) {
          r = i;
          e = false;
          a = false;
        } else {
          r = false;
          e = false;
          a = false;
        }
        n(t);
      };
      i.O = n.O;
      return i;
    };
    var M = {
      Or: false,
      B: P(h)
    };
    var I = Ga();
    var L = He(za);
    var V = e({
      v: nr,
      i: {
        w: 0,
        h: 0
      }
    }, (function() {
      var e = L && L.V(r, a, M, I, n).Z;
      var t = E && O;
      var i = !t && Er(b, Be, Ue);
      var v = !O && A(Ge);
      var o = v && he(y);
      var u = o && H();
      var c = D(Qe, i);
      var f = v && e && e();
      var l = ie(m);
      var s = ve(m);
      if (f) {
        f();
      }
      ge(y, o);
      if (u) {
        u();
      }
      if (i) {
        c();
      }
      return {
        w: l.w + s.w,
        h: l.h + s.h
      };
    })), R = V[0];
    var F = z();
    var N = function setDirection(r) {
      var e = P(h);
      sr(r, {
        Cr: s !== e
      });
      sr(M, {
        B: e
      });
      s = e;
    };
    var j = function onTrinsicChanged(r, e) {
      var a = r[0], n = r[1];
      var i = {
        Er: n
      };
      sr(M, {
        Or: a
      });
      if (!e) {
        t(i);
      }
      return i;
    };
    var U = function onSizeChanged(r) {
      var e = r.pr, a = r.dr;
      var n = a ? t : F;
      var i = {
        pr: e || a,
        dr: a
      };
      N(i);
      n(i, v);
    };
    var q = function onContentMutation(r, e) {
      var a = R(), n = a[1];
      var t = {
        Ar: n
      };
      N(t);
      if (n && !e) {
        F(t, r ? o : i);
      }
      return t;
    };
    var B = function onHostMutation(r, e, a) {
      var n = {
        Tr: e
      };
      N(n);
      if (e && !a) {
        F(n, i);
      }
      return n;
    };
    var Y = w ? rn(b, j) : [], W = Y[0], X = Y[1];
    var Z = !O && Ka(b, U, {
      dr: true
    });
    var G = $a(b, false, B, {
      vr: _,
      ir: _
    }), Q = G[0], $ = G[1];
    var J = O && g && new g((function(r) {
      var e = r[r.length - 1].contentRect;
      U({
        pr: true,
        dr: fe(e, l)
      });
      l = e;
    }));
    return [ function() {
      if (J) {
        J.observe(b);
      }
      var r = Z && Z();
      var e = W && W();
      var a = Q();
      var n = I.J((function(r) {
        var e = R(), a = e[1];
        F({
          Dr: r,
          Ar: a,
          pr: E
        }, u);
      }));
      return function() {
        if (J) {
          J.disconnect();
        }
        if (r) {
          r();
        }
        if (e) {
          e();
        }
        if (f) {
          f();
        }
        a();
        n();
      };
    }, function(r) {
      var e = r.Hr, a = r.Pr, n = r.zr;
      var t = {};
      var l = e("update.ignoreMutation"), s = l[0];
      var g = e("update.attributes"), h = g[0], b = g[1];
      var y = e("update.elementEvents"), E = y[0], A = y[1];
      var D = e("update.debounce"), H = D[0], P = D[1];
      var z = A || b;
      var M = a || n;
      var I = function ignoreMutationFromOptions(r) {
        return C(s) && !!s(r);
      };
      if (z) {
        if (c) {
          c();
        }
        if (f) {
          f();
        }
        var L = $a(w || m, true, q, {
          ir: k(_, h || []),
          ur: E,
          cr: d,
          sr: function _ignoreContentChange(r, e) {
            var a = r.target, n = r.attributeName;
            var t = !e && n && !O ? Rr(a, d, p) : false;
            return t || !!Lr(a, "." + fa) || I(r);
          }
        }), V = L[0], R = L[1];
        f = V();
        c = R;
      }
      if (P) {
        F.O();
        if (x(H) || S(H)) {
          i = H;
          v = false;
          o = Fa;
          u = Na;
        } else if (T(H)) {
          i = H.mutation;
          v = H.resize;
          o = H.event;
          u = H.env;
        } else {
          i = false;
          v = false;
          o = false;
          u = false;
        }
      }
      if (M) {
        var U = $();
        var Y = X && X();
        var W = c && c();
        if (U) {
          sr(t, B(U[0], U[1], M));
        }
        if (Y) {
          sr(t, j(Y[0], M));
        }
        if (W) {
          sr(t, q(W[0], M));
        }
      }
      N(t);
      return t;
    }, M ];
  };
  var an = function resolveInitialization(r, e) {
    return C(e) ? e.apply(0, r) : e;
  };
  var nn = function staticInitializationElement(r, e, a, n) {
    var t = b(n) ? a : n;
    var i = an(r, t);
    return i || e.apply(0, r);
  };
  var tn = function dynamicInitializationElement(r, e, a, n) {
    var t = b(n) ? a : n;
    var i = an(r, t);
    return !!i && (D(i) ? i : e.apply(0, r));
  };
  var vn = function cancelInitialization(r, e) {
    var a = e || {}, n = a.nativeScrollbarsOverlaid, t = a.body;
    var i = Ga(), v = i.L, o = i.N, u = i.K;
    var c = u().cancel, f = c.nativeScrollbarsOverlaid, l = c.body;
    var s = n != null ? n : f;
    var d = b(t) ? l : t;
    var p = (v.x || v.y) && s;
    var _ = r && (m(d) ? !o : d);
    return !!p || !!_;
  };
  var on = function createScrollbarsSetupElements(r, e, a, n) {
    var t = "--os-viewport-percent";
    var i = "--os-scroll-percent";
    var v = "--os-scroll-direction";
    var o = Ga(), u = o.K;
    var c = u(), f = c.scrollbars;
    var l = f.slot;
    var s = e._r, d = e.gr, p = e.F, _ = e.Mr, g = e.hr, b = e.mr, m = e.R;
    var y = _ ? {} : r, S = y.scrollbars;
    var w = S || {}, C = w.slot;
    var x = [];
    var E = [];
    var A = [];
    var T = tn([ s, d, p ], (function() {
      return m && b ? s : d;
    }), l, C);
    var D = function initScrollTimeline(r) {
      if (h) {
        var e = null;
        var n = [];
        var t = new h({
          source: g,
          axis: r
        });
        var i = function cancelAnimation() {
          if (e) {
            e.cancel();
          }
          e = null;
        };
        var v = function _setScrollPercentAnimation(v) {
          var o = a.kr;
          var u = ye(o)[r];
          var c = r === "x";
          var f = [ Jr(0, c), Jr("calc(-100% + 100cq" + (c ? "w" : "h") + ")", c) ];
          var l = u ? f : f.reverse();
          if (n[0] === l[0] && n[1] === l[1]) {
            return i;
          }
          n = l;
          i();
          e = v.Ir.animate({
            clear: [ "left" ],
            transform: l
          }, {
            timeline: t
          });
          return i;
        };
        return {
          Lr: v
        };
      }
    };
    var H = {
      x: D("x"),
      y: D("y")
    };
    var P = function getViewportPercent() {
      var r = a.Vr, e = a.Rr;
      var n = function getAxisValue(r, e) {
        return gr(0, 1, r / (r + e) || 0);
      };
      return {
        x: n(e.x, r.x),
        y: n(e.y, r.y)
      };
    };
    var z = function scrollbarStructureAddRemoveClass(r, e, a) {
      var n = a ? Dr : Tr;
      each(r, (function(r) {
        n(r.Fr, e);
      }));
    };
    var M = function scrollbarStyle(r, e) {
      each(r, (function(r) {
        var a = e(r), n = a[0], t = a[1];
        setStyles(n, t);
      }));
    };
    var k = function scrollbarsAddRemoveClass(r, e, a) {
      var n = O(a);
      var t = n ? a : true;
      var i = n ? !a : true;
      if (t) {
        z(E, r, e);
      }
      if (i) {
        z(A, r, e);
      }
    };
    var L = function refreshScrollbarsHandleLength() {
      var r = P();
      var e = function createScrollbarStyleFn(r) {
        return function(e) {
          var a;
          return [ e.Fr, (a = {}, a[t] = Gr(r) + "", a) ];
        };
      };
      M(E, e(r.x));
      M(A, e(r.y));
    };
    var V = function refreshScrollbarsHandleOffset() {
      if (!h) {
        var r = a.kr;
        var e = Se(r, he(g));
        var n = function createScrollbarStyleFn(r) {
          return function(e) {
            var a;
            return [ e.Fr, (a = {}, a[i] = Gr(r) + "", a) ];
          };
        };
        M(E, n(e.x));
        M(A, n(e.y));
      }
    };
    var R = function refreshScrollbarsScrollCoordinates() {
      var r = a.kr;
      var e = ye(r);
      var n = function createScrollbarStyleFn(r) {
        return function(e) {
          var a;
          return [ e.Fr, (a = {}, a[v] = r ? "0" : "1", a) ];
        };
      };
      M(E, n(e.x));
      M(A, n(e.y));
      if (h) {
        E.forEach(H.x.Lr);
        A.forEach(H.y.Lr);
      }
    };
    var F = function refreshScrollbarsScrollbarOffset() {
      if (m && !b) {
        var r = a.Vr, e = a.kr;
        var n = ye(e);
        var t = Se(e, he(g));
        var i = function styleScrollbarPosition(e) {
          var a = e.Fr;
          var i = Ir(a) === p && a;
          var v = function getTranslateValue(r, e, a) {
            var n = e * r;
            return Qr(a ? n : -n);
          };
          return [ i, i && {
            transform: Jr({
              x: v(t.x, r.x, n.x),
              y: v(t.y, r.y, n.y)
            })
          } ];
        };
        M(E, i);
        M(A, i);
      }
    };
    var j = function generateScrollbarDOM(r) {
      var e = r ? "x" : "y";
      var a = r ? sa : da;
      var t = Br(fa + " " + a);
      var i = Br(pa);
      var v = Br(_a);
      var o = {
        Fr: t,
        Nr: i,
        Ir: v
      };
      var u = H[e];
      I(r ? E : A, o);
      I(x, [ Nr(t, i), Nr(i, v), vr(Fr, t), u && u.Lr(o), n(o, k, r) ]);
      return o;
    };
    var U = vr(j, true);
    var q = vr(j, false);
    var B = function appendElements() {
      Nr(T, E[0].Fr);
      Nr(T, A[0].Fr);
      return vr(N, x);
    };
    U();
    q();
    return [ {
      jr: L,
      Ur: V,
      qr: R,
      Br: F,
      Yr: k,
      Wr: {
        Xr: E,
        Zr: U,
        Gr: vr(M, E)
      },
      Qr: {
        Xr: A,
        Zr: q,
        Gr: vr(M, A)
      }
    }, B ];
  };
  var un = function createScrollbarsSetupEvents(r, e, a, n, t) {
    return function(i, u, c) {
      var f = e.gr, s = e.F, d = e.R, p = e.hr, _ = e.$r, g = e.wr;
      var h = i.Fr, b = i.Nr, m = i.Ir;
      var y = or(333), S = y[0], w = y[1];
      var O = or(444), x = O[0], E = O[1];
      var A = function scrollOffsetElementScrollBy(r) {
        if (C(p.scrollBy)) {
          p.scrollBy({
            behavior: "smooth",
            left: r.x,
            top: r.y
          });
        }
      };
      var T = function createInteractiveScrollEvents() {
        var e = "pointerup pointercancel lostpointercapture";
        var t = "client" + (c ? "X" : "Y");
        var i = c ? Q : $;
        var u = c ? "left" : "top";
        var f = c ? "w" : "h";
        var l = c ? "x" : "y";
        var s = [];
        return se(b, "pointerdown", n((function(n) {
          var d = Lr(n.target, "." + _a) === m;
          var h = d ? m : b;
          var y = r.scrollbars;
          var S = y[d ? "dragScroll" : "clickScroll"];
          var w = n.button, O = n.isPrimary, C = n.pointerType;
          var T = y.pointers;
          var D = w === 0 && O && S && (T || []).includes(C);
          if (D) {
            N(s);
            E();
            var H = !d && (n.shiftKey || S === "instant");
            var P = vr(oe, m);
            var z = vr(oe, b);
            var M = function getHandleOffset(r, e) {
              return (r || P())[u] - (e || z())[u];
            };
            var k = v(oe(p)[i]) / ne(p)[f] || 1;
            var L = he(p)[l];
            var V = function scrollRelative(r) {
              var e;
              ge(p, (e = {}, e[l] = L + r, e));
            };
            var R = function moveHandleRelative(r) {
              var e = a.Vr;
              var n = ne(b)[f] - ne(m)[f];
              var t = 1 / k * r / n;
              V(t * e[l]);
            };
            var F = n[t];
            var j = P();
            var U = z();
            var q = j[i];
            var B = M(j, U) + q / 2;
            var Y = F - U[u];
            var W = Y - B;
            var X = d ? 0 : W;
            var Z = function releasePointerCapture(r) {
              N($);
              h.releasePointerCapture(r.pointerId);
            };
            var G = d || H;
            var Q = g();
            var $ = [ se(_, e, Z), se(_, "selectstart", (function(r) {
              return pe(r);
            }), {
              D: false
            }), se(b, e, Z), G && se(b, "pointermove", (function(r) {
              return R(X + r[t] - F);
            })), G && function() {
              var r = he(p);
              Q();
              var e = he(p);
              var a = {
                x: e.x - r.x,
                y: e.y - r.y
              };
              if (o(a.x) > 3 || o(a.y) > 3) {
                g();
                ge(p, r);
                A(a);
                x(Q);
              }
            } ];
            h.setPointerCapture(n.pointerId);
            if (H) {
              R(W);
            } else if (!d) {
              var J = He(ka);
              if (J) {
                var K = a.Rr;
                var rr = J(V, R, vr(M), W, K[l], S, !!c, (function(r) {
                  if (r) {
                    Q();
                  } else {
                    I($, Q);
                  }
                }));
                I($, rr);
                I(s, vr(rr, true));
              }
            }
          }
        })));
      };
      var D = true;
      return vr(N, [ se(m, "pointermove pointerleave", n(t)), se(h, "pointerenter", n((function() {
        u(ba, true);
      }))), se(h, "pointerleave pointercancel", n((function() {
        u(ba, false);
      }))), se(h, "wheel", n((function(r) {
        var e = r.deltaX, a = r.deltaY, n = r.deltaMode;
        if (D && n === 0 && Ir(h) === f) {
          A({
            x: e,
            y: a
          });
        }
        D = false;
        u(wa, true);
        S((function() {
          D = true;
          u(wa);
        }));
        pe(r);
      })), {
        D: false,
        H: true
      }), !d && se(h, "mousedown", n((function() {
        var r = Vr();
        if (mr(r, We) || mr(r, Be) || r === document.body) {
          l(vr(we, s), 25);
        }
      }))), se(h, "pointerdown", (function() {
        var r = se(_, "click", (function(r) {
          e();
          _e(r);
        }), {
          P: true,
          H: true,
          D: false
        });
        var e = se(_, "pointerup pointercancel", (function() {
          e();
          setTimeout(r, 150);
        }), {
          H: true,
          D: true
        });
      }), {
        H: true,
        D: true
      }), T(), w, E ]);
    };
  };
  var cn = function createScrollbarsSetup(r, e, a, n, t, i, v) {
    var o;
    var u;
    var c;
    var f;
    var l;
    var s = _r;
    var d = 0;
    var p = [ "mouse", "pen" ];
    var _ = function skipEventIfSleeping(r) {
      return function(e) {
        if (!a.Jr) {
          r(e);
        }
      };
    };
    var g = function isHoverablePointerType(r) {
      return p.includes(r.pointerType);
    };
    var h = or(), b = h[0], m = h[1];
    var y = or(100), S = y[0], w = y[1];
    var O = or(50), C = O[0], x = O[1];
    var E = or((function() {
      return d;
    })), A = E[0], T = E[1];
    var D = on(r, i, t, un(e, i, t, _, (function(r) {
      return g(r) && q();
    }))), H = D[0], P = D[1];
    var z = i.gr, M = i.Kr, k = i.mr;
    var L = H.Yr, V = H.jr, R = H.Ur, F = H.qr, j = H.Br;
    var U = function manageScrollbarsAutoHide(r, e) {
      T();
      var n = function hide(r) {
        if (a.Jr) {
          return;
        }
        L(Sa, r);
      };
      if (r) {
        n();
      } else {
        var t = c ? !o : true;
        if (d > 0 && !e) {
          A(vr(n, t));
        } else {
          n(t);
        }
      }
    };
    var q = function manageScrollbarsAutoHideInstantInteraction() {
      if (c ? !o : !f) {
        U(true);
        S((function() {
          U(false);
        }));
      }
    };
    var B = function onHostMouseEnter(r) {
      if (g(r)) {
        o = true;
        if (!a.Jr && c) {
          U(true);
        }
      }
    };
    var Y = function onHostMouseLeave(r) {
      if (g(r)) {
        o = false;
        if (!a.Jr && c) {
          U(false);
        }
      }
    };
    var W = function manageAutoHideSuspension(r) {
      L(ya, r, true);
      L(ya, r, false);
    };
    var X = [ T, w, x, m, function() {
      return s();
    }, se(z, "pointerover", B, {
      P: true
    }), se(z, "pointerenter", B), se(z, "pointerleave", Y), se(z, "pointermove", _((function(r) {
      if (g(r) && u) {
        q();
      }
    }))), se(M, "scroll", _((function(r) {
      b((function() {
        R();
        q();
      }));
      v(r);
      j();
    }))) ];
    var Z = He(za);
    return [ function() {
      return vr(N, I(X, P()));
    }, function(r) {
      var e = r.Hr, a = r.zr, i = r.re, v = r.ee;
      var o = v || {}, p = o.ae, g = o.ne, h = o.te, b = o.ie;
      var m = i || {}, y = m.Cr, S = m.dr;
      var w = n.B;
      var O = Ga(), x = O.L, E = O.N;
      var A = t.ve, T = t.W;
      var D = e("showNativeOverlaidScrollbars"), H = D[0], P = D[1];
      var z = e("scrollbars.theme"), I = z[0], N = z[1];
      var q = e("scrollbars.visibility"), B = q[0], Y = q[1];
      var X = e("scrollbars.autoHide"), G = X[0], Q = X[1];
      var $ = e("scrollbars.autoHideSuspend"), K = $[0], er = $[1];
      var ar = e("scrollbars.autoHideDelay"), nr = ar[0];
      var tr = e("scrollbars.dragScroll"), ir = tr[0], or = tr[1];
      var ur = e("scrollbars.clickScroll"), cr = ur[0], fr = ur[1];
      var lr = e("overflow"), sr = lr[0], dr = lr[1];
      var pr = S && !a;
      var _r = p || g || b || y || a;
      var gr = h || Y || dr;
      var hr = H && x.x && x.y;
      var br = !E && !Z;
      var mr = hr || br;
      var yr = function setScrollbarVisibility(r, e, a) {
        var n = r.includes(rr) && (B === J || B === "auto" && e === rr);
        L(ga, n, a);
        return n;
      };
      d = nr;
      if (P || br) {
        L(ca, mr);
      }
      if (N) {
        L(l);
        L(I, true);
        l = I;
      }
      if (er || pr) {
        W(!K);
        if (pr && K) {
          if (T.x || T.y) {
            s();
            C((function() {
              s = se(M, rr, _(vr(W, true)), {
                P: true
              });
            }));
          } else {
            W(true);
          }
        }
      }
      if (Q) {
        u = G === "move";
        c = G === "leave";
        f = G === "never";
        U(f, true);
      }
      if (or) {
        L(Ca, ir);
      }
      if (fr) {
        L(Oa, !!cr);
      }
      if (gr) {
        var Sr = yr(sr.x, A.x, true);
        var wr = yr(sr.y, A.y, false);
        var Or = Sr && wr;
        L(ha, !Or);
      }
      if (_r) {
        R();
        V();
        j();
        if (b) {
          F();
        }
        L(ma, !T.x, true);
        L(ma, !T.y, false);
        L(la, w && !k);
      }
    }, {}, H ];
  };
  var ln = function createStructureSetupElements(r) {
    var e = Ga();
    var a = e.K, t = e.N;
    var i = a(), v = i.elements;
    var o = v.padding, u = v.viewport, c = v.content;
    var f = D(r);
    var l = f ? {} : r;
    var s = l.elements;
    var d = s || {}, p = d.padding, _ = d.viewport, g = d.content;
    var h = f ? r : l.target;
    var b = Mr(h);
    var m = h.ownerDocument;
    var y = m.documentElement;
    var S = function getDocumentWindow() {
      return m.defaultView || n;
    };
    var w = vr(nn, [ h ]);
    var O = vr(tn, [ h ]);
    var C = vr(Br, "");
    var x = vr(w, C, u);
    var E = vr(O, C, c);
    var A = function elementHasOverflow(r) {
      var e = ne(r);
      var a = ie(r);
      var n = getStyles(r, Z);
      var t = getStyles(r, G);
      return a.w - e.w > 0 && !Ta(n) || a.h - e.h > 0 && !Ta(t);
    };
    var T = x(_);
    var H = T === h;
    var P = H && b;
    var z = !H && E(g);
    var k = !H && T === z;
    var L = P ? y : T;
    var V = P ? L : h;
    var R = !H && O(C, o, p);
    var F = !k && z;
    var j = [ F, L, R, V ].map((function(r) {
      return D(r) && !Ir(r) && r;
    }));
    var U = function elementIsGenerated(r) {
      return r && M(j, r);
    };
    var q = !U(L) && A(L) ? L : h;
    var B = P ? y : L;
    var Y = P ? m : L;
    var W = {
      _r: h,
      gr: V,
      F: L,
      oe: R,
      br: F,
      hr: B,
      Kr: Y,
      ue: b ? y : q,
      $r: m,
      mr: b,
      Mr: f,
      R: H,
      ce: S,
      yr: function _viewportHasClass(r) {
        return Er(L, We, r);
      },
      Sr: function _viewportAddRemoveClass(r, e) {
        return xr(L, We, r, e);
      },
      wr: function _removeScrollObscuringStyles() {
        return xr(B, We, $e, true);
      }
    };
    var X = W._r, Q = W.gr, $ = W.oe, J = W.F, K = W.br;
    var rr = [ function() {
      Sr(Q, [ Be, je ]);
      Sr(X, je);
      if (b) {
        Sr(y, [ je, Be ]);
      }
    } ];
    var er = kr([ K, J, $, Q, X ].find((function(r) {
      return r && !U(r);
    })));
    var ar = P ? X : K || J;
    var nr = vr(N, rr);
    var tr = function appendElements() {
      var r = S();
      var e = Vr();
      var a = function unwrap(r) {
        Nr(Ir(r), kr(r));
        Fr(r);
      };
      var n = function prepareWrapUnwrapFocus(r) {
        return se(r, "focusin focusout focus blur", _e, {
          H: true,
          D: false
        });
      };
      var i = "tabindex";
      var v = br(J, i);
      var o = n(e);
      yr(Q, Be, H ? "" : Ye);
      yr($, ra, "");
      yr(J, We, "");
      yr(K, ea, "");
      if (!H) {
        yr(J, i, v || "-1");
        if (b) {
          yr(y, qe, "");
        }
      }
      Nr(ar, er);
      Nr(Q, $);
      Nr($ || Q, !H && J);
      Nr(J, K);
      I(rr, [ o, function() {
        var r = Vr();
        var e = U(J);
        var t = e && r === J ? X : r;
        var o = n(t);
        Sr($, ra);
        Sr(K, ea);
        Sr(J, We);
        if (b) {
          Sr(y, qe);
        }
        if (v) {
          yr(J, i, v);
        } else {
          Sr(J, i);
        }
        if (U(K)) {
          a(K);
        }
        if (e) {
          a(J);
        }
        if (U($)) {
          a($);
        }
        we(t);
        o();
      } ]);
      if (t && !H) {
        Cr(J, We, Je);
        I(rr, vr(Sr, J, We));
      }
      we(!H && b && e === X && r.top === r ? J : e);
      o();
      er = 0;
      return nr;
    };
    return [ W, tr, nr ];
  };
  var sn = function createTrinsicUpdateSegment(r) {
    var e = r.br;
    return function(r) {
      var a = r.re, n = r.fe, t = r.zr;
      var i = a || {}, v = i.Er;
      var o = n.Or;
      var u = e && (v || t);
      if (u) {
        var c;
        setStyles(e, (c = {}, c[$] = o && "100%", c));
      }
    };
  };
  var dn = function createPaddingUpdateSegment(r, a) {
    var n = r.gr, t = r.oe, i = r.F, v = r.R;
    var o = e({
      v: ir,
      i: $r()
    }, vr($r, n, "padding", "")), u = o[0], c = o[1];
    return function(r) {
      var e = r.Hr, n = r.re, o = r.fe, f = r.zr;
      var l = c(f), s = l[0], d = l[1];
      var p = Ga(), _ = p.N;
      var g = n || {}, h = g.pr, b = g.Ar, m = g.Cr;
      var y = o.B;
      var S = e("paddingAbsolute"), w = S[0], O = S[1];
      var C = f || b;
      if (h || d || C) {
        var x = u(f);
        s = x[0];
        d = x[1];
      }
      var E = !v && (O || m || d);
      if (E) {
        var A, T;
        var D = !w || !t && !_;
        var H = s.r + s.l;
        var P = s.t + s.b;
        var z = (A = {}, A[W] = D && !y ? -H : 0, A[X] = D ? -P : 0, A[Y] = D && y ? -H : 0, 
        A.top = D ? -s.t : 0, A.right = D ? y ? -s.r : "auto" : 0, A.left = D ? y ? "auto" : -s.l : 0, 
        A[Q] = D && "calc(100% + " + H + "px)", A);
        var M = (T = {}, T[j] = D ? s.t : 0, T[U] = D ? s.r : 0, T[B] = D ? s.b : 0, T[q] = D ? s.l : 0, 
        T);
        setStyles(t || i, z);
        setStyles(i, M);
        sr(a, {
          oe: s,
          le: !D,
          Y: t ? M : sr({}, z, M)
        });
      }
      return {
        se: E
      };
    };
  };
  var pn = function createOverflowUpdateSegment(r, a) {
    var i = Ga();
    var v = r.gr, u = r.oe, c = r.F, l = r.R, s = r.Kr, d = r.hr, p = r.mr, _ = r.Sr, g = r.ce;
    var h = i.N;
    var b = p && l;
    var m = vr(t, 0);
    var y = {
      display: function display() {
        return false;
      },
      direction: function direction(r) {
        return r !== "ltr";
      },
      flexDirection: function flexDirection(r) {
        return r.endsWith("-reverse");
      },
      writingMode: function writingMode(r) {
        return r !== "horizontal-tb";
      }
    };
    var S = lr(y);
    var O = {
      v: nr,
      i: {
        w: 0,
        h: 0
      }
    };
    var C = {
      v: tr,
      i: {}
    };
    var x = function setMeasuringMode(r) {
      _(Qe, !b && r);
    };
    var E = function getFlowDirectionStyles() {
      return getStyles(c, S);
    };
    var A = function getMeasuredScrollCoordinates(r, e) {
      var a = !lr(r).length;
      var n = e ? true : S.some((function(e) {
        var a = r[e];
        return w(a) && y[e](a);
      }));
      if (a || !n || !ue(c)) {
        return {
          M: {
            x: 0,
            y: 0
          },
          k: {
            x: 1,
            y: 1
          }
        };
      }
      x(true);
      var t = he(d);
      var i = se(s, rr, (function(r) {
        var e = he(d);
        if (r.isTrusted && e.x === t.x && e.y === t.y) {
          de(r);
        }
      }), {
        H: true,
        P: true
      });
      var v = _(Ke, true);
      ge(d, {
        x: 0,
        y: 0
      });
      v();
      var u = he(d);
      var l = ie(d);
      ge(d, {
        x: l.w,
        y: l.h
      });
      var p = he(d);
      var g = {
        x: p.x - u.x,
        y: p.y - u.y
      };
      ge(d, {
        x: -l.w,
        y: -l.h
      });
      var h = he(d);
      var b = {
        x: h.x - u.x,
        y: h.y - u.y
      };
      var m = {
        x: o(g.x) >= o(b.x) ? p.x : h.x,
        y: o(g.y) >= o(b.y) ? p.y : h.y
      };
      ge(d, t);
      f((function() {
        return i();
      }));
      return {
        M: u,
        k: m
      };
    };
    var T = function getOverflowAmount(r, e) {
      var a = n.devicePixelRatio % 1 !== 0 ? 1 : 0;
      var t = {
        w: m(r.w - e.w),
        h: m(r.h - e.h)
      };
      return {
        w: t.w > a ? t.w : 0,
        h: t.h > a ? t.h : 0
      };
    };
    var D = function getViewportOverflowStyle(r, e) {
      var a = function getAxisOverflowStyle(r, e, a, n) {
        var t = r === J ? K : Da(r);
        var i = Ta(r);
        var v = Ta(a);
        if (!e && !n) {
          return K;
        }
        if (i && v) {
          return J;
        }
        if (i) {
          var o = e ? J : K;
          return e && n ? t : o;
        }
        var u = v && n ? J : K;
        return e ? t : u;
      };
      return {
        x: a(e.x, r.x, e.y, r.y),
        y: a(e.y, r.y, e.x, r.x)
      };
    };
    var H = function setViewportOverflowStyle(r) {
      var e = function createAllOverflowStyleClassNames(r) {
        return [ J, K, rr ].map((function(e) {
          return cr(Ha(e), r);
        }));
      };
      var a = e(true).concat(e()).join(" ");
      _(a);
      _(lr(r).map((function(e) {
        return cr(r[e], e === "x");
      })).join(" "), true);
    };
    var P = e(O, vr(ve, c)), z = P[0], M = P[1];
    var I = e(O, vr(ie, c)), L = I[0], V = I[1];
    var R = e(O), N = R[0], j = R[1];
    var U = e(C), q = U[0];
    var B = e(O), Y = B[0], W = B[1];
    var X = e(C), Z = X[0];
    var G = e({
      v: function _equal(r, e) {
        return ar(r, e, F(k(lr(r), lr(e))));
      },
      i: {}
    }), Q = G[0];
    var $ = e({
      v: function _equal(r, e) {
        return tr(r.M, e.M) && tr(r.k, e.k);
      },
      i: be()
    }), ir = $[0], or = $[1];
    var ur = He(za);
    var cr = function createViewportOverflowStyleClassName(r, e) {
      var a = e ? Xe : Ze;
      return "" + a + er(r);
    };
    return function(e, n) {
      var t = e.Hr, o = e.re, f = e.fe, l = e.zr;
      var s = n.se;
      var d = o || {}, p = d.pr, y = d.Tr, S = d.Ar, w = d.Cr, O = d.dr, C = d.Dr;
      var P = ur && ur.V(r, a, f, i, t);
      var k = P || {}, I = k.X, R = k.Z, F = k.G;
      var U = Aa(t, i), B = U[0], X = U[1];
      var G = t("overflow"), $ = G[0], J = G[1];
      var K = Ta($.x);
      var rr = Ta($.y);
      var er = p || s || S || w || C || X;
      var ar = M(l);
      var nr = V(l);
      var tr = j(l);
      var vr = W(l);
      if (X && h) {
        _(Je, !B);
      }
      if (er) {
        if (Er(v, Be, Ue)) {
          x(true);
        }
        var cr = R && R();
        var fr = ar = z(l), lr = fr[0];
        var dr = nr = L(l), pr = dr[0];
        var _r = te(c);
        var gr = b && ae(g());
        var hr = {
          w: m(pr.w + lr.w),
          h: m(pr.h + lr.h)
        };
        var br = {
          w: m((gr ? gr.w : _r.w + m(_r.w - pr.w)) + lr.w),
          h: m((gr ? gr.h : _r.h + m(_r.h - pr.h)) + lr.h)
        };
        if (cr) {
          cr();
        }
        vr = Y(br);
        tr = N(T(hr, br), l);
      }
      var mr = vr, yr = mr[0], Sr = mr[1];
      var wr = tr, Or = wr[0], Cr = wr[1];
      var Ar = nr, Tr = Ar[0], Dr = Ar[1];
      var Hr = ar, Pr = Hr[0], zr = Hr[1];
      var Mr = q({
        x: Or.w > 0,
        y: Or.h > 0
      }), kr = Mr[0];
      var Ir = K && rr && (kr.x || kr.y) || K && kr.x && !kr.y || rr && kr.y && !kr.x;
      var Lr = s || w || C || zr || Dr || Sr || Cr || J || X || er || y && b;
      var Vr = t("update.flowDirectionStyles"), Rr = Vr[0];
      var Fr = Q(Rr ? Rr(c) || {} : E(), l), Nr = Fr[0], jr = Fr[1];
      var Ur = w || O || jr || l;
      var qr = Ur ? ir(A(Nr, !!Rr), l) : or(), Br = qr[0], Yr = qr[1];
      var Wr = D(kr, $);
      x(false);
      if (Lr) {
        H(Wr);
        Wr = Pa(c, kr);
        if (F && I) {
          I(Wr, Tr, Pr);
          setStyles(c, F(Wr));
        }
      }
      var Xr = Z(Wr), Zr = Xr[0], Gr = Xr[1];
      xr(v, Be, Ue, Ir);
      xr(u, ra, Ue, Ir);
      sr(a, {
        ve: Zr,
        Rr: {
          x: yr.w,
          y: yr.h
        },
        Vr: {
          x: Or.w,
          y: Or.h
        },
        W: kr,
        kr: me(Br, Or)
      });
      return {
        te: Gr,
        ae: Sr,
        ne: Cr,
        ie: Yr || Cr
      };
    };
  };
  var _n = function createStructureSetup(r) {
    var e;
    var a = ln(r), n = a[0], t = a[1], i = a[2];
    var v = {
      oe: {
        t: 0,
        r: 0,
        b: 0,
        l: 0
      },
      le: false,
      Y: (e = {}, e[W] = 0, e[X] = 0, e[Y] = 0, e[j] = 0, e[U] = 0, e[B] = 0, e[q] = 0, 
      e),
      Rr: {
        x: 0,
        y: 0
      },
      Vr: {
        x: 0,
        y: 0
      },
      ve: {
        x: K,
        y: K
      },
      W: {
        x: false,
        y: false
      },
      kr: be()
    };
    var o = n._r, u = n.hr, c = n.R, f = n.wr;
    var l = Ga(), s = l.N, d = l.L;
    var p = !s && (d.x || d.y);
    var _ = [ sn(n), dn(n, v), pn(n, v) ];
    return [ t, function(r) {
      var e = {};
      var a = p;
      var n = a && he(u);
      var t = n && f();
      each(_, (function(a) {
        sr(e, a(r, e) || {});
      }));
      ge(u, n);
      if (t) {
        t();
      }
      if (!c) {
        ge(o, 0);
      }
      return e;
    }, v, n, i ];
  };
  var gn = function createSetups(r, e, a, n) {
    var t = false;
    var i = {
      Jr: false,
      de: false
    };
    var v = qa(e, {});
    var o = _n(r), u = o[0], c = o[1], f = o[2], l = o[3], s = o[4];
    var d = en(l, f, v, (function(r) {
      w({}, r);
    })), p = d[0], _ = d[1], g = d[2];
    var h = cn(r, e, i, g, f, l, n), b = h[0], m = h[1], y = h[3];
    var S = function updateHintsAreTruthy(r) {
      return lr(r).some((function(e) {
        return !!r[e];
      }));
    };
    var w = function update(r, n) {
      var v = i.Jr, o = i.de;
      if (o || v && t) {
        return false;
      }
      var u = r.pe, f = r.zr, l = r.Pr;
      var s = u || {};
      var d = !!f || !t;
      var p = {
        Hr: qa(e, s, d),
        pe: s,
        zr: d
      };
      var h = n || _(sr({}, p, {
        Pr: l
      }));
      var b = c(sr({}, p, {
        fe: g,
        re: h
      }));
      m(sr({}, p, {
        re: h,
        ee: b
      }));
      var y = S(h);
      var w = S(b);
      var O = y || w || !pr(s) || d;
      t = true;
      if (O) {
        a(r, {
          re: h,
          ee: b
        });
      }
      return O;
    };
    return [ function() {
      var r = l.ue, e = l.hr, a = l.wr;
      var n = he(r);
      var t = [ p(), u(), b(), function() {
        i.de = true;
      } ];
      var v = a();
      ge(e, n);
      v();
      return vr(N, t);
    }, w, function(r) {
      var e = i.Jr;
      i.Jr = r;
      if (!r && e !== r) {
        w({
          zr: true,
          Pr: true
        });
      }
    }, function() {
      m({
        Hr: qa(e, {}, false),
        pe: {},
        zr: false
      });
    }, function() {
      return {
        _e: i,
        ge: g,
        he: f
      };
    }, {
      be: l,
      me: y
    }, s ];
  };
  var hn = new WeakMap;
  var bn = function addInstance(r, e) {
    hn.set(r, e);
  };
  var mn = function removeInstance(r) {
    hn.delete(r);
  };
  var yn = function getInstance(r) {
    return hn.get(r);
  };
  var Sn = function OverlayScrollbars(r, e, a) {
    var n = Ga(), t = n.er;
    var i = D(r);
    var v = i ? r : r.target;
    var o = yn(v);
    if (e && !o) {
      var u = [];
      var c = {};
      var f = function validateOptions(r) {
        var e = dr(r);
        var a = He(Ve);
        return a ? a(e, true) : e;
      };
      var l = sr({}, t(), f(e));
      var s = Ce(), d = s[0], p = s[1], _ = s[2];
      var g = Ce(a), h = g[0], b = g[1], m = g[2];
      var y = function triggerEvent(r, e) {
        m(r, e);
        _(r, e);
      };
      var S = gn(r, l, (function(r, e) {
        var a = r.pe, n = r.zr;
        var t = e.re, i = e.ee;
        var v = t.pr, o = t.Cr, u = t.Er, c = t.Ar, f = t.Tr, l = t.dr;
        var s = i.ae, d = i.ne, p = i.te, _ = i.ie;
        y("updated", [ z, {
          updateHints: {
            sizeChanged: !!v,
            directionChanged: !!o,
            heightIntrinsicChanged: !!u,
            overflowEdgeChanged: !!s,
            overflowAmountChanged: !!d,
            overflowStyleChanged: !!p,
            scrollCoordinatesChanged: !!_,
            contentMutation: !!c,
            hostMutation: !!f,
            appear: !!l
          },
          changedOptions: a || {},
          force: !!n
        } ]);
      }), (function(r) {
        return y("scroll", [ z, r ]);
      })), w = S[0], O = S[1], C = S[2], x = S[3], E = S[4], A = S[5], T = S[6];
      var H = function destroy(r) {
        var e = E(), a = e._e;
        var n = a.de;
        if (n) {
          return;
        }
        mn(v);
        N(u);
        y("destroyed", [ z, r ]);
        p();
        b();
      };
      var P = function update(r) {
        return O({
          zr: r,
          Pr: true
        });
      };
      var z = {
        options: function options(r, e) {
          if (r) {
            var a = e ? t() : {};
            var n = Ua(l, sr(a, f(r)));
            if (!pr(n)) {
              sr(l, n);
              O({
                pe: n
              });
            }
          }
          return sr({}, l);
        },
        on: h,
        off: function off(r, e) {
          if (r && e) {
            b(r, e);
          }
        },
        state: function state() {
          var r = E(), e = r._e, a = r.ge, n = r.he;
          var t = e.de, i = e.Jr;
          var v = a.B;
          var o = n.Rr, u = n.Vr, c = n.ve, f = n.W, l = n.oe, s = n.le, d = n.kr;
          return sr({}, {
            overflowEdge: o,
            overflowAmount: u,
            overflowStyle: c,
            hasOverflow: f,
            scrollCoordinates: {
              start: d.M,
              end: d.k
            },
            padding: l,
            paddingAbsolute: s,
            directionRTL: v,
            sleeping: i,
            destroyed: t
          });
        },
        elements: function elements() {
          var r = A.be, e = r._r, a = r.gr, n = r.oe, t = r.F, i = r.br, v = r.hr, o = r.Kr;
          var u = A.me, c = u.Wr, f = u.Qr;
          var l = function translateScrollbarStructure(r) {
            var e = r.Ir, a = r.Nr, n = r.Fr;
            return {
              scrollbar: n,
              track: a,
              handle: e
            };
          };
          var s = function translateScrollbarsSetupElement(r) {
            var e = r.Xr, a = r.Zr;
            var n = l(e[0]);
            return sr({}, n, {
              clone: function clone() {
                var r = l(a());
                x();
                return r;
              }
            });
          };
          return sr({}, {
            target: e,
            host: a,
            padding: n || t,
            viewport: t,
            content: i || t,
            scrollOffsetElement: v,
            scrollEventElement: o,
            scrollbarHorizontal: s(c),
            scrollbarVertical: s(f)
          });
        },
        update: P,
        destroy: vr(H, false),
        sleep: C,
        plugin: function plugin(r) {
          return c[lr(r)[0]];
        }
      };
      I(u, [ T ]);
      bn(v, z);
      Te(xe, Sn, [ z, d, c ]);
      if (vn(A.be.mr, !i && r.cancel)) {
        H(true);
        return z;
      }
      I(u, w());
      y("initialized", [ z ]);
      z.update();
      return z;
    }
    return o;
  };
  Sn.plugin = function(r) {
    var e = x(r);
    var a = e ? r : [ r ];
    var n = a.map((function(r) {
      return Te(r, Sn)[0];
    }));
    Ae(a);
    return e ? n : n[0];
  };
  Sn.valid = function(r) {
    var e = r && r.elements;
    var a = C(e) && e();
    return T(a) && !!yn(a.target);
  };
  Sn.env = function() {
    var r = Ga(), e = r.j, a = r.L, n = r.N, t = r.$, i = r.nr, v = r.tr, o = r.K, u = r.rr, c = r.er, f = r.ar;
    return sr({}, {
      scrollbarsSize: e,
      scrollbarsOverlaid: a,
      scrollbarsHiding: n,
      scrollTimeline: t,
      staticDefaultInitialization: i,
      staticDefaultOptions: v,
      getDefaultInitialization: o,
      setDefaultInitialization: u,
      getDefaultOptions: c,
      setDefaultOptions: f
    });
  };
  Sn.nonce = Wa;
  Sn.trustedTypePolicy = qr;
  r.ClickScrollPlugin = Ia;
  r.OverlayScrollbars = Sn;
  r.ScrollbarsHidingPlugin = Ma;
  r.SizeObserverPlugin = Ea;
  return r;
}({});
//# sourceMappingURL=overlayscrollbars.browser.es5.js.map
