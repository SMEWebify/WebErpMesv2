// Plan bullé : repères (page d'origine / écran tourné) et dessin des bulles,
// partagés par le bulleur et l'onglet Contrôle de l'écran tâche.
import React, { useRef, useState } from 'react';

export const clamp01 = v => Math.min(1, Math.max(0, v));
export const BUBBLE_R = 12;          // rayon d'une bulle à l'écran (px), quel que soit le zoom
export const ACCENT = '#e8590c';     // orange des bulles, lisible sur le blanc du plan en clair comme en sombre

// ---------------------------------------------------------------------------
// Dessin des bulles (écran : SVG ; miniature : canevas)
// ---------------------------------------------------------------------------

export function bubbleCenter(p) {
    return {
        x: p.bubble_x ?? Math.max(0.01, (p.box_x ?? 0) - 0.02),
        y: p.bubble_y ?? Math.max(0.01, (p.box_y ?? 0) - 0.025),
    };
}

// Les cotes sont stockées dans le repère de la page d'origine ; l'écran affiche
// le plan tourné de `rotation` degrés (sens horaire). Conversions en proportions 0–1.
export function toView(x, y, rotation) {
    switch (((rotation % 360) + 360) % 360) {
        case 90:  return [1 - y, x];
        case 180: return [1 - x, 1 - y];
        case 270: return [y, 1 - x];
        default:  return [x, y];
    }
}

export function toPage(u, v, rotation) {
    switch (((rotation % 360) + 360) % 360) {
        case 90:  return [v, 1 - u];
        case 180: return [1 - u, 1 - v];
        case 270: return [1 - v, u];
        default:  return [u, v];
    }
}

export function boxThrough(convert, x, y, w, h, rotation) {
    const [ax, ay] = convert(x, y, rotation);
    const [bx, by] = convert(x + w, y + h, rotation);
    return { box_x: Math.min(ax, bx), box_y: Math.min(ay, by), box_w: Math.abs(bx - ax), box_h: Math.abs(by - ay) };
}

/** Cote telle qu'elle s'affiche : rectangle et bulle passés dans le repère tourné. */
export function pointToView(p, rotation) {
    if (p.box_x == null) return p;
    const c = bubbleCenter(p);
    const [bubble_x, bubble_y] = toView(c.x, c.y, rotation);
    return { ...p, ...boxThrough(toView, p.box_x, p.box_y, p.box_w, p.box_h, rotation), bubble_x, bubble_y };
}

/** Point du rectangle le plus proche de la bulle : l'attache du trait de rappel. */
export function anchorOf(p, cx, cy, w, h) {
    const x0 = p.box_x * w, y0 = p.box_y * h, x1 = x0 + p.box_w * w, y1 = y0 + p.box_h * h;
    return { x: Math.min(Math.max(cx, x0), x1), y: Math.min(Math.max(cy, y0), y1) };
}

export function drawBubblesOnCanvas(ctx, points, w, h, radius) {
    ctx.lineWidth = Math.max(1, radius / 6);
    for (const p of points) {
        if (p.box_x == null) continue;
        const c = bubbleCenter(p);
        const cx = c.x * w, cy = c.y * h;
        ctx.fillStyle = 'rgba(232, 89, 12, 0.12)';
        ctx.strokeStyle = ACCENT;
        ctx.fillRect(p.box_x * w, p.box_y * h, p.box_w * w, p.box_h * h);
        ctx.strokeRect(p.box_x * w, p.box_y * h, p.box_w * w, p.box_h * h);
        const a = anchorOf(p, cx, cy, w, h);
        ctx.beginPath(); ctx.moveTo(cx, cy); ctx.lineTo(a.x, a.y); ctx.stroke();
        ctx.beginPath(); ctx.arc(cx, cy, radius, 0, Math.PI * 2);
        ctx.fillStyle = '#ffffff'; ctx.fill(); ctx.stroke();
        ctx.fillStyle = ACCENT;
        ctx.font = `bold ${Math.round(radius * 1.1)}px sans-serif`;
        ctx.textAlign = 'center'; ctx.textBaseline = 'middle';
        ctx.fillText(String(p.number), cx, cy + 0.5);
    }
}

/**
 * `colorOf(p)` : couleur d'une bulle (défaut : orange) ; `isDimmed(p)` : bulle
 * atténuée (cote d'une autre opération) ; `ringOf(p)` : second cercle (critique).
 */
