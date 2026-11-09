import React, { useCallback, useEffect, useRef, useState } from 'react';
import Viewer3D from './Viewer3D.jsx';

/**
 * Lecteur de programme ISO (G-code) : parcours d'outil en 3D, rapides en orange
 * et usinage en bleu, à côté du texte du programme.
 *
 * Cliquer une ligne la surligne dans la vue et y pose l'outil ; cliquer le
 * parcours sélectionne la ligne qui l'a produit. Le curseur rejoue le programme
 * ligne à ligne. Lecture seule : rien n'est envoyé au serveur.
 */

const ROW_HEIGHT = 20;
const LIST_HEIGHT = 520;
const PLAY_SECONDS = 20;

function fmt(value) {
    return Number.isFinite(value) ? (Math.round(value * 1000) / 1000).toLocaleString() : '–';
}

function CodeList({ lines, selected, progress, onSelect }) {
    const ref = useRef(null);
    const [scrollTop, setScrollTop] = useState(0);

    // Liste virtuelle : un programme peut compter des centaines de milliers de lignes.
    const first = Math.max(0, Math.floor(scrollTop / ROW_HEIGHT) - 10);
    const last = Math.min(lines.length, Math.ceil((scrollTop + LIST_HEIGHT) / ROW_HEIGHT) + 10);

    useEffect(() => {
        const el = ref.current;
        if (!el || selected === null) return;
        const top = selected * ROW_HEIGHT;
        if (top < el.scrollTop || top + ROW_HEIGHT > el.scrollTop + el.clientHeight) {
            el.scrollTop = Math.max(0, top - el.clientHeight / 2);
        }
    }, [selected]);

    const rows = [];
    for (let i = first; i < last; i++) {
        const isSelected = i === selected;
        const isAhead = progress !== null && i > progress;
        rows.push(
            <div
                key={i}
                className={`wem-gcode-row${isSelected ? ' is-selected' : ''}${isAhead ? ' is-ahead' : ''}`}
                style={{ top: i * ROW_HEIGHT, height: ROW_HEIGHT }}
                onClick={() => onSelect(i)}
            >
                <span className="wem-gcode-num">{i + 1}</span>
                <span className="wem-gcode-code">{lines[i] || ' '}</span>
            </div>,
        );
    }

    return (
        <div
            ref={ref}
            className="wem-gcode-list"
            style={{ height: LIST_HEIGHT }}
            onScroll={(e) => setScrollTop(e.currentTarget.scrollTop)}
        >
            <div style={{ position: 'relative', height: lines.length * ROW_HEIGHT }}>{rows}</div>
        </div>
    );
}

