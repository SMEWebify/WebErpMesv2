import { describe, it, expect } from 'vitest';
import {
    DEFAULTS, clone, normBends, build, fillet, usablePunches, dieCatalog, yRangeAt, inPoly,
} from '../../lib/pressbrake/geometry';
import { drawing } from '../../lib/pressbrake/drawing';

// Les bibliothèques constructeurs ne sont pas versionnées : les poinçons de test sont synthétiques,
// au format de press-brake-punches.json (origine au point bas du rayon de pointe, y vers le haut).

/**
 * Poinçon en V : angle A, rayon de pointe R (centre (0, R)), flancs jusqu'à la demi-largeur w du
 * corps, corps vertical jusqu'à H. dx décale le profil ; chord remplace le point bas par deux
 * points à ±chord ramenés à y = 0, comme les profils dont le bas du rayon est arrondi au palier.
 */
function vPunch({ A = 88, R = 1, w = 12, H = 100, dx = 0, chord = 0, radius = R, id = 1, brand = 'Test' } = {}) {
    const th = A / 2 * Math.PI / 180;
    const arc = [];
    for (let i = 0; i <= 12; i++) {
        const a = -Math.PI + th + (Math.PI - 2 * th) * i / 12;   // de la tangente gauche à la tangente droite
        arc.push([R * Math.cos(a), i === 6 ? 0 : R + R * Math.sin(a)]);
    }
    if (chord) arc.splice(6, 1, [-chord, 0], [chord, 0]);
    const tR = arc[arc.length - 1], tL = arc[0];
    const yF = (x0, y0, xe) => y0 + (xe - x0) / Math.tan(th);
    const poly = [...arc, [w, yF(tR[0], tR[1], w)], [w, H], [-w, H], [-w, yF(-tL[0], tL[1], w)]]
        .map((p) => [p[0] + dx, p[1]]);
    return { id, brand, name: 'P' + id, angle: A, radius, height: H, poly };
}

const part = (legs, bends, k = 0, seq) => normBends({ legs, bends: bends.map(([a, sg = 1]) => ({ a, s: sg })), k, seq });
const state = (over = {}) => ({ ...clone(DEFAULTS), src: 'lib', mirror: false, ...over });
const turned = (g, i) => 180 - Math.acos(g.dir[i][0] * g.dir[i + 1][0] + g.dir[i][1] * g.dir[i + 1][1]) * 180 / Math.PI;

describe('fillet', () => {
    it('arrondit un angle droit par un arc de rayon r tangent aux deux côtés', () => {
        const out = fillet([[0, 10], [0, 0], [10, 0]], [0, 2]);
        expect(out[1]).toEqual([0, 2].map((v, i) => expect.closeTo(v, 9)));
        expect(out.at(-2)[0]).toBeCloseTo(2, 9);
        out.slice(1, -1).forEach((p) => expect(Math.hypot(p[0] - 2, p[1] - 2)).toBeCloseTo(2, 9));
    });

    it('réduit le rayon quand il ne tient pas dans les côtés', () => {
        const out = fillet([[0, 1], [0, 0], [1, 0]], [0, 5]);
        out.forEach((p) => { expect(p[0]).toBeLessThanOrEqual(1); expect(p[1]).toBeLessThanOrEqual(1); });
    });
});

