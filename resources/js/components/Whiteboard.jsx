import React, { useCallback, useEffect, useRef, useState } from 'react';
import axios from 'axios';
import './Whiteboard.css';

const DEFAULT_ENDPOINTS = {
    index: '/api/collaboration/whiteboards',
    store: '/api/collaboration/whiteboards',
    show: '/api/collaboration/whiteboards/{id}',
    update: '/api/collaboration/whiteboards/{id}',
    snapshots: '/api/collaboration/whiteboards/{id}/snapshots',
    files: '/api/collaboration/whiteboards/{id}/files',
};

const PALETTE = ['#fed766', '#ff928b', '#9ad0ec', '#c6f68d', '#f7aef8'];

function parseMaybeJson(value) {
    if (!value) return null;
    if (typeof value === 'object') return value;
    try {
        return JSON.parse(value);
    } catch {
        return null;
    }
}

export default function Whiteboard({
    initialWhiteboardId = null,
    initialWhiteboard = null,
    initialSnapshots = [],
    initialFiles = [],
    endpoints = {},
}) {
    const initialBoard = parseMaybeJson(initialWhiteboard);
    const endpointsConfigRef = useRef({
        ...DEFAULT_ENDPOINTS,
        ...(typeof window !== 'undefined' && window.whiteboardEndpoints ? window.whiteboardEndpoints : {}),
        ...(endpoints || {}),
    });

    // DOM refs
    const canvasRef = useRef(null);
    const canvasWrapperRef = useRef(null);
    const imageInputRef = useRef(null);

    // Mutable refs (no re-render)
    const contextRef = useRef(null);
    const isDrawingRef = useRef(false);
    const lastPointerPosRef = useRef(null);
    const noteIdCounterRef = useRef(1);
    const imageIdCounterRef = useRef(1);
    const draggingNoteIdRef = useRef(null);
    const draggingImageIdRef = useRef(null);
    const dragOffsetRef = useRef({ x: 0, y: 0 });
    const initialBoardStateRef = useRef(initialBoard);
    const canvasSizeRef = useRef({ width: 1200, height: 700 });

    // Mirror refs for state used inside DOM listeners (avoid stale closures)
    const activeToolRef = useRef('pen');
    const drawColorRef = useRef('#1f2937');
    const lineWidthRef = useRef(4);

    // State (re-renders)
    const [canvasWidth, setCanvasWidth] = useState(1200);
    const [canvasHeight, setCanvasHeight] = useState(700);
    const [activeTool, setActiveTool] = useState('pen');
    const [drawColor, setDrawColor] = useState('#1f2937');
    const [lineWidth, setLineWidth] = useState(4);
    const [notes, setNotes] = useState([]);
    const [images, setImages] = useState([]);
    const [whiteboards, setWhiteboards] = useState([]);
    const [selectedWhiteboardId, setSelectedWhiteboardId] = useState(
        initialBoard?.id || (initialWhiteboardId ? Number(initialWhiteboardId) : null)
    );
    const [whiteboardTitle, setWhiteboardTitle] = useState(
        initialBoard?.title || initialBoard?.name || 'Nouveau tableau'
    );
    const [snapshotHistory, setSnapshotHistory] = useState(
        Array.isArray(initialSnapshots) ? [...initialSnapshots] : []
    );
    const [boardFiles, setBoardFiles] = useState(
        Array.isArray(initialFiles) ? [...initialFiles] : []
    );
    const [isLoading, setIsLoading] = useState(false);
    const [isSaving, setIsSaving] = useState(false);
    const [error, setError] = useState(null);

    // Sync mirror refs
    useEffect(() => { activeToolRef.current = activeTool; }, [activeTool]);
    useEffect(() => { drawColorRef.current = drawColor; }, [drawColor]);
    useEffect(() => { lineWidthRef.current = lineWidth; }, [lineWidth]);
    useEffect(() => { canvasSizeRef.current = { width: canvasWidth, height: canvasHeight }; }, [canvasWidth, canvasHeight]);

    // === Helpers ===
    const resolveEndpoint = useCallback((name, id = null) => {
        const template = endpointsConfigRef.current[name] || DEFAULT_ENDPOINTS[name];
        if (!template) return null;
        if (typeof template === 'function') return template(id);
        if (id === null || id === undefined) return template;
        const identifier = String(id);
        return template
            .replace('{id}', identifier)
            .replace('{whiteboard}', identifier)
            .replace(':id', identifier)
            .replace(':whiteboard', identifier);
    }, []);

    const normalizeCollectionResponse = (response) => {
        const payload = response?.data ?? response;
        if (Array.isArray(payload)) return payload;
        if (Array.isArray(payload?.data)) return payload.data;
        if (Array.isArray(payload?.data?.data)) return payload.data.data;
        return [];
    };

    const normalizeBoardResponse = (response) => {
        const payload = response?.data ?? response;
        if (!payload) return null;
        if (payload.data && !Array.isArray(payload.data)) return payload.data;
        if (payload.board) return payload.board;
        if (Array.isArray(payload.data)) return payload.data[0] || null;
        return payload;
    };

    const normalizeSnapshotResponse = (response) => {
        const payload = response?.data ?? response;
        if (!payload) return null;
        if (payload.data && !Array.isArray(payload.data)) return payload.data;
        return payload;
    };

    const handleError = useCallback((err, fallbackMessage) => {
        console.error(err);
        if (err?.response?.data?.message) {
            setError(err.response.data.message);
        } else if (err?.message) {
            setError(err.message);
        } else {
            setError(fallbackMessage);
        }
    }, []);

    const randomNoteColor = () => PALETTE[Math.floor(Math.random() * PALETTE.length)];
    const generateNoteId = () => noteIdCounterRef.current++;

    const getRelativePositionFromEvent = useCallback((event) => {
        const wrapper = canvasWrapperRef.current;
        if (!wrapper) return { x: 0, y: 0 };
        const rect = wrapper.getBoundingClientRect();
        const sourceEvent = event.touches && event.touches.length ? event.touches[0] : event;
        const x = sourceEvent.clientX - rect.left;
        const y = sourceEvent.clientY - rect.top;
        const { width, height } = canvasSizeRef.current;
        return {
            x: Math.min(Math.max(0, x), width),
            y: Math.min(Math.max(0, y), height),
        };
    }, []);

    // === Canvas setup ===
    const setupCanvas = useCallback((preserveDrawing = false) => {
        const canvas = canvasRef.current;
        const wrapper = canvasWrapperRef.current;
        if (!canvas || !wrapper) return;

        let previousDrawing = null;
        if (preserveDrawing && canvas.width > 0) {
            previousDrawing = canvas.toDataURL('image/png');
        }

        const width = wrapper.clientWidth || canvasSizeRef.current.width;
        const height = wrapper.clientHeight || 600;
        canvasSizeRef.current = { width, height };
        setCanvasWidth(width);
        setCanvasHeight(height);

        const dpr = window.devicePixelRatio || 1;
        canvas.width = width * dpr;
        canvas.height = height * dpr;
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;

        const context = canvas.getContext('2d');
        context.setTransform(1, 0, 0, 1, 0, 0);
        context.scale(dpr, dpr);
        context.lineCap = 'round';
        context.lineJoin = 'round';
        context.lineWidth = lineWidthRef.current;
        context.strokeStyle = drawColorRef.current;
        context.globalCompositeOperation =
            activeToolRef.current === 'eraser' ? 'destination-out' : 'source-over';

        contextRef.current = context;

        if (previousDrawing) {
            const img = new Image();
            img.onload = () => {
                context.clearRect(0, 0, width, height);
                context.drawImage(img, 0, 0, width, height);
            };
            img.src = previousDrawing;
        } else {
            context.clearRect(0, 0, width, height);
        }
    }, []);

    // === Drawing ===
    const startDrawing = useCallback((event) => {
        if (!contextRef.current) return;
        isDrawingRef.current = true;
        const position = getRelativePositionFromEvent(event);
        lastPointerPosRef.current = position;
        contextRef.current.beginPath();
        contextRef.current.moveTo(position.x, position.y);
    }, [getRelativePositionFromEvent]);

    const draw = useCallback((event) => {
        if (!isDrawingRef.current || !contextRef.current) return;
        const position = getRelativePositionFromEvent(event);
        const ctx = contextRef.current;
        if (activeToolRef.current === 'eraser') {
            ctx.globalCompositeOperation = 'destination-out';
            ctx.strokeStyle = 'rgba(0,0,0,1)';
        } else {
            ctx.globalCompositeOperation = 'source-over';
            ctx.strokeStyle = drawColorRef.current;
        }
        ctx.lineWidth = lineWidthRef.current;
        ctx.lineTo(position.x, position.y);
        ctx.stroke();
        lastPointerPosRef.current = position;
    }, [getRelativePositionFromEvent]);

    const stopDrawing = useCallback(() => {
        if (!isDrawingRef.current || !contextRef.current) return;
        contextRef.current.closePath();
        contextRef.current.globalCompositeOperation =
            activeToolRef.current === 'eraser' ? 'destination-out' : 'source-over';
        isDrawingRef.current = false;
        lastPointerPosRef.current = null;
    }, []);

    // === Notes ===
    const addNote = () => {
        const newId = generateNoteId();
        const x = 40 * newId;
        const y = 40 * newId;
        const noteWidth = 200;
        const noteHeight = 160;
        const { width, height } = canvasSizeRef.current;
        const maxX = Math.max(0, width - noteWidth);
        const maxY = Math.max(0, height - noteHeight);
        setNotes((prev) => [
            ...prev,
            {
                id: newId,
                text: '',
                x: Math.max(0, Math.min(x, maxX)),
                y: Math.max(0, Math.min(y, maxY)),
                color: randomNoteColor(),
            },
        ]);
    };

    const updateNoteText = (id, text) => {
        setNotes((prev) => prev.map((n) => (n.id === id ? { ...n, text } : n)));
    };

    const removeNote = (id) => {
        setNotes((prev) => prev.filter((n) => n.id !== id));
    };

    const beginNoteDrag = (note, event) => {
        draggingNoteIdRef.current = note.id;
        const { x, y } = getRelativePositionFromEvent(event);
        dragOffsetRef.current = { x: x - note.x, y: y - note.y };
    };

    // === Images ===
    const removeImage = (id) => {
        setImages((prev) => prev.filter((i) => i.id !== id));
    };

    const beginImageDrag = (image, event) => {
        draggingImageIdRef.current = image.id;
        const { x, y } = getRelativePositionFromEvent(event);
        dragOffsetRef.current = { x: x - image.x, y: y - image.y };
    };

    const handleImageUpload = (event) => {
        const files = Array.from(event.target.files || []);
        if (!files.length) return;

        files.forEach((file) => {
            const reader = new FileReader();
            reader.onload = (uploadEvent) => {
                const src = uploadEvent.target.result;
                const previewImage = new Image();
                previewImage.onload = () => {
                    const maxWidth = 240;
                    const ratio = previewImage.width > maxWidth ? maxWidth / previewImage.width : 1;
                    const newId = imageIdCounterRef.current++;
                    setImages((prev) => [
                        ...prev,
                        {
                            id: newId,
                            src,
                            name: file.name,
                            x: 40 * prev.length,
                            y: 40 * prev.length,
                            width: Math.round(previewImage.width * ratio),
                            height: Math.round(previewImage.height * ratio),
                        },
                    ]);
                };
                previewImage.src = src;
            };
            reader.readAsDataURL(file);
        });

        if (imageInputRef.current) {
            imageInputRef.current.value = '';
        }
    };

    // === Canvas state ===
    const drawCanvasFromData = useCallback((dataUrl) => {
        if (!contextRef.current || !dataUrl) return;
        const image = new Image();
        const { width, height } = canvasSizeRef.current;
        image.onload = () => {
            contextRef.current.clearRect(0, 0, width, height);
            contextRef.current.drawImage(image, 0, 0, width, height);
        };
        image.src = dataUrl;
    }, []);

    const clearCanvas = useCallback(() => {
        if (!contextRef.current) return;
        const { width, height } = canvasSizeRef.current;
        const previousComposite = contextRef.current.globalCompositeOperation;
        contextRef.current.globalCompositeOperation = 'source-over';
        contextRef.current.clearRect(0, 0, width, height);
        contextRef.current.globalCompositeOperation = previousComposite;
    }, []);

    const clearBoard = useCallback(() => {
        clearCanvas();
        setNotes([]);
        setImages([]);
        setSnapshotHistory([]);
        setBoardFiles([]);
        noteIdCounterRef.current = 1;
        imageIdCounterRef.current = 1;
        setWhiteboardTitle('Nouveau tableau');
    }, [clearCanvas]);

    const applyState = useCallback((state) => {
        if (!state) {
            clearBoard();
            return;
        }
        if (state.title) {
            setWhiteboardTitle(state.title);
        }
        if (Array.isArray(state.notes)) {
            const mapped = state.notes.map((note) => ({
                id: note.id || generateNoteId(),
                text: note.text || '',
                x: typeof note.x === 'number' ? note.x : 40,
                y: typeof note.y === 'number' ? note.y : 40,
                color: note.color || randomNoteColor(),
            }));
            const maxId = mapped.reduce((acc, n) => Math.max(acc, n.id), 0);
            noteIdCounterRef.current = maxId + 1;
            setNotes(mapped);
        } else {
            setNotes([]);
        }
        if (Array.isArray(state.images)) {
            const mapped = state.images.map((image) => ({
                id: image.id || imageIdCounterRef.current++,
                src: image.src,
                name: image.name || 'Image',
                x: typeof image.x === 'number' ? image.x : 40,
                y: typeof image.y === 'number' ? image.y : 40,
                width: image.width || 200,
                height: image.height || 150,
            }));
            const maxImageId = mapped.reduce((acc, im) => Math.max(acc, im.id), 0);
            imageIdCounterRef.current = maxImageId + 1;
            setImages(mapped);
        } else {
            setImages([]);
        }
        if (state.canvas) {
            drawCanvasFromData(state.canvas);
        } else {
            clearCanvas();
        }
    }, [clearBoard, clearCanvas, drawCanvasFromData]);

    const refreshBoardList = useCallback((board) => {
        if (!board || !board.id) return;
        setWhiteboards((prev) => {
            const index = prev.findIndex((item) => Number(item.id) === Number(board.id));
            if (index >= 0) {
                const next = [...prev];
                next.splice(index, 1, board);
                return next;
            }
            return [board, ...prev];
        });
    }, []);

    const applyBoard = useCallback((board) => {
        if (!board) {
            clearBoard();
            setSelectedWhiteboardId(null);
            return;
        }
        initialBoardStateRef.current = board;
        setWhiteboardTitle(board.title || board.name || 'Nouveau tableau');
        const state = parseMaybeJson(board.state) || board.payload || board.content || null;
        if (state) {
            applyState(state);
        } else {
            applyState({
                notes: board.notes,
                images: board.images,
                canvas: board.canvas,
            });
        }
        if (Array.isArray(board.snapshots)) {
            setSnapshotHistory(board.snapshots);
        }
        if (Array.isArray(board.files)) {
            setBoardFiles(board.files);
        }
        refreshBoardList(board);
    }, [applyState, clearBoard, refreshBoardList]);

    const serializeState = useCallback(() => {
        const serializedNotes = notes.map((n) => ({
            id: n.id, text: n.text, x: n.x, y: n.y, color: n.color,
        }));
        const serializedImages = images.map((im) => ({
            id: im.id, src: im.src, name: im.name, x: im.x, y: im.y, width: im.width, height: im.height,
        }));
        return {
            title: whiteboardTitle,
            canvas: canvasRef.current ? canvasRef.current.toDataURL('image/png') : null,
            notes: serializedNotes,
            images: serializedImages,
        };
    }, [notes, images, whiteboardTitle]);

    // === API ===
    const fetchSnapshots = useCallback(async (whiteboardId) => {
        if (!whiteboardId) return;
        const url = resolveEndpoint('snapshots', whiteboardId);
        if (!url) return;
        try {
            const response = await axios.get(url);
            const snapshots = normalizeCollectionResponse(response);
            if (Array.isArray(snapshots) && snapshots.length) {
                setSnapshotHistory(snapshots);
            }
        } catch (err) {
            handleError(err, 'Impossible de récupérer les snapshots.');
        }
    }, [handleError, resolveEndpoint]);

    const fetchFiles = useCallback(async (whiteboardId) => {
        const url = resolveEndpoint('files', whiteboardId);
        if (!url) return;
        try {
            const response = await axios.get(url);
            const files = normalizeCollectionResponse(response);
            if (Array.isArray(files)) {
                setBoardFiles(files);
            }
        } catch (err) {
            handleError(err, 'Impossible de récupérer les fichiers associés.');
        }
    }, [handleError, resolveEndpoint]);

    const fetchWhiteboards = useCallback(async () => {
        const url = resolveEndpoint('index');
        if (!url) return;
        setIsLoading(true);
        setError(null);
        try {
            const response = await axios.get(url);
            const list = normalizeCollectionResponse(response);
            setWhiteboards((_prev) => {
                if (selectedWhiteboardId) {
                    const exists = list.some((b) => Number(b.id) === Number(selectedWhiteboardId));
                    if (!exists && initialBoardStateRef.current) {
                        return [initialBoardStateRef.current, ...list];
                    }
                }
                return list;
            });
        } catch (err) {
            handleError(err, 'Impossible de charger la liste des tableaux.');
        } finally {
            setIsLoading(false);
        }
    }, [handleError, resolveEndpoint, selectedWhiteboardId]);

    const loadWhiteboard = useCallback(async (id = null) => {
        const boardId = Number(id || selectedWhiteboardId);
        if (!boardId) {
            setSelectedWhiteboardId(null);
            clearBoard();
            return;
        }
        const url = resolveEndpoint('show', boardId);
        if (!url) return;
        setIsLoading(true);
        setError(null);
        try {
            const response = await axios.get(url);
            const board = normalizeBoardResponse(response);
            if (board) {
                applyBoard(board);
                setSelectedWhiteboardId(Number(board.id));
                await Promise.all([fetchSnapshots(board.id), fetchFiles(board.id)]);
            }
        } catch (err) {
            handleError(err, 'Impossible de charger le tableau sélectionné.');
        } finally {
            setIsLoading(false);
        }
    }, [applyBoard, clearBoard, fetchFiles, fetchSnapshots, handleError, resolveEndpoint, selectedWhiteboardId]);

    const saveWhiteboard = async () => {
        const payload = {
            title: whiteboardTitle,
            state: serializeState(),
        };
        let url;
        let request;
        if (selectedWhiteboardId) {
            url = resolveEndpoint('update', selectedWhiteboardId);
            request = () => axios.put(url, payload);
        } else {
            url = resolveEndpoint('store');
            request = () => axios.post(url, payload);
        }
        if (!url) return;
        setIsSaving(true);
        setError(null);
        try {
            const response = await request();
            const board = normalizeBoardResponse(response);
            if (board) {
                setSelectedWhiteboardId(Number(board.id));
                applyBoard(board);
                await Promise.all([fetchSnapshots(board.id), fetchFiles(board.id)]);
            }
        } catch (err) {
            handleError(err, "Une erreur est survenue lors de l'enregistrement du tableau.");
        } finally {
            setIsSaving(false);
        }
    };

    const saveSnapshot = async () => {
        if (!selectedWhiteboardId) {
            setError('Enregistrez le tableau avant de créer un snapshot.');
            return;
        }
        const url = resolveEndpoint('snapshots', selectedWhiteboardId);
        if (!url) return;
        try {
            const response = await axios.post(url, serializeState());
            const snapshot = normalizeSnapshotResponse(response);
            if (snapshot) {
                setSnapshotHistory((prev) => [snapshot, ...prev]);
            }
        } catch (err) {
            handleError(err, 'Impossible de créer un snapshot.');
        }
    };

    const loadSnapshot = (snapshot) => {
        if (!snapshot) return;
        const state = parseMaybeJson(snapshot.state || snapshot.payload || snapshot.content);
        if (state) {
            applyState(state);
        }
    };

    const uploadFiles = async (event) => {
        if (!selectedWhiteboardId) {
            setError("Enregistrez le tableau avant d'importer des fichiers.");
            return;
        }
        const fileList = event.target.files;
        if (!fileList || !fileList.length) return;
        const url = resolveEndpoint('files', selectedWhiteboardId);
        if (!url) return;

        const formData = new FormData();
        Array.from(fileList).forEach((file) => {
            formData.append('files[]', file);
        });
        try {
            const response = await axios.post(url, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            const uploaded = normalizeCollectionResponse(response) || [];
            setBoardFiles((prev) => [...uploaded, ...prev]);
        } catch (err) {
            handleError(err, 'Impossible de téléverser les fichiers.');
        } finally {
            event.target.value = '';
        }
    };

    const downloadState = () => {
        const state = JSON.stringify(serializeState(), null, 2);
        const blob = new Blob([state], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = `${whiteboardTitle || 'whiteboard'}.json`;
        anchor.click();
        URL.revokeObjectURL(url);
    };

    const formatDate = (date) => {
        if (!date) return '';
        const parsed = new Date(date);
        if (Number.isNaN(parsed.getTime())) return date;
        return parsed.toLocaleString();
    };

    const formatSnapshotLabel = (snapshot) => {
        const label = snapshot?.name || snapshot?.title || `Snapshot #${snapshot?.id}`;
        const createdAt = snapshot?.created_at || snapshot?.createdAt;
        if (!createdAt) return label;
        return `${label} – ${formatDate(createdAt)}`;
    };

    const createNewWhiteboard = () => {
        setSelectedWhiteboardId(null);
        clearBoard();
        setError(null);
        setIsSaving(false);
    };

    const onSelectWhiteboard = (e) => {
        const value = e.target.value;
        const boardId = value === '' || value === 'null' ? null : Number(value);
        setSelectedWhiteboardId(boardId);
        if (boardId === null) {
            createNewWhiteboard();
        } else {
            loadWhiteboard(boardId);
        }
    };

    // === Mount: setup canvas, attach listeners (mouse + touch with passive:false for canvas) ===
    useEffect(() => {
        setupCanvas();

        const canvas = canvasRef.current;
        if (!canvas) return undefined;

        // Canvas listeners — touch with passive:false to allow preventDefault
        const onCanvasMouseDown = (e) => { e.preventDefault(); startDrawing(e); };
        const onCanvasMouseMove = (e) => { e.preventDefault(); draw(e); };
        const onCanvasMouseUp = (e) => { e.preventDefault(); stopDrawing(e); };
        const onCanvasMouseLeave = (e) => { e.preventDefault(); stopDrawing(e); };
        const onCanvasTouchStart = (e) => { e.preventDefault(); startDrawing(e); };
        const onCanvasTouchMove = (e) => { e.preventDefault(); draw(e); };
        const onCanvasTouchEnd = (e) => { e.preventDefault(); stopDrawing(e); };
        const onCanvasTouchCancel = (e) => { e.preventDefault(); stopDrawing(e); };

        canvas.addEventListener('mousedown', onCanvasMouseDown);
        canvas.addEventListener('mousemove', onCanvasMouseMove);
        canvas.addEventListener('mouseup', onCanvasMouseUp);
        canvas.addEventListener('mouseleave', onCanvasMouseLeave);
        canvas.addEventListener('touchstart', onCanvasTouchStart, { passive: false });
        canvas.addEventListener('touchmove', onCanvasTouchMove, { passive: false });
        canvas.addEventListener('touchend', onCanvasTouchEnd, { passive: false });
        canvas.addEventListener('touchcancel', onCanvasTouchCancel, { passive: false });

        // Window listeners
        const handleResize = () => setupCanvas(true);
        const handleGlobalMouseUp = () => stopDrawing();
        const handleGlobalTouchEnd = () => stopDrawing();
        window.addEventListener('resize', handleResize, { passive: true });
        window.addEventListener('mouseup', handleGlobalMouseUp, { passive: true });
        window.addEventListener('touchend', handleGlobalTouchEnd, { passive: true });

        // Initial board / fetch
        if (initialBoardStateRef.current) {
            applyBoard(initialBoardStateRef.current);
        } else if (selectedWhiteboardId) {
            loadWhiteboard(selectedWhiteboardId);
        }
        fetchWhiteboards();

        return () => {
            canvas.removeEventListener('mousedown', onCanvasMouseDown);
            canvas.removeEventListener('mousemove', onCanvasMouseMove);
            canvas.removeEventListener('mouseup', onCanvasMouseUp);
            canvas.removeEventListener('mouseleave', onCanvasMouseLeave);
            canvas.removeEventListener('touchstart', onCanvasTouchStart);
            canvas.removeEventListener('touchmove', onCanvasTouchMove);
            canvas.removeEventListener('touchend', onCanvasTouchEnd);
            canvas.removeEventListener('touchcancel', onCanvasTouchCancel);
            window.removeEventListener('resize', handleResize);
            window.removeEventListener('mouseup', handleGlobalMouseUp);
            window.removeEventListener('touchend', handleGlobalTouchEnd);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    // === Watchers (Vue → useEffect) ===
    useEffect(() => {
        if (contextRef.current && activeTool === 'pen') {
            contextRef.current.strokeStyle = drawColor;
        }
    }, [drawColor, activeTool]);

    useEffect(() => {
        if (contextRef.current) {
            contextRef.current.lineWidth = lineWidth;
        }
    }, [lineWidth]);

    useEffect(() => {
        if (!contextRef.current) return;
        contextRef.current.globalCompositeOperation =
            activeTool === 'eraser' ? 'destination-out' : 'source-over';
        if (activeTool === 'pen') {
            contextRef.current.strokeStyle = drawColor;
        }
    }, [activeTool, drawColor]);

    // === Note drag (window listeners — attached only while dragging) ===
    useEffect(() => {
        const handleNoteDragMove = (event) => {
            const draggedId = draggingNoteIdRef.current;
            if (draggedId === null) return;
            event.preventDefault?.();
            const { x, y } = getRelativePositionFromEvent(event);
            const noteWidth = 200;
            const noteHeight = 160;
            const { width, height } = canvasSizeRef.current;
            const maxX = Math.max(0, width - noteWidth);
            const maxY = Math.max(0, height - noteHeight);
            const newX = Math.min(Math.max(0, x - dragOffsetRef.current.x), maxX);
            const newY = Math.min(Math.max(0, y - dragOffsetRef.current.y), maxY);
            setNotes((prev) =>
                prev.map((n) => (n.id === draggedId ? { ...n, x: newX, y: newY } : n))
            );
        };
        const handleNoteDragEnd = () => {
            draggingNoteIdRef.current = null;
        };
        window.addEventListener('mousemove', handleNoteDragMove);
        window.addEventListener('touchmove', handleNoteDragMove, { passive: false });
        window.addEventListener('mouseup', handleNoteDragEnd, { passive: true });
        window.addEventListener('touchend', handleNoteDragEnd, { passive: true });
        return () => {
            window.removeEventListener('mousemove', handleNoteDragMove);
            window.removeEventListener('touchmove', handleNoteDragMove);
            window.removeEventListener('mouseup', handleNoteDragEnd);
            window.removeEventListener('touchend', handleNoteDragEnd);
        };
    }, [getRelativePositionFromEvent]);

    // === Image drag (window listeners) ===
    useEffect(() => {
        const handleImageDragMove = (event) => {
            const draggedId = draggingImageIdRef.current;
            if (draggedId === null) return;
            event.preventDefault?.();
            const { x, y } = getRelativePositionFromEvent(event);
            setImages((prev) =>
                prev.map((im) => {
                    if (im.id !== draggedId) return im;
                    const imageWidth = im.width || 200;
                    const imageHeight = im.height || 160;
                    const { width, height } = canvasSizeRef.current;
                    const maxX = Math.max(0, width - imageWidth);
                    const maxY = Math.max(0, height - imageHeight);
                    const newX = Math.min(Math.max(0, x - dragOffsetRef.current.x), maxX);
                    const newY = Math.min(Math.max(0, y - dragOffsetRef.current.y), maxY);
                    return { ...im, x: newX, y: newY };
                })
            );
        };
        const handleImageDragEnd = () => {
            draggingImageIdRef.current = null;
        };
        window.addEventListener('mousemove', handleImageDragMove);
        window.addEventListener('touchmove', handleImageDragMove, { passive: false });
        window.addEventListener('mouseup', handleImageDragEnd, { passive: true });
        window.addEventListener('touchend', handleImageDragEnd, { passive: true });
        return () => {
            window.removeEventListener('mousemove', handleImageDragMove);
            window.removeEventListener('touchmove', handleImageDragMove);
            window.removeEventListener('mouseup', handleImageDragEnd);
            window.removeEventListener('touchend', handleImageDragEnd);
        };
    }, [getRelativePositionFromEvent]);

    return (
        <div className="whiteboard-container">
            <div className="whiteboard-toolbar card mb-3 p-3">
                <div className="row g-3 align-items-center">
                    <div className="col-lg-3 col-md-6">
                        <label className="form-label fw-bold">Tableaux</label>
                        <div className="d-flex gap-2">
                            <select
                                className="form-select"
                                value={selectedWhiteboardId ?? ''}
                                onChange={onSelectWhiteboard}
                            >
                                <option value="">Nouveau tableau</option>
                                {whiteboards.map((board) => (
                                    <option key={board.id} value={board.id}>
                                        {board.title || board.name || `Tableau #${board.id}`}
                                    </option>
                                ))}
                            </select>
                            <button
                                type="button"
                                className="btn btn-outline-secondary"
                                onClick={createNewWhiteboard}
                            >
                                Réinitialiser
                            </button>
                        </div>
                    </div>
                    <div className="col-lg-3 col-md-6">
                        <label className="form-label fw-bold">Titre</label>
                        <input
                            type="text"
                            className="form-control"
                            value={whiteboardTitle}
                            onChange={(e) => setWhiteboardTitle(e.target.value)}
                            placeholder="Titre du tableau"
                        />
                    </div>
                    <div className="col-lg-3 col-md-6">
                        <label className="form-label fw-bold">Outils</label>
                        <div className="d-flex flex-wrap gap-2">
                            <select
                                className="form-select tool-select"
                                value={activeTool}
                                onChange={(e) => setActiveTool(e.target.value)}
                            >
                                <option value="pen">Stylo</option>
                                <option value="eraser">Gomme</option>
                            </select>
                            <input
                                type="color"
                                className="form-control form-control-color"
                                value={drawColor}
                                onChange={(e) => setDrawColor(e.target.value)}
                                disabled={activeTool === 'eraser'}
                                title="Couleur du trait"
                            />
                            <input
                                type="range"
                                className="form-range"
                                min="1"
                                max="40"
                                value={lineWidth}
                                onChange={(e) => setLineWidth(Number(e.target.value))}
                            />
                        </div>
                    </div>
                    <div className="col-lg-3 col-md-6">
                        <label className="form-label fw-bold">Actions</label>
                        <div className="d-flex flex-wrap gap-2">
                            <button type="button" className="btn btn-outline-primary" onClick={addNote}>
                                Ajouter un post-it
                            </button>
                            <label className="btn btn-outline-secondary mb-0">
                                Importer une image
                                <input
                                    ref={imageInputRef}
                                    type="file"
                                    accept="image/*"
                                    className="d-none"
                                    onChange={handleImageUpload}
                                />
                            </label>
                            <button
                                type="button"
                                className="btn btn-success"
                                disabled={isSaving}
                                onClick={saveWhiteboard}
                            >
                                {isSaving ? 'Enregistrement…' : 'Enregistrer'}
                            </button>
                            <button
                                type="button"
                                className="btn btn-outline-success"
                                disabled={!selectedWhiteboardId}
                                onClick={saveSnapshot}
                            >
                                Snapshot
                            </button>
                            <button type="button" className="btn btn-outline-dark" onClick={downloadState}>
                                Export JSON
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div className="whiteboard-main">
                <div className="canvas-wrapper" ref={canvasWrapperRef}>
                    <canvas ref={canvasRef} width={canvasWidth} height={canvasHeight} />

                    {notes.map((note) => (
                        <div
                            key={note.id}
                            className="sticky-note"
                            style={{
                                left: `${note.x}px`,
                                top: `${note.y}px`,
                                backgroundColor: note.color,
                            }}
                        >
                            <div
                                className="sticky-note__header"
                                onMouseDown={(e) => {
                                    e.stopPropagation();
                                    e.preventDefault();
                                    beginNoteDrag(note, e);
                                }}
                                onTouchStart={(e) => {
                                    e.stopPropagation();
                                    beginNoteDrag(note, e);
                                }}
                            >
                                <span>Post-it</span>
                                <button
                                    type="button"
                                    className="btn-close"
                                    aria-label="Fermer"
                                    onClick={() => removeNote(note.id)}
                                />
                            </div>
                            <textarea
                                value={note.text}
                                onChange={(e) => updateNoteText(note.id, e.target.value)}
                                placeholder="Saisir une note"
                            />
                        </div>
                    ))}

                    {images.map((image) => (
                        <div
                            key={image.id}
                            className="board-image"
                            style={{ left: `${image.x}px`, top: `${image.y}px` }}
                        >
                            <div
                                className="board-image__header"
                                onMouseDown={(e) => {
                                    e.stopPropagation();
                                    e.preventDefault();
                                    beginImageDrag(image, e);
                                }}
                                onTouchStart={(e) => {
                                    e.stopPropagation();
                                    beginImageDrag(image, e);
                                }}
                            >
                                <span className="text-truncate">{image.name}</span>
                                <button
                                    type="button"
                                    className="btn-close"
                                    aria-label="Fermer"
                                    onClick={() => removeImage(image.id)}
                                />
                            </div>
                            <img src={image.src} alt={image.name} style={{ width: `${image.width}px` }} />
                        </div>
                    ))}
                </div>

                <aside className="snapshot-panel">
                    <div className="snapshot-panel__section">
                        <h5>Snapshots</h5>
                        {!snapshotHistory.length ? (
                            <p className="text-muted">Aucun snapshot disponible.</p>
                        ) : (
                            <ul className="list-unstyled snapshot-list">
                                {snapshotHistory.map((snapshot) => (
                                    <li key={snapshot.id}>
                                        <button
                                            type="button"
                                            className="btn btn-link p-0"
                                            onClick={() => loadSnapshot(snapshot)}
                                        >
                                            {formatSnapshotLabel(snapshot)}
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                    <div className="snapshot-panel__section">
                        <h5>Fichiers</h5>
                        <input
                            type="file"
                            className="form-control"
                            multiple
                            disabled={!selectedWhiteboardId}
                            onChange={uploadFiles}
                        />
                        {!boardFiles.length ? (
                            <p className="text-muted mt-2">Aucun fichier associé.</p>
                        ) : (
                            <ul className="list-unstyled mt-2 file-list">
                                {boardFiles.map((file) => (
                                    <li key={file.id}>
                                        <a href={file.url || file.path} target="_blank" rel="noopener noreferrer">
                                            {file.name || file.original_name}
                                        </a>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </aside>
            </div>

            {isLoading && <div className="alert alert-info mt-3">Chargement…</div>}
            {error && <div className="alert alert-danger mt-3">{error}</div>}
        </div>
    );
}