export default function GcodeViewer({ file, t }) {
    const pathRef = useRef(null);
    const [program, setProgram] = useState(null);
    const [selected, setSelected] = useState(null);
    const [progress, setProgress] = useState(null);
    const [playing, setPlaying] = useState(false);
    const progressRef = useRef(null);
    progressRef.current = progress;

    const load = async (stage, url, signal) => {
        const [{ parseGcode, formatMinutes }, { gcodeToThree }, { THREE }] = await Promise.all([
            import('../../../lib/gcode/parse.js'),
            import('../../../lib/gcode/toThree.js'),
            import('./scene.js'),
        ]);

        const text = await fetch(url, { signal, headers: { Accept: '*/*' } })
            .then((res) => {
                if (!res.ok) throw new Error(res.statusText);
                return res.text();
            });

        const parsed = parseGcode(text);
        if (!parsed.rapid.length) throw new Error(t('gcode_no_move'));

        const path = gcodeToThree(parsed);
        pathRef.current = path;

        // Clic sur le parcours (pas en fin de rotation de la vue) → ligne du programme.
        const canvas = stage.renderer.domElement;
        let down = null;
        canvas.addEventListener('pointerdown', (e) => { down = [e.clientX, e.clientY]; });
        canvas.addEventListener('pointerup', (e) => {
            if (!down || Math.hypot(e.clientX - down[0], e.clientY - down[1]) > 4) return;
            const rect = canvas.getBoundingClientRect();
            const pointer = new THREE.Vector2(
                ((e.clientX - rect.left) / rect.width) * 2 - 1,
                -((e.clientY - rect.top) / rect.height) * 2 + 1,
            );
            const raycaster = new THREE.Raycaster();
            raycaster.setFromCamera(pointer, stage.camera);
            const line = path.pick(raycaster);
            if (line !== null) setSelected(line);
        });

        const { stats } = parsed;
        const unit = parsed.units === 'inch' ? 'in' : 'mm';
        const time = formatMinutes(stats.feedMinutes);

        setProgram({
            lines: parsed.lines,
            tools: parsed.tools,
            unit,
            size: [0, 1, 2].map((k) => stats.max[k] - stats.min[k]),
            time: stats.feedTimeKnown ? time : null,
            warnings: parsed.warnings.map((w) => (w === 'macro' ? t('gcode_macro') : w)),
        });

        const info = [
            t('gcode_lines').replace(':count', parsed.lines.length.toLocaleString()),
            `${t('gcode_feed_length')} ${fmt(stats.feedLength)} ${unit}`,
            `${t('gcode_rapid_length')} ${fmt(stats.rapidLength)} ${unit}`,
        ].join(' — ');

        return { object: path.group, info, ready: (s) => s.setView('iso') };
    };

    useEffect(() => {
        pathRef.current?.select(selected);
    }, [selected, program]);

    useEffect(() => {
        pathRef.current?.showUpTo(progress);
    }, [progress, program]);

    // Lecture : tout le programme en PLAY_SECONDS environ, quelle que soit sa taille.
    useEffect(() => {
        if (!playing || !program) return undefined;
        const step = Math.max(1, Math.ceil(program.lines.length / (PLAY_SECONDS * 30)));
        let current = progressRef.current ?? -1;
        const timer = setInterval(() => {
            current = Math.min(program.lines.length - 1, current + step);
            setProgress(current);
            setSelected(current);
            if (current >= program.lines.length - 1) setPlaying(false);
        }, 33);
        return () => clearInterval(timer);
    }, [playing, program]);

    const onSlide = useCallback((e) => {
        const value = Number(e.target.value);
        setPlaying(false);
        setProgress(value);
        setSelected(value);
    }, []);

    const togglePlay = () => {
        if (!program) return;
        if (!playing && (progress === null || progress >= program.lines.length - 1)) {
            progressRef.current = -1;
            setProgress(-1);
        }
        setPlaying(!playing);
    };

    const showAll = () => {
        setPlaying(false);
        setProgress(null);
    };

    const aside = program && (
        <div className="wem-gcode-aside">
            <div className="d-flex align-items-center gap-2 mb-2">
                <button type="button" className="btn btn-sm btn-outline-secondary" onClick={togglePlay} title={playing ? t('gcode_pause') : t('gcode_play')}>
                    <i className={`fas ${playing ? 'fa-pause' : 'fa-play'}`} />
                </button>
                <input
                    type="range"
                    className="form-range flex-grow-1"
                    min={0}
                    max={program.lines.length - 1}
                    value={progress ?? program.lines.length - 1}
                    onChange={onSlide}
                    aria-label={t('gcode_simulation')}
                />
                <button type="button" className="btn btn-sm btn-outline-secondary" onClick={showAll} disabled={progress === null}>
                    {t('gcode_show_all')}
                </button>
            </div>

            <CodeList lines={program.lines} selected={selected} progress={progress} onSelect={setSelected} />

            <div className="small text-muted mt-2">
                <span className="me-3"><span className="wem-gcode-swatch is-feed" />{t('gcode_feed')}</span>
                <span><span className="wem-gcode-swatch is-rapid" />{t('gcode_rapid')}</span>
            </div>
        </div>
    );

    const details = program && (
        <div className="small text-muted mt-1">
            {t('gcode_size')} : {program.size.map(fmt).join(' × ')} {program.unit}
            {program.time && <> — {t('gcode_feed_time').replace(':time', program.time)}</>}
            {program.tools.length > 0 && (
                <div>
                    {t('gcode_tools')} :{' '}
                    {program.tools.map((tool) => `T${tool.number}${tool.comment ? ` (${tool.comment})` : ''}`).join(', ')}
                </div>
            )}
            {program.warnings.length > 0 && (
                <div className="text-warning-emphasis">
                    <i className="fas fa-exclamation-triangle me-1" />
                    {t('gcode_ignored').replace(':codes', program.warnings.join(', '))}
                </div>
            )}
        </div>
    );

    return (
        <>
            <Viewer3D file={file} t={t} load={load} aside={aside} />
            {details}
        </>
    );
}