describe('tôle pliée', () => {
    it('épouse le rayon de pointe : intérieur sur la pointe, extérieur à une épaisseur dessous', () => {
        const g = build(state({ part: part([30, 60], [[88]]) }), vPunch({ A: 88, R: 3 }));
        const r = yRangeAt(g.part, 0);
        expect(r[1]).toBeCloseTo(0, 2);
        expect(r[0]).toBeCloseTo(-DEFAULTS.t, 2);
        expect(g.collide).toBe(false);
    });

    it("place l'angle vif virtuel de la pièce à R / sin(Ap/2) sous le centre du rayon", () => {
        const g = build(state({ part: part([30, 60], [[90]]) }), vPunch({ A: 60, R: 4 }));
        expect(g.Ip[1]).toBeCloseTo(4 - 4 / Math.sin(Math.PI / 4), 9);
    });

    it("plie à 90° avec un poinçon à 30° (pliage en l'air), et se referme sur l'outil sous 30°", () => {
        const punch = vPunch({ A: 30, R: 1, w: 10 });
        expect(build(state({ part: part([30, 60], [[90]]) }), punch).collide).toBe(false);
        const closed = build(state({ part: part([30, 60], [[20]]) }), punch);
        expect(closed.collide).toBe(true);
        expect(closed.notes.join(' ')).toMatch(/plus fermé que la pointe/);
    });

    it('chaque pli garde son angle quand le poinçon change de pli', () => {
        const legs = [30, 60, 30], bends = [[90], [120]];
        // poinçon sur le pli 1 puis sur le pli 2, à la dernière étape (les deux plis faits)
        for (const [k, seq] of [[0, [1, 0]], [1, [0, 1]]]) {
            const g = build(state({ part: part(legs, bends, k, seq) }), vPunch({ A: 30 }));
            expect(turned(g, 0)).toBeCloseTo(90, 6);
            expect(turned(g, 1)).toBeCloseTo(120, 6);
        }
    });

    it('un pli « inverse » sous le poinçon retourne la tôle sans déformer la pièce', () => {
        const bends = [[90], [120, -1]];
        // dernière étape dans les deux cas, pour que les deux plis soient faits
        const a = build(state({ part: part([30, 60, 30], bends, 0, [1, 0]) }), vPunch({ A: 30 }));
        const b = build(state({ part: part([30, 60, 30], bends, 1, [0, 1]) }), vPunch({ A: 30 }));
        [0, 1].forEach((i) => expect(turned(b, i)).toBeCloseTo(turned(a, i), 6));
    });

    it('laisse à plat les plis qui suivent dans la séquence', () => {
        const p = part([30, 60, 30, 20], [[90], [120], [90]], 1, [2, 1, 0]);   // étape 2/3 : pli 3 fait, pli 1 à plat
        const g = build(state({ part: p }), vPunch({ A: 30 }));
        expect(g.flat).toEqual([true, false, false]);
        expect(turned(g, 0)).toBeCloseTo(180, 6);
        expect(turned(g, 2)).toBeCloseTo(90, 6);
    });
});

describe('collision avec le poinçon', () => {
    // Régression : une aile traversait le corps du poinçon entre deux points échantillonnés de son contour.
    it("détecte une aile qui traverse le poinçon sans qu'aucun sommet de l'outil ne tombe dans la tôle", () => {
        const punch = vPunch({ A: 30, R: 1, w: 8, H: 150 });
        // pli 2 déjà fait : la dernière aile revient vers la gauche et traverse le corps vers y = 80,
        // entre ses sommets (y ≈ 27 et y = 150) ; le nez, posé sur la face intérieure, est exclu
        const p = part([20, 60, 80], [[90], [90]], 0, [1, 0]);
        const g = build(state({ part: p, t: 1.2 }), punch);
        expect(punch.poly.filter((q) => q[1] > 5).some((q) => inPoly(q, g.part))).toBe(false);
        expect(g.collide).toBe(true);
    });

    it('ne signale rien pour une tôle en appui le long des flancs (angle de pliage = angle de pointe)', () => {
        const g = build(state({ part: part([30, 60], [[88]]) }), vPunch({ A: 88, R: 1 }));
        expect(g.collide).toBe(false);
    });

    // Régression (Wilson 50309) : le rayon est un cercle centré en x ≈ 0,5, et l'origine du profil est
    // à une extrémité d'une corde arrondie à y = 0 (points (0, 0) et (0,99, 0)) au lieu du point bas.
    it('centre la tôle sur un nez dont le point bas est décalé', () => {
        const punch = vPunch({ A: 88, R: 3.2, dx: 0.5, chord: 0.5 });
        const g = build(state({ part: part([30, 60], [[120]]) }), punch);
        expect(g.C[0]).toBeCloseTo(0.5, 9);
        expect(g.collide).toBe(false);
    });

    // Régression : sans rayon renseigné, la tôle prenait un angle vif qui entrait dans le nez.
    it('mesure le rayon de pointe sur le profil quand la bibliothèque ne le donne pas', () => {
        const g = build(state({ part: part([30, 60], [[90]]) }), vPunch({ A: 88, R: 0.9, radius: null }));
        expect(g.Rn).toBeGreaterThan(0.8);
        expect(g.Rn).toBeLessThan(1.0);
        expect(String(g.R)).toMatch(/^≈/);
        expect(g.collide).toBe(false);
    });
});