export function Overlay({ points, selectedId, width, height, mode = 'select', editable = false, onSelect, onDraw, onMoveBubble,
                          colorOf = () => ACCENT, isDimmed = () => false, ringOf = () => null }) {
    const [draft, setDraft] = useState(null);
    const [drag, setDrag] = useState(null);
    const svgRef = useRef(null);
    // Le déplacement capture le pointeur : le clic qui le termine arrive sur le
    // SVG et désélectionnerait la cote qu'on vient de déplacer.
    const swallowClick = useRef(false);

    const toRatio = (e) => {
        const r = svgRef.current.getBoundingClientRect();
        return { x: clamp01((e.clientX - r.left) / r.width), y: clamp01((e.clientY - r.top) / r.height) };
    };

    const onPointerDown = (e) => {
        if (mode !== 'draw' || !editable || e.button !== 0) return;
        if (e.target.closest('[data-point]')) return;
        e.currentTarget.setPointerCapture(e.pointerId);
        const start = toRatio(e);
        setDraft({ start, end: start });
    };

    const onPointerMove = (e) => {
        if (draft) setDraft(d => ({ ...d, end: toRatio(e) }));
        if (drag) setDrag(d => ({ ...d, pos: toRatio(e) }));
    };

    const onPointerUp = () => {
        if (draft) {
            const x = Math.min(draft.start.x, draft.end.x), y = Math.min(draft.start.y, draft.end.y);
            const w = Math.abs(draft.end.x - draft.start.x), h = Math.abs(draft.end.y - draft.start.y);
            setDraft(null);
            if (w * width > 6 && h * height > 6) onDraw({ box_x: x, box_y: y, box_w: w, box_h: h });
        }
        if (drag) {
            if (drag.pos) onMoveBubble(drag.id, drag.pos);
            swallowClick.current = true;
            setDrag(null);
        }
    };

    const onClick = (e) => {
        if (swallowClick.current) { swallowClick.current = false; return; }
        if (!e.target.closest('[data-point]') && mode !== 'draw') onSelect(null);
    };

    return (
        <svg ref={svgRef} width={width} height={height}
             style={{ position: 'absolute', inset: 0, cursor: mode === 'draw' && editable ? 'crosshair' : 'default', touchAction: 'none' }}
             onPointerDown={onPointerDown} onPointerMove={onPointerMove} onPointerUp={onPointerUp}
             onClick={onClick}>
            {points.map(p => {
                if (p.box_x == null) return null;
                const selected = p.id === selectedId;
                const c = drag?.id === p.id && drag.pos ? drag.pos : bubbleCenter(p);
                const cx = c.x * width, cy = c.y * height;
                const a = anchorOf(p, cx, cy, width, height);
                const color = colorOf(p);
                const ring = ringOf(p);
                return (
                    <g key={p.id} data-point={p.id} opacity={isDimmed(p) && !selected ? 0.35 : 1}
                       style={{ cursor: editable && mode !== 'draw' ? 'move' : 'pointer' }}
                       onClick={e => { e.stopPropagation(); onSelect(p.id); }}>
                        <rect x={p.box_x * width} y={p.box_y * height} width={p.box_w * width} height={p.box_h * height}
                              fill={color} fillOpacity={selected ? 0.22 : 0.10}
                              stroke={color} strokeWidth={selected ? 2.5 : 1.5} rx={2} />
                        <line x1={cx} y1={cy} x2={a.x} y2={a.y} stroke={color} strokeWidth={1.5} strokeDasharray="4 3" />
                        {ring && <circle cx={cx} cy={cy} r={BUBBLE_R + 4} fill="none" stroke={ring} strokeWidth={2} />}
                        <circle cx={cx} cy={cy} r={BUBBLE_R} fill={selected ? color : '#ffffff'} stroke={color} strokeWidth={2.5}
                                onPointerDown={e => {
                                    if (!editable || mode === 'draw') return;
                                    e.stopPropagation();
                                    svgRef.current.setPointerCapture(e.pointerId);
                                    onSelect(p.id);
                                    setDrag({ id: p.id, pos: null });
                                }} />
                        <text x={cx} y={cy + 0.5} textAnchor="middle" dominantBaseline="middle" fontWeight="700" fontSize={12}
                              fill={selected ? '#ffffff' : color} style={{ pointerEvents: 'none' }}>{p.number}</text>
                    </g>
                );
            })}
            {draft && (
                <rect x={Math.min(draft.start.x, draft.end.x) * width} y={Math.min(draft.start.y, draft.end.y) * height}
                      width={Math.abs(draft.end.x - draft.start.x) * width} height={Math.abs(draft.end.y - draft.start.y) * height}
                      fill="rgba(13,110,253,0.10)" stroke="#0d6efd" strokeWidth={1.5} strokeDasharray="5 3" />
            )}
        </svg>
    );
}