describe('matrice', () => {
    const V10 = { v: 10, a: 88, r: 1, h: 50, label: 'V10' };

    it('remonte au contact de la tôle, appuyée sur les épaules', () => {
        const g = build(state({ part: part([30, 60], [[90]]) }), vPunch({ A: 88 }), V10);
        const gaps = g.die.poly.filter((q) => Math.abs(q[0]) <= 6).map((q) => { const r = yRangeAt(g.part, q[0]); return r ? r[0] - q[1] : Infinity; });
        expect(Math.min(...gaps)).toBeGreaterThan(-0.01);
        expect(Math.min(...gaps)).toBeLessThan(0.05);
        expect(g.dieCollide).toBe(false);
    });

    it("signale une aile trop courte pour l'ouverture du V", () => {
        const g = build(state({ part: part([20, 20], [[90]]) }), vPunch({ A: 88 }), { ...V10, v: 80, h: 80 });
        expect(g.notes.join(' ')).toMatch(/Aile trop courte pour V80/);
    });

    it('rappelle la règle V ≈ 6 à 10 × t', () => {
        const g = build(state({ part: part([30, 60], [[90]]), t: 3 }), vPunch({ A: 88 }), V10);
        expect(g.notes.join(' ')).toMatch(/V10 étroit pour 3 mm/);
    });
});

describe('données de bibliothèque', () => {
    it('écarte les gabarits « Missing Profile » partagés à l’identique', () => {
        const placeholder = [[0, 0], [1, 0], [1, 1]];
        const data = [
            ...Array.from({ length: 10 }, (_, i) => ({ id: i, poly: placeholder })),
            vPunch({ id: 100 }), vPunch({ id: 101, A: 30 }),
        ];
        expect(usablePunches(data).map((t) => t.id)).toEqual([100, 101]);
    });

    it('garde les matrices en V cotées et complète rayon et hauteur absents', () => {
        const dies = dieCatalog([
            { type: 'die', id: 1, brand: 'W', name: 'D1', v: 10, angle: 88, radius: null, height: null },
            { type: 'die', id: 2, brand: 'W', name: 'D2', v: null, angle: 88 },
            { type: 'punch', id: 3, brand: 'W', name: 'P', v: 10, angle: 88 },
        ]);
        expect(dies).toEqual([expect.objectContaining({ id: 1, v: 10, a: 88, r: 1, h: 50 })]);
    });
});

describe('dessin', () => {
    it('produit un SVG sans valeur non numérique, matrice et étape comprises', () => {
        const s = state({ part: part([30, 60, 30], [[90], [120]], 1, [0, 1]) });
        const { svg } = drawing(s, build(s, vPunch({ A: 30 }), { v: 10, a: 88, r: 1, h: 50, label: 'Wilson V10' }));
        expect(svg).not.toMatch(/NaN|Infinity/);
        expect(svg).toContain('Wilson V10');
        expect(svg).toContain('étape 2/2');
    });
});
